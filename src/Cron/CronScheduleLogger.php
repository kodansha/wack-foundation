<?php

namespace WackFoundation\Cron;

use WackFoundation\Support\Logger;

/**
 * WP-Cron schedule logger
 *
 * Logs the state of the WP-Cron schedule immediately before and after a cron
 * run, so that intermittent "the cron job did not run as expected" problems can
 * be diagnosed after the fact.
 *
 * Disabled by default. Enable it from a child theme with:
 *
 * <code>
 * <?php
 * add_filter('wack_cron_log_enabled', '__return_true');
 * ?>
 * </code>
 *
 * A cron run that has nothing to do produces no output at all, so the number of
 * log lines is proportional to the amount of work actually performed rather
 * than to how often WP-Cron is polled. A run with work produces exactly two
 * lines, plus one line per rescheduling error:
 *
 * - `start` : environment, cron lock, registered schedules and every scheduled
 *             event (both overdue and future)
 * - `end`   : per job timings, every scheduled event after the run, and a diff
 *             against the `start` snapshot
 *
 * Both lines are single line JSON prefixed with `[wack-foundation][cron]` so
 * that they survive log aggregation. Event arguments are never written out;
 * only the first bytes of their md5 hash are included, which is enough to tell
 * events of the same hook apart. Lines that report an anomaly are logged at
 * warning level and carry a `warn` field, so monitoring can alert on warnings
 * alone.
 *
 * All work is wrapped in a guard: a failure inside the logger must never break
 * the cron run it is observing.
 */
class CronScheduleLogger
{
    /**
     * Prefix identifying this theme's cron log lines.
     */
    private const PREFIX = '[wack-foundation][cron]';

    /**
     * Soft limit for a single log line. Longer payloads are progressively
     * reduced rather than emitted in full.
     */
    private const MAX_BYTES = 32768;

    /**
     * Snapshot taken before any event of this run was processed.
     */
    private ?CronScheduleSnapshot $before = null;

    /**
     * Value of the cron lock when the run started.
     */
    private string $lockAtStart = '';

    /**
     * Identifier correlating the two lines of a single run.
     */
    private string $runId = '';

    /**
     * Unix timestamp with microseconds when the run started.
     */
    private float $startedAt = 0.0;

    /**
     * Whether the `start` line has already been written.
     */
    private bool $started = false;

    /**
     * Hooks this instance has wrapped for measurement, keyed by hook name.
     *
     * @var array<string, true>
     */
    private array $wrapped = [];

    /**
     * Measurements for each event that was executed during this run.
     *
     * @var array<int, array>
     */
    private array $jobs = [];

    /**
     * Indices into $jobs for the events currently being executed.
     *
     * A stack rather than a single value, because a cron callback may itself
     * fire another hook that is being measured.
     *
     * @var array<int, int>
     */
    private array $stack = [];

    /**
     * Constructor
     *
     * Registers the hooks required to observe a cron run. Whether logging is
     * actually enabled is decided later, when a cron run is detected, so that
     * the `wack_cron_log_enabled` filter can be registered from anywhere that
     * runs before `wp_loaded`.
     */
    public function __construct()
    {
        add_action('wp_loaded', [$this, 'onWpLoaded'], PHP_INT_MAX);
        add_action('shutdown', [$this, 'onShutdown'], PHP_INT_MAX);
        add_action('cron_reschedule_event_error', [$this, 'onRescheduleError'], 10, 2);
        add_action('cron_unschedule_event_error', [$this, 'onUnscheduleError'], 10, 2);
    }

    /**
     * Take the snapshot for the run that is about to start.
     *
     * In a `wp-cron.php` request this fires after `DOING_CRON` has been defined
     * but before WordPress reads the list of ready jobs, which makes it the
     * exact "before" point. Nothing is written when no event is due, because
     * WP-Cron exits immediately in that case and there is nothing to report.
     *
     * @return void
     */
    public function onWpLoaded(): void
    {
        if (!$this->isCronContext() || !$this->isEnabled()) {
            return;
        }

        $this->guard(function (): void {
            $this->startedAt = microtime(true);
            $this->lockAtStart = $this->lock();
            $this->runId = $this->resolveRunId();
            $this->before = CronScheduleSnapshot::capture(fn(string $hook): bool => $this->hasCallback($hook));

            if ($this->before->due === 0) {
                // Nothing to do: stay completely silent.
                $this->before = null;
                return;
            }

            $this->wrapDueHooks($this->before->dueHooks);

            // Write the start line as early as possible so that the schedule is
            // on record even if the process is killed mid run and `shutdown`
            // never fires. Under WP-CLI, DOING_CRON is not defined yet at this
            // point, so the line is deferred until a job actually runs.
            if (wp_doing_cron()) {
                $this->emitStart();
            }
        });
    }

