<?php
/**
 * No-op dispatcher — used until M3 wires the real queue producer.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Events;

defined( 'ABSPATH' ) || exit;

/**
 * Swallows every event. Lets the sensor wiring compile and run end-to-end
 * before {@see \HelloLog\Queue\QueueEventDispatcher} arrives.
 */
final class NullEventDispatcher implements EventDispatcher {

	public function dispatch( int $code, array $fields = [] ): void {
		// no-op
	}
}
