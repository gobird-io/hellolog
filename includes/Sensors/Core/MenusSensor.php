<?php
/**
 * Sensor: navigation menus.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Core;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks the three menu lifecycle hooks; item-level changes (add / remove
 * /reorder) ride on the underlying nav_menu_item post type which we skip
 * in ContentSensor — that noise belongs here, batched per menu save.
 */
final class MenusSensor extends AbstractSensor {

	public function key(): string {
		return 'core-menus';
	}

	public function boot(): void {
		add_action( 'wp_create_nav_menu', [ $this, 'on_create' ], 10, 2 );
		add_action( 'wp_update_nav_menu', [ $this, 'on_update' ], 10, 2 );
		add_action( 'wp_delete_nav_menu', [ $this, 'on_delete' ], 10, 1 );
	}

	/**
	 * @param array<string, mixed> $menu_data
	 */
	public function on_create( int $menu_id, array $menu_data ): void {
		$this->emit(
			2400,
			[
				'name'     => (string) ( $menu_data['menu-name'] ?? '' ),
				'metadata' => [ 'menu_id' => $menu_id ],
			]
		);
	}

	/**
	 * @param array<string, mixed> $menu_data
	 */
	public function on_update( int $menu_id, array $menu_data = [] ): void {
		$this->emit(
			2401,
			[
				'name'     => (string) ( $menu_data['menu-name'] ?? '' ),
				'metadata' => [ 'menu_id' => $menu_id ],
			]
		);
	}

	/**
	 * @param mixed $term
	 */
	public function on_delete( $term ): void {
		$name = '';
		if ( is_object( $term ) && isset( $term->name ) ) {
			$name = (string) $term->name;
		}
		$this->emit(
			2402,
			[
				'name'     => $name,
				'metadata' => [ 'menu_id' => is_object( $term ) ? (int) ( $term->term_id ?? 0 ) : (int) $term ],
			]
		);
	}
}
