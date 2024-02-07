<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP\Payment_Gateways;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class Tamara_Block_Support_WC_Payment_Method extends AbstractPaymentMethodType {
	/**
	 * The gateway instance.
	 *
	 * @var Tamara_WC_Payment_Gateway
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'tamara-gateway';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_tamara-gateway_settings', [] );
		$gateways = WC()->payment_gateways->payment_gateways();
		$this->gateway = $gateways[ $this->name ];
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path = '/public-assets/dist/blocks/js/frontend/blocks.js';
		$script_asset_path = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_base_path() . '/public-assets/dist/blocks/js/frontend/blocks.asset.php';
		$script_asset = file_exists( $script_asset_path )
			? require $script_asset_path 
			: [
				'dependencies' => [],
				'version' => TAMARA_CHECKOUT_VERSION,
			];
		$script_url = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_base_url() . $script_path;

		wp_register_script(
			'wc-tamara-checkout-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-tamara-checkout-blocks', 'woocommerce-gateway-dummy', Tamara_Checkout_WP_Plugin::wp_app_instance()->get_base_path() . '/languages/' );
		}

		return [ 'wc-tamara-checkout-blocks' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] ),
		];
	}
}
