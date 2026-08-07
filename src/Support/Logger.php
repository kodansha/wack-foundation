<?php

namespace WackFoundation\Support;

/**
 * Logger facade
 *
 * Provides a single entry point for writing log messages from the theme.
 *
 * When the WACK Log plugin is active, messages are written through its global
 * `logger()` function so that they are emitted to stdout (optionally as JSON),
 * which is convenient for containerized environments. When the plugin is not
 * available, messages fall back to `error_log()` and therefore end up in the
 * regular WordPress debug log.
 *
 * The availability of `logger()` is checked at call time rather than at
 * registration time, because the WACK Log plugin defines the function while
 * plugins are being loaded, which happens before the theme is loaded.
 *
 * Note that `logger()` only accepts a message string and does not take a
 * context array. Callers that need structured output must encode it into the
 * message themselves.
 *
 * Example usage:
 * <code>
 * <?php
 * use WackFoundation\Support\Logger;
 *
 * Logger::info('Something worth knowing happened.');
 * Logger::warning('Something looks wrong.');
 * Logger::error('Something failed.');
 * ?>
 * </code>
 *
 * @see https://packagist.org/packages/kodansha/wack-log
 */
final class Logger
{
    /**
     * Log an informational message.
     *
     * @param string $message The message to log
     * @return void
     */
    public static function info(string $message): void
    {
        self::write('info', $message);
    }

    /**
     * Log a warning message.
     *
     * @param string $message The message to log
     * @return void
     */
    public static function warning(string $message): void
    {
        self::write('warning', $message);
    }

    /**
     * Log an error message.
     *
     * @param string $message The message to log
     * @return void
     */
    public static function error(string $message): void
    {
        self::write('error', $message);
    }

    /**
     * Write a message through WACK Log, or fall back to the debug log.
     *
     * @param string $level   One of 'info', 'warning' or 'error'
     * @param string $message The message to log
     * @return void
     */
    private static function write(string $level, string $message): void
    {
        if (!function_exists('logger')) {
            error_log($message);
            return;
        }

        match ($level) {
            'warning' => logger()->warning($message),
            'error' => logger()->error($message),
            default => logger()->info($message),
        };
    }
}
