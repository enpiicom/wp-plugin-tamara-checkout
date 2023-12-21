<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP\Payment_Gateways;

use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

/**
 * Pay in 2 using Tamara Payment Gateway
 * @package Tamara_Checkout\App\WP
 */
class Pay_In_2_WC_Payment_Gateway extends Tamara_WC_Payment_Gateway {

	public function init_settings() {
		parent::init_settings();
		$this->id = Tamara_Checkout_WP_Plugin::TAMARA_GATEWAY_PAY_IN_2;
		$this->payment_type = static::PAYMENT_TYPE_PAY_BY_INSTALMENTS;
		$this->instalment_period = 2;
	}
}
