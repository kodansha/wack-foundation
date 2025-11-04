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
 * You can inspect registered styles in the browser console:
 * `wp.blocks.getBlockTypes().filter(b => b.styles?.length > 0).map(b => ({block: b.name, styles: b.styles}))`
 *
 * Example usage:
 * <code>
 * <?php
 * // Instantiate to enable the feature
 * new BlockStyle();
 *
 * // Use filter to specify enabled non-default block styles
 * add_filter('wack_block_style_enabled_styles', fn() => [
 *     'core/button:outline',
 *     'core/image:rounded',
 *     'core/quote:plain',
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
     * Applies the 'block_style_enabled_styles' filter to allow customization.
     *
     * Format: 'blockName:styleName' (e.g., 'core/button:outline', 'core/image:rounded')
     *
     * Note: Only non-default styles can be controlled. Default styles (isDefault: true)
     * always remain available and cannot be unregistered.
     *
     * @return string[] Array of block style identifiers (e.g., 'core/button:outline')
     */
    protected function getEnabledStyles(): array
    {
        /**
         * Filter the block styles to enable
         *
         * @param string[] $styles Array of block style identifiers. Default empty array.
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

        // Convert to 'blockName:styleName' format for comparison
        $all_style_identifiers = [];
        foreach ($all_non_default_styles as $block_name => $styles) {
            foreach ($styles as $style) {
                $all_style_identifiers[] = $block_name . ':' . $style['name'];
            }
        }

        // Calculate disabled styles
        $disabled_style_identifiers = array_values(
            array_diff(
                $all_style_identifiers,
                $this->getEnabledStyles(),
            ),
        );

        // Group styles by block name
        $disabled_by_block = [];
        foreach ($disabled_style_identifiers as $identifier) {
            [$block_name, $style_name] = explode(':', $identifier, 2);
            if (!isset($disabled_by_block[$block_name])) {
                $disabled_by_block[$block_name] = [];
            }
            $disabled_by_block[$block_name][] = $style_name;
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
