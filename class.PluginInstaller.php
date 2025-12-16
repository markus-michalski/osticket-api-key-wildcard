<?php
/**
 * PluginInstaller
 *
 * Handles file operations for plugin installation/uninstallation.
 * Encapsulates all file copy, chmod, and unlink operations.
 *
 * SINGLE RESPONSIBILITY: File system operations for plugin deployment
 */
class PluginInstaller
{
    /** @var string Plugin source directory */
    private $pluginDir;

    /** @var string Target API directory */
    private $apiDir;

    /**
     * @param string $pluginDir Source directory (plugin location)
     * @param string $apiDir Target directory (osTicket /api/ folder)
     */
    public function __construct(string $pluginDir, string $apiDir)
    {
        $this->pluginDir = rtrim($pluginDir, '/');
        $this->apiDir = rtrim($apiDir, '/');
    }

    /**
     * Get source path for the wildcard endpoint file
     */
    private function getSourcePath(): string
    {
        return $this->pluginDir . '/' . PluginConstants::ENDPOINT_FILE;
    }

    /**
     * Get target path for the wildcard endpoint file
     */
    private function getTargetPath(): string
    {
        return $this->apiDir . '/' . PluginConstants::ENDPOINT_FILE;
    }

    /**
     * Check if endpoint is already installed
     */
    public function isInstalled(): bool
    {
        return file_exists($this->getTargetPath());
    }

    /**
     * Install the wildcard endpoint file
     *
     * @return array List of errors (empty if successful)
     */
    public function install(): array
    {
        $errors = [];
        $source = $this->getSourcePath();
        $target = $this->getTargetPath();

        if (!file_exists($source)) {
            $errors[] = sprintf('Source file not found: %s', $source);
            return $errors;
        }

        if (!copy($source, $target)) {
            $errors[] = sprintf(
                'Failed to copy %s to %s. Please copy manually.',
                PluginConstants::ENDPOINT_FILE,
                $this->apiDir
            );
            return $errors;
        }

        if (!chmod($target, PluginConstants::FILE_MODE)) {
            $errors[] = sprintf(
                'Failed to set permissions on %s. Please run: chmod %o %s',
                $target,
                PluginConstants::FILE_MODE,
                $target
            );
        }

        return $errors;
    }

    /**
     * Uninstall the wildcard endpoint file
     *
     * @return bool True on success or if file doesn't exist
     */
    public function uninstall(): bool
    {
        $target = $this->getTargetPath();

        if (!file_exists($target)) {
            return true; // Already uninstalled
        }

        if (!unlink($target)) {
            error_log(sprintf('[API Key Wildcard] Failed to uninstall endpoint: %s', $target));
            return false;
        }

        return true;
    }

    /**
     * Update the wildcard endpoint file (reinstall with latest version)
     *
     * @return array List of errors (empty if successful)
     */
    public function update(): array
    {
        // For updates, we simply reinstall (copy overwrites existing)
        return $this->install();
    }
}
