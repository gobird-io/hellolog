<?php
/**
 * WooCommerce: product lifecycle.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * Picks up the canonical WC product-save hook plus the dedicated
 * price/stock filters so the operator can see those changes as
 * distinct events (codes 9003 / 9004) instead of one generic update.
 */
final class WooCommerceProductSensor extends AbstractSensor {

	private const POST_TYPE = 'product';

	public function key(): string {
		return 'woocommerce-product';
	}

	public function should_load(): bool {
		return class_exists( 'WooCommerce', false );
	}

	public function boot(): void {
		add_action( 'woocommerce_new_product', [ $this, 'on_create' ], 10, 1 );
		add_action( 'woocommerce_update_product', [ $this, 'on_update' ], 10, 1 );
		add_action( 'before_delete_post', [ $this, 'on_delete_post' ], 10, 2 );
		add_action( 'woocommerce_product_set_stock', [ $this, 'on_stock_changed' ], 10, 1 );
		add_action( 'woocommerce_product_object_updated_props', [ $this, 'on_props_updated' ], 10, 2 );
	}

	public function on_create( int $product_id ): void {
		$this->emit_product( 9000, $product_id );
	}

	public function on_update( int $product_id ): void {
		$this->emit_product( 9001, $product_id, [ 'fields' => 'general' ] );
	}

	/**
	 * @param mixed $post
	 */
	public function on_delete_post( int $post_id, $post ): void {
		if ( ! is_object( $post ) || self::POST_TYPE !== ( $post->post_type ?? '' ) ) {
			return;
		}
		$this->emit_product( 9002, $post_id );
	}

	/**
	 * @param mixed $product
	 */
	public function on_stock_changed( $product ): void {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return;
		}
		$this->emit(
			9004,
			[
				'name' => $product->get_name(),
				'old'  => '',
				'new'  => (string) $product->get_stock_quantity(),
				'post' => [
					'id'   => (int) $product->get_id(),
					'type' => self::POST_TYPE,
				],
			]
		);
	}

	/**
	 * @param mixed              $product
	 * @param array<int, string> $updated_props
	 */
	public function on_props_updated( $product, array $updated_props ): void {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return;
		}
		if ( in_array( 'price', $updated_props, true ) || in_array( 'regular_price', $updated_props, true ) ) {
			$this->emit(
				9003,
				[
					'name' => $product->get_name(),
					'old'  => '',
					'new'  => (string) $product->get_price(),
					'post' => [
						'id'   => (int) $product->get_id(),
						'type' => self::POST_TYPE,
					],
				]
			);
		}
	}

	/**
	 * @param array<string, mixed> $extra
	 */
	private function emit_product( int $code, int $product_id, array $extra = [] ): void {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		$name    = $product && method_exists( $product, 'get_name' ) ? (string) $product->get_name() : '#' . $product_id;
		$this->emit(
			$code,
			array_merge(
				[
					'name' => $name,
					'post' => [
						'id'   => $product_id,
						'type' => self::POST_TYPE,
					],
				],
				$extra
			)
		);
	}
}
