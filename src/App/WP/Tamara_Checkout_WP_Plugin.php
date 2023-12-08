<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP;

use Enpii_Base\App\Jobs\Show_Admin_Notice_And_Disable_Plugin_Job;
use Enpii_Base\Foundation\WP\WP_Plugin;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tamara_Checkout\App\Queries\Add_Main_Tamara_Payment_Gateway_Query;
use Tamara_Checkout\App\Support\Tamara_Client;
use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;
use Tamara_Checkout\Deps\Tamara\Client;
use Tamara_Checkout\Deps\Tamara\Configuration;
use Tamara_Checkout\Deps\Tamara\HttpClient\ClientInterface;
use Tamara_Checkout\Deps\Tamara\HttpClient\GuzzleHttpAdapter;

/**
 * @inheritDoc
 * @package Tamara_Checkout\App\WP
 * @method static Tamara_Checkout_WP_Plugin wp_app_instance()
 */
class Tamara_Checkout_WP_Plugin extends WP_Plugin {
	public const TEXT_DOMAIN = 'tamara';
	public const SERVICE_TAMARA_CLIENT = 'tamara-client-service';
	public const SERVICE_TAMARA_NOTIFICATION = 'tamara-notification-service';
	public const SERVICE_TAMARA_WIDGET = 'tamara-widget-service';

	public const DEFAULT_TAMARA_GATEWAY_ID = 'tamara-gateway';

	public function get_tamara_client_service() : Tamara_Client {
		return wp_app(static::SERVICE_TAMARA_CLIENT);
	}

	public function get_tamara_notification_service() : Tamara_Client {
		return wp_app(static::SERVICE_TAMARA_NOTIFICATION);
	}

	public function get_tamara_widget_service() : Tamara_Client {
		return wp_app(static::SERVICE_TAMARA_WIDGET);
	}

	public function get_tamara_gateway_service() : Tamara_WC_Payment_Gateway {
		return wp_app(static::DEFAULT_TAMARA_GATEWAY_ID);
	}

	public function wc_tamara_gateway_service() : Tamara_WC_Payment_Gateway {
		if (empty(wp_app()->has(static::DEFAULT_TAMARA_GATEWAY_ID))) {
			Tamara_WC_Payment_Gateway::init_instance(
				new Tamara_WC_Payment_Gateway(),
				true
			);
			wp_app()->instance(static::DEFAULT_TAMARA_GATEWAY_ID, Tamara_WC_Payment_Gateway::instance());
		}
		return wp_app(static::DEFAULT_TAMARA_GATEWAY_ID);
	}

	public function register() {
		parent::register();
	}

	public function manipulate_hooks(): void {
		// We want to use the check prerequisites within the plugins_loaded action
		//  because we need to detect if WooCommerce is loaded or not
		add_action( 'plugins_loaded', [ $this, 'check_prerequisites' ] );

		/** For WooCommerce */
		// Add more payment gateways
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_payment_gateways' ] );

		add_action('woocommerce_init', [$this, 'init_woocommerce'] );
	}

	public function init_woocommerce() {
		$api_token = $this->wc_tamara_gateway_service()->settings['sandbox_api_token'] ?? null;
		if (!empty($api_token)) {
			Tamara_Client::init_wp_app_instance($api_token);
		}

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->wc_tamara_gateway_service()->id,
			[ $this->wc_tamara_gateway_service(), 'process_admin_options' ], 10, 1 );
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
