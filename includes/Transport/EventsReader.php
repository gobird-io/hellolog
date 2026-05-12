<?php
/**
 * Reads paginated events from the backend's GET /events endpoint.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Transport;

defined( 'ABSPATH' ) || exit;

/**
 * Powers the **Tools → Activity Log** admin page. Talks to the backend
 * Read API (`GET {prefix}/events`) with the site's bearer token,
 * forwarding filters and the cursor verbatim. Never persists anything
 * locally — the WP DB stays clean of historical events.
 */
final class EventsReader {

	private const TIMEOUT_SEC = 10;

	public function __construct(
		private string $endpoint_url,
		private string $token
	) {
	}

	public function is_configured(): bool {
		return '' !== $this->endpoint_url && '' !== $this->token;
	}

	/**
	 * @param array<string, mixed> $filters  from / to / code[] / user_id / object / event_type / q / cursor / limit
	 * @return array{ok: bool, status: int, body: array<string, mixed>|string}
	 */
	public function list( array $filters ): array {
		if ( ! $this->is_configured() ) {
			return [ 'ok' => false, 'status' => 0, 'body' => 'transport not configured' ];
		}

		$query    = $this->build_query_string( $filters );
		$response = wp_remote_get(
			$this->endpoint_url . '/events' . $query,
			[
				'timeout' => self::TIMEOUT_SEC,
				'headers' => [
					'Authorization' => 'Bearer ' . $this->token,
					'Accept'        => 'application/json',
					'X-Site-Domain' => $this->site_domain(),
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return [ 'ok' => false, 'status' => 0, 'body' => $response->get_error_message() ];
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = (string) wp_remote_retrieve_body( $response );
		if ( $status < 200 || $status >= 300 ) {
			return [ 'ok' => false, 'status' => $status, 'body' => $body ];
		}

		$decoded = json_decode( $body, true );
		if ( ! is_array( $decoded ) ) {
			return [ 'ok' => false, 'status' => $status, 'body' => 'unparseable response' ];
		}

		return [ 'ok' => true, 'status' => $status, 'body' => $decoded ];
	}

	/**
	 * @param array<string, mixed> $filters
	 */
	private function build_query_string( array $filters ): string {
		$allowed = [ 'from', 'to', 'user_id', 'object', 'event_type', 'q', 'cursor', 'limit' ];
		$pairs   = [];
		foreach ( $allowed as $key ) {
			if ( isset( $filters[ $key ] ) && '' !== $filters[ $key ] ) {
				$pairs[ $key ] = (string) $filters[ $key ];
			}
		}
		if ( isset( $filters['code'] ) ) {
			$codes = is_array( $filters['code'] ) ? $filters['code'] : [ $filters['code'] ];
			$codes = array_values( array_filter( array_map( 'intval', $codes ) ) );
			if ( ! empty( $codes ) ) {
				$pairs['code'] = implode( ',', $codes );
			}
		}
		if ( empty( $pairs ) ) {
			return '';
		}
		return '?' . http_build_query( $pairs );
	}

	private function site_domain(): string {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		return is_string( $host ) ? strtolower( $host ) : '';
	}
}
