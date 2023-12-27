<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Helpers;

use DateTimeImmutable;
use Enpii_Base\App\Exceptions\Simple_Exception;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Support\Traits\Trans_Trait;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Model\ShippingInfo;
use Tamara_Checkout\Deps\Tamara\Request\Order\GetOrderByReferenceIdRequest;
use Tamara_Checkout\Deps\Tamara\Response\Order\GetOrderByReferenceIdResponse;

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
	public static function update_tamara_order_id_to_wc_order( $tamara_order_id, $wc_order_id ): void {
		$wc_order = wc_get_order( $wc_order_id );
		if ( $wc_order ) {
			update_post_meta( $wc_order_id, '_payment_method', $wc_order->get_payment_method() );
		}
		update_post_meta( $wc_order_id, '_tamara_order_id', $tamara_order_id );
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

	/**
	 * @param $wc_order_id
	 */
	public static function update_tamara_payment_type_to_wc_order( $wc_order_id ) {
		$payment_type = '';
		update_post_meta( $wc_order_id, '_tamara_payment_type', $payment_type );
	}

	/**
	 * Get Tamara shipping information
	 */
	public static function get_tamara_shipping_info() : ShippingInfo {
		$shipped_at = new DateTimeImmutable();
		$shipping_company = 'N/A';
		$tracking_number = 'N/A';
		$tracking_url = 'N/A';

		return new ShippingInfo($shipped_at, $shipping_company, $tracking_number, $tracking_url);
	}
}
