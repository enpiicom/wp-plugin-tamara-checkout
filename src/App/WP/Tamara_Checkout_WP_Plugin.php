<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP;

use Enpii_Base\App\Jobs\Show_Admin_Notice_And_Disable_Plugin_Job;
use Enpii_Base\Foundation\WP\WP_Plugin;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tamara_Checkout\App\Queries\Add_Main_Tamara_Payment_Gateway_Query;
use Tamara_Checkout\App\Services\Tamara_Client;
use Tamara_Checkout\App\Services\Tamara_Notification;
use Tamara_Checkout\App\Services\Tamara_Widget;
use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;

/**
 * @inheritDoc
 * @package Tamara_Checkout\App\WP
 * static @method Tamara_Checkout_WP_Plugin wp_app_instance()
 */
class Tamara_Checkout_WP_Plugin extends WP_Plugin {
	public const TEXT_DOMAIN = 'tamara';
	public const SERVICE_TAMARA_CLIENT = 'tamara-client-service';
	public const SERVICE_TAMARA_NOTIFICATION = 'tamara-notification-service';
	public const SERVICE_TAMARA_WIDGET = 'tamara-widget-service';

	public const DEFAULT_TAMARA_GATEWAY_ID = 'tamara-gateway';

	public function register() {
		$this->register_services();

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

		add_action(
			'woocommerce_update_options_payment_gateways_' . static::DEFAULT_TAMARA_GATEWAY_ID,
			[ $this, 'tamara_gateway_process_admin_options' ]
		);
	}

	public function init_woocommerce() {
		// Init default Tamara payment gateway
		Tamara_WC_Payment_Gateway::init_instance( new Tamara_WC_Payment_Gateway() );
		wp_app()->instance(static::DEFAULT_TAMARA_GATEWAY_ID, Tamara_WC_Payment_Gateway::instance());
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
		return wp_app(static::SERVICE_TAMARA_CLIENT);
	}

	public function get_tamara_notification_service() : Tamara_Notification {
		return wp_app(static::SERVICE_TAMARA_NOTIFICATION);
	}

	public function get_tamara_widget_service() : Tamara_Widget {
		return wp_app(static::SERVICE_TAMARA_WIDGET);
	}

	public function get_tamara_gateway_service() : Tamara_WC_Payment_Gateway {
		return wp_app(static::DEFAULT_TAMARA_GATEWAY_ID);
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
		$setting_configs = [
			'api_token' => '',
			'notification_key' => '',
			'public_key' => '',
		];

		$this->set_up_tamara_client_service($setting_configs['api_token']);
		$this->set_up_tamara_notification_service($setting_configs['notification_key']);
		$this->set_up_tamara_widget_service($setting_configs['public_key']);
	}

	protected function set_up_tamara_client_service($api_token) {
		Tamara_Client::init_wp_app_instance($api_token);
		wp_app()->instance(Tamara_Checkout_WP_Plugin::SERVICE_TAMARA_CLIENT, Tamara_Client::instance());
	}

	protected function set_up_tamara_notification_service($notification_key) {
		Tamara_Notification::init_wp_app_instance($notification_key);
		wp_app()->instance(Tamara_Checkout_WP_Plugin::SERVICE_TAMARA_NOTIFICATION, Tamara_Notification::instance());
	}

	protected function set_up_tamara_widget_service($public_key) {
		Tamara_Widget::init_wp_app_instance($public_key, Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->get_working_mode());
		wp_app()->instance(Tamara_Checkout_WP_Plugin::SERVICE_TAMARA_WIDGET, Tamara_Widget::instance());
	}
}
