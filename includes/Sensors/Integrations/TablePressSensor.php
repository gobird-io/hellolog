<?php
/**
 * TablePress sensor.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * TablePress stores tables in its own post type but the high-level
 * `tablepress_event_*` actions are easier to listen for.
 */
final class TablePressSensor extends AbstractSensor {

	public function key(): string {
		return 'tablepress';
	}

	public function should_load(): bool {
		return class_exists( 'TablePress', false );
	}

	public function boot(): void {
		add_action( 'tablepress_event_added_table', [ $this, 'on_added' ], 10, 1 );
		add_action( 'tablepress_event_saved_table', [ $this, 'on_saved' ], 10, 1 );
		add_action( 'tablepress_event_deleted_table', [ $this, 'on_deleted' ], 10, 1 );
	}

	/**
	 * @param mixed $table_id
	 */
	public function on_added( $table_id ): void {
		$this->emit_table( 5300, $table_id );
	}

	/**
	 * @param mixed $table_id
	 */
	public function on_saved( $table_id ): void {
		$this->emit_table( 5301, $table_id );
	}

	/**
	 * @param mixed $table_id
	 */
	public function on_deleted( $table_id ): void {
		$this->emit_table( 5302, $table_id );
	}

	/**
	 * @param mixed $table_id
	 */
	private function emit_table( int $code, $table_id ): void {
		$this->emit(
			$code,
			[
				'name'     => (string) $table_id,
				'metadata' => [ 'table_id' => (string) $table_id ],
			]
		);
	}
}
