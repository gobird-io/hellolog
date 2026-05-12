<?php
/**
 * Catalog entry for one audit event code.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Events;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable record describing one logged event "kind". Sensors look this up
 * by integer code when calling {@see EventBuilder::build()}, so the wire
 * payload stays consistent regardless of which hook fired.
 */
final class EventDefinition {

	public function __construct(
		public int $code,
		public string $object,
		public string $event_type,
		public string $severity,
		public string $message_template
	) {
	}
}
