<?php

namespace WackFoundation\Media;

/**
 * Class for controlling WordPress image size generation
 *
 * Disables all WordPress default and auto-generated image sizes, and enables only
 * custom image sizes defined via the 'wack_image_size_control_custom_sizes' filter.
 * Also disables the big image auto-resize feature introduced in WordPress 5.3+ (always disabled).
 *
 * This class sets all default WordPress image size options to 0 via fixMediaOptions().
 *
 * Use the 'wack_image_size_control_custom_sizes' filter to define custom image sizes.
 * The filter should return an array of size definitions, where each definition is
 * an array with the same format as add_image_size() parameters.
 *
 * Example usage:
 * <code>
 * <?php
 * // Instantiate to enable the feature
 * new ImageSizeControl();
 *
 * // Define custom image sizes via filter
 * add_filter('wack_image_size_control_custom_sizes', fn() => [
 *     // Format: 'size-name' => [width, height, crop]
 *     // All parameters are optional except width
 *
 *     // Fixed size with crop
 *     'card-thumbnail' => [400, 300, true],
 *
 *     // Fixed size without crop (default)
 *     'hero-banner' => [1200, 600],
 *
 *     // Width only (height auto-calculated)
 *     'content-width' => [800],
 *
 *     // Width and height, no crop
 *     'gallery-large' => [1024, 768, false],
 *
 *     // Width only with soft crop
 *     'post-thumbnail' => [600, 0, false],
 * ]);
 * ?>
 * </code>
 */
class ImageSizeControl
{
    /**
     * Constructor
     *
     * Automatically registers all hooks for image size control:
     * - init: Registers custom image sizes and sets media options to 0
     * - intermediate_image_sizes_advanced: Controls which image sizes are generated
     * - big_image_size_threshold: Disables big image auto-resize (WP 5.3+) (always disabled)
     */
    public function __construct()
    {
        add_action('init', [$this, 'registerCustomSizes']);
        add_filter('intermediate_image_sizes_advanced', [$this, 'filterIntermediateSizes'], PHP_INT_MAX - 1, 2);
        add_filter('big_image_size_threshold', '__return_false', PHP_INT_MAX - 1);
        add_action('init', [$this, 'fixMediaOptions'], 20);
    }

    /**
     * Register custom image sizes based on filter
     *
     * @return void
     */
    public function registerCustomSizes(): void
    {
        $custom_sizes = $this->getCustomSizes();

        foreach ($custom_sizes as $name => $config) {
            $width = $config[0] ?? 0;
            $height = $config[1] ?? 0;
            $crop = $config[2] ?? false;

            add_image_size($name, $width, $height, $crop);
        }
    }

    /**
     * Get custom image size definitions
     *
    * Applies the 'wack_image_size_control_custom_sizes' filter to get custom size definitions.
     * Returns an empty array by default (no custom sizes).
     *
     * @return array Associative array of custom size definitions
     */
    protected function getCustomSizes(): array
    {
        /**
         * Filter the custom image size definitions
         *
         * @param array $custom_sizes Array of custom size definitions.
         *                            Format: ['size-name' => [width, height, crop]]
         */
        return apply_filters('wack_image_size_control_custom_sizes', []);
    }

    /**
     * Filter callback for intermediate_image_sizes_advanced
     *
     * Only enables custom image sizes defined via filter.
     * All WordPress default and auto-generated sizes are excluded.
     *
     * @param array $sizes Array of image sizes with their configurations
     * @return array Filtered array of enabled image sizes
     */
    public function filterIntermediateSizes(array $sizes): array
    {
        return $this->getEnabledSizes($sizes);
    }

    /**
     * Get enabled image sizes
     *
     * Returns only the custom image sizes defined via the 'wack_image_size_control_custom_sizes' filter.
     * All other sizes (WordPress defaults and auto-generated sizes) are excluded.
     *
     * @param array $sizes Associative array of image sizes (key: size name, value: size config)
     * @return array Array of enabled image sizes
     */
    protected function getEnabledSizes(array $sizes): array
    {
        $custom_sizes = $this->getCustomSizes();
        $custom_size_names = array_keys($custom_sizes);

        // Return only the custom sizes that exist in $sizes
        return array_intersect_key($sizes, array_flip($custom_size_names));
    }

    /**
     * Set all media size options to 0 to prevent UI from restoring them
     *
     * This method sets all WordPress media size options (thumbnail, medium, large, etc.)
     * to 0, preventing the admin UI from inadvertently re-enabling image generation.
     * Can be overridden in child classes for custom behavior.
     *
     * @return void
     */
    public function fixMediaOptions(): void
    {
        update_option('thumbnail_size_w', 0);
        update_option('thumbnail_size_h', 0);
        update_option('medium_size_w', 0);
        update_option('medium_size_h', 0);
        update_option('large_size_w', 0);
        update_option('large_size_h', 0);
        update_option('medium_large_size_w', 0);
        update_option('medium_large_size_h', 0);
    }
}
