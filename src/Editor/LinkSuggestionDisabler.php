<?php

namespace WackFoundation\Editor;

/**
 * Link suggestion disabler for the block editor
 *
 * This class provides functionality to disable link search suggestions in the
 * WordPress block editor's link insertion interface. This is particularly useful
 * for headless WordPress installations where internal post/page links should not
 * be selectable.
 *
 * By default, the link suggestions are disabled. Child themes can enable
 * suggestions by using the 'wack_link_suggestion_disabled' filter.
 *
 * The implementation uses wp.data.subscribe() to continuously monitor the
 * __experimentalFetchLinkSuggestions setting, as WordPress overwrites this
 * setting multiple times during editor initialization.
 *
 * Example usage:
 * <code>
 * <?php
 * // Instantiate to enable the feature (disables suggestions by default)
 * new LinkSuggestionDisabler();
 *
 * // To enable suggestions in child theme
 * add_filter('wack_link_suggestion_disabled', fn() => false);
 * ?>
 * </code>
 *
 * Reference: https://wordpress.org/support/topic/modify-gutenberg-link-dialog-suggestions/
 */
class LinkSuggestionDisabler
{
    use Trait\AssetUrlTrait;

    /**
     * Script handle and file name
     */
    private const string SCRIPT_HANDLE = 'link-suggestion-disabler';
    private const string SCRIPT_FILE = 'link-suggestion-disabler.js';

    /**
     * Check if link suggestions should be disabled
     *
     * Applies the 'wack_link_suggestion_disabled' filter to allow customization.
     *
     * @return bool True if suggestions should be disabled, false otherwise
     */
    protected function isDisabled(): bool
    {
        /**
         * Filter whether to disable link suggestions
         *
         * @param bool $disabled Whether to disable link suggestions. Default true.
         */
        return apply_filters('wack_link_suggestion_disabled', true);
    }

    /**
     * Constructor
     *
     * Registers the script that disables link suggestions.
     */
    public function __construct()
    {
        add_action('enqueue_block_editor_assets', [$this, 'enqueueScript']);
    }

    /**
     * Enqueue the JavaScript file to disable link suggestions
     *
     * Loads the script only if suggestions should be disabled.
     * JavaScript will continuously monitor and override the
     * __experimentalFetchLinkSuggestions setting.
     *
     * @return void
     */
    public function enqueueScript(): void
    {
        // Only enqueue if suggestions should be disabled
        if (!$this->isDisabled()) {
            return;
        }

        $this->enqueueAssetSafely(
            self::SCRIPT_HANDLE,
            self::SCRIPT_FILE,
            ['wp-dom-ready', 'wp-data'],
            'script',
        );
    }
}
