<?php

namespace WackFoundation\Editor;

/**
 * Block editor UI adjustments that cannot be handled by standard theme
 * customization or block supports configuration.
 * Implemented as a workaround for limitations in WordPress core.
 *
 * All adjustment groups are enabled by default. Individual groups can be
 * disabled by adding their feature key to the `wack_ui_workaround_disabled_features`
 * filter. When all features are disabled, no assets are enqueued.
 *
 * Available feature keys:
 * - headingToolbar    : hide "Text alignment", "Bold", "Link" in heading toolbar;
 *                       disable "Align" button support
 * - separatorToolbar  : disable "Align" button support; hide as fallback if needed
 * - imageToolbar      : hide "Align", "Link", "Crop", "Add caption" group in toolbar;
 *                       disable "Align" button support
 * - imageSidebar      : hide "Settings" panel (alt text, aspect ratio, width, height…)
 * - statusVisibility  : hide "Password protection" and "Stick to the top of the blog"
 * - viewOptionsDevices: hide device selection (Desktop / Tablet / Mobile)
 * - previewButton     : hide preview dropdown and View/Preview links by post status
 *
 * Example — disable specific features:
 *
 * ```php
 * add_filter('wack_ui_workaround_disabled_features', function (array $disabled): array {
 *     return [...$disabled, 'statusVisibility', 'previewButton'];
 * });
 * ```
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

    /** @var string[] All available feature keys */
    private const array ALL_FEATURES = [
        'headingToolbar',
        'separatorToolbar',
        'imageToolbar',
        'imageSidebar',
        'statusVisibility',
        'viewOptionsDevices',
        'previewButton',
    ];

    /**
     * Constructor
     *
     * Registers hooks. If all features are disabled via the filter, no assets
     * are enqueued. To disable individual features:
     *
     * ```php
     * add_filter('wack_ui_workaround_disabled_features', function (array $disabled): array {
     *     return [...$disabled, 'statusVisibility', 'previewButton'];
     * });
     * ```
     */
    public function __construct()
    {
        add_action('enqueue_block_editor_assets', [$this, 'enqueueAssets']);
    }

    /**
     * Enqueue scripts and styles for the block editor, and pass per-feature
     * config to the script via an inline window variable.
     *
     * Skipped entirely when all features are disabled.
     */
    public function enqueueAssets(): void
    {
        $config = $this->buildConfig();

        // Skip enqueueing when no features are active
        if (!in_array(true, $config, true)) {
            return;
        }

        $enqueued = $this->enqueueAssetSafely(
            self::SCRIPT_HANDLE,
            self::SCRIPT_FILE,
            ['wp-hooks', 'wp-data', 'wp-dom-ready', 'wp-edit-post'],
            'script',
        );

        if ($enqueued) {
            wp_add_inline_script(
                self::SCRIPT_HANDLE,
                'window.' . self::CONFIG_OBJECT . ' = ' . wp_json_encode($config) . ';',
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
     * Build the per-feature config object passed to JavaScript.
     *
     * Applies the `wack_ui_workaround_disabled_features` filter to determine
     * which features to disable. Each key is true (enabled) by default;
     * features listed in the filter result are set to false.
     *
     * @return array<string, bool>
     */
    private function buildConfig(): array
    {
        /**
         * Filter the list of disabled UI workaround features.
         *
         * Pass feature keys to disable. All features are enabled by default.
         * See UICustomizationWorkaround::ALL_FEATURES for available keys.
         *
         * @param string[] $disabled Feature keys to disable. Default [].
         */
        $disabled = (array) apply_filters('wack_ui_workaround_disabled_features', []);

        return array_combine(
            self::ALL_FEATURES,
            array_map(fn($feature) => !in_array($feature, $disabled, true), self::ALL_FEATURES),
        );
    }
}
