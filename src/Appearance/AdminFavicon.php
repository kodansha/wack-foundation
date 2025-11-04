<?php

namespace WackFoundation\Appearance;

/**
 * Admin favicon manager
 *
 * Automatically sets the favicon for WordPress admin dashboard and login page
 * by detecting favicon files in the theme's root directory. This allows each
 * child theme to have its own custom favicon.
 *
 * Supported files (in priority order):
 * 1. favicon.ico - ICO format (recommended for compatibility)
 * 2. favicon.png - PNG format (modern browsers)
 * 3. favicon.svg - SVG format (scalable vector graphics)
 *
 * Key features:
 * - Automatic detection from theme root directory
 * - Fixed file names (no customization needed)
 * - Automatic MIME type detection
 * - Works on both admin dashboard and login page
 * - Child theme support
 *
 * Usage:
 * 1. Place a favicon file (favicon.ico, favicon.png, or favicon.svg) in your theme's root directory
 * 2. Instantiate this class
 * 3. The favicon will automatically appear in the admin area and login page
 *
 * Example usage:
 * <code>
 * <?php
 * // Enable admin and login favicon
 * new AdminFavicon();
 * ?>
 * </code>
 *
 * Note: If multiple files exist, only the first one found (by priority order) will be used.
 */
class AdminFavicon
{
    /**
     * Supported favicon file names in priority order
     *
     * These file names are fixed and cannot be customized.
     * The first file found in the theme directory will be used.
     *
     * @var array<string>
     */
    private const array FAVICON_FILES = [
        'favicon.ico',
        'favicon.png',
        'favicon.svg',
    ];

    /**
     * MIME types for supported favicon formats
     *
     * @var array<string, string>
     */
    private const array MIME_TYPES = [
        'ico' => 'image/x-icon',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
    ];

    /**
     * Constructor
     *
     * Registers the admin and login favicon hooks.
     */
    public function __construct()
    {
        add_action('admin_head', [$this, 'outputFavicon']);
        add_action('login_head', [$this, 'outputFavicon']);
    }

    /**
     * Output favicon link tag in admin and login head
     *
     * Detects the favicon file and outputs the appropriate link tag
     * with MIME type automatically determined from file extension.
     * Works for both admin dashboard and login page.
     *
     * @return void
     */
    public function outputFavicon(): void
    {
        $favicon_url = $this->findFaviconUrl();

        if (!$favicon_url) {
            return;
        }

        $mime_type = $this->getMimeType($favicon_url);

        echo '<link rel="icon" type="' . esc_attr($mime_type) . '" href="' . esc_url($favicon_url) . '">' . "\n";
    }

    /**
     * Find favicon URL in theme directory
     *
     * Searches for favicon files in the current theme's root directory
     * according to the priority order (ico → png → svg).
     *
     * @return string|null Favicon URL if found, null otherwise
     */
    private function findFaviconUrl(): ?string
    {
        $theme_dir = get_stylesheet_directory();
        $theme_uri = get_stylesheet_directory_uri();

        foreach (self::FAVICON_FILES as $filename) {
            $file_path = $theme_dir . '/' . $filename;

            if (file_exists($file_path)) {
                return $theme_uri . '/' . $filename;
            }
        }

        return null;
    }

    /**
     * Get MIME type from file URL
     *
     * Determines the MIME type based on the file extension.
     *
     * @param string $url File URL
     * @return string MIME type
     */
    private function getMimeType(string $url): string
    {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        return self::MIME_TYPES[$extension] ?? 'image/x-icon';
    }
}
