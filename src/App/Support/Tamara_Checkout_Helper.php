<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support;

use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Model\Money;

class Tamara_Checkout_Helper {
	public const PLUGIN_NAME = 'Tamara Checkout';
	public const TEXT_DOMAIN = 'tamara';
	public const DEFAULT_TAMARA_GATEWAY_ID = 'tamara-gateway';
	public const DEFAULT_COUNTRY_CODE = 'SA';

	public const TAMARA_ORDER_STATUS_APPROVED = 'approved';
	public const TAMARA_ORDER_STATUS_AUTHORISED = 'authorised';
	public const TAMARA_ORDER_STATUS_PARTIALLY_CAPTURED = 'partially_captured';
	public const TAMARA_ORDER_STATUS_FULLY_CAPTURED = 'fully_captured';
	public const TAMARA_ORDER_STATUS_CANCELED = 'canceled';
	public const TAMARA_ORDER_STATUS_DECLINED = 'declined';
	public const TAMARA_ORDER_STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
	public const TAMARA_ORDER_STATUS_REFUNDED = 'fully_refunded';

	public const TAMARA_EVENT_TYPE_ORDER_APPROVED = 'order_approved';
	public const TAMARA_EVENT_TYPE_ORDER_AUTHORISED = 'order_authorised';
	public const TAMARA_EVENT_TYPE_ORDER_CANCELED = 'order_canceled';
	public const TAMARA_EVENT_TYPE_ORDER_DECLINED = 'order_declined';
	public const TAMARA_EVENT_TYPE_ORDER_EXPIRED = 'order_expired';
	public const TAMARA_EVENT_TYPE_ORDER_CAPTURED = 'order_captured';
	public const TAMARA_EVENT_TYPE_ORDER_REFUNDED = 'order_refunded';

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
		$store_base_country = ! empty( WC()->countries->get_base_country() ) ? WC()->countries->get_base_country() : static::DEFAULT_COUNTRY_CODE;
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
			$section_slug = static::DEFAULT_TAMARA_GATEWAY_ID;
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

	/**
	 * Check if current screen is Tamara Admin Settings page
	 *
	 * @return bool
	 */
	public static function is_tamara_admin_settings_screen(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return ! ! ( is_admin() && isset( $_GET['page'], $_GET['tab'], $_GET['section'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& ( $_GET['page'] === 'wc-settings' )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& ( $_GET['tab'] === 'checkout' )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& ( $_GET['section'] === static::DEFAULT_TAMARA_GATEWAY_ID ) );
	}

	/**
	 * Check if current screen is WC shop order page
	 *
	 * @return bool
	 */
	public static function is_shop_order_screen(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return ! ! ( is_admin() && isset( $_GET['post_type'] ) && ( $_GET['post_type'] === 'shop_order' ) );
	}

	/**
	 *  Get displayed price of a WC product
	 *
	 * @return float | false
	 */
	public static function get_displayed_product_price() {
		global $product;

		if ( $product instanceof \WC_Product_Variable ) {
			return $product->get_variation_prices( true )['price'];
		}

		if ( $product instanceof \WC_Product ) {
			return wc_get_price_to_display( $product );
		}

		return false;
	}

	public static function is_tamara_gateway( $payment_method ): bool {
		return strpos(
			$payment_method,
			static::DEFAULT_TAMARA_GATEWAY_ID
		) === 0;
	}

	/**
	 * Get store's base country code
	 *
	 * @return string
	 */
	public static function get_store_base_country_code(): string {
		return ! empty( WC()->countries->get_base_country() )
			? WC()->countries->get_base_country()
			: static::DEFAULT_COUNTRY_CODE;
	}

	/**
	 * Define which total amount should be used for Tamara order on checkout page
	 *
	 * @param $amount
	 *
	 * @return mixed
	 */
	public static function define_total_amount_to_calculate( $amount ) {
		global $wp;

		if ( is_checkout_pay_page() ) {
			if ( isset( $wp->query_vars['order-pay'] ) && absint( $wp->query_vars['order-pay'] ) > 0 ) {
				$wc_order_id = absint( $wp->query_vars['order-pay'] );
				$wc_order = wc_get_order( $wc_order_id );
				if ( $wc_order ) {
					return $wc_order->get_total();
				}
			}
		}

		return $amount;
	}

	/**
	 * @return array
	 */
	public static function get_current_cart_info(): array {
		$current_cart_total = ! empty( WC()->cart->total ) ? WC()->cart->total : 0;
		$runtime_checkout_data = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_checkout_data_on_runtime();

		$billing_customer_phone = ! empty( WC()->customer->get_billing_phone() ) ? WC()->customer->get_billing_phone() : '';
		$cart_total = static::define_total_amount_to_calculate( $current_cart_total );
		$country_mapping = static::get_currency_country_mappings()[ get_woocommerce_currency() ];
		$country_code = WC()->customer->get_shipping_country() ? WC()->customer->get_shipping_country() : $country_mapping;
		$customer_phone = ! empty( $runtime_checkout_data['billing_phone'] ) ?
			$runtime_checkout_data['billing_phone'] :
			$billing_customer_phone;

		return [
			'cart_total' => $cart_total,
			'customer_phone' => $customer_phone,
			'country_code' => $country_code,
		];
	}
}
