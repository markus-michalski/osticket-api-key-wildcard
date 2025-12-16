<?php
/**
 * HtaccessManager
 *
 * Handles all .htaccess file operations for the plugin.
 * Encapsulates the complex regex patterns and file manipulation.
 *
 * SINGLE RESPONSIBILITY: .htaccess file modifications
 */
class HtaccessManager
{
    /** @var string */
    private $htaccessPath;

    /** @var string|null Cached file content */
    private $content = null;

    /**
     * Regex pattern to find osTicket's default API rewrite block.
     * Matches: RewriteCond !-f, RewriteCond !-d, RewriteCond ...api
     */
    const PATTERN_API_REWRITE = '/^(\s*)RewriteCond\s+%\{REQUEST_FILENAME\}\s+!-f\s*\n\s*RewriteCond\s+%\{REQUEST_FILENAME\}\s+!-d\s*\n(\s*)RewriteCond\s+%\{REQUEST_URI\}\s+\(\..*\/api\)/m';

    /**
     * @param string $htaccessPath Full path to .htaccess file
     */
    public function __construct(string $htaccessPath)
    {
        $this->htaccessPath = $htaccessPath;
    }

    /**
     * Check if .htaccess file exists
     */
    public function exists(): bool
    {
        return file_exists($this->htaccessPath);
    }

    /**
     * Get file content (cached)
     */
    private function getContent(): string
    {
        if ($this->content === null) {
            $this->content = file_get_contents($this->htaccessPath) ?: '';
        }
        return $this->content;
    }

    /**
     * Invalidate cache after modifications
     */
    private function invalidateCache(): void
    {
        $this->content = null;
    }

    /**
     * Check if MultiViews option is already set
     */
    public function hasMultiViews(): bool
    {
        return strpos($this->getContent(), PluginConstants::HTACCESS_MULTIVIEWS) !== false;
    }

    /**
     * Check if wildcard rewrite rule exists
     */
    public function hasWildcardRule(): bool
    {
        return strpos($this->getContent(), 'wildcard/') !== false;
    }

    /**
     * Add Options -MultiViews after RewriteEngine On
     *
     * @return bool True on success
     */
    public function addMultiViews(): bool
    {
        if ($this->hasMultiViews()) {
            return true; // Already exists
        }

        $content = $this->getContent();
        $newContent = str_replace(
            'RewriteEngine On',
            "RewriteEngine On\n\n  # Disable MultiViews for wildcard endpoint (prevents mod_negotiation)\n  " . PluginConstants::HTACCESS_MULTIVIEWS,
            $content
        );

        $result = file_put_contents($this->htaccessPath, $newContent);
        $this->invalidateCache();

        return $result !== false;
    }

    /**
     * Add wildcard rewrite rule before the default API rule
     *
     * @return bool True on success
     */
    public function addWildcardRule(): bool
    {
        if ($this->hasWildcardRule()) {
            return true; // Already exists
        }

        $content = $this->getContent();

        if (!preg_match(self::PATTERN_API_REWRITE, $content, $matches, PREG_OFFSET_MATCH)) {
            return false; // Pattern not found, cannot inject rule
        }

        $insertPos = $matches[0][1];
        $indent = $matches[1][0];

        $rewriteRule = "{$indent}" . PluginConstants::HTACCESS_WILDCARD_COMMENT . "\n";
        $rewriteRule .= "{$indent}RewriteCond %{REQUEST_FILENAME} !-f\n";
        $rewriteRule .= "{$indent}RewriteCond %{REQUEST_FILENAME} !-d\n";
        $rewriteRule .= "{$indent}RewriteRule ^wildcard/(.*)$ " . PluginConstants::ENDPOINT_FILE . "/\$1 [L]\n\n";
        $rewriteRule .= "{$indent}" . PluginConstants::HTACCESS_DEFAULT_COMMENT . "\n";

        $newContent = substr_replace($content, $rewriteRule, $insertPos, 0);

        $result = file_put_contents($this->htaccessPath, $newContent);
        $this->invalidateCache();

        return $result !== false;
    }

    /**
     * Remove wildcard rewrite rule from .htaccess
     *
     * @return bool True on success
     */
    public function removeWildcardRule(): bool
    {
        if (!$this->hasWildcardRule()) {
            return true; // Nothing to remove
        }

        $content = $this->getContent();

        // Remove the wildcard block including comments
        $pattern = '/' . preg_quote(PluginConstants::HTACCESS_WILDCARD_COMMENT, '/') . '.*?RewriteRule.*?wildcard\.php.*?\n\n/s';
        $newContent = preg_replace($pattern, '', $content);

        // Also remove the "Default API endpoint" comment if it was added by us
        $newContent = str_replace(
            "  " . PluginConstants::HTACCESS_DEFAULT_COMMENT . "\n",
            '',
            $newContent
        );

        $result = file_put_contents($this->htaccessPath, $newContent);
        $this->invalidateCache();

        return $result !== false;
    }

    /**
     * Remove Options -MultiViews directive from .htaccess
     *
     * @return bool True on success
     */
    public function removeMultiViews(): bool
    {
        if (!$this->hasMultiViews()) {
            return true; // Nothing to remove
        }

        $content = $this->getContent();

        // Remove the MultiViews block including comment
        $pattern = '/\n\n  # Disable MultiViews.*?\n  ' . preg_quote(PluginConstants::HTACCESS_MULTIVIEWS, '/') . '/s';
        $newContent = preg_replace($pattern, '', $content);

        $result = file_put_contents($this->htaccessPath, $newContent);
        $this->invalidateCache();

        return $result !== false;
    }

    /**
     * Apply all required .htaccess modifications
     *
     * @return array List of errors (empty if successful)
     */
    public function applyAll(): array
    {
        $errors = [];

        if (!$this->exists()) {
            $errors[] = '.htaccess file not found at ' . $this->htaccessPath;
            return $errors;
        }

        if (!$this->addMultiViews()) {
            $errors[] = 'Failed to add Options -MultiViews to .htaccess';
        }

        if (!$this->addWildcardRule()) {
            $errors[] = 'Failed to add wildcard rewrite rule. Could not find osTicket API rewrite pattern.';
        }

        return $errors;
    }
}
