<?php

namespace WackFoundation\HealthCheck;

/**
 * Provides a health check endpoint for load balancers and monitoring systems.
 *
 * Requests to `/healthcheck` are handled on the WordPress `parse_request` hook,
 * verifying that PHP and the normal WordPress bootstrap have completed.
 *
 * On multisite, WordPress identifies the current site from the request Host header
 * before loading the theme. ALB health checks set the Host header to the target's
 * private IP address, which does not match a registered domain, so WordPress redirects
 * the request to the network URL with a 302 response. The request does not reach
 * `parse_request` in this case, and this endpoint cannot return a 200 response.
 * A mu-plugin cannot avoid this behavior because mu-plugins are also loaded after
 * WordPress has identified the current site.
 *
 * For a host-independent health check on multisite, define the `SUNRISE` constant and
 * handle the request in `WP_CONTENT_DIR/sunrise.php`. WordPress loads `sunrise.php`
 * after initializing `$wpdb` but before identifying the current site from the Host
 * header. The drop-in can call `$wpdb->check_connection(false)` and return 200 when
 * the connection succeeds or 503 when it fails.
 *
 * Treating 302 as healthy in the ALB configuration does not guarantee that this
 * handler ran and should not be used as a permanent solution.
 *
 * This endpoint is disabled on multisite, i.e. when `is_multisite()` returns true
 * (`MULTISITE` is true, or `SUNRISE` is defined while `MULTISITE` is not defined).
 * Handle `/healthcheck` in `sunrise.php` instead.
 *
 * `get_option()` may return a value from the object cache, so it does not always verify
 * the current database connection. Use `$wpdb->check_connection(false)` when a live
 * database connectivity check is required.
 */
class HealthCheckEndpoint
{
    /**
     * Constructor
     *
     * Registers the health check handler to WordPress hooks.
     * Does nothing on multisite, where `/healthcheck` should be handled in `sunrise.php`.
     */
    public function __construct()
    {
        if (is_multisite()) {
            return;
        }

        add_action('parse_request', [$this, 'handleHealthCheck']);
    }

    /**
     * Health Check Handler
     *
     * Intercepts requests to /healthcheck, performs a lightweight option read,
     * and returns an HTTP 200 response with "OK".
     */
    public function handleHealthCheck(): void
    {
        // Check if the request is for /healthcheck
        if ($_SERVER['REQUEST_URI'] === '/healthcheck') {
            // Read an option (may be served from the object cache)
            get_option('blogname');

            // Set response headers (HTTP 200 by default)
            header('Content-Type: text/plain; charset=utf-8');

            // Output OK message
            echo "OK\n";

            // Terminate WordPress processing
            exit;
        }
    }
}
