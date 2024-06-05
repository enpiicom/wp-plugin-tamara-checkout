<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP\Payment_Gateways;

class Pay_Now_WC_Payment_Gateway extends WC_Tamara_Payment_Type {
	public $id = 'tamara-gateway-pay-now';

	public function get_checkout_widget_type() {
		return 'tamara-card-snippet';
	}
}
