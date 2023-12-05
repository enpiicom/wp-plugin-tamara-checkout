<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP;

use Enpii_Base\App\Jobs\Show_Admin_Notice_And_Disable_Plugin_Job;
use Enpii_Base\Foundation\WP\WP_Plugin;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tamara_Checkout\App\Queries\Add_Main_Tamara_Payment_Gateway_Query;
use Tamara_Checkout\App\WooCommerce\Payment_Gateways\Pay_In_3_WC_Payment_Gateway;
use Tamara_Checkout\App\WooCommerce\Payment_Gateways\Tamara_WC_Payment_Gateway;

/**
 * @inheritDoc
 * @package Tamara_Checkout\App\WP
 */
class Tamara_Checkout_WP_Plugin extends WP_Plugin {
	public const TEXT_DOMAIN = 'tamara';
	public const DEFAULT_TAMARA_PAYMENT_GATEWAY_ID = 'tamara-gateway';

	public function manipulate_hooks(): void {
		// We want to use the check prerequisites within the plugins_loaded action
		//  because we need to detect if WooCommerce is loaded or not
		add_action( 'plugins_loaded', [ $this, 'check_prerequisites' ] );

		/** For WooCommerce */
		// Add more payment gateways
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_payment_gateways' ] );
	}

	public function get_name(): string {
		return 'Tamara Checkout';
	}

	public function get_version(): string {
		return TAMARA_CHECKOUT_VERSION;
	}

	public function get_text_domain(): string {
		return static::TEXT_DOMAIN;
	}

	/**
	 * We want to check the needed dependency for this plugin to work
	 */
	public function check_prerequisites() {
		if ( ! class_exists( \WooCommerce::class ) ) {
			$messages = [
				sprintf(
					$this->_t( 'Plugin <strong>%s</strong> needs WooCommerce to work, please install and activate WooCommerce as well.' ),
					$this->get_name() . ' ' . $this->get_version()
				),
			];
			Show_Admin_Notice_And_Disable_Plugin_Job::dispatchSync( $this, $messages );

			return;
		}
	}

	/**
	 * Modify the available payment gateways by adding more
	 *
	 * @param mixed $gateways array of gateways before the filter
	 * @return array of added payment gateways
	 * @throws BindingResolutionException
	 */
	public function add_payment_gateways( $gateways ) {
		return Add_Main_Tamara_Payment_Gateway_Query::dispatchSync( $gateways );
	}
}