    /**
     * Record the start of an individual cron event.
     *
     * @param mixed ...$args Arguments the event was scheduled with
     * @return void
     */
    public function onJobStart(mixed ...$args): void
    {
        $this->guard(function () use ($args): void {
            if ($this->before === null || !wp_doing_cron()) {
                // The hook was fired outside of a cron run; not our business.
                return;
            }

            if (!$this->started) {
                $this->emitStart();
            }

            $this->stack[] = count($this->jobs);
            $this->jobs[] = [
                'hook' => (string) current_filter(),
                'args' => $this->hashArgs($args),
                'ms' => 0.0,
                'done' => false,
                '_startedAt' => microtime(true),
            ];
        });
    }

    /**
     * Record the completion of an individual cron event.
     *
     * @param mixed ...$args Arguments the event was scheduled with
     * @return void
     */
    public function onJobEnd(mixed ...$args): void
    {
        $this->guard(function (): void {
            $index = array_pop($this->stack);

            if ($index === null || !isset($this->jobs[$index])) {
                return;
            }

            $this->jobs[$index]['ms'] = round((microtime(true) - $this->jobs[$index]['_startedAt']) * 1000, 1);
            $this->jobs[$index]['done'] = true;
        });
    }

    /**
     * Take the closing snapshot and write the `end` line.
     *
     * This also runs when WP-Cron bails out early because another process stole
     * the lock, and when a cron callback triggers a fatal error, because
     * WordPress registers the `shutdown` action as a PHP shutdown function.
     *
     * @return void
     */
    public function onShutdown(): void
    {
        if (!$this->started || $this->before === null) {
            return;
        }

        $this->guard(function (): void {
            $after = CronScheduleSnapshot::capture(fn(string $hook): bool => $this->hasCallback($hook));
            $diff = CronScheduleSnapshot::diff($this->before, $after);
            $lock = $this->lock();

            $warn = array_merge(
                $this->prefixAll('orphan:', $after->orphanedHooks()),
                $this->prefixAll('unknown_schedule:', $after->unknownSchedules()),
                $this->prefixAll('incomplete:', $this->incompleteHooks()),
                $this->prefixAll('not_run:', $this->hooksOf($diff['still_due'])),
                $lock === $this->lockAtStart ? [] : ['lock_stolen'],
            );

            $this->emit([
                'event' => 'end',
                'run' => $this->runId,
                'time' => $this->isoTime($after->time),
                'elapsed_ms' => round((microtime(true) - $this->startedAt) * 1000, 1),
                'jobs' => $this->renderJobs(),
                'events' => $after->events,
                'diff' => $diff,
                'lock' => $lock,
                'counts' => $this->counts($after),
            ], $warn);
        });
    }

    /**
     * Log a rescheduling failure reported by WP-Cron.
     *
     * @param mixed  $result The WP_Error describing the failure
     * @param string $hook   The hook that could not be rescheduled
     * @return void
     */
    public function onRescheduleError(mixed $result, string $hook = ''): void
    {
        $this->logEventError('reschedule', $result, $hook);
    }

    /**
     * Log an unscheduling failure reported by WP-Cron.
     *
     * @param mixed  $result The WP_Error describing the failure
     * @param string $hook   The hook that could not be unscheduled
     * @return void
     */
    public function onUnscheduleError(mixed $result, string $hook = ''): void
    {
        $this->logEventError('unschedule', $result, $hook);
    }

    /**
     * Write the `start` line.
     *
     * @return void
     */
    private function emitStart(): void
    {
        if ($this->started || $this->before === null) {
            return;
        }

        $this->started = true;

        $warn = array_merge(
            $this->prefixAll('orphan:', $this->before->orphanedHooks()),
            $this->prefixAll('unknown_schedule:', $this->before->unknownSchedules()),
        );

        $this->emit([
            'event' => 'start',
            'run' => $this->runId,
            'time' => $this->isoTime($this->before->time),
            'env' => $this->environment(),
            'lock' => $this->lockAtStart,
            'schedules' => CronScheduleSnapshot::scheduleIntervals(),
            'events' => $this->before->events,
            'counts' => $this->counts($this->before),
        ], $warn);
    }

