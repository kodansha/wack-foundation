<?php

namespace WackFoundation\Editor;

use WP_Block_Type_Registry;

/**
 * Block style controller for the block editor
 *
 * This class provides functionality for controlling which block styles
 * are available in the WordPress block editor. Block styles allow users
 * to apply different visual variations to blocks (e.g., rounded images,
 * outlined buttons, striped tables).
 *
 * Use the 'wack_block_style_enabled_styles' filter to specify which non-default
 * styles to enable. All other non-default styles will be automatically disabled.
 *
 * Important: Only non-default styles can be disabled. Default styles (isDefault: true)
 * such as 'core/button:fill', 'core/image:default', 'core/table:regular', etc.
 * are always available and cannot be unregistered via JavaScript.
 *
 * The actual disabling is performed by a JavaScript file that will be enqueued
 * in the block editor. The JavaScript uses wp.blocks.unregisterBlockStyle()
 * to remove the specified non-default styles.
 *
 * Primary inspection (PHP):
 * Retrieve all registered block styles via the block type registry:
 *
 * ```php
 * $registry = \WP_Block_Type_Registry::get_instance();
 * $styles = [];
 * foreach ($registry->get_all_registered() as $block) {
 *     if (!empty($block->styles)) {
 *         $styles[$block->name] = $block->styles; // each item has ['name' => 'outline', 'label' => 'Outline', 'isDefault' => bool]
 *     }
 * }
 * // $styles now maps block name => array of style meta arrays
 * ```
 *
 * Secondary (browser console quick check):
 * `wp.blocks.getBlockTypes().filter(b => b.styles?.length).map(b => ({block: b.name, styles: b.styles}))`
 * Prefer the PHP form for automation and test assertions.
 *
 * Example usage:
 * <code>
 * <?php
 * // Instantiate to enable the feature
 * new BlockStyle();
 *
 * // Use filter to specify enabled non-default block styles
 * add_filter('wack_block_style_enabled_styles', fn() => [
 *     'core/button' => ['outline'],
 *     'core/image' => ['rounded'],
 *     'core/quote' => ['plain'],
 * ]);
 * ?>
 * </code>
 */
class BlockStyle
{
    use Trait\AssetUrlTrait;

    /**
     * Script handle and file name
     */
    private const string SCRIPT_HANDLE = 'block-style';
    private const string SCRIPT_FILE = 'block-style.js';

    /**
     * Get the list of enabled block styles
     *
     * Applies the 'wack_block_style_enabled_styles' filter to allow customization.
     *
     * Format: Associative array mapping block names to arrays of style names
     * Example: ['core/button' => ['outline'], 'core/image' => ['rounded']]
     *
     * Note: Only non-default styles can be controlled. Default styles (isDefault: true)
     * always remain available and cannot be unregistered.
     *
     * @return array<string, string[]> Associative array of block styles
     */
    protected function getEnabledStyles(): array
    {
        /**
         * Filter the block styles to enable
         *
         * @param array<string, string[]> $styles Associative array of block styles. Default empty array.
         */
        return apply_filters('wack_block_style_enabled_styles', []);
    }

    /**
     * Constructor
     *
     * Registers the script that disables the specified block styles.
     */
    public function __construct()
    {
        add_action('enqueue_block_editor_assets', [$this, 'enqueueScript']);
    }

    /**
     * Calculate which styles should be disabled
     *
     * @return array<string, string[]> Array mapping block names to arrays of style names to disable
     */
    private function getDisabledStyles(): array
    {
        // Get all non-default block styles from the registry
        $all_non_default_styles = $this->getAllNonDefaultBlockStyles();

        // Get enabled styles from filter
        $enabled_styles = $this->getEnabledStyles();

        // Calculate disabled styles for each block
        $disabled_by_block = [];
        foreach ($all_non_default_styles as $block_name => $styles) {
            // Extract style names from the style arrays
            $all_style_names = array_map(fn($style) => $style['name'], $styles);

            // Get enabled style names for this block (default to empty array)
            $enabled_style_names = $enabled_styles[$block_name] ?? [];

            // Calculate disabled styles for this block
            $disabled_style_names = array_values(array_diff($all_style_names, $enabled_style_names));

            // Only add to result if there are disabled styles
            if (!empty($disabled_style_names)) {
                $disabled_by_block[$block_name] = $disabled_style_names;
            }
        }

        return $disabled_by_block;
    }

    /**
     * Get all registered non-default block styles
     *
     * @return array<string, array<int, array<string, mixed>>> Array of non-default block styles keyed by block name
     */
    private function getAllNonDefaultBlockStyles(): array
    {
        $blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

        // First, extract all block styles
        $all_styles = array_reduce(
            $blocks,
            function ($styles, $block) {
                if (!empty($block->styles)) {
                    $styles[$block->name] = $block->styles;
                }
                return $styles;
            },
            [],
        );

        // Then, filter to only non-default styles
        return array_reduce(
            array_keys($all_styles),
            function ($non_default_styles, $block_name) use ($all_styles) {
                $filtered = array_filter(
                    $all_styles[$block_name],
                    fn($style) => empty($style['isDefault']) && empty($style['is_default']),
                );

                if (!empty($filtered)) {
                    $non_default_styles[$block_name] = array_values($filtered);
                }

                return $non_default_styles;
            },
            [],
        );
    }

    /**
     * Enqueue the JavaScript file to disable block styles
     *
     * Loads the script and passes the disabled styles as inline script data.
     *
     * @return void
     */
    public function enqueueScript(): void
    {
        if (!$this->enqueueAssetSafely(
            self::SCRIPT_HANDLE,
            self::SCRIPT_FILE,
            ['wp-blocks', 'wp-dom-ready', 'wp-edit-post'],
            'script',
        )) {
            return;
        }

        // Pass the disabled styles to the JavaScript
        wp_localize_script(
            self::SCRIPT_HANDLE,
            'blockStyleDisablerConfig',
            [
                'disabledStyles' => $this->getDisabledStyles(),
            ],
        );
    }
}
