<?php
/**
 * Bundles queue rows into a single batch payload.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Transport;

use HelloLog\Queue\QueueRow;

defined( 'ABSPATH' ) || exit;

/**
 * Assembles `{ batch_id, events: [...] }` from a list of queue rows. Skips
 * unparseable rows so a single bad payload cannot poison the whole flush.
 */
final class PayloadBuilder {

	/**
	 * @param array<int, QueueRow> $rows
	 * @return array{batch_id: string, events: array<int, array<string, mixed>>, ids: array<int, int>}
	 */
	public function build( array $rows ): array {
		$events = [];
		$ids    = [];
		foreach ( $rows as $row ) {
			$event = json_decode( $row->payload, true );
			if ( ! is_array( $event ) ) {
				continue;
			}
			$events[] = $event;
			$ids[]    = $row->id;
		}

		return [
			'batch_id' => self::generate_batch_id(),
			'events'   => $events,
			'ids'      => $ids,
		];
	}

	private static function generate_batch_id(): string {
		// 16 hex chars is plenty for batch correlation; not security-sensitive.
		return bin2hex( random_bytes( 8 ) );
	}
}
