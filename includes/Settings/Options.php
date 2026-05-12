<?php
/**
 * Typed wrapper around the plugin's `hellolog_*` options.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * One source of truth for option keys, defaults, and read/write coercion.
 * The Settings page, the dispatcher, and the transport all go through here
 * so a typo or schema change doesn't silently produce a wrong value.
 *
 * The endpoint URL is fixed across the whole gobird fleet and never
 * stored as an option — see {@see self::endpoint_url()}.
 */
final class Options {

	/**
	 * Production backend URL. Operators only configure the site token;
	 * the endpoint itself is not user-changeable on purpose.
	 */
	public const ENDPOINT_URL = 'https://api.gobird.io/v1/wordpress-activity-audit-log';

	public const KEY_TOKEN          = 'hellolog_token';
	public const KEY_ANONYMIZE_IP   = 'hellolog_anonymize_ip';
	public const KEY_SENSOR_FILTERS = 'hellolog_sensor_filters';

	public function endpoint_url(): string {
		return self::ENDPOINT_URL;
	}

	public function token(): string {
		return (string) get_option( self::KEY_TOKEN, '' );
	}

	/**
	 * Convenience predicate: are we ready to talk to the backend?
	 *
	 * Used to gate the queue dispatcher and the Activity Log admin page,
	 * neither of which has anything sensible to do without a token.
	 * A "non-empty token" is not enough — we also confirm it matches the
	 * backend's expected `goal_<env>_<prefix>_<secret>` shape so a
	 * stray paste (a UUID, a sentence, …) doesn't flip the UI to "Active".
	 */
	public function is_configured(): bool {
		return self::is_valid_token( $this->token() );
	}

	/**
	 * Backend's token shape: `goal_<env>_<prefix>_<secret>`, with
	 * env ∈ {live, test}, prefix exactly 8 chars and secret exactly
	 * 40 chars. Kept in lock-step with `internal/token/token.go::Parse`.
	 */
	public static function is_valid_token( string $token ): bool {
		return 1 === preg_match( '/^goal_(live|test)_[a-z0-9]{8}_[a-z0-9]{40}$/', $token );
	}

	public function anonymize_ip(): bool {
		return (bool) get_option( self::KEY_ANONYMIZE_IP, false );
	}

	/**
	 * @return array<string, bool>
	 */
	public function sensor_filters(): array {
		$raw = get_option( self::KEY_SENSOR_FILTERS, [] );
		if ( ! is_array( $raw ) ) {
			return [];
		}
		$out = [];
		foreach ( $raw as $key => $value ) {
			if ( is_string( $key ) ) {
				$out[ $key ] = (bool) $value;
			}
		}
		return $out;
	}
}
