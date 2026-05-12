<?php
/**
 * Redirection plugin sensor.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * Redirection (johngodley/redirection) exposes `redirection_redirect_updated`
 * and `redirection_redirect_deleted` actions.
 */
final class RedirectionSensor extends AbstractSensor {

	public function key(): string {
		return 'redirection';
	}

	public function should_load(): bool {
		return defined( 'REDIRECTION_DB_VERSION' );
	}

	public function boot(): void {
		add_action( 'redirection_redirect_updated', [ $this, 'on_updated' ], 10, 2 );
		add_action( 'redirection_redirect_deleted', [ $this, 'on_deleted' ], 10, 1 );
	}

	/**
	 * @param mixed $redirect
	 */
	public function on_updated( int $id, $redirect ): void {
		$source = is_object( $redirect ) && isset( $redirect->url ) ? (string) $redirect->url : '';
		$target = is_object( $redirect ) && isset( $redirect->action_data ) ? (string) $redirect->action_data : '';
		$this->emit(
			5200,
			[
				'source'   => $source,
				'target'   => $target,
				'metadata' => [
					'id'     => $id,
					'source' => $source,
					'target' => $target,
				],
			]
		);
	}

	/**
	 * @param mixed $redirect
	 */
	public function on_deleted( $redirect ): void {
		$this->emit(
			5201,
			[
				'metadata' => [ 'id' => is_object( $redirect ) && isset( $redirect->id ) ? (int) $redirect->id : 0 ],
			]
		);
	}
}
