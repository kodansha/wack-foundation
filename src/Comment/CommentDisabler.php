<?php

namespace WackFoundation\Comment;

use WP_Admin_Bar;

/**
 * Comment & trackback disabler for headless WordPress setups.
 *
 * Purpose:
 * - Remove comment and trackback support from all public post types
 * - Hide all comment‑related admin UI (menus, dashboard modules, profile shortcuts)
 * - Block REST API comment endpoints (/wp/v2/comments)
 * - Block XML‑RPC comment methods (wp.newComment, wp.editComment, etc.)
 * - Remove comment related widgets and admin bar items
 *
 * Out of scope:
 * - Frontend template overrides
 * - Feed suppression
 * - Persistent settings / database storage
 * - XML-RPC service itself (use XmlRpcDisabler for complete XML-RPC blocking)
 * - X-Pingback header removal (handled by XmlRpcDisabler)
 *
 * Implementation notes:
 * - Activates entirely via hooks; instantiation is enough.
 * - High hook priorities are used (PHP_INT_MAX - 1) to win over late additions.
 * - XML‑RPC comment methods are removed to prevent comment submission via XML-RPC.
 * - For complete XML-RPC blocking including pingbacks, combine with XmlRpcDisabler.
 *
 * Example usage:
 * <code>
 * <?php
 * // Disable comments only (blocks REST API & XML-RPC comment operations)
 * new CommentDisabler();
 *
 * // Recommended: Combine with XmlRpcDisabler for complete protection
 * new CommentDisabler();
 * new XmlRpcDisabler();
 * ?>
 * </code>
 */
class CommentDisabler
{
    /**
     * Constructor
     *
     * Registers all hooks and filters to disable comment functionality.
     */
    public function __construct()
    {
        // Hooks that need to run early
        add_action('widgets_init', [$this, 'disableCommentsWidget']);

        // Admin bar filtering (since WP 3.6)
        add_action('admin_init', [$this, 'removeAdminBarComments']);

        // Disable REST API endpoints (use very high priority to win late additions)
        add_filter('rest_endpoints', [$this, 'disableRestApiComments'], PHP_INT_MAX - 1);

        // Disable XML-RPC comment methods (comprehensive removal of comment operations)
        add_filter('xmlrpc_methods', [$this, 'disableXmlrpcComments'], PHP_INT_MAX - 1);

        // Hooks to run on wp_loaded
        add_action('wp_loaded', [$this, 'initFilters']);
    }

    /**
     * Initialize filters on wp_loaded
     *
     * @return void
     */
    public function initFilters(): void
    {
        // Remove comment support from all post types
        $this->removeCommentSupport();

        // Comment-related filters
        add_filter('comments_array', '__return_empty_array', 20);
        add_filter('comments_open', '__return_false', 20);
        add_filter('pings_open', '__return_false', 20);
        add_filter('get_comments_number', '__return_zero', 20);

        // Admin-only filters
        if (is_admin()) {
            add_action('admin_menu', [$this, 'removeAdminMenuItems'], PHP_INT_MAX - 1);
            add_action('admin_print_styles-index.php', [$this, 'hideCommentsInDashboard']);
            add_action('admin_print_styles-profile.php', [$this, 'hideCommentsInDashboard']);
            add_action('wp_dashboard_setup', [$this, 'removeDashboardWidget']);
        }
    }

    /**
     * Remove comment and trackback support from all post types
     *
     * @return void
     */
    private function removeCommentSupport(): void
    {
        $post_types = get_post_types(['public' => true]);

        foreach ($post_types as $post_type) {
            if (post_type_supports($post_type, 'comments')) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }

    /**
     * Disable recent comments widget
     *
     * @return void
     */
    public function disableCommentsWidget(): void
    {
        unregister_widget('WP_Widget_Recent_Comments');

        // Filter out style action added by widget constructor
        add_filter('show_recent_comments_widget_style', '__return_false');
    }

    /**
     * Remove comment links from admin bar
     *
     * @return void
     */
    public function removeAdminBarComments(): void
    {
        if (!is_admin_bar_showing()) {
            return;
        }

        // Remove comments links from admin bar
        remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);

        // Additional removal for multisite
        if (is_multisite()) {
            add_action('admin_bar_menu', [$this, 'removeNetworkCommentLinks'], 500);
        }
    }

    /**
     * Remove comment links from admin bar in multisite
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar instance
     * @return void
     */
    public function removeNetworkCommentLinks(WP_Admin_Bar $wp_admin_bar): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        // Remove only current blog's comment link
        $wp_admin_bar->remove_menu('blog-' . get_current_blog_id() . '-c');
    }

    /**
     * Disable REST API comment endpoints
     *
     * @param array<string, mixed> $endpoints REST API endpoints
     * @return array<string, mixed> Modified endpoints
     */
    public function disableRestApiComments(array $endpoints): array
    {
        // Remove comment-related endpoints
        unset(
            $endpoints['comments'],
            $endpoints['/wp/v2/comments'],
            $endpoints['/wp/v2/comments/(?P<id>[\d]+)'],
        );

        return $endpoints;
    }

    /**
     * Disable XML-RPC comment methods
     *
     * @param array<string, callable> $methods XML-RPC methods
     * @return array<string, callable> Modified methods
     */
    public function disableXmlrpcComments(array $methods): array
    {
        // Remove all comment-related XML-RPC methods
        unset(
            $methods['wp.newComment'],
            $methods['wp.editComment'],
            $methods['wp.deleteComment'],
            $methods['wp.getComment'],
            $methods['wp.getComments'],
        );

        return $methods;
    }

    /**
     * Remove comment-related admin menu items
     *
     * @return void
     */
    public function removeAdminMenuItems(): void
    {
        global $pagenow;

        // Block access to comment management pages
        if (in_array($pagenow, ['comment.php', 'edit-comments.php', 'options-discussion.php'], true)) {
            wp_die(
                esc_html__('Comments are closed.', 'wack-foundation'),
                '',
                ['response' => 403],
            );
        }

        // Remove from menu
        remove_menu_page('edit-comments.php');
        remove_submenu_page('options-general.php', 'options-discussion.php');
    }

    /**
     * Hide comment elements in dashboard with CSS
     *
     * @return void
     */
    public function hideCommentsInDashboard(): void
    {
        echo '<style>
          #dashboard_right_now .comment-count,
          #dashboard_right_now .comment-mod-count,
          #latest-comments,
          #welcome-panel .welcome-comments,
          .user-comment-shortcuts-wrap {
            display: none !important;
          }
        </style>';
    }

    /**
     * Remove recent comments dashboard widget
     *
     * @return void
     */
    public function removeDashboardWidget(): void
    {
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    }
}
