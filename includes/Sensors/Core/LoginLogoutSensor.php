<?php
/**
 * Sensor: authentication-related events.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Core;

use HelloLog\Sensors\AbstractSensor;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps the *successful* auth hooks: login, logout, password-reset
 * requests. Failed login attempts moved to {@see FailedLoginSensor}
 * because they're noisy on internet-exposed sites and operators often
 * want them off by default.
 */
final class LoginLogoutSensor extends AbstractSensor {

	public function key(): string {
		return 'core-auth';
	}

	public function boot(): void {
		add_action( 'wp_login', [ $this, 'on_login' ], 10, 2 );
		add_action( 'wp_logout', [ $this, 'on_logout' ], 10, 1 );
		add_action( 'retrieve_password_key', [ $this, 'on_password_reset_request' ], 10, 1 );
	}

	public function on_login( string $user_login, WP_User $user ): void {
		$this->emit(
			1000,
			[
				'user'     => [
					'id'       => (int) $user->ID,
					'username' => $user_login,
					'roles'    => array_values( (array) $user->roles ),
				],
				'username' => $user_login,
			]
		);
	}

	public function on_logout( int $user_id ): void {
		$user = $user_id > 0 ? get_user_by( 'id', $user_id ) : null;
		$this->emit(
			1001,
			[
				'user'     => $user instanceof WP_User
					? [
						'id'       => (int) $user->ID,
						'username' => (string) $user->user_login,
						'roles'    => array_values( (array) $user->roles ),
					]
					: null,
				'username' => $user instanceof WP_User ? (string) $user->user_login : '',
			]
		);
	}

	public function on_password_reset_request( string $username ): void {
		$this->emit(
			1003,
			[ 'username' => $username ]
		);
	}
}
