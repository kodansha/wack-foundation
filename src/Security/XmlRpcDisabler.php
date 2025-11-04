<?php

namespace WackFoundation\Security;

/**
 * XML-RPC disabler
 *
 * Completely disables XML-RPC functionality in WordPress.
 * XML-RPC is a legacy API that poses security risks including:
 * - Brute force attacks via system.multicall
 * - DDoS attacks via pingback
 * - Unnecessary exposure of site functionality
 *
 * Key features:
 * - Disables XML-RPC API completely
 * - Blocks access to xmlrpc.php
 * - Removes X-Pingback header
 * - Disables pingback functionality
 *
 * Example usage:
 * <code>
 * <?php
 * // Simply instantiate to disable XML-RPC
 * new XmlRpcDisabler();
 * ?>
 * </code>
 *
 * Note: This is recommended for headless WordPress where XML-RPC
 * is not needed for any functionality.
 */
class XmlRpcDisabler
{
    /**
     * Constructor
     *
     * Registers all hooks and filters to disable XML-RPC functionality.
     */
    public function __construct()
    {
        // Disable XML-RPC completely
        add_filter('xmlrpc_enabled', '__return_false');

        // Block access to xmlrpc.php
        add_action('init', [$this, 'blockXmlRpcAccess'], 1);

        // Remove X-Pingback header
        add_filter('wp_headers', [$this, 'removeXPingbackHeader']);

        // Disable pingback functionality
        add_filter('xmlrpc_methods', [$this, 'removeXmlRpcMethods']);

        // Disable pingback flag
        add_filter('pre_option_default_pingback_flag', '__return_zero');
    }

    /**
     * Block direct access to xmlrpc.php
     *
     * Returns 403 Forbidden when accessing xmlrpc.php directly.
     *
     * @return void
     */
    public function blockXmlRpcAccess(): void
    {
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            wp_die(
                esc_html__('XML-RPC services are disabled on this site.', 'wack-foundation'),
                esc_html__('Forbidden', 'wack-foundation'),
                ['response' => 403],
            );
        }
    }

    /**
     * Remove X-Pingback HTTP header
     *
     * The X-Pingback header advertises the XML-RPC endpoint URL.
     * Removing it helps prevent automated attacks.
     *
     * @param array<string, string> $headers HTTP headers
     * @return array<string, string> Modified headers
     */
    public function removeXPingbackHeader(array $headers): array
    {
        unset($headers['X-Pingback']);
        return $headers;
    }

    /**
     * Remove all XML-RPC methods
     *
     * Removes all XML-RPC methods as an additional security measure.
     * This prevents any XML-RPC functionality even if the service
     * is somehow accessed.
     *
     * @param array<string, callable> $methods XML-RPC methods
     * @return array<string, callable> Empty array
     */
    public function removeXmlRpcMethods(array $methods): array
    {
        return [];
    }
}
