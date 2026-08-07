<?php

namespace WackFoundation\Cron;

/**
 * Snapshot of the WP-Cron schedule at a single point in time
 *
 * This class is a plain, side effect free representation of the WordPress cron
 * array. It only reads state; it never writes to the database and never
 * registers hooks. All of the presentation concerns (log formatting, log
 * levels) are handled by {@see CronScheduleLogger}.
 *
 * Two snapshots taken before and after a cron run can be compared with
 * {@see CronScheduleSnapshot::diff()} to determine which events were executed,
 * which were rescheduled, and which were left behind.
 */
final class CronScheduleSnapshot
{
    /**
     * Maximum number of events included in the rendered event list.
     *
     * Events are sorted by their scheduled time in ascending order, so due
     * events (whose timestamps are in the past) always come first and are
     * never dropped in favour of far-future events.
     */
    public const MAX_EVENTS = 50;

    /**
     * @param float                $time      Unix timestamp with microseconds when this snapshot was taken
     * @param array<int, array>    $events    Rendered event rows, possibly truncated to MAX_EVENTS
     * @param array<string, array> $index     Full event index keyed by "hook|args", used for diffing
     * @param array<int, string>   $dueHooks  Distinct hook names that are due, based on the full event set
     * @param int                  $total     Total number of scheduled events
     * @param int                  $due       Number of events whose scheduled time has passed
     * @param int                  $truncated Number of events omitted from $events
     */
    private function __construct(
        public readonly float $time,
        public readonly array $events,
        public readonly array $index,
        public readonly array $dueHooks,
        public readonly int $total,
        public readonly int $due,
        public readonly int $truncated,
    ) {}

    /**
     * Capture the current state of the WP-Cron schedule.
     *
     * Whether a hook has a callback registered cannot be determined reliably by
     * this class alone, because the logger registers its own measurement
     * wrappers on the hooks it observes. The caller therefore passes a resolver
     * that knows how to discount those wrappers.
     *
     * @param callable(string): bool $hasCallback Resolver telling whether a hook has a real callback
     * @return self
     */
    public static function capture(callable $hasCallback): self
    {
        $now = microtime(true);
        $nowSeconds = (int) $now;
        $schedules = self::schedules();

        $events = [];
        $index = [];
        $dueHooks = [];
        $due = 0;

        foreach (self::cronArray() as $timestamp => $hooks) {
            if (!is_array($hooks)) {
                continue;
            }

            $eventTime = (int) $timestamp;
            $isDue = $eventTime <= $now;

            foreach ($hooks as $hook => $entries) {
                if (!is_array($entries)) {
                    continue;
                }

                $hook = (string) $hook;

                foreach ($entries as $argsKey => $entry) {
                    // The cron array keys events by md5(serialize($args)). The
                    // first bytes are enough to tell events of the same hook
                    // apart without ever exposing the argument values.
                    $args = substr((string) $argsKey, 0, 8);
                    $schedule = (is_array($entry) && !empty($entry['schedule'])) ? (string) $entry['schedule'] : false;
                    $interval = (is_array($entry) && isset($entry['interval'])) ? (int) $entry['interval'] : null;

                    $events[] = [
                        'hook' => $hook,
                        'args' => $args,
                        'at' => gmdate('Y-m-d\TH:i:s\Z', $eventTime),
                        'delta_sec' => $eventTime - $nowSeconds,
                        'due' => $isDue,
                        'sched' => $schedule,
                        'interval' => $interval,
                        'cb' => (bool) $hasCallback($hook),
                        // Sorting key, stripped before the row is rendered.
                        '_ts' => $eventTime,
                    ];

                    // Duplicate hook/args pairs scheduled at different times are
                    // collapsed onto the earliest occurrence so that a
                    // rescheduled event is reported as a reschedule rather than
                    // as a removal followed by an addition.
                    $key = $hook . '|' . $args;
                    if (!isset($index[$key]) || $eventTime < $index[$key]['ts']) {
                        $index[$key] = [
                            'hook' => $hook,
                            'args' => $args,
                            'ts' => $eventTime,
                            'due' => $isDue,
                            'sched' => $schedule,
                            'unknown_sched' => is_string($schedule) && !isset($schedules[$schedule]),
                        ];
                    }

                    if ($isDue) {
                        $due++;
                        $dueHooks[$hook] = true;
                    }
                }
            }
        }

        usort($events, fn(array $a, array $b) => $a['_ts'] <=> $b['_ts']);

        $total = count($events);
        $truncated = max(0, $total - self::MAX_EVENTS);
        $events = array_slice($events, 0, self::MAX_EVENTS);

        foreach ($events as &$event) {
            unset($event['_ts']);
        }
        unset($event);

        return new self(
            time: $now,
            events: $events,
            index: $index,
            dueHooks: array_keys($dueHooks),
            total: $total,
            due: $due,
            truncated: $truncated,
        );
    }

