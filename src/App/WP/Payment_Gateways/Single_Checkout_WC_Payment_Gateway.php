<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP\Payment_Gateways;

use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

/**
 * Single Checkout using Tamara Payment Gateway
 * @package Tamara_Checkout\App\WP
 */
class Single_Checkout_WC_Payment_Gateway extends Tamara_WC_Payment_Gateway {

	/**
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function init_settings() {
		parent::init_settings();
		$this->id = Tamara_Checkout_WP_Plugin::TAMARA_GATEWAY_SINGLE_CHECKOUT_ID;
		$this->title = sprintf($this->_t('Tamara: Split in %d, interest-free'), 4);
	}
}