    /**
     * Encode and write a single log line.
     *
     * Lines carrying at least one warning label are logged at warning level so
     * that monitoring can alert on them without parsing the payload.
     *
     * @param array              $payload The structured payload
     * @param array<int, string> $warn    Warning labels detected for this line
     * @return void
     */
    private function emit(array $payload, array $warn): void
    {
        if ($warn !== []) {
            $payload['warn'] = array_slice(array_values(array_unique($warn)), 0, CronScheduleSnapshot::MAX_EVENTS);
        }

        $message = self::PREFIX . ' ' . $this->encode($payload);

        if ($warn !== []) {
            Logger::warning($message);
        } else {
            Logger::info($message);
        }
    }

    /**
     * Encode a payload as single line JSON, reducing it if it grows too large.
     *
     * @param array $payload The structured payload
     * @return string
     */
    private function encode(array $payload): string
    {
        $json = wp_json_encode($payload);

        if (is_string($json) && strlen($json) <= self::MAX_BYTES) {
            return $json;
        }

        // First reduction: drop the event details, keep the counts.
        if (isset($payload['events'])) {
            $payload['events'] = ['omitted' => count($payload['events'])];
        }
        $json = wp_json_encode($payload);

        if (is_string($json) && strlen($json) <= self::MAX_BYTES) {
            return $json;
        }

        // Second reduction: drop the diff details as well.
        if (isset($payload['diff'])) {
            $payload['diff'] = ['omitted' => true];
        }
        $json = wp_json_encode($payload);

        return is_string($json) ? $json : '{"event":"encode_failed"}';
    }

    /**
     * Register measurement wrappers around the hooks that are about to run.
     *
     * The wrappers bracket every other callback on the hook. A callback
     * registered while the hook is already running would still execute after
     * the closing wrapper, but cron callbacks do not normally do this.
     *
     * @param array<int, string> $hooks Hook names that are due
     * @return void
     */
    private function wrapDueHooks(array $hooks): void
    {
        foreach ($hooks as $hook) {
            if (isset($this->wrapped[$hook])) {
                continue;
            }

            $this->wrapped[$hook] = true;
            add_action($hook, [$this, 'onJobStart'], PHP_INT_MIN, 10);
            add_action($hook, [$this, 'onJobEnd'], PHP_INT_MAX, 10);
        }
    }

    /**
     * Determine whether a hook has a callback other than this logger's wrappers.
     *
     * A due event whose hook has no callback is an orphan: WP-Cron keeps firing
     * and rescheduling it while nothing happens.
     *
     * @param string $hook The hook name
     * @return bool
     */
    private function hasCallback(string $hook): bool
    {
        global $wp_filter;

        if (!isset($wp_filter[$hook]) || !isset($wp_filter[$hook]->callbacks)) {
            return false;
        }

        $count = 0;
        foreach ($wp_filter[$hook]->callbacks as $callbacks) {
            $count += is_array($callbacks) ? count($callbacks) : 0;
        }

        // Discount the two wrappers registered by wrapDueHooks().
        if (isset($this->wrapped[$hook])) {
            $count -= 2;
        }

        return $count > 0;
    }

    /**
     * Render the collected job measurements for output.
     *
     * @return array<int, array>
     */
    private function renderJobs(): array
    {
        $jobs = [];

        foreach (array_slice($this->jobs, 0, CronScheduleSnapshot::MAX_EVENTS) as $job) {
            unset($job['_startedAt']);
            $jobs[] = $job;
        }

        return $jobs;
    }

    /**
     * Hook names of events whose execution never completed.
     *
     * @return array<int, string>
     */
    private function incompleteHooks(): array
    {
        $hooks = [];

        foreach ($this->jobs as $job) {
            if (!$job['done']) {
                $hooks[$job['hook']] = true;
            }
        }

        return array_keys($hooks);
    }

    /**
     * Extract distinct hook names from a list of "hook|args" diff keys.
     *
     * @param array<int, string> $keys Diff keys
     * @return array<int, string>
     */
    private function hooksOf(array $keys): array
    {
        $hooks = [];

        foreach ($keys as $key) {
            $key = (string) $key;
            // The args hash is appended last, so split on the final separator
            // to stay correct for hook names that contain one themselves.
            $position = strrpos($key, '|');
            $hooks[$position === false ? $key : substr($key, 0, $position)] = true;
        }

        return array_keys($hooks);
    }

    /**
     * Prefix every entry of a list.
     *
     * @param string             $prefix The prefix to apply
     * @param array<int, string> $values The values to prefix
     * @return array<int, string>
     */
    private function prefixAll(string $prefix, array $values): array
    {
        return array_map(fn(string $value): string => $prefix . $value, $values);
    }

