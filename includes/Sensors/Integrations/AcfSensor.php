<?php
/**
 * Advanced Custom Fields sensor.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * ACF stores field groups as `acf-field-group` CPT entries. We hook the
 * canonical `acf/update_field_group` action and the underlying CPT delete
 * path.
 */
final class AcfSensor extends AbstractSensor {

	private const POST_TYPE = 'acf-field-group';

	public function key(): string {
		return 'acf';
	}

	public function should_load(): bool {
		return class_exists( 'ACF', false );
	}

	public function boot(): void {
		add_action( 'acf/update_field_group', [ $this, 'on_update' ], 10, 1 );
		add_action( 'before_delete_post', [ $this, 'on_delete_post' ], 10, 2 );
	}

	/**
	 * @param array<string, mixed> $field_group
	 */
	public function on_update( array $field_group ): void {
		$is_new = empty( $field_group['ID'] );
		$this->emit(
			$is_new ? 5400 : 5401,
			[
				'title'    => (string) ( $field_group['title'] ?? '' ),
				'metadata' => [
					'field_group_id' => (int) ( $field_group['ID'] ?? 0 ),
					'key'            => (string) ( $field_group['key'] ?? '' ),
				],
			]
		);
	}

	/**
	 * @param mixed $post
	 */
	public function on_delete_post( int $post_id, $post ): void {
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return;
		}
		$this->emit(
			5402,
			[
				'title'    => (string) $post->post_title,
				'metadata' => [ 'field_group_id' => $post_id ],
			]
		);
	}
}
