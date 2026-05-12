<?php
/**
 * Sensor: custom database table create/drop detection.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Core;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks `query` so we can spot `CREATE TABLE` / `DROP TABLE` issued
 * outside WordPress core. The filter is cheap — early-exit unless the
 * query starts with one of the keywords we care about.
 *
 * This is best-effort: a plugin issuing the DDL is the common path and
 * is covered here. Migration tools running outside the WP request are
 * not (run an ops-side migration audit instead).
 */
final class DatabaseSensor extends AbstractSensor {

	public function key(): string {
		return 'core-database';
	}

	public function boot(): void {
		add_filter( 'query', [ $this, 'on_query' ], 10, 1 );
	}

	public function on_query( string $query ): string {
		$trimmed = ltrim( $query );
		$head    = strtoupper( substr( $trimmed, 0, 12 ) );

		if ( str_starts_with( $head, 'CREATE TABLE' ) ) {
			$this->emit(
				7100,
				[
					'name'     => $this->extract_table( $trimmed, 'CREATE TABLE' ),
					'metadata' => [ 'query' => substr( $trimmed, 0, 256 ) ],
				]
			);
		} elseif ( str_starts_with( $head, 'DROP TABLE' ) ) {
			$this->emit(
				7101,
				[
					'name'     => $this->extract_table( $trimmed, 'DROP TABLE' ),
					'metadata' => [ 'query' => substr( $trimmed, 0, 256 ) ],
				]
			);
		}

		return $query;
	}

	private function extract_table( string $query, string $prefix ): string {
		$after = trim( substr( $query, strlen( $prefix ) ) );
		// Skip IF NOT EXISTS / IF EXISTS clauses.
		$after = preg_replace( '/^IF\s+(NOT\s+)?EXISTS\s+/i', '', $after ) ?? $after;
		// First word is the table name.
		preg_match( '/^[`"\']?([\w\.]+)[`"\']?/', $after, $matches );
		return $matches[1] ?? '';
	}
}
