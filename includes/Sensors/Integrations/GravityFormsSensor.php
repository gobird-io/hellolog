<?php
/**
 * Gravity Forms sensor.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * Form CRUD + entry submission. Gravity Forms exposes its own hooks
 * (`gform_after_save_form`, `gform_after_delete_form`, `gform_after_submission`).
 */
final class GravityFormsSensor extends AbstractSensor {

	public function key(): string {
		return 'gravity-forms';
	}

	public function should_load(): bool {
		return class_exists( 'GFForms', false );
	}

	public function boot(): void {
		add_action( 'gform_after_save_form', [ $this, 'on_save_form' ], 10, 2 );
		add_action( 'gform_after_delete_form', [ $this, 'on_delete_form' ], 10, 1 );
		add_action( 'gform_after_submission', [ $this, 'on_submission' ], 10, 2 );
	}

	/**
	 * @param array<string, mixed> $form
	 */
	public function on_save_form( array $form, bool $is_new ): void {
		$this->emit(
			$is_new ? 5700 : 5701,
			[
				'title'    => (string) ( $form['title'] ?? '' ),
				'metadata' => [ 'form_id' => (int) ( $form['id'] ?? 0 ) ],
			]
		);
	}

	public function on_delete_form( int $form_id ): void {
		$this->emit(
			5702,
			[
				'title'    => '#' . $form_id,
				'metadata' => [ 'form_id' => $form_id ],
			]
		);
	}

	/**
	 * @param array<string, mixed> $entry
	 * @param array<string, mixed> $form
	 */
	public function on_submission( array $entry, array $form ): void {
		$this->emit(
			5710,
			[
				'title'    => (string) ( $form['title'] ?? '' ),
				'metadata' => [
					'form_id'  => (int) ( $form['id'] ?? 0 ),
					'entry_id' => (int) ( $entry['id'] ?? 0 ),
				],
			]
		);
	}
}
