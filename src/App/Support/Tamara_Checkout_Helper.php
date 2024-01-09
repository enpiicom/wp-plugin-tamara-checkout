<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support;

use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class Tamara_Checkout_Helper {
	const TAMARA_ORDER_STATUS_AUTHORISED = 'authorised';
	const TAMARA_ORDER_STATUS_PARTIALLY_CAPTURED = 'partially_captured';
	const TAMARA_ORDER_STATUS_FULLY_CAPTURED = 'fully_captured';
	const TAMARA_ORDER_STATUS_CANCELED = 'canceled';
	const TAMARA_ORDER_STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
	const TAMARA_ORDER_STATUS_REFUNDED = 'fully_refunded';

	public static function check_mandatory_prerequisites(): bool {
		return static::check_enpii_base_plugin() && static::check_woocommerce_plugin();
	}

	public static function check_enpii_base_plugin(): bool {
		return ! ! class_exists( \Enpii_Base\App\WP\WP_Application::class );
	}

	public static function check_woocommerce_plugin(): bool {
		return ! ! class_exists( \WooCommerce::class );
	}

	/**
	 * @return string
	 */
	public static function get_admin_settings_section_url() {
		if ( version_compare( WC()->version, '2.6', '>=' ) ) {
			$section_slug = Tamara_Checkout_WP_Plugin::DEFAULT_TAMARA_GATEWAY_ID;
		} else {
			$section_slug = strtolower( Tamara_WC_Payment_Gateway::class );
		}

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
	}
}
