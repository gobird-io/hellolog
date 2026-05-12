<?php
/**
 * Registry of audit event definitions.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Events;

defined( 'ABSPATH' ) || exit;

/**
 * Look-up of {@see EventDefinition} entries by integer code.
 *
 * Sensor modules call {@see self::register()} during boot to add their codes.
 * Keeping the registry runtime-extensible lets each integration ship its own
 * event definitions while the catalog stays small and predictable in memory.
 *
 * Code ranges follow WSAL conventions so prior knowledge transfers:
 *
 *   1000–1099  Authentication / sessions
 *   2000–2099  Content (posts / pages / CPT)
 *   2100–2199  Taxonomies
 *   2200–2299  Comments
 *   2300–2499  Menus & widgets
 *   4000–4499  User profile, 2FA, app passwords, multisite
 *   5000–5199  Plugins & themes
 *   5200–5599  3rd-party plugin events (Redirection, PMP, etc.)
 *   5700–5899  Form plugins (Gravity, WPForms)
 *   6000–6399  Settings, system, files
 *   7100–7199  Database
 *   8000–8999  bbPress, SEO, etc.
 *   9000–9499  WooCommerce
 */
final class EventCatalog {

	/** @var array<int, EventDefinition> */
	private array $definitions = [];

	public function register( EventDefinition $definition ): void {
		$this->definitions[ $definition->code ] = $definition;
	}

	public function get( int $code ): ?EventDefinition {
		return $this->definitions[ $code ] ?? null;
	}

	/**
	 * @return array<int, EventDefinition>
	 */
	public function all(): array {
		return $this->definitions;
	}

	public function has( int $code ): bool {
		return isset( $this->definitions[ $code ] );
	}
}
