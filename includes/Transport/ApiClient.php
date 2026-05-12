<?php
/**
 * HTTP client for the events ingest endpoint.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Transport;

defined( 'ABSPATH' ) || exit;

/**
 * Thin wrapper over `wp_remote_post` with the conventions the backend's
 * ingest endpoint expects: Bearer auth, gzipped JSON body, short timeout,
 * structured result for the dispatcher.
 */
final class ApiClient {

	private const TIMEOUT_SEC = 10;
	private const SDK_HEADER  = 'hellolog';

	public function __construct(
		private string $endpoint_url,
		private string $token
	) {
	}

	public function is_configured(): bool {
		return '' !== $this->endpoint_url && '' !== $this->token;
	}

	/**
	 * POST a batch of events. Returns an {@see ApiResult} describing the
	 * outcome — callers MUST inspect ->ok before considering events delivered.
	 *
	 * @param string $batch_json Already-serialized batch payload.
	 */
	public function post_batch( string $batch_json ): ApiResult {
		if ( ! $this->is_configured() ) {
			return ApiResult::error( 0, 'transport not configured', false );
		}

		$gz = gzencode( $batch_json, 6 );
		if ( false === $gz ) {
			// Fall back to plain JSON; compression is a nice-to-have.
			$gz       = $batch_json;
			$encoding = null;
		} else {
			$encoding = 'gzip';
		}

		$headers = [
			'Authorization' => 'Bearer ' . $this->token,
			'Content-Type'  => 'application/json',
			'X-Goal-Sdk'    => self::SDK_HEADER . '/' . HELLOLOG_VERSION,
			'X-Site-Domain' => $this->site_domain(),
		];
		if ( null !== $encoding ) {
			$headers['Content-Encoding'] = $encoding;
		}

		$response = wp_remote_post(
			$this->endpoint_url . '/events',
			[
				'timeout'     => self::TIMEOUT_SEC,
				'redirection' => 0,
				'headers'     => $headers,
				'body'        => $gz,
				'blocking'    => true,
			]
		);

		if ( is_wp_error( $response ) ) {
			return ApiResult::error( 0, $response->get_error_message(), true );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = (string) wp_remote_retrieve_body( $response );

		if ( $status >= 200 && $status < 300 ) {
			return ApiResult::success( $status, $body );
		}

		// 4xx (other than 429) → permanent — the backend rejected the
		// shape or the token. Retrying wouldn't help. 5xx / 429 → retry.
		$retryable = $status >= 500 || 429 === $status || 408 === $status;
		return ApiResult::error( $status, $body, $retryable );
	}

	/**
	 * Hostname the WordPress install lives on, sent as `X-Site-Domain`
	 * so the backend can pin a token to its issuing domain.
	 */
	private function site_domain(): string {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		return is_string( $host ) ? strtolower( $host ) : '';
	}
}
