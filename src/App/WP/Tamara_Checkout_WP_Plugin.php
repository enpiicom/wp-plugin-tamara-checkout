<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP;

use Enpii_Base\App\Jobs\Show_Admin_Notice_And_Disable_Plugin_Job;
use Enpii_Base\App\WP\WP_Application;
use Enpii_Base\Foundation\WP\WP_Plugin;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tamara_Checkout\App\Jobs\Register_Tamara_Webhook_Job;
use Tamara_Checkout\App\Services\Tamara_Client;
use Tamara_Checkout\App\Services\Tamara_Notification;
use Tamara_Checkout\App\Services\Tamara_Widget;
use Tamara_Checkout\App\VOs\Tamara_WC_Payment_Gateway_Settings_VO;
use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;

/**
 * @inheritDoc
 * @package Tamara_Checkout\App\WP
 * static @method Tamara_Checkout_WP_Plugin wp_app_instance()
 */
class Tamara_Checkout_WP_Plugin extends WP_Plugin {
	public const TEXT_DOMAIN = 'tamara';

	public const DEFAULT_TAMARA_GATEWAY_ID = 'tamara-gateway';

	public function register() {
		$this->register_services();

		parent::register();
	}

	public function manipulate_hooks(): void {
		// We want to use the check prerequisites within the plugins_loaded action
		//  because we need to detect if WooCommerce is loaded or not
		add_action( 'init', [ $this, 'check_prerequisites' ], -100 );

		/** For WooCommerce */
		// Add more payment gateways
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_payment_gateways' ] );

		add_action( 'woocommerce_init', [$this, 'init_woocommerce'] );

		add_action(
			'woocommerce_update_options_payment_gateways_' . static::DEFAULT_TAMARA_GATEWAY_ID,
			[ $this, 'tamara_gateway_process_admin_options' ]
		);

		add_action(
			'woocommerce_update_options_payment_gateways_' . static::DEFAULT_TAMARA_GATEWAY_ID,
			[ $this, 'tamara_gateway_register_webhook' ]
		);
	}

	public function init_woocommerce() {
		// Init default Tamara payment gateway
		wp_app()->singleton(Tamara_WC_Payment_Gateway::class, function (WP_Application $app) {
			return Tamara_WC_Payment_Gateway::instance();
		});
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

	public function get_tamara_client_service() : Tamara_Client {
		return wp_app(Tamara_Client::class);
	}

	public function get_tamara_notification_service() : Tamara_Notification {
		return wp_app(Tamara_Notification::class);
	}

	public function get_tamara_widget_service() : Tamara_Widget {
		return wp_app(Tamara_Widget::class);
	}

	public function get_tamara_gateway_service() : Tamara_WC_Payment_Gateway {
		return wp_app(Tamara_WC_Payment_Gateway::class);
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

	public function tamara_gateway_process_admin_options() {
		$this->get_tamara_gateway_service()->process_admin_options();
	}

	public function tamara_gateway_register_webhook() {
		Register_Tamara_Webhook_Job::dispatch()->onConnection('database')->onQueue('low');
	}

	/**
	 * Modify the available payment gateways by adding more
	 *
	 * @param mixed $gateways array of gateways before the filter
	 * @return array of added payment gateways
	 * @throws BindingResolutionException
	 */
	public function add_payment_gateways( $gateways ) {
		$gateways[] = $this->get_tamara_gateway_service();

		return $gateways;
	}

	/**
	 * We want to register all services with this plugin here
	 *
	 * @return void
	 *
	 */
	protected function register_services(): void {
		$settings = [
			'api_token' => '',
			'notification_key' => '',
			'public_key' => '',
		];

		wp_app()->singleton(Tamara_Client::class, function (WP_Application $app) use ($settings) {
			return Tamara_Client::instance($settings['api_token']);
		});
		wp_app()->singleton(Tamara_Notification::class, function (WP_Application $app) use ($settings) {
			return Tamara_Notification::instance($settings['notification_key']);
		});
		wp_app()->singleton(Tamara_Widget::class, function (WP_Application $app) use ($settings) {
			return Tamara_Widget::instance($settings['public_key']);
		});
	}
}
