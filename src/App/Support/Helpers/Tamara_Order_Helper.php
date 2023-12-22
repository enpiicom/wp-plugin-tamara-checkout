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
	 * @param $country_code
	 *
	 * @return bool
	 */
	public static function is_supported_country( $country_code ): bool {
		$supported_countries = [ 'SA', 'AE', 'KW', 'BH' ];

		return ! ! in_array( $country_code, $supported_countries );
	}
}
