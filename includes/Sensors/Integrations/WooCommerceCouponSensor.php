<?php
/**
 * WooCommerce: coupon lifecycle.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * Coupon CRUD (9200–9202). Coupons live as `shop_coupon` posts, so we
 * also listen to the underlying `before_delete_post` hook for the trash /
 * permanent-delete path.
 */
final class WooCommerceCouponSensor extends AbstractSensor {

	private const POST_TYPE = 'shop_coupon';

	public function key(): string {
		return 'woocommerce-coupon';
	}

	public function should_load(): bool {
		return class_exists( 'WooCommerce', false );
	}

	public function boot(): void {
		add_action( 'woocommerce_new_coupon', [ $this, 'on_created' ], 10, 2 );
		add_action( 'woocommerce_update_coupon', [ $this, 'on_updated' ], 10, 2 );
		add_action( 'before_delete_post', [ $this, 'on_delete_post' ], 10, 2 );
	}

	/**
	 * @param mixed $coupon
	 */
	public function on_created( int $coupon_id, $coupon ): void {
		$this->emit_coupon( 9200, $coupon_id, $coupon );
	}

	/**
	 * @param mixed $coupon
	 */
	public function on_updated( int $coupon_id, $coupon ): void {
		$this->emit_coupon( 9201, $coupon_id, $coupon );
	}

	/**
	 * @param mixed $post
	 */
	public function on_delete_post( int $post_id, $post ): void {
		if ( ! is_object( $post ) || self::POST_TYPE !== ( $post->post_type ?? '' ) ) {
			return;
		}
		$this->emit(
			9202,
			[
				'code'     => (string) $post->post_title,
				'metadata' => [ 'coupon_id' => $post_id ],
			]
		);
	}

	/**
	 * @param mixed $coupon
	 */
	private function emit_coupon( int $code, int $coupon_id, $coupon ): void {
		$code_str = '';
		if ( is_object( $coupon ) && method_exists( $coupon, 'get_code' ) ) {
			$code_str = (string) $coupon->get_code();
		}
		$this->emit(
			$code,
			[
				'code'     => $code_str,
				'metadata' => [ 'coupon_id' => $coupon_id ],
			]
		);
	}
}
