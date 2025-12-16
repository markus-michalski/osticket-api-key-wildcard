<?php

require_once INCLUDE_DIR . 'class.plugin.php';
require_once __DIR__ . '/class.PluginConstants.php';
require_once __DIR__ . '/class.PluginInstaller.php';
require_once __DIR__ . '/class.HtaccessManager.php';

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
class ApiKeyWildcardPlugin extends Plugin
{
    var $config_class = 'ApiKeyWildcardConfig';

    /**
     * Only one instance of this plugin is needed.
     * Multiple instances would all do the same thing.
     */
    function isSingleton()
    {
        return true;
    }

    /**
     * Get the PluginInstaller instance
     */
    private function getInstaller(): PluginInstaller
    {
        return new PluginInstaller(__DIR__, ROOT_DIR . 'api');
    }

    /**
     * Get the HtaccessManager instance
     */
    private function getHtaccessManager(): HtaccessManager
    {
        return new HtaccessManager(ROOT_DIR . 'api/.htaccess');
    }

    /**
     * Bootstrap the plugin.
     * Checks for updates and auto-updates if needed.
     */
    function bootstrap()
    {
        // Get current plugin version from plugin.php file
        $pluginInfo = include(__DIR__ . '/plugin.php');
        $currentVersion = $pluginInfo['version'];

        // Get installed version from config
        $installedVersion = $this->getConfig()->get('installed_version');

        // First time activation or update needed?
        if (!$installedVersion || version_compare($installedVersion, $currentVersion, '<')) {
            $this->performUpdate($installedVersion, $currentVersion);
        }
    }

    /**
     * Called when plugin is enabled.
     * Automatically installs the wildcard endpoint.
     */
    function enable()
    {
        $errors = [];

        // Auto-create instance for singleton plugin
        if ($this->isSingleton() && $this->getNumInstances() === 0) {
            $instanceVars = [
                'name' => $this->getName(),
                'isactive' => 1,
                'notes' => 'Auto-created singleton instance'
            ];

            if (!$this->addInstance($instanceVars, $instanceErrors)) {
                error_log(sprintf(
                    '[API Key Wildcard] Failed to auto-create instance: %s',
                    json_encode($instanceErrors)
                ));
                return $instanceErrors;
            }

            error_log('[API Key Wildcard] Auto-created singleton instance');
        }

        // Install wildcard endpoint file
        $installer = $this->getInstaller();
        $installErrors = $installer->install();
        $errors = array_merge($errors, $installErrors);

        // Configure .htaccess
        $htaccess = $this->getHtaccessManager();
        $htaccessErrors = $htaccess->applyAll();
        $errors = array_merge($errors, $htaccessErrors);

        return count($errors) === 0 ? true : $errors;
    }

    /**
     * Called when plugin is disabled.
     * Removes the wildcard endpoint and fully cleans up .htaccess.
     */
    function disable()
    {
        // Remove wildcard.php from /api/ directory
        $installer = $this->getInstaller();
        $installer->uninstall();

        // Clean up ALL .htaccess modifications
        $htaccess = $this->getHtaccessManager();
        if ($htaccess->exists()) {
            $htaccess->removeWildcardRule();
            $htaccess->removeMultiViews();
        }

        return true;
    }

    /**
     * Perform update from old version to new version.
     * This is called automatically when bootstrap() detects a version change.
     *
     * @param string|null $fromVersion Previously installed version
     * @param string $toVersion New version from plugin.php
     * @return bool|array True on success, array of errors on failure
     */
    function performUpdate($fromVersion, $toVersion)
    {
        $errors = [];

        // Update wildcard endpoint file
        $installer = $this->getInstaller();
        $updateErrors = $installer->update();
        $errors = array_merge($errors, $updateErrors);

        // Ensure .htaccess is properly configured
        $htaccess = $this->getHtaccessManager();
        if ($htaccess->exists()) {
            $htaccessErrors = $htaccess->applyAll();
            $errors = array_merge($errors, $htaccessErrors);
        }

        // Save installed version in config
        $this->getConfig()->set('installed_version', $toVersion);

        // Log update
        if (count($errors) === 0) {
            if ($fromVersion) {
                error_log(sprintf(
                    'API Key Wildcard Plugin: Updated from v%s to v%s',
                    $fromVersion,
                    $toVersion
                ));
            } else {
                error_log(sprintf('API Key Wildcard Plugin: Installed v%s', $toVersion));
            }
        }

        return count($errors) === 0 ? true : $errors;
    }
}

/**
 * Plugin configuration
 */
class ApiKeyWildcardConfig extends PluginConfig
{
    function getOptions()
    {
        return [
            'installed_version' => new TextboxField([
                'id' => 'installed_version',
                'label' => 'Installed Version',
                'configuration' => [
                    'desc' => 'Currently installed plugin version (auto-updated)',
                    'size' => 10,
                    'length' => 10
                ],
                'default' => '',
                'required' => false,
                'disabled' => true
            ]),
            'log_wildcard_access' => new BooleanField([
                'id' => 'log_wildcard_access',
                'label' => 'Log Wildcard API Access',
                'configuration' => [
                    'desc' => 'Log when a wildcard (0.0.0.0) API key is used via /api/wildcard.php'
                ],
                'default' => true
            ])
        ];
    }
}
