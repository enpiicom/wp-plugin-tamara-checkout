<?php

declare(strict_types=1);

use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

if ( ! function_exists( 'remove_trailing_slashes' ) ) {
	 function remove_trailing_slashes( string $url ): string {
		return rtrim( trim( $url ), '/' );
	}
}

if ( ! function_exists( 'get_default_billing_country_code' ) ) {
	function get_default_billing_country_code(): string {
		return ! empty( get_currency_to_country_mapping()[ get_woocommerce_currency() ] )
			? get_currency_to_country_mapping()[ get_woocommerce_currency() ]
			: get_store_base_country_code();
	}
}

if ( ! function_exists( 'get_currency_to_country_mapping' ) ) {
	function get_currency_to_country_mapping(): array {
		return [
			'SAR' => 'SA',
			'AED' => 'AE',
			'KWD' => 'KW',
			'BHD' => 'BH',
		];
	}
}

if ( ! function_exists( 'get_store_base_country_code' ) ) {
	function get_store_base_country_code() : string
	{
		return ! empty(WC()->countries->get_base_country())
			? WC()->countries->get_base_country()
			: Tamara_Checkout_WP_Plugin::DEFAULT_COUNTRY_CODE;
	}
}
