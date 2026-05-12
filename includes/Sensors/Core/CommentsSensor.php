<?php
/**
 * Sensor: comment lifecycle.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Core;

use HelloLog\Sensors\AbstractSensor;
use WP_Comment;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks comment lifecycle: new, approve / spam / trash transitions,
 * permanent delete, and in-place edits.
 */
final class CommentsSensor extends AbstractSensor {

	public function key(): string {
		return 'core-comments';
	}

	public function boot(): void {
		add_action( 'wp_insert_comment', [ $this, 'on_insert' ], 10, 2 );
		add_action( 'transition_comment_status', [ $this, 'on_status' ], 10, 3 );
		add_action( 'edit_comment', [ $this, 'on_edit' ], 10, 1 );
		add_action( 'deleted_comment', [ $this, 'on_delete' ], 10, 1 );
	}

	public function on_insert( int $id, WP_Comment $comment ): void {
		$this->emit_comment_event( 2200, $comment );
	}

	public function on_status( string $new_status, string $old_status, $comment ): void {
		if ( ! $comment instanceof WP_Comment ) {
			return;
		}
		$code = match ( $new_status ) {
			'approved' => 2201,
			'spam'     => 2202,
			'trash'    => 2203,
			default    => 0,
		};
		if ( 0 === $code ) {
			return;
		}
		$this->emit_comment_event( $code, $comment, [ 'old_status' => $old_status ] );
	}

	public function on_edit( int $id ): void {
		$comment = get_comment( $id );
		if ( $comment instanceof WP_Comment ) {
			$this->emit_comment_event( 2205, $comment );
		}
	}

	public function on_delete( int $id ): void {
		$comment = get_comment( $id );
		if ( $comment instanceof WP_Comment ) {
			$this->emit_comment_event( 2204, $comment );
		}
	}

	/**
	 * @param array<string, mixed> $extra
	 */
	private function emit_comment_event( int $code, WP_Comment $comment, array $extra = [] ): void {
		$post  = $comment->comment_post_ID ? get_post( (int) $comment->comment_post_ID ) : null;
		$title = $post ? (string) $post->post_title : '#' . $comment->comment_post_ID;

		$this->emit(
			$code,
			[
				'post'       => $post
					? [
						'id'   => (int) $post->ID,
						'type' => (string) $post->post_type,
					]
					: null,
				'post_title' => $title,
				'author'     => (string) $comment->comment_author,
				'metadata'   => array_merge(
					[
						'comment_id'      => (int) $comment->comment_ID,
						'comment_author'  => (string) $comment->comment_author,
						'comment_post_id' => (int) $comment->comment_post_ID,
					],
					$extra
				),
			]
		);
	}
}
