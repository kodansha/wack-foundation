<?php

namespace WackFoundation\Editor;

/**
 * Block variation controller for the block editor
 *
 * This class provides functionality for controlling which block variations
 * are available in the WordPress block editor. Use the 'wack_block_enabled_variations'
 * filter to specify which variations to enable for each block type.
 *
 * The actual disabling is performed by a JavaScript file that will be enqueued
 * in the block editor. For most variations, JavaScript is used to unregister them.
 * However, some variations (like the generic URL embed block 'url' variation) cannot
 * be disabled via JavaScript, so CSS is used to hide them from the block inserter
 * when not enabled.
 *
 * To find the list of all block variations for a specific block, you can run the
 * following in the browser console on the block editor page:
 * `wp.blocks.getBlockVariations('core/embed')`
 *
 * For embed blocks specifically, see:
 * https://developer.wordpress.org/block-editor/reference-guides/core-blocks/#embed
 *
 * Example usage:
 * <code>
 * <?php
 * // Instantiate to enable the feature
 * new BlockVariation();
 *
 * // Use filter to specify enabled variations per block type
 * add_filter('wack_block_enabled_variations', function($variations) {
 *     return [
 *         'core/embed' => [
 *             'youtube',
 *             'vimeo',
 *             'url', // Include 'url' to allow generic URL embed block
 *         ],
 *         // Add more block types and their enabled variations as needed
 *     ];
 * });
 * ?>
 * </code>
 */
class BlockVariation
{
    use Trait\AssetUrlTrait;

    /**
     * Script handle and file name
     */
    private const string SCRIPT_HANDLE = 'block-variation';
    private const string SCRIPT_FILE = 'block-variation.js';

    /**
     * Style handle and file name for hiding generic URL embed
     */
    private const string STYLE_HANDLE = 'hide-generic-url-embed';
    private const string STYLE_FILE = 'hide-generic-url-embed.css';

    /**
     * Get the map of enabled block variations per block type
     *
     * Applies the 'wack_block_enabled_variations' filter to allow customization.
     *
     * Note: For embed blocks, include 'url' in the array if you want to allow
     * the generic URL embed block. The 'url' variation is handled differently
     * from other variations because it cannot be disabled via JavaScript and
     * requires CSS to hide it.
     *
     * @return array<string, string[]> Map of block types to their enabled variations
     *                                  e.g., ['core/embed' => ['youtube', 'vimeo', 'url']]
     */
    protected function getEnabledVariations(): array
    {
        /**
         * Filter the block variations to enable per block type
         *
         * @param array<string, string[]> $variations Map of block types to enabled variation names.
         *                                             Default empty array.
         */
        return apply_filters('wack_block_enabled_variations', []);
    }

    /**
     * Constructor
     *
     * Registers the script that disables the specified block variations
     * and optionally the CSS to hide the generic URL embed block.
     */
    public function __construct()
    {
        add_action('enqueue_block_editor_assets', [$this, 'enqueueScript']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueueStyle']);
    }

    /**
     * Enqueue the JavaScript file to disable block variations
     *
     * Loads the script and passes the enabled variations as inline script data.
     * JavaScript will handle determining which variations to disable.
     *
     * @return void
     */
    public function enqueueScript(): void
    {
        if (!$this->enqueueAssetSafely(
            self::SCRIPT_HANDLE,
            self::SCRIPT_FILE,
            ['wp-blocks', 'wp-dom-ready'],
            'script',
        )) {
            return;
        }

        // Pass the enabled variations per block type to JavaScript.
        // JavaScript will get all variations for each block and disable those not in this list.
        wp_localize_script(
            self::SCRIPT_HANDLE,
            'blockVariationDisablerConfig',
            [
                'enabledVariations' => $this->getEnabledVariations(),
            ],
        );
    }

    /**
     * Enqueue the CSS file to hide the generic URL embed block
     *
     * The generic URL embed block cannot be disabled via JavaScript like other variations,
     * so we use CSS to hide it from the block inserter when 'url' is not in the enabled variations
     * for 'core/embed'. If 'url' is included in the enabled variations, this CSS will not be loaded.
     *
     * @return void
     */
    public function enqueueStyle(): void
    {
        $enabledVariations = $this->getEnabledVariations();

        // If 'url' is enabled for core/embed, don't hide the generic embed block
        if (
            isset($enabledVariations['core/embed'])
            && in_array('url', $enabledVariations['core/embed'], true)
        ) {
            return;
        }

        // If core/embed is not configured at all, also hide the generic embed
        // (assuming we want to disable all variations by default)
        $this->enqueueAssetSafely(
            self::STYLE_HANDLE,
            self::STYLE_FILE,
        );
    }
}
