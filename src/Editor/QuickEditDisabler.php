<?php

namespace WackFoundation\Editor;

use WP_Post;

/**
 * Quick Edit disabler
 *
 * Disables the "Quick Edit" functionality in WordPress admin post lists.
 * By default, Quick Edit is disabled for all post types, but can be
 * selectively enabled via a whitelist filter.
 *
 * Quick Edit allows inline editing of post title, slug, date, categories,
 * tags, and other basic fields without opening the full editor. Disabling
 * it can help maintain data consistency in headless WordPress setups.
 *
 * Key features:
 * - Disabled by default for all post types
 * - Whitelist-based selective enabling
 * - Supports posts, pages, and custom post types
 * - Configurable via WordPress filter
 *
 * Example usage:
 * <code>
 * <?php
 * // Disable Quick Edit for all post types (default behavior)
 * new QuickEditDisabler();
 *
 * // Enable Quick Edit only for specific post types
 * add_filter('wack_quick_edit_enabled_post_types', function($post_types) {
 *     $post_types[] = 'post';  // Enable for posts
 *     $post_types[] = 'page';  // Enable for pages
 *     return $post_types;
 * });
 * ?>
 * </code>
 */
class QuickEditDisabler
{
    /**
     * Default enabled post types
     *
     * By default, Quick Edit is disabled for all post types (empty array).
     * Use the filter to enable for specific post types.
     *
     * @var array<string>
     */
    private const array DEFAULT_ENABLED_POST_TYPES = [];

    /**
     * Constructor
     *
     * Registers hooks to disable Quick Edit functionality.
     */
    public function __construct()
    {
        add_filter('post_row_actions', [$this, 'removeQuickEdit'], 10, 2);
        add_filter('page_row_actions', [$this, 'removeQuickEdit'], 10, 2);
    }

    /**
     * Remove Quick Edit action from row actions
     *
     * Removes the "Quick Edit" link from post/page list rows
     * unless the post type is whitelisted.
     *
     * @param array<string, string> $actions Row actions
     * @param WP_Post $post Post object
     * @return array<string, string> Modified actions
     */
    public function removeQuickEdit(array $actions, WP_Post $post): array
    {
        // Check if Quick Edit should be enabled for this post type
        if ($this->isQuickEditEnabled($post->post_type)) {
            return $actions;
        }

        // Remove Quick Edit action
        unset($actions['inline hide-if-no-js']);

        return $actions;
    }

    /**
     * Check if Quick Edit is enabled for a post type
     *
     * @param string $post_type Post type slug
     * @return bool True if Quick Edit is enabled for this post type
     */
    private function isQuickEditEnabled(string $post_type): bool
    {
        $enabled_post_types = $this->getEnabledPostTypes();

        return in_array($post_type, $enabled_post_types, true);
    }

    /**
     * Get post types where Quick Edit is enabled
     *
     * Retrieves the whitelist of post types that should have
     * Quick Edit functionality enabled.
     *
     * @return array<string> List of post type slugs
     */
    private function getEnabledPostTypes(): array
    {
        /**
         * Filter the post types where Quick Edit is enabled
         *
         * Add post types to the whitelist to enable Quick Edit functionality.
         * By default, Quick Edit is disabled for all post types.
         *
         * Example usage:
         * <code>
         * <?php
         * // Enable Quick Edit for posts and pages
         * add_filter('wack_quick_edit_enabled_post_types', function($post_types) {
         *     $post_types[] = 'post';
         *     $post_types[] = 'page';
         *     return $post_types;
         * });
         *
         * // Enable for custom post type
         * add_filter('wack_quick_edit_enabled_post_types', function($post_types) {
         *     $post_types[] = 'product';
         *     return $post_types;
         * });
         * ?>
         * </code>
         *
         * @param array<string> $post_types List of post type slugs
         */
        return apply_filters(
            'wack_quick_edit_enabled_post_types',
            self::DEFAULT_ENABLED_POST_TYPES,
        );
    }
}
