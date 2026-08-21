<?php

namespace WackFoundation\Editor;

/**
 * Openverse disabler for the block editor
 *
 * Removes the "Openverse" category from the "Media" tab of the block inserter.
 * Openverse lets editors search openly licensed media on an external service
 * (api.openverse.org) and insert it with a generated attribution caption. When
 * the image cannot be side-loaded into the media library, WordPress offers to
 * insert it as an external image instead, which the provider can remove at any
 * time. Neither is desirable for an editorial workflow where every asset and
 * its licensing is reviewed before publication.
 *
 * The category is controlled by the 'enableOpenverseMediaCategory' block editor
 * setting, which is set through the 'block_editor_settings_all' filter. The
 * remaining categories of the "Media" tab (uploaded images and other media
 * library entries) are left untouched.
 *
 * By default Openverse is disabled. Child themes can enable it by using the
 * 'wack_openverse_disabled' filter.
 *
 * Example usage:
 * <code>
 * <?php
 * // Instantiate to enable the feature (disables Openverse by default)
 * new OpenverseDisabler();
 *
 * // To enable Openverse in a child theme
 * add_filter('wack_openverse_disabled', fn() => false);
 * ?>
 * </code>
 *
 * Reference: https://developer.wordpress.org/block-editor/reference-guides/filters/editor-filters/
 */
class OpenverseDisabler
{
    /**
     * Check if Openverse should be disabled
     *
     * Applies the 'wack_openverse_disabled' filter to allow customization.
     *
     * @return bool True if Openverse should be disabled, false otherwise
     */
    protected function isDisabled(): bool
    {
        /**
         * Filter whether to disable the Openverse media category
         *
         * @param bool $disabled Whether to disable Openverse. Default true.
         */
        return apply_filters('wack_openverse_disabled', true);
    }

    /**
     * Constructor
     *
     * Registers the filter that removes the Openverse media category.
     */
    public function __construct()
    {
        add_filter('block_editor_settings_all', [$this, 'filterEditorSettings']);
    }

    /**
     * Filter callback for the block editor settings
     *
     * Disables the Openverse media category in the inserter.
     *
     * @param array<string, mixed> $settings Block editor settings.
     * @return array<string, mixed> Filtered block editor settings
     */
    public function filterEditorSettings(array $settings): array
    {
        if (!$this->isDisabled()) {
            return $settings;
        }

        $settings['enableOpenverseMediaCategory'] = false;

        return $settings;
    }
}
