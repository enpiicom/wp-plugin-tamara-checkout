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
	const TAMARA_ORDER_STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
	const TAMARA_ORDER_STATUS_REFUNDED = 'fully_refunded';

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
}
