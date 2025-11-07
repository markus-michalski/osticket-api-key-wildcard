<?php

require_once INCLUDE_DIR . 'class.plugin.php';

/**
 * API Key Wildcard Plugin
 * 
 * Provides a separate API endpoint (/api/wildcard.php) that allows 
 * API keys with IP address 0.0.0.0 to accept requests from any IP.
 * 
 * This is useful for development environments where IP addresses change.
 * 
 * SECURITY WARNING: Never use 0.0.0.0 API keys in production!
 * 
 * Usage:
 *   POST /api/wildcard/tickets.json
 *   (instead of /api/tickets.json)
 */
class ApiKeyWildcardPlugin extends Plugin {

    var $config_class = 'ApiKeyWildcardConfig';

    /**
     * Only one instance of this plugin is needed
     * Multiple instances would all do the same thing
     */
    function isSingleton() {
        return true;
    }

    /**
     * Bootstrap the plugin
     * Checks for updates and auto-updates if needed
     */
    function bootstrap() {
        // Get current plugin version from plugin.php file
        $plugin_file = INCLUDE_DIR . 'plugins/api-key-wildcard/plugin.php';
        if (file_exists($plugin_file)) {
            $plugin_info = include($plugin_file);
            $current_version = $plugin_info['version'];

            // Get installed version from config
            $installed_version = $this->getConfig()->get('installed_version');

            // First time activation or update needed?
            if (!$installed_version || version_compare($installed_version, $current_version, '<')) {
                $this->performUpdate($installed_version, $current_version);
            }
        }
    }

    /**
     * Called when plugin is enabled
     * Automatically installs the wildcard endpoint
     */
    function enable() {
        $errors = array();

        // Auto-create instance for singleton plugin
        if ($this->isSingleton() && $this->getNumInstances() === 0) {
            $vars = array(
                'name' => $this->getName(),
                'isactive' => 1,
                'notes' => 'Auto-created singleton instance'
            );

            if (!$this->addInstance($vars, $errors)) {
                error_log(sprintf('[API Key Wildcard] Failed to auto-create instance: %s',
                    json_encode($errors)));
                return $errors;
            }

            error_log('[API Key Wildcard] Auto-created singleton instance');
        }

        // 1. Copy wildcard.php to /api/ directory
        $source = INCLUDE_DIR . 'plugins/api-key-wildcard/wildcard.php';
        $target = ROOT_DIR . 'api/wildcard.php';

        if (!copy($source, $target)) {
            $errors[] = 'Failed to copy wildcard.php to /api/ directory. Please copy manually.';
        } else {
            chmod($target, 0755);
        }

        // 2. Update .htaccess in /api/ directory
        $htaccess_file = ROOT_DIR . 'api/.htaccess';
        $htaccess_content = file_get_contents($htaccess_file);

        // Check if already configured
        if (strpos($htaccess_content, 'Options -MultiViews') === false) {
            // Add Options -MultiViews after RewriteEngine On
            $new_content = str_replace(
                'RewriteEngine On',
                "RewriteEngine On\n\n  # Disable MultiViews for wildcard endpoint (prevents mod_negotiation)\n  Options -MultiViews",
                $htaccess_content
            );

            if (!file_put_contents($htaccess_file, $new_content)) {
                $errors[] = 'Failed to update .htaccess. Please add "Options -MultiViews" manually.';
            }
        }

        // Check if wildcard rewrite rule exists
        if (strpos($htaccess_content, 'wildcard/') === false) {
            // Find the complete RewriteCond block for the default API rule
            // This matches the pattern: RewriteCond...!-f, RewriteCond...!-d, RewriteCond...api, RewriteRule
            $pattern = '/^(\s*)RewriteCond\s+%\{REQUEST_FILENAME\}\s+!-f\s*\n\s*RewriteCond\s+%\{REQUEST_FILENAME\}\s+!-d\s*\n(\s*)RewriteCond\s+%\{REQUEST_URI\}\s+\(\..*\/api\)/m';

            if (preg_match($pattern, $htaccess_content, $matches, PREG_OFFSET_CAPTURE)) {
                $insert_pos = $matches[0][1];
                $indent = $matches[1][0];

                $rewrite_rule = "{$indent}# Wildcard API endpoint (must come BEFORE the default rule)\n";
                $rewrite_rule .= "{$indent}RewriteCond %{REQUEST_FILENAME} !-f\n";
                $rewrite_rule .= "{$indent}RewriteCond %{REQUEST_FILENAME} !-d\n";
                $rewrite_rule .= "{$indent}RewriteRule ^wildcard/(.*)$ wildcard.php/\$1 [L]\n\n";
                $rewrite_rule .= "{$indent}# Default API endpoint (standard osTicket)\n";

                $new_content = substr_replace($htaccess_content, $rewrite_rule, $insert_pos, 0);

                if (!file_put_contents($htaccess_file, $new_content)) {
                    $errors[] = 'Failed to add rewrite rule to .htaccess. Please add manually.';
                }
            } else {
                $errors[] = 'Could not find standard osTicket API rewrite pattern in .htaccess. Please add wildcard rewrite rule manually.';
            }
        }

        return count($errors) == 0 ? true : $errors;
    }

