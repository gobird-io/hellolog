<?php
/**
 * Sensor: user registration, profile edits, role changes, deletion.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Core;

use HelloLog\Sensors\AbstractSensor;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Catches user-lifecycle events: register, profile_update, role change,
 * and deletion. Role transitions are special: WordPress fires
 * `set_user_role` for every role assignment, including the one immediately
 * after `user_register`, so we de-duplicate by tracking the just-registered
 * user id in the request.
 */
final class UserProfileSensor extends AbstractSensor {

	private ?int $just_registered = null;

	public function key(): string {
		return 'core-user-profile';
	}

	public function boot(): void {
		add_action( 'user_register', [ $this, 'on_register' ], 10, 1 );
		add_action( 'profile_update', [ $this, 'on_profile_update' ], 10, 1 );
		add_action( 'set_user_role', [ $this, 'on_role_change' ], 10, 3 );
		add_action( 'delete_user', [ $this, 'on_delete' ], 10, 1 );
	}

	public function on_register( int $user_id ): void {
		$this->just_registered = $user_id;
		$user                  = get_user_by( 'id', $user_id );
		if ( ! $user instanceof WP_User ) {
			return;
		}
		$this->emit(
			4000,
			[
				'user'     => [
					'id'       => (int) $user->ID,
					'username' => (string) $user->user_login,
					'roles'    => array_values( (array) $user->roles ),
				],
				'username' => (string) $user->user_login,
				'role'     => $user->roles[0] ?? '',
				'metadata' => [
					'email'        => (string) $user->user_email,
					'display_name' => (string) $user->display_name,
				],
			]
		);
	}

	public function on_profile_update( int $user_id ): void {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user instanceof WP_User ) {
			return;
		}
		$this->emit(
			4001,
			[
				'user'     => [
					'id'       => (int) $user->ID,
					'username' => (string) $user->user_login,
					'roles'    => array_values( (array) $user->roles ),
				],
				'username' => (string) $user->user_login,
			]
		);
	}

	/**
	 * @param array<int, string> $old_roles
	 */
	public function on_role_change( int $user_id, string $new_role, array $old_roles ): void {
		if ( $user_id === $this->just_registered ) {
			return;
		}
		$user = get_user_by( 'id', $user_id );
		if ( ! $user instanceof WP_User ) {
			return;
		}
		$this->emit(
			4002,
			[
				'user'     => [
					'id'       => (int) $user->ID,
					'username' => (string) $user->user_login,
					'roles'    => [ $new_role ],
				],
				'username' => (string) $user->user_login,
				'old_role' => $old_roles[0] ?? '',
				'new_role' => $new_role,
				'metadata' => [
					'old_roles' => $old_roles,
					'new_role'  => $new_role,
				],
			]
		);
	}

	public function on_delete( int $user_id ): void {
		$user = get_user_by( 'id', $user_id );
		$name = $user instanceof WP_User ? (string) $user->user_login : '#' . $user_id;
		$this->emit(
			4003,
			[
				'username' => $name,
				'metadata' => [ 'deleted_user_id' => $user_id ],
			]
		);
	}
}
