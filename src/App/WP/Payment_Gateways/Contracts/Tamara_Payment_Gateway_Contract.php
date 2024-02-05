<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP\Payment_Gateways\Contracts;

/**
 * Base payment gateway method for Tamara
 * @package Tamara_Checkout\App\WP
 */
interface Tamara_Payment_Gateway_Contract {
	/**
	 * Get payment type for a Tamara Payment Gateway
	 * @return string
	 */
	public function get_payment_type(): string;
}
