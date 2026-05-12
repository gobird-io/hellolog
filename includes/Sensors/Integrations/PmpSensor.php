<?php
/**
 * Paid Memberships Pro sensor.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * PMP exposes the after-level-change action with the relevant user and
 * level ids; one event per level grant or revoke.
 */
final class PmpSensor extends AbstractSensor {

	public function key(): string {
		return 'paid-memberships-pro';
	}

	public function should_load(): bool {
		return defined( 'PMPRO_VERSION' );
	}

	public function boot(): void {
		add_action( 'pmpro_after_change_membership_level', [ $this, 'on_change' ], 10, 3 );
	}

	public function on_change( int $level_id, int $user_id, ?int $cancel_level = null ): void {
		$user  = get_userdata( $user_id );
		$level = function_exists( 'pmpro_getLevel' ) ? pmpro_getLevel( $level_id ) : null;
		$this->emit(
			5500,
			[
				'username' => $user ? (string) $user->user_login : '#' . $user_id,
				'level'    => is_object( $level ) && isset( $level->name ) ? (string) $level->name : '#' . $level_id,
				'metadata' => [
					'user_id'      => $user_id,
					'level_id'     => $level_id,
					'cancel_level' => $cancel_level,
				],
			]
		);
	}
}
