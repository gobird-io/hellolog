<?php
/**
 * WPForms sensor.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * WPForms persists forms as `wpforms` CPT posts; we listen to the
 * dedicated `wpforms_save_form` action and the cross-cutting submission
 * action `wpforms_process_complete`.
 */
final class WPFormsSensor extends AbstractSensor {

	public function key(): string {
		return 'wpforms';
	}

	public function should_load(): bool {
		return defined( 'WPFORMS_VERSION' );
	}

	public function boot(): void {
		add_action( 'wpforms_save_form', [ $this, 'on_save_form' ], 10, 2 );
		add_action( 'wpforms_process_complete', [ $this, 'on_submit' ], 10, 4 );
		add_action( 'before_delete_post', [ $this, 'on_delete_post' ], 10, 2 );
	}

	/**
	 * @param array<string, mixed> $form_data
	 */
	public function on_save_form( int $form_id, array $form_data ): void {
		$is_new = empty( $form_data['id'] );
		$this->emit(
			$is_new ? 5800 : 5801,
			[
				'title'    => (string) ( $form_data['settings']['form_title'] ?? '' ),
				'metadata' => [ 'form_id' => $form_id ],
			]
		);
	}

	/**
	 * @param array<int, mixed>    $fields
	 * @param array<string, mixed> $entry
	 * @param array<string, mixed> $form_data
	 */
	public function on_submit( array $fields, array $entry, array $form_data, int $entry_id ): void {
		$this->emit(
			5810,
			[
				'title'    => (string) ( $form_data['settings']['form_title'] ?? '' ),
				'metadata' => [
					'form_id'  => (int) ( $form_data['id'] ?? 0 ),
					'entry_id' => $entry_id,
				],
			]
		);
	}

	/**
	 * @param mixed $post
	 */
	public function on_delete_post( int $post_id, $post ): void {
		if ( ! $post instanceof WP_Post || 'wpforms' !== $post->post_type ) {
			return;
		}
		$this->emit(
			5801,
			[
				'title'    => (string) $post->post_title,
				'metadata' => [ 'form_id' => $post_id ],
			]
		);
	}
}