    /**
     * Compare two snapshots taken before and after a cron run.
     *
     * @param self $before Snapshot taken before the events were processed
     * @param self $after  Snapshot taken after the events were processed
     * @return array{gone: array, rescheduled: array, still_due: array, added: array}
     */
    public static function diff(self $before, self $after): array
    {
        $gone = [];
        $rescheduled = [];
        $stillDue = [];
        $added = [];

        foreach ($before->index as $key => $event) {
            if (!isset($after->index[$key])) {
                $gone[] = $key;
                continue;
            }

            $current = $after->index[$key];

            if ($current['ts'] !== $event['ts']) {
                $rescheduled[] = [
                    'hook' => $current['hook'],
                    'args' => $current['args'],
                    'at' => gmdate('Y-m-d\TH:i:s\Z', $current['ts']),
                ];
            }

            // An event that was due before the run and is still due afterwards
            // was never processed.
            if ($event['due'] && $current['due']) {
                $stillDue[] = $key;
            }
        }

        foreach ($after->index as $key => $event) {
            if (!isset($before->index[$key])) {
                $added[] = $key;
            }
        }

        return [
            'gone' => array_slice($gone, 0, self::MAX_EVENTS),
            'rescheduled' => array_slice($rescheduled, 0, self::MAX_EVENTS),
            'still_due' => array_slice($stillDue, 0, self::MAX_EVENTS),
            'added' => array_slice($added, 0, self::MAX_EVENTS),
        ];
    }

    /**
     * Hook names that are due and have no callback registered.
     *
     * These are orphaned events: WP-Cron keeps firing and rescheduling them
     * forever while nothing actually happens. This is one of the most common
     * reasons for a cron job appearing not to run.
     *
     * @return array<int, string>
     */
    public function orphanedHooks(): array
    {
        $hooks = [];

        foreach ($this->events as $event) {
            if ($event['due'] && !$event['cb']) {
                $hooks[$event['hook']] = true;
            }
        }

        return array_keys($hooks);
    }

    /**
     * Schedule names referenced by events but not registered in WordPress.
     *
     * An event referencing an unregistered schedule cannot be rescheduled and
     * will silently stop recurring after it runs once.
     *
     * @return array<int, string>
     */
    public function unknownSchedules(): array
    {
        $names = [];

        foreach ($this->index as $event) {
            if ($event['unknown_sched']) {
                $names[(string) $event['sched']] = true;
            }
        }

        return array_keys($names);
    }

    /**
     * Registered cron schedules as a name to interval map.
     *
     * @return array<string, int>
     */
    public static function scheduleIntervals(): array
    {
        $intervals = [];

        foreach (self::schedules() as $name => $schedule) {
            $intervals[(string) $name] = isset($schedule['interval']) ? (int) $schedule['interval'] : 0;
        }

        return $intervals;
    }

    /**
     * Retrieve the registered cron schedules.
     *
     * @return array<string, array>
     */
    private static function schedules(): array
    {
        $schedules = function_exists('wp_get_schedules') ? wp_get_schedules() : [];

        return is_array($schedules) ? $schedules : [];
    }

    /**
     * Retrieve the raw cron array.
     *
     * Prefers `_get_cron_array()`, which normalizes legacy formats, and falls
     * back to reading the option directly if that private function is ever
     * removed.
     *
     * @return array<int|string, mixed>
     */
    private static function cronArray(): array
    {
        if (function_exists('_get_cron_array')) {
            $cron = _get_cron_array();
        } else {
            $cron = get_option('cron');
            if (is_array($cron)) {
                unset($cron['version']);
            }
        }

        return is_array($cron) ? $cron : [];
    }
}
