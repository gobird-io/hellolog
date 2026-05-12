<?php
/**
 * WooCommerce: order lifecycle.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * Order lifecycle (9100–9104). Uses WC's high-level hooks so HPOS-mode
 * stores work identically — no direct `$wpdb` reads.
 */
final class WooCommerceOrderSensor extends AbstractSensor {

	public function key(): string {
		return 'woocommerce-order';
	}

	public function should_load(): bool {
		return class_exists( 'WooCommerce', false );
	}

	public function boot(): void {
		add_action( 'woocommerce_new_order', [ $this, 'on_created' ], 10, 1 );
		add_action( 'woocommerce_order_status_changed', [ $this, 'on_status_changed' ], 10, 4 );
		add_action( 'woocommerce_delete_order', [ $this, 'on_deleted' ], 10, 1 );
		add_action( 'woocommerce_order_note_added', [ $this, 'on_note_added' ], 10, 2 );
		add_action( 'woocommerce_order_refunded', [ $this, 'on_refunded' ], 10, 2 );
	}

	public function on_created( int $order_id ): void {
		$this->emit(
			9100,
			[
				'id'       => $order_id,
				'metadata' => [ 'order_id' => $order_id ],
			]
		);
	}

	/**
	 * @param mixed $order
	 */
	public function on_status_changed( int $order_id, string $old, string $new, $order ): void {
		$this->emit(
			9101,
			[
				'id'       => $order_id,
				'old'      => $old,
				'new'      => $new,
				'metadata' => [
					'order_id'   => $order_id,
					'old_status' => $old,
					'new_status' => $new,
				],
			]
		);
	}

	public function on_deleted( int $order_id ): void {
		$this->emit(
			9102,
			[
				'id'       => $order_id,
				'metadata' => [ 'order_id' => $order_id ],
			]
		);
	}

	/**
	 * @param mixed $order
	 */
	public function on_note_added( int $note_id, $order ): void {
		$order_id = is_object( $order ) && method_exists( $order, 'get_id' ) ? (int) $order->get_id() : 0;
		$this->emit(
			9103,
			[
				'id'       => $order_id,
				'metadata' => [
					'order_id' => $order_id,
					'note_id'  => $note_id,
				],
			]
		);
	}

	public function on_refunded( int $order_id, int $refund_id ): void {
		$refund = function_exists( 'wc_get_order' ) ? wc_get_order( $refund_id ) : null;
		$amount = $refund && method_exists( $refund, 'get_amount' ) ? (string) $refund->get_amount() : '';
		$this->emit(
			9104,
			[
				'id'       => $order_id,
				'amount'   => $amount,
				'metadata' => [
					'order_id'  => $order_id,
					'refund_id' => $refund_id,
					'amount'    => $amount,
				],
			]
		);
	}
}
