<?php
/**
 * Production dispatcher — builds and persists outgoing events.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Queue;

use HelloLog\Events\EventBuilder;
use HelloLog\Events\EventDispatcher;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Default implementation of {@see EventDispatcher}. Runs synchronously off
 * a sensor hook, so it MUST stay tight: build the canonical event, JSON
 * encode, single `INSERT` into the local queue, return.
 *
 * The transport layer drains the queue out-of-band on the Action Scheduler.
 */
final class QueueEventDispatcher implements EventDispatcher {

	public function __construct(
		private EventBuilder $builder,
		private QueueRepository $repository
	) {
	}

	public function dispatch( int $code, array $fields = [] ): void {
		try {
			$payload = $this->builder->build( $code, $fields );
			if ( null === $payload ) {
				return; // Unknown code — silently drop.
			}

			$json = wp_json_encode( $payload );
			if ( ! is_string( $json ) ) {
				return;
			}

			$this->repository->insert( $json );
		} catch ( Throwable $e ) {
			/**
			 * Allow operators to observe internal dispatcher failures
			 * (e.g. via Query Monitor) without bringing the surrounding
			 * request down. We intentionally swallow the throwable here.
			 *
			 * @since 0.1.0
			 *
			 * @param Throwable            $error  The captured exception.
			 * @param int                  $code   Event code that was being dispatched.
			 * @param array<string, mixed> $fields Sensor-supplied fields.
			 */
			do_action( 'hellolog_dispatch_failed', $e, $code, $fields );
		}
	}
}
