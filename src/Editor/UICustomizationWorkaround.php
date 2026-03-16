<?php

namespace WackFoundation\Editor;

/**
 * Block editor UI adjustments that cannot be handled by standard theme
 * customization or block supports configuration.
 * Implemented as a workaround for limitations in WordPress core.
 *
 * Each adjustment group can be individually disabled via a dedicated filter.
 * All filters default to true (enabled). The entire feature can also be
 * disabled at once via `wack_enable_ui_customization_workaround`.
 *
 * [Heading block toolbar] — filter: wack_ui_workaround_heading_toolbar_enabled
 * - "Align": disabled via blocks.registerBlockType JS filter
 * - "Text alignment", "Bold", "Link": hidden by JS via wack-ui-hidden class
 *
 * [Separator block toolbar] — filter: wack_ui_workaround_separator_toolbar_enabled
 * - "Align": disabled via blocks.registerBlockType JS filter
 *   (JS also applies wack-ui-hidden as a fallback if the filter has no effect)
 *
 * [Image block toolbar] — filter: wack_ui_workaround_image_toolbar_enabled
 * - "Align", "Link", "Crop", "Add caption": group hidden by JS via wack-ui-hidden
 *   (align also disabled via JS filter)
 *
 * [Image block sidebar] — filter: wack_ui_workaround_image_sidebar_enabled
 * - "Settings" panel (alt text, aspect ratio, width, height, resolution):
 *   panel hidden by JS via wack-ui-hidden
 *
 * [Status & Visibility popup] — filter: wack_ui_workaround_status_visibility_enabled
 * - "Password protection", "Stick to the top of the blog":
 *   hidden by JS via wack-ui-hidden (observed via MutationObserver)
 *
 * [View Options menu] — filter: wack_ui_workaround_view_options_devices_enabled
 * - Device selection group (Desktop / Tablet / Mobile):
 *   hidden by JS via wack-ui-hidden (observed via MutationObserver)
 *
 * [Preview / View button] — filter: wack_ui_workaround_preview_button_enabled
 * - Published: hides the preview dropdown (monitor icon) via wack-ui-hidden
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
    private const string CONFIG_OBJECT = 'wackUiWorkaroundConfig';

    /**
     * Constructor
     *
     * Registers hooks. All UI customization can be disabled entirely via the
     * `wack_enable_ui_customization_workaround` filter:
     *
     * ```php
     * add_filter('wack_enable_ui_customization_workaround', '__return_false');
     * ```
     *
     * Individual adjustment groups can be disabled via their own filters:
     *
     * ```php
     * add_filter('wack_ui_workaround_heading_toolbar_enabled',        '__return_false');
     * add_filter('wack_ui_workaround_separator_toolbar_enabled',      '__return_false');
     * add_filter('wack_ui_workaround_image_toolbar_enabled',          '__return_false');
     * add_filter('wack_ui_workaround_image_sidebar_enabled',          '__return_false');
     * add_filter('wack_ui_workaround_status_visibility_enabled',      '__return_false');
     * add_filter('wack_ui_workaround_view_options_devices_enabled',   '__return_false');
     * add_filter('wack_ui_workaround_preview_button_enabled',         '__return_false');
     * ```
     */
    public function __construct()
    {
        /**
         * Filter whether to enable all UI customization workarounds at once
         *
         * @param bool $enabled Whether to enable the workaround. Default true.
         */
        if (!apply_filters('wack_enable_ui_customization_workaround', true)) {
            return;
        }

        add_action('enqueue_block_editor_assets', [$this, 'enqueueAssets']);
    }

    /**
     * Enqueue scripts and styles for the block editor, and pass per-feature
     * config to the script via an inline window variable
     */
    public function enqueueAssets(): void
    {
        $enqueued = $this->enqueueAssetSafely(
            self::SCRIPT_HANDLE,
            self::SCRIPT_FILE,
            ['wp-hooks', 'wp-data', 'wp-dom-ready', 'wp-edit-post'],
            'script',
        );

        if ($enqueued) {
            wp_add_inline_script(
                self::SCRIPT_HANDLE,
                'window.' . self::CONFIG_OBJECT . ' = ' . wp_json_encode($this->buildConfig()) . ';',
                'before',
            );
        }

        $this->enqueueAssetSafely(
            self::STYLE_HANDLE,
            self::STYLE_FILE,
            [],
            'style',
        );
    }

    /**
     * Build the per-feature config object to be passed to JavaScript
     *
     * Each key maps to a filter that child themes can use to disable
     * the corresponding adjustment group.
     *
     * @return array<string, bool>
     */
    private function buildConfig(): array
    {
        return [
            /**
             * Filter whether to hide heading block toolbar controls
             * ("Text alignment", "Bold", "Link", and "Align" support)
             *
             * @param bool $enabled Default true.
             */
            'headingToolbar' => (bool) apply_filters('wack_ui_workaround_heading_toolbar_enabled', true),

            /**
             * Filter whether to hide separator block toolbar controls
             * ("Align" support and fallback CSS hide)
             *
             * @param bool $enabled Default true.
             */
            'separatorToolbar' => (bool) apply_filters('wack_ui_workaround_separator_toolbar_enabled', true),

            /**
             * Filter whether to hide image block toolbar controls
             * ("Align", "Link", "Crop", "Add caption")
             *
             * @param bool $enabled Default true.
             */
            'imageToolbar' => (bool) apply_filters('wack_ui_workaround_image_toolbar_enabled', true),

            /**
             * Filter whether to hide image block sidebar settings panel
             * (alt text, aspect ratio, width, height, resolution)
             *
             * @param bool $enabled Default true.
             */
            'imageSidebar' => (bool) apply_filters('wack_ui_workaround_image_sidebar_enabled', true),

            /**
             * Filter whether to hide Status & Visibility popup items
             * ("Password protection" and "Stick to the top of the blog")
             *
             * @param bool $enabled Default true.
             */
            'statusVisibility' => (bool) apply_filters('wack_ui_workaround_status_visibility_enabled', true),

            /**
             * Filter whether to hide View Options menu device selection
             * (Desktop / Tablet / Mobile)
             *
             * @param bool $enabled Default true.
             */
            'viewOptionsDevices' => (bool) apply_filters('wack_ui_workaround_view_options_devices_enabled', true),

            /**
             * Filter whether to hide Preview / View header buttons
             * based on post status (published → dropdown, private → dropdown + links)
             *
             * @param bool $enabled Default true.
             */
            'previewButton' => (bool) apply_filters('wack_ui_workaround_preview_button_enabled', true),
        ];
    }
}
