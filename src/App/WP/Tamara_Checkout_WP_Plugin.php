<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP;

use Enpii_Base\App\Jobs\Show_Admin_Notice_And_Disable_Plugin_Job;
use Enpii_Base\App\Support\App_Const;
use Enpii_Base\App\WP\WP_Application;
use Enpii_Base\Foundation\WP\WP_Plugin;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tamara_Checkout\App\Jobs\Register_Tamara_Webhook_Job;
use Tamara_Checkout\App\Jobs\Register_Tamara_WP_Api_Routes_Job;
use Tamara_Checkout\App\Services\Tamara_Client;
use Tamara_Checkout\App\Services\Tamara_Notification;
use Tamara_Checkout\App\Services\Tamara_Widget;
use Tamara_Checkout\App\Support\Traits\Tamara_Order_Trait;
use Tamara_Checkout\App\Support\Traits\Wc_Order_Settings_Trait;
use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;

/**
 * @inheritDoc
 * @package Tamara_Checkout\App\WP
 * static @method Tamara_Checkout_WP_Plugin wp_app_instance()
 */
class Tamara_Checkout_WP_Plugin extends WP_Plugin {

	use Tamara_Order_Trait;
	use Wc_Order_Settings_Trait;

	public const TEXT_DOMAIN = 'tamara';
	public const DEFAULT_TAMARA_GATEWAY_ID = 'tamara-gateway';
	public const DEFAULT_COUNTRY_CODE = 'SA';

	public function manipulate_hooks(): void {
		// We want to use the check prerequisites within the plugins_loaded action
		//  because we need to detect if WooCommerce is loaded or not
		add_action( 'init', [ $this, 'check_prerequisites' ], -100 );

		/** For WooCommerce */
		// Add more payment gateways
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_payment_gateways' ] );

		add_action( 'woocommerce_init', [ $this, 'init_woocommerce' ] );

		add_action(
			'woocommerce_update_options_payment_gateways_' . static::DEFAULT_TAMARA_GATEWAY_ID,
			[ $this, 'tamara_gateway_process_admin_options' ]
		);

		add_action(
			'woocommerce_update_options_payment_gateways_' . static::DEFAULT_TAMARA_GATEWAY_ID,
			[ $this, 'tamara_gateway_register_webhook' ]
		);

		add_action( App_Const::ACTION_WP_API_REGISTER_ROUTES, [ $this, 'tamara_gateway_register_wp_api_routes' ] );
	}

	public function init_woocommerce() {
		// Init default Tamara payment gateway
		wp_app()->singleton(
			Tamara_WC_Payment_Gateway::class,
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			function ( WP_Application $app ) {
				return Tamara_WC_Payment_Gateway::instance();
			}
		);

		$this->register_services();
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

	public function get_tamara_client_service(): Tamara_Client {
		return wp_app( Tamara_Client::class );
	}

	public function get_tamara_notification_service(): Tamara_Notification {
		return wp_app( Tamara_Notification::class );
	}

	public function get_tamara_widget_service(): Tamara_Widget {
		return wp_app( Tamara_Widget::class );
	}

	public function get_tamara_gateway_service(): Tamara_WC_Payment_Gateway {
		return wp_app( Tamara_WC_Payment_Gateway::class );
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
			Show_Admin_Notice_And_Disable_Plugin_Job::execute_now( $this, $messages );

			return;
		}
	}

	public function tamara_gateway_process_admin_options() {
		$this->get_tamara_gateway_service()->process_admin_options();
	}

	public function tamara_gateway_register_webhook(): void {
		Register_Tamara_Webhook_Job::dispatch()->onConnection( 'database' )->onQueue( 'low' );
	}

	public function tamara_gateway_register_wp_api_routes(): void {
		Register_Tamara_WP_Api_Routes_Job::execute_now();
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

	public function enqueue_tamara_widget_client_scripts(): void {
		$this->get_tamara_widget_service()->enqueue_client_scripts();
	}

	public function show_tamara_pdp_widget(): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->fetch_tamara_pdp_widget();
	}

	public function fetch_tamara_pdp_widget( $data = null ) {
		return $this->get_tamara_widget_service()->fetch_tamara_pdp_widget( $data );
	}

	public function show_tamara_cart_widget(): void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->fetch_tamara_cart_widget();
	}

	public function fetch_tamara_cart_widget( $data = null ) {
		return $this->get_tamara_widget_service()->fetch_tamara_cart_widget( $data );
	}

	public function show_tamara_footprint(): void {
		echo '<meta name="generator" content="Tamara Checkout ' . esc_attr( $this->get_version() ) . '" />';
	}

	public function adjust_tamara_payment_types_on_checkout($available_gateways): array {
		dev_error_log($available_gateways);
		return $available_gateways;
	}

	/**
	 * We want to register all services with this plugin here
	 *
	 * @return void
	 *
	 */
	protected function register_services(): void {
		$gateway_settings = $this->get_tamara_gateway_service()->get_settings();

		wp_app()->singleton(
			Tamara_Client::class,
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			function ( WP_Application $app ) use ( $gateway_settings ) {
				return Tamara_Client::instance( $gateway_settings->api_token, $gateway_settings->api_url );
			}
		);
		wp_app()->singleton(
			Tamara_Notification::class,
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			function ( WP_Application $app ) use ( $gateway_settings ) {
				return Tamara_Notification::instance( $gateway_settings->notification_key );
			}
		);
		wp_app()->singleton(
			Tamara_Widget::class,
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			function ( WP_Application $app ) use ( $gateway_settings ) {
				return Tamara_Widget::instance( $gateway_settings->public_key, $gateway_settings->is_live_mode );
			}
		);

		$this->manipulate_hooks_after_settings();
	}

	/**
	 *
	 * @return void
	 * @throws BindingResolutionException
	 */
	protected function manipulate_hooks_after_settings(): void {
		if ( $this->get_tamara_gateway_service()->get_settings()->enabled ) {
			if ( ! $this->get_tamara_gateway_service()->get_settings()->popup_widget_disabled ) {
				add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tamara_widget_client_scripts' ], 5 );

				add_action( $this->get_tamara_gateway_service()->get_settings()->popup_widget_position, [ $this, 'show_tamara_pdp_widget' ] );
				add_shortcode( 'tamara_show_popup', [ $this, 'fetch_tamara_pdp_widget' ] );
			}

			if ( ! $this->get_tamara_gateway_service()->get_settings()->cart_popup_widget_disabled ) {
				add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tamara_widget_client_scripts' ], 5 );

				add_action( $this->get_tamara_gateway_service()->get_settings()->cart_popup_widget_position, [ $this, 'show_tamara_cart_widget' ] );
				add_shortcode( 'tamara_show_cart_popup', [ $this, 'fetch_tamara_cart_widget' ] );
			}

			add_action( 'wp_head', [ $this, 'show_tamara_footprint' ] );

			add_filter('woocommerce_available_payment_gateways', [$this, 'adjust_tamara_payment_types_on_checkout'], 9998, 1);
		}
	}
}
