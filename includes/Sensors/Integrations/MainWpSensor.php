<?php
/**
 * MainWP Dashboard sensor.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * MainWP child-site add/remove on the dashboard side. The MainWP plugin
 * fires `mainwp_added_new_website` and `mainwp_delete_site` actions.
 */
final class MainWpSensor extends AbstractSensor {

	public function key(): string {
		return 'mainwp';
	}

	public function should_load(): bool {
		return class_exists( 'MainWP\Dashboard\MainWP', false );
	}

	public function boot(): void {
		add_action( 'mainwp_added_new_website', [ $this, 'on_added' ], 10, 1 );
		add_action( 'mainwp_delete_site', [ $this, 'on_deleted' ], 10, 1 );
	}

	/**
	 * @param mixed $website
	 */
	public function on_added( $website ): void {
		$url = is_object( $website ) && isset( $website->url ) ? (string) $website->url : '';
		$this->emit(
			7700,
			[
				'url'      => $url,
				'metadata' => [ 'url' => $url ],
			]
		);
	}

	/**
	 * @param mixed $website
	 */
	public function on_deleted( $website ): void {
		$url = is_object( $website ) && isset( $website->url ) ? (string) $website->url : '';
		$this->emit(
			7701,
			[
				'url'      => $url,
				'metadata' => [ 'url' => $url ],
			]
		);
	}
}
