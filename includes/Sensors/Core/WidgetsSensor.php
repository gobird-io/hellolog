<?php
/**
 * Sensor: widgets (block + classic).
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Core;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks the canonical widget-update path. WordPress fires
 * `widget_update_callback` for both the legacy widgets screen and the
 * block widgets editor, so one subscription covers both.
 */
final class WidgetsSensor extends AbstractSensor {

	public function key(): string {
		return 'core-widgets';
	}

	public function boot(): void {
		add_filter( 'widget_update_callback', [ $this, 'on_update' ], 10, 4 );
		add_action( 'delete_widget', [ $this, 'on_delete' ], 10, 3 );
	}

	/**
	 * @param array<string, mixed> $instance
	 * @param array<string, mixed> $new_instance
	 * @param array<string, mixed> $old_instance
	 * @param mixed                $widget
	 * @return array<string, mixed>
	 */
	public function on_update( $instance, $new_instance, $old_instance, $widget ) {
		if ( ! is_object( $widget ) || ! isset( $widget->id_base ) ) {
			return $instance;
		}
		$this->emit(
			2300,
			[
				'id_base'  => (string) $widget->id_base,
				'sidebar'  => '',
				'metadata' => [
					'id_base' => (string) $widget->id_base,
					'fields'  => array_keys( (array) $new_instance ),
				],
			]
		);
		return $instance;
	}

	public function on_delete( string $widget_id, string $sidebar_id, string $id_base ): void {
		$this->emit(
			2301,
			[
				'id_base'  => $id_base,
				'sidebar'  => $sidebar_id,
				'metadata' => [
					'widget_id' => $widget_id,
					'sidebar'   => $sidebar_id,
				],
			]
		);
	}
}
