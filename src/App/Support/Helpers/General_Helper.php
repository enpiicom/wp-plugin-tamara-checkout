<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Helpers;

use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class General_Helper {

	public function get_default_billing_country_code(): string {
		return ! empty( $this->get_currency_to_country_mapping()[ get_woocommerce_currency() ] )
			? $this->get_currency_to_country_mapping()[ get_woocommerce_currency() ]
			: $this->get_store_base_country_code();
	}

	public static function convert_message( $tamara_message ): string {
		return Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( $tamara_message );
	}

	/**
	 * Common error codes when calling create checkout session API
	 */
	public static function get_error_map(): array {
		return [
			'total_amount_invalid_limit_24hrs_gmv' => Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'We are not able to process your order via Tamara currently, please try again later or proceed with a different payment method.' ),
			'tamara_disabled' => Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'Tamara is currently unavailable, please try again later.' ),
			'consumer_invalid_phone_number' => Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'Invalid Consumer Phone Number' ),
			'invalid_phone_number' => Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'Invalid Phone Number.' ),
			'total_amount_invalid_currency' => Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'We do not support cross currencies. Please select the correct currency for your country.' ),
			'billing_address_invalid_phone_number' => Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'Invalid Billing Address Phone Number.' ),
			'shipping_address_invalid_phone_number' => Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'Invalid Shipping Address Phone Number.' ),
			'total_amount_invalid_limit' => Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'The grand total of order is over/under limit of Tamara.' ),
			'currency_unsupported' => Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'We do not support cross currencies. Please select the correct currency for your country.' ),
			'Your order information is invalid' => Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'Your order information is invalid.' ),
			'Invalid country code' => Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'Invalid country code.' ),
			'We do not support your delivery country' => Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'We do not support your delivery country.' ),
			'Your phone number is invalid. Please check again' => Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'Your phone number is invalid. Please check again.' ),
			'We do not support cross currencies. Please select the correct currency for your country' => Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'We do not support cross currencies. Please select the correct currency for your country.' ),
		];
	}

	/**
	 * Remove trailing slashes of an url
	 *
	 * @param  string  $url
	 *
	 * @return string
	 */
	public static function remove_trailing_slashes( string $url ): string {
		return rtrim( trim( $url ), '/' );
	}

	/**
	 * Get country code based on its currency
	 *
	 * @return array
	 */
	public static function get_currency_to_country_mapping(): array {
		return [
			'SAR' => 'SA',
			'AED' => 'AE',
			'KWD' => 'KW',
			'BHD' => 'BH',
		];
	}

	/**
	 * Get store base country code
	 *
	 * @return string
	 */
	public static function get_store_base_country_code(): string {
		return ! empty( WC()->countries->get_base_country() )
			? WC()->countries->get_base_country()
			: Tamara_Checkout_WP_Plugin::DEFAULT_COUNTRY_CODE;
	}

	/**
	 * Format the amount of money for Tamara SDK
	 *
	 * @param $amount
	 *
	 * @return float
	 */
	public static function format_tamara_number( $amount ): float {
		return floatval( number_format( $amount, 2, '.', '' ) );
	}

	/**
	 * Format the amount of money for general with 2 decimals
	 *
	 * @param $amount
	 *
	 * @return string
	 */
	public static function format_number_general( $amount ): string {
		return number_format( $amount, 2, '.', '' );
	}
}
