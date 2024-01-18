<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support;

use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Model\Money;

class Tamara_Checkout_Helper {
	const TAMARA_ORDER_STATUS_APPROVED = 'approved';
	const TAMARA_ORDER_STATUS_AUTHORISED = 'authorised';
	const TAMARA_ORDER_STATUS_PARTIALLY_CAPTURED = 'partially_captured';
	const TAMARA_ORDER_STATUS_FULLY_CAPTURED = 'fully_captured';
	const TAMARA_ORDER_STATUS_CANCELED = 'canceled';
	const TAMARA_ORDER_STATUS_DECLINED = 'declined';
	const TAMARA_ORDER_STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
	const TAMARA_ORDER_STATUS_REFUNDED = 'fully_refunded';

	const TAMARA_EVENT_TYPE_ORDER_APPROVED = 'order_approved';
	const TAMARA_EVENT_TYPE_ORDER_AUTHORISED = 'order_authorised';
	const TAMARA_EVENT_TYPE_ORDER_CANCELED = 'order_canceled';
	const TAMARA_EVENT_TYPE_ORDER_DECLINED = 'order_declined';
	const TAMARA_EVENT_TYPE_ORDER_EXPIRED = 'order_expired';
	const TAMARA_EVENT_TYPE_ORDER_CAPTURED = 'order_captured';
	const TAMARA_EVENT_TYPE_ORDER_REFUNDED = 'order_refunded';

	public static function check_mandatory_prerequisites(): bool {
		return static::check_enpii_base_plugin() && static::check_woocommerce_plugin();
	}

	public static function check_enpii_base_plugin(): bool {
		return ! ! class_exists( \Enpii_Base\App\WP\WP_Application::class );
	}

	public static function check_woocommerce_plugin(): bool {
		return ! ! class_exists( \WooCommerce::class );
	}

	/**
	 * Get country code based on its currency
	 *
	 * @return array
	 */
	public static function get_currency_country_mappings(): array {
		return [
			'SAR' => 'SA',
			'AED' => 'AE',
			'KWD' => 'KW',
			'BHD' => 'BH',
		];
	}

	/**
	 * Get store's country code, upper case
	 *
	 * @return string
	 */
	public static function get_current_country_code(): string {
		$store_base_country = ! empty( WC()->countries->get_base_country() ) ? WC()->countries->get_base_country() : Tamara_Checkout_WP_Plugin::DEFAULT_COUNTRY_CODE;
		$currency_country_mapping = static::get_currency_country_mappings();

		return $currency_country_mapping[ strtoupper( get_woocommerce_currency() ) ] ?? $store_base_country;
	}

	/**
	 * Get store's current language code, lower case
	 *
	 * @return string
	 */
	public static function get_current_language_code(): string {
		$lang = substr( get_locale(), 0, 2 ) ?? 'en';
		return strtolower( $lang );
	}

	/**
	 * @return string
	 */
	public static function get_admin_settings_section_url() {
		if ( version_compare( WC()->version, '2.6', '>=' ) ) {
			$section_slug = Tamara_Checkout_WP_Plugin::DEFAULT_TAMARA_GATEWAY_ID;
		} else {
			$section_slug = strtolower( Tamara_WC_Payment_Gateway::class );
		}

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
	}

	public static function is_tamara_payment_option( $payment_option_id ): bool {
		return strpos( $payment_option_id, 'tamara-gateway' ) === 0;
	}

	/**
	 * Currency decimal digits mapping
	 *
	 * @return array
	 */
	public static function get_currency_decimal_digits_mappings(): array {
		return [
			'SAR' => 2,
			'AED' => 2,
			'KWD' => 4,
			'BHD' => 4,
		];
	}

	/**
	 * Format the amount of money for Tamara SDK
	 *
	 * @param $amount
	 * @param  string  $currency
	 *
	 * @return float
	 */
	public static function format_price_number( $amount, $currency = 'SAR' ): float {
		$decimal_digits = static::get_currency_decimal_digits_mappings()[ strtoupper( $currency ) ] ?? 2;
		return floatval( number_format( floatval( $amount ), $decimal_digits, '.', '' ) );
	}

	/**
	 * Build the Money Object for Tamara API
	 *
	 * @param mixed $amount
	 * @param string $currency
	 * @return Money
	 */
	public static function build_money_object( $amount, $currency = 'SAR' ): Money {
		return new Money(
			static::format_price_number(
				$amount,
				$currency
			),
			$currency
		);
	}

	/**
	 * Get messages translated
	 *
	 * @param $tamara_message
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function convert_message( $tamara_message ): string {
		return ! empty( static::get_error_map()[ $tamara_message ] ) ?
			Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( static::get_error_map()[ $tamara_message ] ) :
			Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( $tamara_message );
	}

	/**
	 * Common error codes when calling create checkout session API
	 *
	 * @throws \Exception
	 */
	public static function get_error_map(): array {
		return [
			'total_amount_invalid_limit_24hrs_gmv' => 'We are not able to process your order via Tamara currently, please try again later or proceed with a different payment method.',
			'tamara_disabled' => 'Tamara is currently unavailable, please try again later.',
			'consumer_invalid_phone_number' => 'Invalid Consumer Phone Number',
			'invalid_phone_number' => 'Invalid Phone Number.',
			'total_amount_invalid_currency' => 'We do not support cross currencies. Please select the correct currency for your country.',
			'billing_address_invalid_phone_number' => 'Invalid Billing Address Phone Number.',
			'shipping_address_invalid_phone_number' => 'Invalid Shipping Address Phone Number.',
			'total_amount_invalid_limit' => 'The grand total of order is over/under limit of Tamara.',
			'currency_unsupported' => 'We do not support cross currencies. Please select the correct currency for your country.',
			'not_supported_delivery_country' => 'We do not support your delivery country.',
		];
	}
}
