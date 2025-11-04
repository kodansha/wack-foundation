<?php

namespace WackFoundation\Editor;

/**
 * Content editor disabler for specific post types
 *
 * Applies a stylesheet to hide the content editor for specific post types
 * where the content editor is not needed in the Gutenberg editor.
 *
 * By default, no post types have their content editor disabled. Use the
 * 'wack_content_editor_disabled_post_types' filter to specify target post types.
 *
 * Example usage:
 * <code>
 * <?php
 * // Instantiate to enable the feature
 * new ContentEditorDisabler();
 *
 * // Use filter to specify post types
 * add_filter('wack_content_editor_disabled_post_types', fn() => ['author', 'product']);
 * ?>
 * </code>
 */
class ContentEditorDisabler
{
    use Trait\AssetUrlTrait;

    /**
     * CSS handle and file name
     */
    private const string STYLE_HANDLE = 'content-editor-disabler';
    private const string STYLE_FILE = 'content-editor-disabler.css';

    /**
     * Constructor
     *
     * Registers the hook to conditionally load the stylesheet.
     */
    public function __construct()
    {
        add_action('enqueue_block_assets', [$this, 'enqueueStyles']);
    }

    /**
     * Get the list of post types for which the content editor should be disabled
     *
    * Applies the 'wack_content_editor_disabled_post_types' filter to allow customization.
     *
     * @return string[] Array of post type slugs (e.g., 'author', 'product')
     */
    protected function getTargetPostTypes(): array
    {
        /**
         * Filter the post types for which the content editor should be disabled
         *
         * @param string[] $post_types Array of post type slugs. Default empty array.
         */
        return apply_filters('wack_content_editor_disabled_post_types', []);
    }

    /**
     * Enqueue the CSS file to disable the content editor
     *
     * Loads the stylesheet only for the specified post types.
     *
     * @return void
     */
    public function enqueueStyles(): void
    {
        if (!$this->shouldDisableEditor()) {
            return;
        }

        $this->enqueueAssetSafely(
            self::STYLE_HANDLE,
            self::STYLE_FILE,
            ['wp-edit-blocks'],
        );
    }

    /**
     * Check if the content editor should be disabled for the current post
     *
     * @return bool True if the editor should be disabled, false otherwise
     */
    private function shouldDisableEditor(): bool
    {
        global $post;

        if (!isset($post) || !isset($post->post_type)) {
            return false;
        }

        return in_array($post->post_type, $this->getTargetPostTypes(), true);
    }
}
