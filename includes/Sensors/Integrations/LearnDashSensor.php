<?php
/**
 * LearnDash sensor.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * LearnDash content CPTs: `sfwd-courses`, `sfwd-lessons`, `sfwd-topic`,
 * `sfwd-quiz`. Plus the enrollment hook.
 */
final class LearnDashSensor extends AbstractSensor {

	private const TYPES = [ 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ];

	public function key(): string {
		return 'learndash';
	}

	public function should_load(): bool {
		return defined( 'LEARNDASH_VERSION' );
	}

	public function boot(): void {
		add_action( 'transition_post_status', [ $this, 'on_status' ], 10, 3 );
		add_action( 'ld_added_course_access', [ $this, 'on_enrollment' ], 10, 2 );
	}

	public function on_status( string $new_status, string $old_status, WP_Post $post ): void {
		if ( ! in_array( $post->post_type, self::TYPES, true ) ) {
			return;
		}
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}
		$this->emit(
			8200,
			[
				'title'    => (string) $post->post_title,
				'sub_type' => str_replace( 'sfwd-', '', (string) $post->post_type ),
				'post'     => [
					'id'   => (int) $post->ID,
					'type' => (string) $post->post_type,
				],
			]
		);
	}

	public function on_enrollment( int $user_id, int $course_id ): void {
		$user   = get_userdata( $user_id );
		$course = get_post( $course_id );
		$this->emit(
			8201,
			[
				'username' => $user ? (string) $user->user_login : '#' . $user_id,
				'course'   => $course ? (string) $course->post_title : '#' . $course_id,
				'metadata' => [
					'user_id'   => $user_id,
					'course_id' => $course_id,
				],
			]
		);
	}
}
