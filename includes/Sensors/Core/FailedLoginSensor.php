<?php
/**
 * Sensor: failed login attempts.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Core;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * Carved out of {@see LoginLogoutSensor} because failed logins are a
 * separate operational concern: noisy on any public site (bot
 * attempts), useful when you're investigating an incident, and rarely
 * worth recording continuously. Defaults to OFF — operators flip it on
 * in `Settings → Filters` when they need it.
 */
final class FailedLoginSensor extends AbstractSensor {

	public function key(): string {
		return 'core-failed-login';
	}

	public function boot(): void {
		add_action( 'wp_login_failed', [ $this, 'on_failed_login' ], 10, 1 );
	}

	public function on_failed_login( string $username ): void {
		$this->emit(
			1002,
			[
				'username' => $username,
				'metadata' => [ 'attempted_username' => $username ],
			]
		);
	}
}
