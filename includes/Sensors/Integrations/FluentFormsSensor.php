<?php
/**
 * Fluent Forms sensor.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * Fluent Forms uses its own table + hooks: `fluentform/inserted_new_form`,
 * `fluentform/form_updated`, `fluentform/submission_inserted`.
 */
final class FluentFormsSensor extends AbstractSensor {

	public function key(): string {
		return 'fluent-forms';
	}

	public function should_load(): bool {
		return defined( 'FLUENTFORM_VERSION' );
	}

	public function boot(): void {
		add_action( 'fluentform/inserted_new_form', [ $this, 'on_create' ], 10, 2 );
		add_action( 'fluentform/form_updated', [ $this, 'on_update' ], 10, 2 );
		add_action( 'fluentform/submission_inserted', [ $this, 'on_submit' ], 10, 3 );
	}

	/**
	 * @param array<string, mixed> $form
	 */
	public function on_create( int $form_id, array $form ): void {
		$this->emit(
			5860,
			[
				'title'    => (string) ( $form['title'] ?? '' ),
				'metadata' => [ 'form_id' => $form_id ],
			]
		);
	}

	/**
	 * @param array<string, mixed> $form
	 */
	public function on_update( int $form_id, array $form ): void {
		$this->emit(
			5861,
			[
				'title'    => (string) ( $form['title'] ?? '' ),
				'metadata' => [ 'form_id' => $form_id ],
			]
		);
	}

	/**
	 * @param array<string, mixed> $form_data
	 * @param mixed                $form
	 */
	public function on_submit( int $entry_id, array $form_data, $form ): void {
		$title   = is_object( $form ) && isset( $form->title ) ? (string) $form->title : '';
		$form_id = is_object( $form ) && isset( $form->id ) ? (int) $form->id : 0;
		$this->emit(
			5870,
			[
				'title'    => $title,
				'metadata' => [
					'form_id'  => $form_id,
					'entry_id' => $entry_id,
				],
			]
		);
	}
}
