<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WooCommerce\Payment_Gateways;

use Tamara_Checkout\App\WooCommerce\Payment_Gateways\Contracts\Tamara_Payment_Gateway_Contract;

/**
 * Pay in 3 using Tamara Payment Gateway
 * @package Tamara_Checkout\App\WooCommerce
 */
class Pay_In_3_WC_Payment_Gateway extends Tamara_WC_Payment_Gateway implements Tamara_Payment_Gateway_Contract {
	public $id = 'tamara-gateway-pay-in-3';

	public function get_payment_type(): string
	{
		return parent::get_payment_type();
	}
}
