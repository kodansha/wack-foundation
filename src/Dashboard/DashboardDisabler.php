<?php

namespace WackFoundation\Dashboard;

/**
 * Dashboard disabler
 *
 * Disables the WordPress admin dashboard (index.php) and redirects users
 * to a different admin page. This is useful for headless WordPress setups
 * where the dashboard serves no purpose.
 *
 * Key features:
 * - Redirects dashboard access to a configurable admin page
 * - Removes dashboard menu item from admin sidebar
 * - Allows specific user capabilities to access the dashboard
 * - Fully configurable via WordPress filters
 *
 * Example usage:
 * <code>
 * <?php
 * // Disable dashboard for all users (redirect to posts list)
 * new DashboardDisabler();
 *
 * // Customize redirect URL
 * add_filter('wack_dashboard_redirect_url', fn() =>
 *     admin_url('edit.php?post_type=page'));
 *
 * // Allow administrators to access dashboard
 * add_filter('wack_dashboard_allowed_capabilities', function($capabilities) {
 *     $capabilities[] = 'manage_options';
 *     return $capabilities;
 * });
 * ?>
 * </code>
 */
class DashboardDisabler
{
    /**
     * Default redirect URL
     *
     * Users will be redirected to this URL when accessing the dashboard.
     * By default, redirects to the posts list page.
     *
     * @var string
     */
    private const string DEFAULT_REDIRECT_URL = 'edit.php';

    /**
     * Default allowed capabilities
     *
     * Users with these capabilities can access the dashboard.
     * By default, no capabilities are allowed (dashboard disabled for all).
     *
     * @var array<string>
     */
    private const array DEFAULT_ALLOWED_CAPABILITIES = [];

    /**
     * Constructor
     *
     * Registers the dashboard redirect and menu removal hooks.
     */
    public function __construct()
    {
        add_action('admin_init', [$this, 'redirectDashboard']);
        add_action('admin_menu', [$this, 'removeDashboardMenu'], 999);
    }

    /**
     * Redirect dashboard access to configured page
     *
     * Checks if the current admin page is the dashboard and redirects
     * users who don't have the required capabilities.
     *
     * @return void
     */
    public function redirectDashboard(): void
    {
        global $pagenow;

        // Only process dashboard page
        if ($pagenow !== 'index.php') {
            return;
        }

        // Allow users with specific capabilities
        if ($this->userHasAllowedCapability()) {
            return;
        }

        // Redirect to configured page
        $redirect_url = $this->getRedirectUrl();
        wp_safe_redirect(admin_url($redirect_url));
        exit;
    }

    /**
     * Remove dashboard menu item
     *
     * Removes the "Dashboard" menu item from the admin sidebar
     * for users who don't have the required capabilities.
     *
     * @return void
     */
    public function removeDashboardMenu(): void
    {
        // Allow users with specific capabilities to see the menu
        if ($this->userHasAllowedCapability()) {
            return;
        }

        // Remove dashboard menu
        remove_menu_page('index.php');
    }

    /**
     * Check if current user has allowed capability
     *
     * @return bool True if user has at least one allowed capability
     */
    private function userHasAllowedCapability(): bool
    {
        $allowed_capabilities = $this->getAllowedCapabilities();

        // If no capabilities are specified, deny access to all users
        if (empty($allowed_capabilities)) {
            return false;
        }

        // Check if user has any of the allowed capabilities
        return array_any(
            $allowed_capabilities,
            fn($capability) => current_user_can($capability),
        );

    }

    /**
     * Get redirect URL
     *
     * Retrieves the URL to redirect to from filter or default setting.
     *
     * @return string Relative admin URL (e.g., 'edit.php')
     */
    private function getRedirectUrl(): string
    {
        /**
         * Filter the dashboard redirect URL
         *
         * Specify which admin page users should be redirected to when
         * accessing the dashboard.
         *
         * Example usage:
         * <code>
         * <?php
         * add_filter('wack_dashboard_redirect_url', fn() =>
         *     'edit.php?post_type=page'); // Redirect to pages list
         * ?>
         * </code>
         *
         * @param string $url Relative admin URL (e.g., 'edit.php', 'edit.php?post_type=page')
         */
        return apply_filters(
            'wack_dashboard_redirect_url',
            self::DEFAULT_REDIRECT_URL,
        );
    }

    /**
     * Get allowed capabilities
     *
     * Retrieves the list of capabilities that grant dashboard access.
     *
     * @return array<string> List of capability names
     */
    private function getAllowedCapabilities(): array
    {
        /**
         * Filter the capabilities allowed to access dashboard
         *
         * Add capabilities that should be able to access the WordPress dashboard.
         * Users with any of these capabilities will not be redirected.
         *
         * Example usage:
         * <code>
         * <?php
         * // Allow administrators to access dashboard
         * add_filter('wack_dashboard_allowed_capabilities', function($capabilities) {
         *     $capabilities[] = 'manage_options';
         *     return $capabilities;
         * });
         *
         * // Allow editors and administrators
         * add_filter('wack_dashboard_allowed_capabilities', function($capabilities) {
         *     $capabilities[] = 'manage_options';  // Administrators
         *     $capabilities[] = 'edit_others_posts'; // Editors
         *     return $capabilities;
         * });
         * ?>
         * </code>
         *
         * @param array<string> $capabilities List of capability names
         */
        return apply_filters(
            'wack_dashboard_allowed_capabilities',
            self::DEFAULT_ALLOWED_CAPABILITIES,
        );
    }
}
