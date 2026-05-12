<?php
/**
 * Exponential backoff schedule for failed event deliveries.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Transport;

defined( 'ABSPATH' ) || exit;

/**
 * Deterministic backoff with a hard cap on attempts. Sized to ride out
 * the kind of transient backend hiccups that happen during a deploy —
 * roughly 38 hours from first failure to dead-letter.
 */
final class RetryPolicy {

	/** Delay in seconds for attempts 1..N (0-indexed). */
	private const SCHEDULE = [
		30,        // 30 s
		120,       // 2 m
		600,       // 10 m
		3600,      // 1 h
		21600,     // 6 h
		86400,     // 24 h
	];

	public function max_attempts(): int {
		return count( self::SCHEDULE );
	}

	/**
	 * Seconds to wait before the (attempt+1)th try.
	 *
	 * @param int $attempts How many attempts have already failed.
	 */
	public function delay_seconds( int $attempts ): int {
		if ( $attempts < 0 ) {
			$attempts = 0;
		}
		if ( $attempts >= count( self::SCHEDULE ) ) {
			return self::SCHEDULE[ count( self::SCHEDULE ) - 1 ];
		}
		return self::SCHEDULE[ $attempts ];
	}

	public function should_dead_letter( int $attempts ): bool {
		return $attempts >= $this->max_attempts();
	}

	public function next_try( int $attempts ): string {
		$ts = time() + $this->delay_seconds( $attempts );
		return gmdate( 'Y-m-d H:i:s', $ts );
	}
}
