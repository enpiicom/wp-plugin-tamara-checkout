<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Enpii_Base\Foundation\Bus\Dispatchable_Trait;
use Enpii_Base\Foundation\Shared\Base_Query;
use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class Add_Main_Tamara_Payment_Gateway_Query extends Base_Query {
	use Dispatchable_Trait;

	protected $gateways;

	public function __construct( $gateways ) {
		$this->gateways = $gateways;
	}

	public function handle() {
		$gateways = $this->gateways;
		$gateways[] = Tamara_Checkout_WP_Plugin::wp_app_instance()->wc_tamara_gateway_service();

		return $gateways;
	}
}
