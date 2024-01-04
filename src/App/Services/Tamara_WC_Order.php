<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use WC_Order;

/**
 * A service for Tamara related WC Orders
 * @package Tamara_Checkout\App\Services
 */
class Tamara_WC_Order {
	use Static_Instance_Trait;

	public function is_paid_with_tamara(WC_Order $wc_order): bool {
		$payment_method = $wc_order->get_payment_method();

		return strpos(
			$payment_method,
			Tamara_Checkout_WP_Plugin::DEFAULT_TAMARA_GATEWAY_ID
		) === 0;
	}

	public function get_tamara_order_id(WC_Order $wc_order): bool {
		$tamara_order_id = get_post_meta( $wc_order->get_id(), '_tamara_order_id' );
		if ( ! $tamara_order_id ) {
			$tamara_order_id = get_post_meta( $wc_order->get_id(), 'tamara_order_id' );
		}

		return get_post_meta( $wc_order->get_id(), '_tamara_order_id' );;
	}
}
