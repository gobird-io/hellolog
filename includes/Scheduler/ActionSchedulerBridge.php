<?php
/**
 * Registers the 30-second flush job with Action Scheduler.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Scheduler;

use HelloLog\Transport\QueueFlusher;

defined( 'ABSPATH' ) || exit;

/**
 * Action Scheduler is shipped as a Composer dependency
 * ({@see https://actionscheduler.org/}). We use its recurring action API to
 * drain the queue out-of-band, freeing user requests from any HTTP work.
 *
 * Bridge stays thin — bind the `hellolog_flush_queue` hook to the flusher and
 * ensure exactly one schedule exists at any time.
 */
final class ActionSchedulerBridge {

	private const HOOK     = 'hellolog_flush_queue';
	private const GROUP    = 'hellolog';
	private const INTERVAL = 30;

	public function __construct(
		private QueueFlusher $flusher
	) {
	}

	public function register(): void {
		add_action( self::HOOK, [ $this->flusher, 'run' ] );
		add_action( 'init', [ $this, 'ensure_scheduled' ] );
	}

	public function ensure_scheduled(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) || ! function_exists( 'as_schedule_recurring_action' ) ) {
			return;
		}
		if ( as_has_scheduled_action( self::HOOK, [], self::GROUP ) ) {
			return;
		}
		as_schedule_recurring_action( time() + self::INTERVAL, self::INTERVAL, self::HOOK, [], self::GROUP );
	}
}
