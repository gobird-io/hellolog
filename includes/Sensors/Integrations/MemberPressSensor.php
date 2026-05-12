<?php
/**
 * MemberPress sensor.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * MemberPress membership CPT + transaction completion hook.
 */
final class MemberPressSensor extends AbstractSensor {

	public function key(): string {
		return 'memberpress';
	}

	public function should_load(): bool {
		return defined( 'MEPR_VERSION' );
	}

	public function boot(): void {
		add_action( 'transition_post_status', [ $this, 'on_status' ], 10, 3 );
		add_action( 'mepr-txn-status-complete', [ $this, 'on_txn_complete' ], 10, 1 );
	}

	public function on_status( string $new_status, string $old_status, WP_Post $post ): void {
		if ( 'memberpressproduct' !== $post->post_type ) {
			return;
		}
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}
		$this->emit(
			6500,
			[
				'title' => (string) $post->post_title,
				'post'  => [
					'id'   => (int) $post->ID,
					'type' => (string) $post->post_type,
				],
			]
		);
	}

	/**
	 * @param mixed $txn
	 */
	public function on_txn_complete( $txn ): void {
		if ( ! is_object( $txn ) ) {
			return;
		}
		$user_id = isset( $txn->user_id ) ? (int) $txn->user_id : 0;
		$user    = $user_id ? get_userdata( $user_id ) : null;
		$amount  = isset( $txn->amount ) ? (string) $txn->amount : '';
		$this->emit(
			6501,
			[
				'username' => $user ? (string) $user->user_login : '#' . $user_id,
				'amount'   => $amount,
				'metadata' => [
					'user_id'        => $user_id,
					'transaction_id' => isset( $txn->id ) ? (int) $txn->id : 0,
					'amount'         => $amount,
				],
			]
		);
	}
}
