<?php
/**
 * Easy Digital Downloads sensor.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * EDD downloads (CPT `download`) + payment status changes.
 */
final class EddSensor extends AbstractSensor {

	public function key(): string {
		return 'edd';
	}

	public function should_load(): bool {
		return defined( 'EDD_VERSION' );
	}

	public function boot(): void {
		add_action( 'transition_post_status', [ $this, 'on_status' ], 10, 3 );
		add_action( 'edd_update_payment_status', [ $this, 'on_payment_status' ], 10, 3 );
	}

	public function on_status( string $new_status, string $old_status, WP_Post $post ): void {
		if ( 'download' !== $post->post_type ) {
			return;
		}
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}
		$this->emit(
			8300,
			[
				'title' => (string) $post->post_title,
				'post'  => [
					'id'   => (int) $post->ID,
					'type' => (string) $post->post_type,
				],
			]
		);
	}

	public function on_payment_status( int $payment_id, string $new_status, string $old_status ): void {
		$this->emit(
			8301,
			[
				'id'       => $payment_id,
				'status'   => $new_status,
				'metadata' => [
					'payment_id' => $payment_id,
					'old_status' => $old_status,
					'new_status' => $new_status,
				],
			]
		);
	}
}
