<?php
/**
 * AJAX handler for the Settings → Connection "Send test event" button.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Admin;

use HelloLog\Settings\Options;
use HelloLog\Transport\ApiClient;

defined( 'ABSPATH' ) || exit;

/**
 * One-shot diagnostics call from the admin UI. Bypasses the queue: builds a
 * minimal "ping" payload and POSTs it directly through {@see ApiClient}.
 * Operator gets an immediate status + body in the response.
 */
final class TestConnectionHandler {

	public const ACTION = 'hellolog_test_connection';

	public function register(): void {
		add_action( 'wp_ajax_' . self::ACTION, [ $this, 'handle' ] );
	}

	public function handle(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'error' => 'forbidden' ], 403 );
		}
		check_ajax_referer( self::ACTION, 'nonce' );

		$options = new Options();
		if ( '' === $options->endpoint_url() || '' === $options->token() ) {
			wp_send_json_error( [ 'error' => 'transport not configured' ], 400 );
		}

		$client = new ApiClient( $options->endpoint_url(), $options->token() );
		$batch  = wp_json_encode(
			[
				'batch_id' => 'connection-test-' . substr( md5( (string) microtime( true ) ), 0, 8 ),
				'events'   => [
					[
						'code'        => 9999,
						'occurred_at' => gmdate( 'Y-m-d\TH:i:s.v\Z' ),
						'severity'    => 'info',
						'object'      => 'system',
						'event_type'  => 'connection-test',
						'message'     => 'Test event from hellolog Settings page.',
					],
				],
			]
		);
		if ( ! is_string( $batch ) ) {
			wp_send_json_error( [ 'error' => 'encode failed' ], 500 );
		}

		$result = $client->post_batch( $batch );
		// A successful round-trip is what flips the license from "stored"
		// to "active". Sensors only attach after this, so it's the
		// gatekeeping step.
		$options->mark_active( $result->ok );
		if ( $result->ok ) {
			wp_send_json_success(
				[
					'status' => $result->status,
					'body'   => $result->body,
				]
			);
		}
		wp_send_json_error(
			[
				'status'    => $result->status,
				'body'      => $result->body,
				'retryable' => $result->retryable,
			],
			$result->status > 0 ? $result->status : 502
		);
	}
}
