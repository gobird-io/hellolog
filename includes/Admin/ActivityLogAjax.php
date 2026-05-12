<?php
/**
 * AJAX endpoint that fetches events from the backend for the admin page.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Admin;

use HelloLog\Settings\Options;
use HelloLog\Transport\EventsReader;

defined( 'ABSPATH' ) || exit;

/**
 * Proxies filtered GET /events queries from the browser to the backend
 * through {@see EventsReader}. Validates capabilities + nonce on every
 * request, never embeds the bearer token in HTML (the WP token stays
 * server-side).
 */
final class ActivityLogAjax {

	public const ACTION = 'hellolog_list_events';

	public function register(): void {
		add_action( 'wp_ajax_' . self::ACTION, [ $this, 'handle' ] );
	}

	public function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'error' => 'forbidden' ], 403 );
		}
		check_ajax_referer( self::ACTION, 'nonce' );

		$opts   = new Options();
		$reader = new EventsReader( $opts->endpoint_url(), $opts->token() );

		$filters = $this->collect_filters();
		$result  = $reader->list( $filters );

		if ( ! $result['ok'] ) {
			wp_send_json_error(
				[
					'status' => $result['status'],
					'body'   => $result['body'],
				],
				$result['status'] > 0 ? $result['status'] : 502
			);
		}
		wp_send_json_success( $result['body'] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function collect_filters(): array {
		$accepted = [ 'from', 'to', 'object', 'event_type', 'code', 'q', 'cursor', 'limit', 'user_id' ];
		$out      = [];
		foreach ( $accepted as $key ) {
			$value = $_POST[ $key ] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( null === $value || '' === $value ) {
				continue;
			}
			$out[ $key ] = is_string( $value ) ? sanitize_text_field( wp_unslash( $value ) ) : $value;
		}
		return $out;
	}
}
