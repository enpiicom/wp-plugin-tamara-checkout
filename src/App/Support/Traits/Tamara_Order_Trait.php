<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Traits;

trait Tamara_Order_Trait {
	/**
	 * Common error codes when calling create checkout session API
	 */
	protected function get_error_map(): array {
		return [
			'total_amount_invalid_limit_24hrs_gmv' => $this->_t( 'We are not able to process your order via Tamara currently, please try again later or proceed with a different payment method.' ),
			'tamara_disabled' => $this->_t( 'Tamara is currently unavailable, please try again later.' ),
			'consumer_invalid_phone_number' => $this->_t( 'Invalid Consumer Phone Number' ),
			'invalid_phone_number' => $this->_t( 'Invalid Phone Number.' ),
			'total_amount_invalid_currency' => $this->_t( 'We do not support cross currencies. Please select the correct currency for your country.' ),
			'billing_address_invalid_phone_number' => $this->_t( 'Invalid Billing Address Phone Number.' ),
			'shipping_address_invalid_phone_number' => $this->_t( 'Invalid Shipping Address Phone Number.' ),
			'total_amount_invalid_limit' => $this->_t( 'The grand total of order is over/under limit of Tamara.' ),
			'currency_unsupported' => $this->_t( 'We do not support cross currencies. Please select the correct currency for your country.' ),
			'Your order information is invalid' => $this->_t( 'Your order information is invalid.' ),
			'Invalid country code' => $this->_t( 'Invalid country code.' ),
			'We do not support your delivery country' => $this->_t( 'We do not support your delivery country.' ),
			'Your phone number is invalid. Please check again' => $this->_t( 'Your phone number is invalid. Please check again.' ),
			'We do not support cross currencies. Please select the correct currency for your country' => $this->_t( 'We do not support cross currencies. Please select the correct currency for your country.' ),
		];
	}
}