    /**
     * Event counts for a snapshot.
     *
     * @param CronScheduleSnapshot $snapshot The snapshot to summarize
     * @return array{total: int, due: int, truncated: int}
     */
    private function counts(CronScheduleSnapshot $snapshot): array
    {
        return [
            'total' => $snapshot->total,
            'due' => $snapshot->due,
            'truncated' => $snapshot->truncated,
        ];
    }

    /**
     * Cron related environment settings.
     *
     * @return array<string, mixed>
     */
    private function environment(): array
    {
        return [
            'disable_wp_cron' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
            'alternate_wp_cron' => defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON,
            'lock_timeout' => defined('WP_CRON_LOCK_TIMEOUT') ? (int) WP_CRON_LOCK_TIMEOUT : null,
            'cli' => defined('WP_CLI') && WP_CLI,
            'tz' => wp_timezone_string(),
        ];
    }

    /**
     * Write a log line for a scheduling error reported by WP-Cron.
     *
     * @param string $operation Either 'reschedule' or 'unschedule'
     * @param mixed  $result    The WP_Error describing the failure
     * @param string $hook      The hook the operation failed for
     * @return void
     */
    private function logEventError(string $operation, mixed $result, string $hook): void
    {
        if ($this->before === null) {
            return;
        }

        $this->guard(function () use ($operation, $result, $hook): void {
            $payload = [
                'event' => $operation . '_error',
                'run' => $this->runId,
                'time' => $this->isoTime(microtime(true)),
                'hook' => $hook,
                'code' => is_wp_error($result) ? (string) $result->get_error_code() : '',
                'message' => is_wp_error($result) ? (string) $result->get_error_message() : '',
            ];

            Logger::error(self::PREFIX . ' ' . $this->encode($payload));
        });
    }

    /**
     * Identifier correlating all log lines of a single cron run.
     *
     * The cron lock is the natural identifier for a run. Under WP-CLI no lock is
     * taken, so a generated identifier is used instead.
     *
     * @return string
     */
    private function resolveRunId(): string
    {
        if (!empty($_GET['doing_wp_cron'])) {
            return sanitize_text_field(wp_unslash($_GET['doing_wp_cron']));
        }

        $lock = $this->lock();

        return $lock !== '' ? $lock : uniqid('run-', true);
    }

    /**
     * Current value of the cron lock.
     *
     * @return string
     */
    private function lock(): string
    {
        $lock = get_transient('doing_cron');

        return is_scalar($lock) ? (string) $lock : '';
    }

    /**
     * Short hash identifying a set of event arguments.
     *
     * Matches the key WordPress uses in the cron array. Argument values are
     * never included in the log output.
     *
     * @param array $args The event arguments
     * @return string
     */
    private function hashArgs(array $args): string
    {
        return substr(md5(serialize(array_values($args))), 0, 8);
    }

    /**
     * Format a timestamp as an ISO 8601 string in UTC.
     *
     * @param float $timestamp Unix timestamp with microseconds
     * @return string
     */
    private function isoTime(float $timestamp): string
    {
        return gmdate('Y-m-d\TH:i:s\Z', (int) $timestamp);
    }

    /**
     * Whether this request may be processing cron events.
     *
     * `wp-cron.php` defines DOING_CRON before WordPress loads, whereas the
     * WP-CLI cron command only defines it once the command runs, which is after
     * `wp_loaded`. Both are covered here; whether anything is written is decided
     * later by checking `wp_doing_cron()` at the moment an event actually runs.
     *
     * @return bool
     */
    private function isCronContext(): bool
    {
        return wp_doing_cron() || (defined('WP_CLI') && WP_CLI);
    }

    /**
     * Whether cron schedule logging is enabled.
     *
     * @return bool
     */
    private function isEnabled(): bool
    {
        /**
         * Filters whether WP-Cron schedule logging is enabled.
         *
         * Logging is off by default because it is a diagnostic aid rather than
         * something to leave running permanently.
         *
         * @param bool $enabled Whether to log the cron schedule. Default false.
         */
        return (bool) apply_filters('wack_cron_log_enabled', false);
    }

    /**
     * Run a callback, swallowing any failure.
     *
     * Diagnostics must never take down the cron run they are observing.
     *
     * @param callable $callback The callback to run
     * @return void
     */
    private function guard(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            try {
                Logger::error(self::PREFIX . ' logging failed: ' . $e->getMessage());
            } catch (\Throwable) {
                // Nothing further can be done here.
            }
        }
    }
}
