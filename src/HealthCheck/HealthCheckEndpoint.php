<?php

namespace WackFoundation\HealthCheck;

/**
 * Health Check Endpoint
 *
 * Provides a health check endpoint at /healthcheck that verifies database
 * connectivity and returns an HTTP 200 response with "OK".
 * This is designed for use with load balancers and monitoring systems.
 */
class HealthCheckEndpoint
{
    /**
     * Constructor
     *
     * Registers the health check handler to WordPress hooks.
     */
    public function __construct()
    {
        add_action('parse_request', [$this, 'handleHealthCheck']);
    }

    /**
     * Health Check Handler
     *
     * Intercepts requests to /healthcheck, verifies database connectivity,
     * and returns an HTTP 200 response with "OK".
     */
    public function handleHealthCheck(): void
    {
        // Check if the request is for /healthcheck
        if ($_SERVER['REQUEST_URI'] === '/healthcheck') {
            // Verify database connectivity by executing a query
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
