<?php

namespace WackFoundation\Editor;

/**
 * Block editor UI adjustments that cannot be handled by standard theme
 * customization or block supports configuration.
 * Implemented as a workaround for limitations in WordPress core.
 *
 * Current adjustments:
 *
 * [Heading block toolbar]
 * - "Align": overrides supports.align via blocks.registerBlockType JS filter
 * - "Text alignment", "Bold", "Link": monitored via wp.data.subscribe; body class
 *   toggled to hide controls via CSS
 *
 * [Separator block toolbar]
 * - "Align": overrides supports.align via blocks.registerBlockType JS filter
 *   (CSS fallback also applied in case the filter has no effect)
 *
 * [Image block toolbar]
 * - "Align", "Link", "Crop", "Add caption": hidden as a group via CSS
 *   (align also disabled via JS filter)
 *
 * [Image block sidebar]
 * - "Settings" panel (alt text, aspect ratio, width, height, resolution):
 *   hidden via CSS panel-level selector
 *
 * [Status & Visibility popup]
 * - "Password protection", "Stick to the top of the blog": hidden via CSS
 *
 * [View Options menu]
 * - Device selection group (Desktop / Tablet / Mobile): hidden via CSS
 *   Anchored on .editor-preview-dropdown__button-external (language-independent)
 *
 * [Preview / View button]
 * - Monitors post status via wp.data; sets data-custom-post-status on body
 * - Published: hides the preview dropdown (monitor icon)
 * - Private: hides both the preview dropdown and View/Preview header links
 *
 * Note: relies on WordPress internal CSS classes and DOM structure,
 * so adjustments may stop working after a WordPress version upgrade.
 * Verified on WordPress 6.9.3.
 */
class UICustomizationWorkaround
{
    use Trait\AssetUrlTrait;

    private const string SCRIPT_HANDLE = 'wack-ui-customization-workaround';
    private const string STYLE_HANDLE = 'wack-ui-customization-workaround';
    private const string SCRIPT_FILE = 'ui-customization-workaround.js';
    private const string STYLE_FILE = 'ui-customization-workaround.css';

    /**
     * Constructor
     *
     * Registers hooks. UI customization can be disabled entirely via the
     * `wack_enable_ui_customization_workaround` filter:
     *
     * ```php
     * add_filter('wack_enable_ui_customization_workaround', '__return_false');
     * ```
     */
    public function __construct()
    {
        /**
         * Filter whether to enable the UI customization workaround
         *
         * @param bool $enabled Whether to enable the workaround. Default true.
         */
        if (!apply_filters('wack_enable_ui_customization_workaround', true)) {
            return;
        }

        add_action('enqueue_block_editor_assets', [$this, 'enqueueAssets']);
    }

    /**
     * Enqueue scripts and styles for the block editor
     */
    public function enqueueAssets(): void
    {
        $this->enqueueAssetSafely(
            self::SCRIPT_HANDLE,
            self::SCRIPT_FILE,
            ['wp-hooks', 'wp-data', 'wp-dom-ready', 'wp-edit-post'],
            'script',
        );

        $this->enqueueAssetSafely(
            self::STYLE_HANDLE,
            self::STYLE_FILE,
            [],
            'style',
        );
    }
}
