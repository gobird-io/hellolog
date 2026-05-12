<?php
/**
 * Outcome of one API call.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Transport;

defined( 'ABSPATH' ) || exit;

/**
 * Discriminated result for `ApiClient::post_batch()`. `retryable` tells the
 * dispatcher whether to schedule another attempt (5xx, 408, 429, network
 * errors) or to dead-letter the batch immediately (4xx auth/validation).
 */
final class ApiResult {

	private function __construct(
		public bool $ok,
		public int $status,
		public string $body,
		public bool $retryable
	) {
	}

	public static function success( int $status, string $body ): self {
		return new self( true, $status, $body, false );
	}

	public static function error( int $status, string $body, bool $retryable ): self {
		return new self( false, $status, $body, $retryable );
	}
}