    /**
     * Called when plugin is disabled
     * Removes the wildcard endpoint
     */
    function disable() {
        // Remove wildcard.php from /api/ directory
        $target = ROOT_DIR . 'api/wildcard.php';
        if (file_exists($target)) {
            @unlink($target);
        }

        // Note: We don't remove .htaccess changes as they don't hurt when plugin is disabled

        return true;
    }

    /**
     * Perform update from old version to new version
     * This is called automatically when bootstrap() detects a version change
     */
    function performUpdate($from_version, $to_version) {
        $errors = array();

        // Always update wildcard.php to latest version
        $source = INCLUDE_DIR . 'plugins/api-key-wildcard/wildcard.php';
        $target = ROOT_DIR . 'api/wildcard.php';

        if (file_exists($target)) {
            // Update existing file
            if (!copy($source, $target)) {
                $errors[] = 'Failed to update wildcard.php. Please copy manually.';
            } else {
                chmod($target, 0755);
            }
        } else {
            // File doesn't exist, copy it
            if (!copy($source, $target)) {
                $errors[] = 'Failed to copy wildcard.php. Please copy manually.';
            } else {
                chmod($target, 0755);
            }
        }

        // Update .htaccess if needed
        $htaccess_file = ROOT_DIR . 'api/.htaccess';
        if (file_exists($htaccess_file)) {
            $htaccess_content = file_get_contents($htaccess_file);

            // Check if Options -MultiViews is missing
            if (strpos($htaccess_content, 'Options -MultiViews') === false) {
                $new_content = str_replace(
                    'RewriteEngine On',
                    "RewriteEngine On\n\n  # Disable MultiViews for wildcard endpoint (prevents mod_negotiation)\n  Options -MultiViews",
                    $htaccess_content
                );
                if (!file_put_contents($htaccess_file, $new_content)) {
                    $errors[] = 'Failed to update .htaccess with Options -MultiViews.';
                }
            }

            // Check if wildcard rewrite rule is missing
            $htaccess_content = file_get_contents($htaccess_file); // Re-read
            if (strpos($htaccess_content, 'wildcard/') === false) {
                // Find the complete RewriteCond block for the default API rule
                // This matches the pattern: RewriteCond...!-f, RewriteCond...!-d, RewriteCond...api, RewriteRule
                $pattern = '/^(\s*)RewriteCond\s+%\{REQUEST_FILENAME\}\s+!-f\s*\n\s*RewriteCond\s+%\{REQUEST_FILENAME\}\s+!-d\s*\n(\s*)RewriteCond\s+%\{REQUEST_URI\}\s+\(\..*\/api\)/m';

                if (preg_match($pattern, $htaccess_content, $matches, PREG_OFFSET_CAPTURE)) {
                    $insert_pos = $matches[0][1];
                    $indent = $matches[1][0];

                    $rewrite_rule = "{$indent}# Wildcard API endpoint (must come BEFORE the default rule)\n";
                    $rewrite_rule .= "{$indent}RewriteCond %{REQUEST_FILENAME} !-f\n";
                    $rewrite_rule .= "{$indent}RewriteCond %{REQUEST_FILENAME} !-d\n";
                    $rewrite_rule .= "{$indent}RewriteRule ^wildcard/(.*)$ wildcard.php/\$1 [L]\n\n";
                    $rewrite_rule .= "{$indent}# Default API endpoint (standard osTicket)\n";

                    $new_content = substr_replace($htaccess_content, $rewrite_rule, $insert_pos, 0);

                    if (!file_put_contents($htaccess_file, $new_content)) {
                        $errors[] = 'Failed to add wildcard rewrite rule to .htaccess.';
                    }
                } else {
                    $errors[] = 'Could not find standard osTicket API rewrite pattern in .htaccess. Please add wildcard rewrite rule manually.';
                }
            }
        }

        // Save installed version in config
        $this->getConfig()->set('installed_version', $to_version);

        // Log update
        if (count($errors) == 0) {
            if ($from_version) {
                error_log("API Key Wildcard Plugin: Updated from v{$from_version} to v{$to_version}");
            } else {
                error_log("API Key Wildcard Plugin: Installed v{$to_version}");
            }
        }

        return count($errors) == 0 ? true : $errors;
    }
}

/**
 * Plugin configuration
 */
class ApiKeyWildcardConfig extends PluginConfig {

    function getOptions() {
        return array(
            'installed_version' => new TextboxField(array(
                'id' => 'installed_version',
                'label' => 'Installed Version',
                'configuration' => array(
                    'desc' => 'Currently installed plugin version (auto-updated)',
                    'size' => 10,
                    'length' => 10
                ),
                'default' => '',
                'required' => false,
                'disabled' => true  // Make it read-only instead of using VisibilityConstraint
            )),
            'log_wildcard_access' => new BooleanField(array(
                'id' => 'log_wildcard_access',
                'label' => 'Log Wildcard API Access',
                'configuration' => array(
                    'desc' => 'Log when a wildcard (0.0.0.0) API key is used via /api/wildcard.php'
                ),
                'default' => true
            ))
        );
    }
}
