<?php
/**
 * Sensor registry — gates and boots the active integrations.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors;

defined( 'ABSPATH' ) || exit;

/**
 * Holds every {@see AbstractSensor} the plugin knows about and wires the
 * ones that should run on this request:
 *
 *   - skipped if {@see AbstractSensor::should_load()} returns false (e.g.
 *     WooCommerce sensor on a site without WooCommerce),
 *   - skipped if the operator turned that sensor off in the settings
 *     (`hellolog_sensor_filters` option, keyed by {@see AbstractSensor::key()}).
 *
 * Keeps `boot()` idempotent so the manager can be safely re-run during
 * tests or after late registrations from third-party code.
 */
final class SensorManager {

	/** @var array<string, AbstractSensor> */
	private array $sensors = [];

	/** @var array<string, bool> */
	private array $disabled = [];

	private bool $booted = false;

	public function register( AbstractSensor $sensor ): void {
		$this->sensors[ $sensor->key() ] = $sensor;
	}

	/**
	 * Mark the given sensor keys as disabled by the operator.
	 *
	 * @param array<int, string> $keys
	 */
	public function disable( array $keys ): void {
		foreach ( $keys as $key ) {
			if ( is_string( $key ) && '' !== $key ) {
				$this->disabled[ $key ] = true;
			}
		}
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		foreach ( $this->sensors as $key => $sensor ) {
			if ( isset( $this->disabled[ $key ] ) ) {
				continue;
			}
			if ( ! $sensor->should_load() ) {
				continue;
			}
			$sensor->boot();
		}
	}

	/**
	 * @return array<string, AbstractSensor>
	 */
	public function sensors(): array {
		return $this->sensors;
	}
}
