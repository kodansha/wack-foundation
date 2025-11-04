<?php

namespace WackFoundation\Editor;

/**
 * Text format type controller for the block editor
 *
 * This class provides functionality for controlling which text format types
 * (such as bold, italic, code, subscript, etc.) are available in the WordPress
 * block editor. Use the 'wack_text_format_enabled_types' filter to specify which
 * formats to enable.
 *
 * The actual disabling is performed by a JavaScript file that will be enqueued
 * in the block editor. The JavaScript uses wp.richText.unregisterFormatType()
 * to remove the specified formats.
 *
 * To find the list of all available format types, see:
 * https://github.com/WordPress/gutenberg/tree/trunk/packages/format-library
 *
 * You can also get the list of registered formats by running the following
 * in the browser console on the block editor page:
 * `wp.data.select('core/rich-text').getFormatTypes()`
 *
 * Example usage:
 * <code>
 * <?php
 * // Instantiate to enable the feature
 * new Format();
 *
 * // Use filter to specify enabled format types
 * add_filter('wack_text_format_enabled_types', fn() => [
 *     'core/bold',
 *     'core/italic',
 *     'core/link',
 *     'core/strikethrough',
 * ]);
 * ?>
 * </code>
 */
class Format
{
    use Trait\AssetUrlTrait;

    /**
     * Script handle and file name
     */
    private const string SCRIPT_HANDLE = 'format';
    private const string SCRIPT_FILE = 'format.js';

    /**
     * Get the list of enabled format types
     *
     * Applies the 'wack_text_format_enabled_types' filter to allow customization.
     *
     * @return string[] Array of format names (e.g., 'core/bold', 'core/italic')
     */
    protected function getEnabledFormats(): array
    {
        /**
         * Filter the text format types to enable
         *
         * @param string[] $formats Array of format type names. Default empty array.
         */
        return apply_filters('wack_text_format_enabled_types', []);
    }

    /**
     * Constructor
     *
     * Registers the script that disables the specified format types.
     */
    public function __construct()
    {
        add_action('enqueue_block_editor_assets', [$this, 'enqueueScript']);
    }

    /**
     * Enqueue the JavaScript file to disable format types
     *
     * Loads the script and passes the enabled formats as inline script data.
     * JavaScript will handle determining which formats to disable.
     *
     * @return void
     */
    public function enqueueScript(): void
    {
        if (!$this->enqueueAssetSafely(
            self::SCRIPT_HANDLE,
            self::SCRIPT_FILE,
            ['wp-rich-text', 'wp-dom-ready'],
            'script',
        )) {
            return;
        }

        // Pass the enabled formats to JavaScript.
        // JavaScript will get all formats and disable those not in this list.
        wp_localize_script(
            self::SCRIPT_HANDLE,
            'formatDisablerConfig',
            [
                'enabledFormats' => $this->getEnabledFormats(),
            ],
        );
    }
}
