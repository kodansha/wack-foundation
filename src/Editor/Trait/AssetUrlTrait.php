<?php

namespace WackFoundation\Editor\Trait;

use ReflectionClass;

/**
 * Trait for handling asset URL generation in editor components
 *
 * This trait provides common asset file URL generation logic used across
 * editor-related classes. It normalizes asset file paths and calculates
 * relative paths from the theme directory to generate full URLs.
 */
trait AssetUrlTrait
{
    /**
     * Generate URL for an asset file
     *
     * Creates a complete URL for a file in the current class's assets directory
     * based on the specified filename.
     *
     * @param string $filename Asset filename (e.g., 'style.css', 'script.js')
     * @return string Complete URL to the asset file
     */
    protected function getAssetUrl(string $filename): string
    {
        $asset_path = $this->getAssetPath($filename);
        $template_dir = wp_normalize_path(get_template_directory());
        $asset_path_normalized = wp_normalize_path($asset_path);
        $relative_path = str_replace($template_dir, '', $asset_path_normalized);

        return get_template_directory_uri() . $relative_path;
    }

    /**
     * Get physical path to an asset file
     *
     * Used when you need to access the file directly, such as with filemtime()
     * to get file modification time. Uses reflection to get the correct path
     * relative to the calling class, not the trait.
     *
     * @param string $filename Asset filename
     * @return string Physical path to the asset file
     */
    protected function getAssetPath(string $filename): string
    {
        $reflection = new ReflectionClass($this);
        $class_dir = dirname($reflection->getFileName());
        return $class_dir . '/assets/' . $filename;
    }

    /**
     * Check if an asset file exists
     *
     * Verifies file existence and logs an error if the file is not found.
     *
     * @param string $filename Asset filename
     * @return bool True if file exists, false otherwise
     */
    protected function assetExists(string $filename): bool
    {
        $path = $this->getAssetPath($filename);
        $exists = file_exists($path);

        if (!$exists) {
            error_log('Asset file not found: ' . $path);
        }

        return $exists;
    }

    /**
     * Safely enqueue an asset with existence check
     *
     * Checks for file existence before enqueueing the asset.
     * Supports both stylesheets and scripts.
     *
     * @param string $handle Enqueue handle name
     * @param string $filename Asset filename
     * @param array $dependencies Array of dependencies
     * @param string $type Asset type ('style' or 'script')
     * @return bool True if enqueue succeeded, false if failed
     */
    protected function enqueueAssetSafely(
        string $handle,
        string $filename,
        array $dependencies = [],
        string $type = 'style',
    ): bool {
        if (!$this->assetExists($filename)) {
            return false;
        }

        $url = $this->getAssetUrl($filename);
        $path = $this->getAssetPath($filename);
        $version = filemtime($path);

        if ($type === 'script') {
            wp_enqueue_script($handle, $url, $dependencies, $version, true);
        } else {
            wp_enqueue_style($handle, $url, $dependencies, $version);
        }

        return true;
    }
}
