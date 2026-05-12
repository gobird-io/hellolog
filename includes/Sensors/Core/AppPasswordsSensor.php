<?php
/**
 * Sensor: WordPress application passwords.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Core;

use HelloLog\Sensors\AbstractSensor;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks the three application-password actions: created, revoked, used.
 * App passwords are programmatic credentials so each event matters.
 */
final class AppPasswordsSensor extends AbstractSensor {

	public function key(): string {
		return 'core-app-passwords';
	}

	public function boot(): void {
		add_action( 'wp_create_application_password', [ $this, 'on_create' ], 10, 4 );
		add_action( 'wp_delete_application_password', [ $this, 'on_delete' ], 10, 2 );
		add_action( 'application_password_did_authenticate', [ $this, 'on_authenticate' ], 10, 2 );
	}

	/**
	 * @param array<string, mixed> $new_item
	 * @param mixed                $new_password
	 * @param array<string, mixed> $args
	 */
	public function on_create( int $user_id, array $new_item, $new_password, array $args ): void {
		$user = get_userdata( $user_id );
		$this->emit(
			4500,
			[
				'name'     => (string) ( $new_item['name'] ?? '' ),
				'username' => $user instanceof WP_User ? (string) $user->user_login : '#' . $user_id,
				'metadata' => [
					'user_id' => $user_id,
					'uuid'    => (string) ( $new_item['uuid'] ?? '' ),
					'app_id'  => (string) ( $new_item['app_id'] ?? '' ),
				],
			]
		);
	}

	public function on_delete( int $user_id, string $uuid ): void {
		$user = get_userdata( $user_id );
		$this->emit(
			4501,
			[
				'username' => $user instanceof WP_User ? (string) $user->user_login : '#' . $user_id,
				'metadata' => [
					'user_id' => $user_id,
					'uuid'    => $uuid,
				],
			]
		);
	}

	/**
	 * @param array<string, mixed> $item
	 */
	public function on_authenticate( WP_User $user, array $item ): void {
		$this->emit(
			4502,
			[
				'user'     => [
					'id'       => (int) $user->ID,
					'username' => (string) $user->user_login,
					'roles'    => array_values( (array) $user->roles ),
				],
				'username' => (string) $user->user_login,
				'metadata' => [
					'name'   => (string) ( $item['name'] ?? '' ),
					'uuid'   => (string) ( $item['uuid'] ?? '' ),
					'app_id' => (string) ( $item['app_id'] ?? '' ),
				],
			]
		);
	}
}
