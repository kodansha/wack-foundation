<?php

namespace WackFoundation\Editor;

/**
 * Embed block variation controller for the block editor
 *
 * This class provides functionality for controlling which embed block variations
 * (such as Twitter, YouTube, Facebook, etc.) are available in the WordPress
 * block editor. Use the 'wack_embed_block_enabled_variations' filter to specify
 * which variations to enable.
 *
 * The actual disabling is performed by a JavaScript file that will be enqueued
 * in the block editor. For most variations, JavaScript is used to unregister them.
 * However, the generic URL embed block ('url' variation) cannot be disabled via
 * JavaScript, so CSS is used to hide it from the block inserter when not enabled.
 *
 * To find the list of all embed block variations, see:
 * https://developer.wordpress.org/block-editor/reference-guides/core-blocks/#embed
 *
 * You can also get the list of registered variations by running the following
 * in the browser console on the block editor page:
 * `wp.blocks.getBlockVariations('core/embed')`
 *
 * Example usage:
 * <code>
 * <?php
 * // Instantiate to enable the feature
 * new EmbedBlockVariation();
 *
 * // Use filter to specify enabled variations
 * add_filter('wack_embed_block_enabled_variations', fn() => [
 *     'youtube',
 *     'vimeo',
 *     'url', // Include 'url' to allow generic URL embed block
 * ]);
 * ?>
 * </code>
 */
class EmbedBlockVariation
{
    use Trait\AssetUrlTrait;

    /**
     * Script handle and file name
     */
    private const string SCRIPT_HANDLE = 'embed-block-variation';
    private const string SCRIPT_FILE = 'embed-block-variation.js';

    /**
     * Style handle and file name for hiding generic URL embed
     */
    private const string STYLE_HANDLE = 'hide-generic-url-embed';
    private const string STYLE_FILE = 'hide-generic-url-embed.css';

    /**
     * Get the list of enabled embed block variations
     *
    * Applies the 'wack_embed_block_enabled_variations' filter to allow customization.
     *
     * Note: Include 'url' in this array if you want to allow the generic URL
     * embed block. The 'url' variation is handled differently from other variations
     * because it cannot be disabled via JavaScript and requires CSS to hide it.
     *
     * @return string[] Array of variation names (e.g., 'youtube', 'vimeo', 'url')
     */
    protected function getEnabledVariations(): array
    {
        /**
         * Filter the embed block variations to enable
         *
         * @param string[] $variations Array of variation names. Default empty array.
         */
        return apply_filters('wack_embed_block_enabled_variations', []);
    }

    /**
     * Constructor
     *
     * Registers the script that disables the specified embed variations
     * and optionally the CSS to hide the generic URL embed block.
     */
    public function __construct()
    {
        add_action('enqueue_block_editor_assets', [$this, 'enqueueScript']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueueStyle']);
    }

    /**
     * Enqueue the JavaScript file to disable embed block variations
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

        // Pass the enabled variations to JavaScript.
        // JavaScript will get all variations and disable those not in this list.
        wp_localize_script(
            self::SCRIPT_HANDLE,
            'embedBlockVariationDisablerConfig',
            [
                'enabledVariations' => $this->getEnabledVariations(),
            ],
        );
    }

    /**
     * Enqueue the CSS file to hide the generic URL embed block
     *
     * The generic URL embed block cannot be disabled via JavaScript like other variations,
     * so we use CSS to hide it from the block inserter when 'url' is not in the enabled variations.
     * If 'url' is included in $enabled_variations, this CSS will not be loaded.
     *
     * @return void
     */
    public function enqueueStyle(): void
    {
        // If 'url' is enabled, don't hide the generic embed block
        if (in_array('url', $this->getEnabledVariations(), true)) {
            return;
        }

        $this->enqueueAssetSafely(
            self::STYLE_HANDLE,
            self::STYLE_FILE,
        );
    }
}
