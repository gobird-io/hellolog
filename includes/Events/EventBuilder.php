<?php
/**
 * Turns sensor input into the canonical event payload shape.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Events;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the JSON payload that goes onto the outgoing queue and ultimately
 * to `POST /v1/wordpress-activity-audit-log/events`. Keeps sensor code
 * focused on "which event fired with which extra fields", not on payload
 * shape, timestamping, or context capture.
 */
final class EventBuilder {

	public function __construct(
		private EventCatalog $catalog,
		private bool $anonymize_ip = false
	) {
	}

	/**
	 * Build a wire-shape event from a code plus sensor-supplied extras.
	 *
	 * @param int                  $code     Catalog code for the event kind.
	 * @param array<string, mixed> $fields   Optional overrides: user (assoc), post (assoc),
	 *                                       message (string), metadata (assoc), client_event_id (string).
	 * @return array<string, mixed>|null  null if the code is unknown.
	 */
	public function build( int $code, array $fields = [] ): ?array {
		$def = $this->catalog->get( $code );
		if ( null === $def ) {
			return null;
		}

		$ctx = RequestContext::capture( $this->anonymize_ip );

		$payload = [
			'code'        => $def->code,
			'occurred_at' => gmdate( 'Y-m-d\TH:i:s.v\Z' ),
			'severity'    => $def->severity,
			'object'      => $def->object,
			'event_type'  => $def->event_type,
		];

		$user = $this->merge_user( $ctx, $fields['user'] ?? null );
		if ( null !== $user ) {
			$payload['user'] = $user;
		}
		if ( null !== $ctx->client_ip ) {
			$payload['client_ip'] = $ctx->client_ip;
		}
		if ( null !== $ctx->user_agent ) {
			$payload['user_agent'] = $ctx->user_agent;
		}
		if ( null !== $ctx->session_id ) {
			$payload['session_id'] = $ctx->session_id;
		}
		if ( isset( $fields['post'] ) && is_array( $fields['post'] ) ) {
			$payload['post'] = $fields['post'];
		}

		$message            = $fields['message'] ?? $this->render_message( $def->message_template, $fields );
		$payload['message'] = (string) $message;

		$metadata = ( isset( $fields['metadata'] ) && is_array( $fields['metadata'] ) ) ? $fields['metadata'] : [];

		// Surface the post id and title in `metadata` so the read API (which
		// only returns metadata + scalar columns) can link to the edit screen.
		if ( ! empty( $payload['post']['id'] ) && ! isset( $metadata['post_id'] ) ) {
			$metadata['post_id'] = (int) $payload['post']['id'];
		}
		if ( ! empty( $payload['post']['type'] ) && ! isset( $metadata['post_type'] ) ) {
			$metadata['post_type'] = (string) $payload['post']['type'];
		}
		if ( isset( $fields['title'] ) && '' !== $fields['title'] && ! isset( $metadata['post_title'] ) ) {
			$metadata['post_title'] = (string) $fields['title'];
		}

		if ( ! empty( $metadata ) ) {
			$payload['metadata'] = $metadata;
		}
		if ( isset( $fields['client_event_id'] ) && is_string( $fields['client_event_id'] ) ) {
			$payload['client_event_id'] = $fields['client_event_id'];
		}

		return $payload;
	}

	/**
	 * @param array<string, mixed>|null $override
	 * @return array<string, mixed>|null
	 */
	private function merge_user( RequestContext $ctx, ?array $override ): ?array {
		if ( null === $override && null === $ctx->user_id ) {
			return null;
		}

		$user = [
			'id'       => $ctx->user_id,
			'username' => $ctx->username,
			'roles'    => $ctx->roles,
		];
		if ( is_array( $override ) ) {
			$user = array_merge( $user, $override );
		}
		// Drop empty entries to keep the payload compact.
		return array_filter( $user, static fn( $v ) => null !== $v && '' !== $v && [] !== $v );
	}

	/**
	 * Naive `{key}` placeholder substitution. The catalog ships short
	 * English templates; localized rendering happens on the read side.
	 *
	 * @param array<string, mixed> $fields
	 */
	private function render_message( string $template, array $fields ): string {
		if ( '' === $template ) {
			return '';
		}
		$replacements = [];
		foreach ( $fields as $k => $v ) {
			if ( is_scalar( $v ) ) {
				$replacements[ '{' . $k . '}' ] = (string) $v;
			}
		}
		return strtr( $template, $replacements );
	}
}
