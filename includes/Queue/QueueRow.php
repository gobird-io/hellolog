<?php
/**
 * Value object representing a row in `{$wpdb->prefix}hellolog_queue`.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Queue;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable view over a queued outgoing event. Carries the bookkeeping
 * columns the dispatcher reads back when retrying or dead-lettering.
 */
final class QueueRow {

	public function __construct(
		public int $id,
		public string $payload,
		public int $attempts,
		public string $next_try,
		public string $status,
		public ?string $last_error,
		public string $created_at
	) {
	}

	public static function from_db( object $row ): self {
		return new self(
			(int) $row->id,
			(string) $row->payload,
			(int) $row->attempts,
			(string) $row->next_try,
			(string) $row->status,
			null !== $row->last_error ? (string) $row->last_error : null,
			(string) $row->created_at
		);
	}
}
