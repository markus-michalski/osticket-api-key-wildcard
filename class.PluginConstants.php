<?php
/**
 * Plugin Constants
 *
 * Centralizes all magic strings and configuration values.
 * Eliminates hardcoded strings throughout the plugin.
 */
class PluginConstants
{
    // Plugin identification
    const PLUGIN_SLUG = 'api-key-wildcard';
    const ENDPOINT_FILE = 'wildcard.php';

    // Wildcard configuration
    const WILDCARD_IP = '0.0.0.0';

    // .htaccess configuration
    const HTACCESS_MULTIVIEWS = 'Options -MultiViews';
    const HTACCESS_WILDCARD_COMMENT = '# Wildcard API endpoint (must come BEFORE the default rule)';
    const HTACCESS_DEFAULT_COMMENT = '# Default API endpoint (standard osTicket)';

    // File permissions
    const FILE_MODE = 0755;
}
