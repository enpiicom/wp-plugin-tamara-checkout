<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Traits;

trait Wc_Order_Settings_Trait {
	
	public function get_default_billing_country_code(): string {
		return ! empty( $this->get_currency_to_country_mapping()[ get_woocommerce_currency() ] )
			? $this->get_currency_to_country_mapping()[ get_woocommerce_currency() ]
			: $this->get_store_base_country_code();
	}

	/**
	 * Get country code based on its currency
	 *
	 * @return array
	 */
	public function get_currency_to_country_mapping(): array {
		return [
			'SAR' => 'SA',
			'AED' => 'AE',
			'KWD' => 'KW',
			'BHD' => 'BH',
		];
	}

	public function get_store_base_country_code(): string {
		return ! empty( WC()->countries->get_base_country() )
			? WC()->countries->get_base_country()
			: static::DEFAULT_COUNTRY_CODE;
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public function remove_trailing_slashes( $url ): string {
		return rtrim( trim( $url ), '/' );
	}
}
