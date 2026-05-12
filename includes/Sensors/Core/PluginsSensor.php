<?php
/**
 * Sensor: plugin install/activate/deactivate/update/delete.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Core;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * Drives the plugin-lifecycle codes (5000–5004). Uses the standard WP
 * hooks; `upgrader_process_complete` covers both fresh install and
 * update because both flow through Plugin_Upgrader.
 */
final class PluginsSensor extends AbstractSensor {

	public function key(): string {
		return 'core-plugins';
	}

	public function boot(): void {
		add_action( 'activated_plugin', [ $this, 'on_activated' ], 10, 2 );
		add_action( 'deactivated_plugin', [ $this, 'on_deactivated' ], 10, 2 );
		add_action( 'deleted_plugin', [ $this, 'on_deleted' ], 10, 2 );
		add_action( 'upgrader_process_complete', [ $this, 'on_upgrader' ], 10, 2 );
	}

	public function on_activated( string $plugin, bool $network_wide ): void {
		$this->emit(
			5001,
			[
				'name'     => $this->name( $plugin ),
				'metadata' => [
					'plugin_file'  => $plugin,
					'network_wide' => $network_wide,
				],
			]
		);
	}

	public function on_deactivated( string $plugin, bool $network_wide ): void {
		$this->emit(
			5002,
			[
				'name'     => $this->name( $plugin ),
				'metadata' => [
					'plugin_file'  => $plugin,
					'network_wide' => $network_wide,
				],
			]
		);
	}

	/**
	 * @param mixed $error
	 */
	public function on_deleted( string $plugin, $error ): void {
		if ( true !== $error ) {
			return;
		}
		$this->emit(
			5004,
			[
				'name'     => $this->name( $plugin ),
				'metadata' => [ 'plugin_file' => $plugin ],
			]
		);
	}

	/**
	 * @param mixed                $upgrader
	 * @param array<string, mixed> $hook_extra
	 */
	public function on_upgrader( $upgrader, array $hook_extra ): void {
		if ( 'plugin' !== ( $hook_extra['type'] ?? '' ) ) {
			return;
		}
		$action  = (string) ( $hook_extra['action'] ?? '' );
		$plugins = (array) ( $hook_extra['plugins'] ?? [] );
		if ( empty( $plugins ) && isset( $hook_extra['plugin'] ) ) {
			$plugins = [ (string) $hook_extra['plugin'] ];
		}
		$code = 'install' === $action ? 5000 : 5003;
		foreach ( $plugins as $plugin ) {
			$plugin = (string) $plugin;
			$this->emit(
				$code,
				[
					'name'     => $this->name( $plugin ),
					'version'  => $this->version( $plugin ),
					'metadata' => [
						'plugin_file' => $plugin,
						'action'      => $action,
					],
				]
			);
		}
	}

	private function name( string $plugin_file ): string {
		$data = $this->plugin_data( $plugin_file );
		return $data['Name'] ?? $plugin_file;
	}

	private function version( string $plugin_file ): string {
		$data = $this->plugin_data( $plugin_file );
		return $data['Version'] ?? '';
	}

	/**
	 * @return array<string, string>
	 */
	private function plugin_data( string $plugin_file ): array {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$path = WP_PLUGIN_DIR . '/' . $plugin_file;
		if ( ! file_exists( $path ) ) {
			return [];
		}
		return get_plugin_data( $path, false, false );
	}
}
