<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP\Payment_Gateways;

use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class Pay_Next_Month_WC_Payment_Gateway extends Tamara_WC_Payment_Gateway
{
	public function init_settings() {
		parent::init_settings();
		$this->id = Tamara_Checkout_WP_Plugin::TAMARA_GATEWAY_PAY_NEXT_MONTH;
		$this->payment_type = static::PAYMENT_TYPE_PAY_NEXT_MONTH;
	}
}
