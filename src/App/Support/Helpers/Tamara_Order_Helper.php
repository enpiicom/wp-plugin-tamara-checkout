<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Helpers;

class Tamara_Order_Helper {

	/**
	 * Detect if an order is authorised from Tamara or not
	 *
	 * @param $wc_order_id
	 *
	 * @return bool
	 */
	public static function is_order_authorised( $wc_order_id ): bool {
		return ! ! get_post_meta( $wc_order_id, '_tamara_authorized', true );
	}

	/**
	 * @param $tamara_order_id
	 * @param $wc_order_id
	 */
	public static function update_tamara_order_id_to_wc_order($tamara_order_id, $wc_order_id): void {
		$wc_order = wc_get_order($wc_order_id);
		if ($wc_order) {
			update_post_meta($wc_order_id, '_payment_method', $wc_order->get_payment_method());
		}
		update_post_meta($wc_order_id, '_tamara_order_id', $tamara_order_id);
	}

	/**
	 * @param $country_code
	 *
	 * @return bool
	 */
	public static function is_supported_country( $country_code ): bool {
		$supported_countries = [ 'SA', 'AE', 'KW', 'BH' ];

		return ! ! in_array( $country_code, $supported_countries );
	}
}
