<?php
/**
 * Sensor: posts, pages, and custom post types.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Core;

use HelloLog\Sensors\AbstractSensor;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks the post lifecycle: creation, publish / unpublish, trash, restore,
 * permanent delete, and ordinary updates. We follow {@see transition_post_status}
 * for state changes and {@see post_updated} for in-place content edits.
 *
 * Ignores noisy / system post types ({@see self::IGNORED_TYPES}); each
 * integration sensor can hook its own CPTs separately if needed.
 */
final class ContentSensor extends AbstractSensor {

	private const IGNORED_TYPES = [
		'attachment',
		'revision',
		'nav_menu_item',
		'customize_changeset',
		'custom_css',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_navigation',
	];

	public function key(): string {
		return 'core-content';
	}

	public function boot(): void {
		add_action( 'transition_post_status', [ $this, 'on_status' ], 10, 3 );
		add_action( 'post_updated', [ $this, 'on_updated' ], 10, 3 );
		add_action( 'before_delete_post', [ $this, 'on_delete' ], 10, 2 );
		add_action( 'untrashed_post', [ $this, 'on_untrashed' ], 10, 2 );
	}

	public function on_status( string $new_status, string $old_status, WP_Post $post ): void {
		if ( ! $this->should_track( $post ) ) {
			return;
		}
		if ( $new_status === $old_status ) {
			return;
		}

		if ( 'auto-draft' === $old_status && 'auto-draft' !== $new_status ) {
			$this->emit_post_event( 2000, $post );
			return;
		}
		if ( 'publish' === $new_status && 'publish' !== $old_status ) {
			$this->emit_post_event( 2001, $post );
			return;
		}
		if ( 'publish' === $old_status && 'publish' !== $new_status && 'trash' !== $new_status ) {
			$this->emit_post_event( 2002, $post );
			return;
		}
		if ( 'trash' === $new_status ) {
			$this->emit_post_event( 2003, $post );
		}
	}

	public function on_untrashed( int $post_id, string $previous_status ): void {
		$post = get_post( $post_id );
		if ( $post instanceof WP_Post && $this->should_track( $post ) ) {
			$this->emit_post_event( 2004, $post, [ 'previous_status' => $previous_status ] );
		}
	}

	public function on_delete( int $post_id, WP_Post $post ): void {
		if ( ! $this->should_track( $post ) ) {
			return;
		}
		// `before_delete_post` only fires for permanent deletes — trash uses
		// `wp_trash_post` which we already handle via `transition_post_status`.
		$this->emit_post_event( 2005, $post );
	}

	public function on_updated( int $post_id, WP_Post $after, WP_Post $before ): void {
		if ( ! $this->should_track( $after ) ) {
			return;
		}
		if ( $after->post_status !== $before->post_status ) {
			return; // status changes are handled by `transition_post_status`
		}
		if ( $after->post_modified === $before->post_modified ) {
			return; // no real change
		}
		$this->emit_post_event(
			2006,
			$after,
			[
				'changed' => $this->diff_fields( $before, $after ),
			]
		);
	}

	private function should_track( WP_Post $post ): bool {
		return ! in_array( $post->post_type, self::IGNORED_TYPES, true );
	}

	/**
	 * @param array<string, mixed> $extra
	 */
	private function emit_post_event( int $code, WP_Post $post, array $extra = [] ): void {
		$this->emit(
			$code,
			[
				'post'      => [
					'id'     => (int) $post->ID,
					'type'   => (string) $post->post_type,
					'status' => (string) $post->post_status,
				],
				'title'     => (string) $post->post_title,
				'post_type' => ucfirst( str_replace( [ '_', '-' ], ' ', (string) $post->post_type ) ),
				'metadata'  => array_merge(
					[
						'slug'   => (string) $post->post_name,
						'author' => (int) $post->post_author,
					],
					$extra
				),
			]
		);
	}

	/**
	 * @return array<int, string>
	 */
	private function diff_fields( WP_Post $before, WP_Post $after ): array {
		$fields  = [ 'post_title', 'post_content', 'post_excerpt', 'post_name', 'post_parent', 'post_author', 'comment_status', 'ping_status' ];
		$changed = [];
		foreach ( $fields as $field ) {
			if ( $before->$field !== $after->$field ) {
				$changed[] = $field;
			}
		}
		return $changed;
	}
}
