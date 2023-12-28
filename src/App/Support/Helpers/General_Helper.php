<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Helpers;

use Tamara_Checkout\App\WP\Payment_Gateways\Pay_Now_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class General_Helper {

	/**
	 * Get store's country code
	 *
	 * @return string
	 */
	public static function get_current_country_code(): string {
		$store_base_country = WC()->countries->get_base_country() ?? Tamara_Checkout_WP_Plugin::DEFAULT_COUNTRY_CODE;
		$currency_country_mapping = static::get_currency_country_mappings();

		return $currency_country_mapping[ strtoupper( get_woocommerce_currency() ) ] ?? $store_base_country;
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
		return static::get_error_map()[ $tamara_message ] ?
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
	 * Get store's current language code
	 *
	 * @return string
	 */
	public static function get_current_language_code(): string {
		$lang = substr( get_locale(), 0, 2 ) ?? 'en';
		return strtolower( $lang );
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
	 * Get store's base country code
	 *
	 * @return string
	 */
	public static function get_store_base_country_code(): string {
		return ! empty( WC()->countries->get_base_country() )
			? WC()->countries->get_base_country()
			: Tamara_Checkout_WP_Plugin::DEFAULT_COUNTRY_CODE;
	}

	/**
	 * @return string
	 */
	public static function get_current_currency(): string {
		return get_woocommerce_currency() ?? 'SAR';
	}

	/**
	 *  Get displayed price of a WC product
	 *
	 * @return float | false
	 */
	public static function get_displayed_product_price() {
		global $product;

		if ( $product ) {
			if ( $product instanceof \WC_Product ) {
				if ( $product instanceof \WC_Product_Variable ) {
					return $product->get_variation_prices( true )['price'];
				} else {
					return wc_get_price_to_display( $product );
				}
			}
		}

		return false;
	}

	/**
	 * Format the amount of money for Tamara SDK
	 *
	 * @param $amount
	 * @param  string  $currency
	 *
	 * @return float
	 */
	public static function format_tamara_number( $amount, $currency = 'SAR' ): float {
		$decimal_digits = static::get_currency_decimal_digits_mappings()[ strtoupper( $currency ) ] ?? 2;
		return floatval( number_format( floatval( $amount ), $decimal_digits, '.', '' ) );
	}

	/**
	 * Format the amount of money for general with 2 decimals
	 *
	 * @param $amount
	 *
	 * @return string
	 */
	public static function format_number_general( $amount ): string {
		return number_format( floatval( $amount ), 2, '.', '' );
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
				&& ( $_GET['section'] === Tamara_Checkout_WP_Plugin::DEFAULT_TAMARA_GATEWAY_ID ) );
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
	 * @return string|void
	 */
	public static function get_tamara_admin_settings_section_url() {
		if ( version_compare( WC()->version, '2.6', '>=' ) ) {
			$section_slug = Tamara_Checkout_WP_Plugin::DEFAULT_TAMARA_GATEWAY_ID;
		} else {
			$section_slug = strtolower( Tamara_WC_Payment_Gateway::class );
		}

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
	}

	public static function is_paid_with_tamara( $payment_method ): bool {
		return strpos(
			       $payment_method,
			       Tamara_Checkout_WP_Plugin::DEFAULT_TAMARA_GATEWAY_ID
		       ) === 0;
	}

	public static function is_live_mode(): bool {
		return ! ! Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_gateway_service()->get_settings()->is_live_mode;
	}

	/**
	 * Handle Tamara log message
	 *
	 * @param $message
	 */
	public static function log_message( $message ): void {
		$tamara_gateway_service = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_gateway_service();
		$gateway_settings = $tamara_gateway_service->get_settings( true );
		if ( ! $gateway_settings->get_custom_log_message_enabled() ) {
			return;
		}
		$formatted_message = is_array( $message ) ? wp_json_encode( $message ) : (string) $message;

		static::write_to_log( $formatted_message );
	}

	/**
	 * Get Tamara log file path
	 *
	 * @return string
	 */
	public static function get_log_message_file_path(): string {
		$upload_dir = defined( 'UPLOADS' ) ? UPLOADS : wp_get_upload_dir()['basedir'];
		$log_file_name = Tamara_Checkout_WP_Plugin::MESSAGE_LOG_FILE_NAME;
		return $upload_dir . DIRECTORY_SEPARATOR . $log_file_name;
	}

	/**
	 * Write message to the log file
	 *
	 * @param string $message
	 */

	protected static function write_to_log( string $message ): void {
		$log_file_path = static::get_log_message_file_path();

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
		file_put_contents( $log_file_path, sprintf( "[%s] %s\n", static::current_date(), $message ), FILE_APPEND );
	}

	/**
	 * Get current date formatted for log
	 *
	 * @return string
	 */
	protected static function current_date(): string {
		return gmdate( 'Y-m-d h:i:s' );
	}
}
