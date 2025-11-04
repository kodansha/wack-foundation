<?php

namespace WackFoundation\Security;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * REST API access controller
 *
 * Controls access to WordPress REST API endpoints by namespace whitelist.
 * By default, all REST API access is disabled except for:
 * - Logged-in users with edit_posts capability (required for Gutenberg)
 * - Whitelisted namespaces configured via filter
 *
 * Key features:
 * - Namespace-based whitelist control
 * - Automatic access for dashboard users (Gutenberg support)
 * - Optional route blacklist for security
 * - Flexible configuration via WordPress filters
 * - Default blocking of /wp/v2/users endpoint to prevent user enumeration
 *
 * Example usage:
 * <code>
 * <?php
 * // Enable REST API controller
 * new RestApiController();
 *
 * // Whitelist custom namespace
 * add_filter('wack_rest_api_namespace_whitelist', function($namespaces) {
 *     $namespaces[] = 'my-plugin/v1';
 *     return $namespaces;
 * });
 *
 * // Blacklist additional routes
 * add_filter('wack_rest_api_forbidden_routes', function($routes) {
 *     $routes[] = '/wp/v2/settings';
 *     return $routes;
 * });
 * ?>
 * </code>
 *
 * Note: This is designed for headless WordPress where REST API access
 * should be strictly controlled.
 */
class RestApiController
{
    /**
     * Default namespace whitelist
     *
     * These namespaces are allowed by default. You can extend this list
     * using the 'wack_rest_api_namespace_whitelist' filter.
     *
     * @var array<string>
     */
    private const array DEFAULT_NAMESPACE_WHITELIST = [];

    /**
     * Default forbidden routes
     *
     * These routes are always blocked regardless of namespace whitelist.
     * You can extend this list using the 'wack_rest_api_forbidden_routes' filter.
     *
     * @var array<string>
     */
    private const array DEFAULT_FORBIDDEN_ROUTES = [
        '/wp/v2/users', // Prevent user enumeration
    ];

    /**
     * Constructor
     *
     * Registers the REST API access control filter.
     */
    public function __construct()
    {
        add_filter('rest_pre_dispatch', [$this, 'filterRestApiAccess'], 10, 3);
    }

    /**
     * Filter REST API access
     *
     * Controls access to REST API endpoints based on:
     * 1. User capabilities (edit_posts allows all access)
     * 2. Route blacklist (forbidden routes)
     * 3. Namespace whitelist (allowed namespaces)
     *
     * @param mixed $result Response to replace the requested version with
     * @param WP_REST_Server $server Server instance
     * @param WP_REST_Request $request Request used to generate the response
     * @return mixed Original result or WP_Error if access denied
     */
    public function filterRestApiAccess(mixed $result, WP_REST_Server $server, WP_REST_Request $request): mixed
    {
        $route = $request->get_route();

        // Allow all routes for users with edit_posts capability
        // This is essential for Gutenberg and dashboard functionality
        if ($this->isAdminUser()) {
            return $result;
        }

        // Check forbidden routes first (highest priority)
        if ($this->isForbiddenRoute($route)) {
            return $this->createUnauthorizedError();
        }

        // Check namespace whitelist
        if ($this->isWhitelistedNamespace($route)) {
            return $result;
        }

        // Deny access by default
        return $this->createUnauthorizedError();
    }

    /**
     * Check if current user is an admin user
     *
     * Admin users (with edit_posts capability) have full REST API access
     * to support Gutenberg and other dashboard functionalities.
     *
     * @return bool True if user has edit_posts capability
     */
    private function isAdminUser(): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * Check if route is in forbidden list
     *
     * Forbidden routes are always blocked regardless of namespace whitelist.
     *
     * @param string $route Route path to check
     * @return bool True if route is forbidden
     */
    private function isForbiddenRoute(string $route): bool
    {
        $forbidden_routes = $this->getForbiddenRoutes();

        return array_any(
            $forbidden_routes,
            fn($forbidden_route) => str_starts_with($route, $forbidden_route),
        );

    }

    /**
     * Check if route namespace is whitelisted
     *
     * Routes are matched by namespace prefix. For example:
     * - Whitelist: ['my-plugin/v1']
     * - Matches: '/my-plugin/v1/posts', '/my-plugin/v1/settings', etc.
     *
     * @param string $route Route path to check
     * @return bool True if namespace is whitelisted
     */
    private function isWhitelistedNamespace(string $route): bool
    {
        $namespace_whitelist = $this->getNamespaceWhitelist();

        // Check if route starts with namespace (after leading slash)
        return array_any(
            $namespace_whitelist,
            fn($namespace) => str_starts_with($route, '/' . $namespace),
        );

    }

    /**
     * Get namespace whitelist
     *
     * Retrieves the list of allowed namespaces from default settings
     * and applies the 'wack_rest_api_namespace_whitelist' filter.
     *
     * @return array<string> List of allowed namespace prefixes
     */
    private function getNamespaceWhitelist(): array
    {
        /**
         * Filter the REST API namespace whitelist
         *
         * Add namespaces that should be accessible via REST API.
         *
         * Example usage:
         * <code>
         * <?php
         * add_filter('wack_rest_api_namespace_whitelist', function($namespaces) {
         *     $namespaces[] = 'my-plugin/v1';
         *     $namespaces[] = 'wp/v2'; // WordPress core API
         *     return $namespaces;
         * });
         * ?>
         * </code>
         *
         * @param array<string> $namespaces List of namespace prefixes
         */
        return apply_filters(
            'wack_rest_api_namespace_whitelist',
            self::DEFAULT_NAMESPACE_WHITELIST,
        );
    }

    /**
     * Get forbidden routes
     *
     * Retrieves the list of forbidden routes from default settings
     * and applies the 'wack_rest_api_forbidden_routes' filter.
     *
     * @return array<string> List of forbidden route paths
     */
    private function getForbiddenRoutes(): array
    {
        /**
         * Filter the REST API forbidden routes
         *
         * Add routes that should be completely blocked even if their
         * namespace is whitelisted. By default, '/wp/v2/users' is blocked
         * to prevent user enumeration.
         *
         * Example usage:
         * <code>
         * <?php
         * add_filter('wack_rest_api_forbidden_routes', function($routes) {
         *     $routes[] = '/wp/v2/settings'; // Block settings access
         *     return $routes;
         * });
         * ?>
         * </code>
         *
         * @param array<string> $routes List of route paths to block
         */
        return apply_filters(
            'wack_rest_api_forbidden_routes',
            self::DEFAULT_FORBIDDEN_ROUTES,
        );
    }

    /**
     * Create unauthorized error response
     *
     * @return WP_Error Error object with 401/403 status code
     */
    private function createUnauthorizedError(): WP_Error
    {
        return new WP_Error(
            'rest_unauthorized',
            'REST API access is restricted.',
            ['status' => rest_authorization_required_code()],
        );
    }
}
