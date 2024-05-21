<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP;

use Closure;
use Enpii_Base\App\Support\Traits\Queue_Trait;
use Enpii_Base\App\WP\WP_Application;
use Enpii_Base\Foundation\WP\WP_Plugin;
use Exception;
use RuntimeException;
use Tamara_Checkout\App\Jobs\Cancel_Tamara_Order_If_Possible_Job;
use Tamara_Checkout\App\Jobs\Capture_Tamara_Order_If_Possible_Job;
use Tamara_Checkout\App\Jobs\Refund_Tamara_Order_If_Possible_Job;
use Tamara_Checkout\App\Jobs\Register_Tamara_Custom_Order_Statuses;
use Tamara_Checkout\App\Jobs\Register_Tamara_Webhook_Job;
use Tamara_Checkout\App\Jobs\Register_Tamara_WP_Api_Routes;
use Tamara_Checkout\App\Jobs\Register_Tamara_WP_App_Routes;
use Tamara_Checkout\App\Queries\Add_Tamara_Custom_Statuses;
use Tamara_Checkout\App\Queries\Get_Cart_Products;
use Tamara_Checkout\App\Queries\Get_Tamara_Payment_Options;
use Tamara_Checkout\App\Repositories\WC_Order_Repository;
use Tamara_Checkout\App\Repositories\WC_Order_Repository_Contract;
use Tamara_Checkout\App\Repositories\WC_Order_Woo7_Repository;
use Tamara_Checkout\App\Services\Tamara_Client;
use Tamara_Checkout\App\Services\Tamara_Notification;
use Tamara_Checkout\App\Services\Tamara_Widget;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_Block_Support_WC_Payment_Method;
use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;
use Tamara_Checkout\Deps\Tamara\Model\Money;

/**
 * @inheritDoc
 * @package Tamara_Checkout\App\WP
 * @method static Tamara_Checkout_WP_Plugin wp_app_instance()
 */
class Tamara_Checkout_WP_Plugin extends WP_Plugin {
	use Queue_Trait;
	use Tamara_Trans_Trait;

	protected $checkout_data_on_runtime = [];

	public function manipulate_hooks(): void {
		add_action( 'init', [ $this, 'register_tamara_custom_order_statuses' ] );
		add_action( 'woocommerce_init', [ $this, 'load_text_domain' ] );

		/** For WooCommerce */
		// Add more payment gateways
		add_filter( 'plugin_action_links_' . $this->get_plugin_basename(), [ $this, 'add_plugin_settings_link' ] );
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_payment_gateways' ] );
		add_action( 'woocommerce_init', [ $this, 'init_woocommerce' ] );

		add_action(
			'woocommerce_update_options_payment_gateways_' . Tamara_Checkout_Helper::DEFAULT_TAMARA_GATEWAY_ID,
			[ $this, 'tamara_gateway_process_admin_options' ],
			10
		);
		add_action(
			'woocommerce_update_options_payment_gateways_' . Tamara_Checkout_Helper::DEFAULT_TAMARA_GATEWAY_ID,
			[ $this, 'tamara_gateway_register_webhook' ],
			11
		);

		// Add Tamara custom statuses to wc order status list
		add_filter( 'wc_order_statuses', [ $this, 'add_tamara_custom_order_statuses' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tamara_general_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_tamara_admin_scripts' ] );

		// For WP App
		wp_app()->register_api_routes( [ $this, 'tamara_gateway_register_wp_api_routes' ] );
		wp_app()->register_routes( [ $this, 'tamara_gateway_register_wp_app_routes' ] );
	}

	/**
	 * We register/d
	 * @return void
	 */
	public function manipulate_hooks_after_settings(): void {
		if ( $this->get_tamara_gateway_service()->get_settings_vo()->enabled ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tamara_widget_client_scripts' ], 5 );

			add_shortcode( 'tamara_show_popup', [ $this, 'fetch_tamara_pdp_widget' ] );
			if ( ! $this->get_tamara_gateway_service()->get_settings_vo()->popup_widget_disabled ) {
				add_action( $this->get_tamara_gateway_service()->get_settings_vo()->popup_widget_position, [ $this, 'show_tamara_pdp_widget' ] );
			}

			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tamara_widget_client_scripts' ], 5 );
			add_shortcode( 'tamara_show_cart_popup', [ $this, 'fetch_tamara_cart_widget' ] );
			if ( ! $this->get_tamara_gateway_service()->get_settings_vo()->cart_popup_widget_disabled ) {
				add_action( $this->get_tamara_gateway_service()->get_settings_vo()->cart_popup_widget_position, [ $this, 'show_tamara_cart_widget' ] );
			}

			add_action(
				'wp_head',
				[ $this, 'show_tamara_footprint' ]
			);
			add_action(
				'woocommerce_checkout_update_order_review',
				[ $this, 'build_checkout_data_on_runtime' ]
			);
			add_filter(
				'woocommerce_available_payment_gateways',
				[ $this, 'adjust_tamara_payment_types_on_checkout' ],
				9998,
				1
			);
			add_filter(
				'woocommerce_gateway_description',
				[ $this, 'render_payment_types_description_on_checkout' ],
				9999,
				2
			);
			add_filter(
				'woocommerce_thankyou_order_received_text',
				[ $this, 'fetch_tamara_order_received_note' ],
				10,
				2
			);
			add_action(
				'woocommerce_order_status_changed',
				[ $this, 'capture_tamara_order_if_possible' ],
				10,
				4
			);
			add_action(
				'woocommerce_order_status_changed',
				[ $this, 'cancel_tamara_order_if_possible' ],
				10,
				4
			);
			add_action(
				'woocommerce_create_refund',
				[ $this, 'refund_tamara_order_if_possible' ],
				10,
				2
			);

			if ( $this->get_tamara_gateway_service()->get_settings_vo()->force_checkout_phone ) {
				add_filter(
					'woocommerce_billing_fields',
					[ $this, 'force_billing_address_phone_field' ],
					1001,
					2
				);
				add_filter(
					'woocommerce_shipping_fields',
					[ $this, 'force_shipping_address_phone_field' ],
					1001,
					2
				);
			}

			if ( $this->get_tamara_gateway_service()->get_settings_vo()->force_checkout_email ) {
				add_filter(
					'woocommerce_billing_fields',
					[ $this, 'force_billing_address_email_field' ],
					1001,
					2
				);
				add_filter(
					'woocommerce_shipping_fields',
					[ $this, 'force_shipping_address_email_field' ],
					1001,
					2
				);
			}
		}
	}

	public function init_woocommerce() {
		$this->register_services();
		$this->manipulate_hooks_after_settings();
	}

	public function get_name(): string {
		return 'Tamara Checkout';
	}

	public function get_version(): string {
		return TAMARA_CHECKOUT_VERSION;
	}

	public function get_text_domain(): string {
		return \Tamara_Checkout\App\Support\Tamara_Checkout_Helper::TEXT_DOMAIN;
	}

	public function provides() {
		return [
			Tamara_WC_Payment_Gateway::class,
			Tamara_Client::class,
			Tamara_Notification::class,
			Tamara_Widget::class,
			WC_Order_Repository_Contract::class,
		];
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

	public function get_checkout_data_on_runtime() {
		return $this->checkout_data_on_runtime;
	}

	public function tamara_gateway_process_admin_options() {
		$this->get_tamara_gateway_service()->process_admin_options();
	}

	public function tamara_gateway_register_webhook(): void {
		try {
			Register_Tamara_Webhook_Job::dispatchSync();
		} catch ( Exception $e ) {
			// We want to re-perform this job 7 mins later
			$this->enqueue_job_later( Register_Tamara_Webhook_Job::dispatch() );

		}
	}

	public function tamara_gateway_register_wp_app_routes(): void {
		Register_Tamara_WP_App_Routes::execute_now();
	}

	public function tamara_gateway_register_wp_api_routes(): void {
		Register_Tamara_WP_Api_Routes::execute_now();
	}

	/**
	 * Modify the available payment gateways by adding more
	 *
	 * @param mixed $gateways array of gateways before the filter
	 * @return array of added payment gateways
	 */
	public function add_payment_gateways( $gateways ): array {
		$gateways[] = $this->get_tamara_gateway_service();

		return $this->adjust_tamara_payment_types_on_checkout( $gateways );
	}

	public function enqueue_tamara_general_scripts(): void {
		$this->get_tamara_gateway_service()->enqueue_general_scripts();
	}

	public function enqueue_tamara_admin_scripts(): void {
		$this->get_tamara_gateway_service()->enqueue_admin_scripts();
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

	public function fetch_tamara_order_received_note( $order_note, $wc_order ) {
		return $this->get_tamara_widget_service()->fetch_tamara_order_received_note( $order_note, $wc_order );
	}

	/**
	 * @throws \Exception
	 */
	public function render_payment_types_description_on_checkout( $description, $id ): string {
		return $this->get_tamara_widget_service()->fetch_tamara_checkout_widget( $description, $id );
	}

	public function show_tamara_footprint(): void {
		echo '<meta name="generator" content="Tamara Checkout ' . esc_attr( $this->get_version() ) . '" />';
	}

	/**
	 * @param $available_gateways
	 *
	 * @return array
	 */
	public function adjust_tamara_payment_types_on_checkout( $available_gateways ): array {
		if ( is_checkout() && $this->get_tamara_gateway_service()->get_settings_vo()->enabled ) {
			list($cart_product_ids, $cart_product_category_ids) = Get_Cart_Products::execute_now();

			$product_valid = ( count(
				array_intersect(
					$cart_product_ids,
					$this->get_tamara_gateway_service()->get_settings_vo()->excluded_products
				)
			) < 1 );
			$product_category_valid = ( count(
				array_intersect(
					$cart_product_category_ids,
					$this->get_tamara_gateway_service()->get_settings_vo()->excluded_product_categories
				)
			) < 1 );

			if ( ! $product_valid || ! $product_category_valid ) {
				$tamara_default_gateway_instance = $this->get_tamara_gateway_service();
				$tamara_default_gateway_index = (int) array_search(
					$tamara_default_gateway_instance,
					$available_gateways
				);
				unset( $available_gateways[ $tamara_default_gateway_index ] );

				return $available_gateways;
			}

			$current_cart_info = Tamara_Checkout_Helper::get_current_cart_info();
			$cart_total = $current_cart_info['cart_total'] ?? 0;
			$customer_phone = $current_cart_info['customer_phone'] ?? '';

			$country_code = ! empty( $current_cart_info['country_code'] )
				? $current_cart_info['country_code']
				: Tamara_Checkout_Helper::DEFAULT_COUNTRY_CODE;
			$currency_by_country_code = array_flip( Tamara_Checkout_Helper::get_currency_country_mappings() );

			if ( ! empty( $currency_by_country_code[ $country_code ] ) ) {
				$currency_code = $currency_by_country_code[ $country_code ];
				$order_total = new Money(
					Tamara_Checkout_Helper::format_price_number( $cart_total, $currency_code ),
					$currency_code
				);
				// return $available_gateways;
				return Get_Tamara_Payment_Options::execute_now(
					[
						'available_gateways' => $available_gateways,
						'order_total' => $order_total,
						'country_code' => $country_code,
						'customer_phone' => $customer_phone,
					]
				);
			}

			unset( $available_gateways[ Tamara_Checkout_Helper::DEFAULT_TAMARA_GATEWAY_ID ] );
		}

		return $available_gateways;
	}

	/**
	 * Register Tamara new statuses
	 *
	 * @throws \Exception
	 */
	public function register_tamara_custom_order_statuses() {
		Register_Tamara_Custom_Order_Statuses::execute_now();
	}

	/**
	 * Add Tamara Statuses to the list of WC Order statuses
	 *
	 * @param  array  $order_statuses
	 *
	 * @return array $order_statuses
	 * @throws \Exception
	 */
	public function add_tamara_custom_order_statuses( array $order_statuses ): array {
		return Add_Tamara_Custom_Statuses::execute_now( $order_statuses );
	}

	/**
	 * Add the Tamara Settings links to plugin links
	 * @param mixed $plugin_links
	 * @return array
	 * @throws Exception
	 */
	public function add_plugin_settings_link( $plugin_links ) {
		$settings_link = '<a href="' . Tamara_Checkout_Helper::get_admin_settings_section_url() . '">' . $this->__( 'Settings' ) . '</a>';
		array_unshift( $plugin_links, $settings_link );

		return $plugin_links;
	}

	/**
	 * We need to fetch checkout data (from post submit everytime the data is changed)
	 *
	 * @param $posted_data
	 *
	 * @return void
	 */
	public function build_checkout_data_on_runtime( $posted_data ): void {
		// Parsing posted data on checkout
		$post = [];
		$vars = explode( '&', $posted_data );
		foreach ( $vars as $k => $value ) {
			$v = explode( '=', urldecode( $value ) );
			$post[ $v[0] ] = $v[1];
		}

		$this->checkout_data_on_runtime = $post;
	}

	/**
	 * Tamara Capture Payment
	 *
	 * @param $wc_order_id
	 * @param $status_from
	 * @param $status_to
	 * @param $wc_order
	 *
	 * @return void
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException|\Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	public function capture_tamara_order_if_possible( $wc_order_id, $status_from, $status_to, $wc_order ) {
		$to_capture_status = $this->get_tamara_gateway_service()->get_settings_vo()->order_status_to_capture_tamara_payment;

		if ( $to_capture_status !== 'wc-' . $status_to ) {
			return;
		}

		$args = [
			'wc_order_id' => $wc_order_id,
			'status_from' => $status_from,
			'status_to' => $status_to,
			'to_capture_status' => $to_capture_status,
		];

		try {
			Capture_Tamara_Order_If_Possible_Job::dispatchSync( $args );
		} catch ( Exception $e ) {
			$this->enqueue_job_later( Capture_Tamara_Order_If_Possible_Job::dispatch( $args ) );
		}
	}

	/**
	 * Tamara Cancel Order
	 *
	 * @param $wc_order_id
	 * @param $status_from
	 * @param $status_to
	 * @param $wc_order
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException|\Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	public function cancel_tamara_order_if_possible( $wc_order_id, $status_from, $status_to, $wc_order ) {
		$to_cancel_status = $this->get_tamara_gateway_service()->get_settings_vo()->order_status_to_cancel_tamara_payment;

		if ( $to_cancel_status !== 'wc-' . $status_to ) {
			return;
		}

		$args = [
			'wc_order_id' => $wc_order_id,
			'status_from' => $status_from,
			'status_to' => $status_to,
			'to_cancel_status' => $to_cancel_status,
		];
		try {
			Cancel_Tamara_Order_If_Possible_Job::dispatchSync( $args );
		} catch ( Exception $e ) {
			$this->enqueue_job_later( Cancel_Tamara_Order_If_Possible_Job::dispatch( $args ) );
		}
	}

	/**
	 * Process the Tamara Refund
	 * @param \WC_Order_Refund $wc_order_refund
	 * @param array $refund_args
	 * @return void
	 * @throws RuntimeException
	 */
	public function refund_tamara_order_if_possible( $wc_order_refund, $refund_args ): void {
		$args = [
			'wc_order_refund' => $wc_order_refund,
			'wc_order_id' => $refund_args['order_id'],
			'refund_args' => $refund_args,
		];
		try {
			Refund_Tamara_Order_If_Possible_Job::dispatchSync( $args );
		} catch ( Exception $e ) {
			$this->enqueue_job_later( Refund_Tamara_Order_If_Possible_Job::dispatch( $args ) );
		}
	}

	/**
	 * As Phone field is mandatory for Tamara checking out
	 *  therefore, we need to put back the default Phone field
	 * @param mixed $fields
	 * @param mixed $country
	 * @return array
	 */
	public function force_billing_address_phone_field( $fields, $country ): array {
		if ( is_wc_endpoint_url( 'edit-address' ) || ! empty( $fields['billing_phone'] ) ) {
			return $fields;
		} elseif ( empty( $fields['billing_phone'] ) ) {
			$fields['billing_phone'] = [
				'label' => __( 'Phone', 'woocommerce' ),
				'required' => true,
			];

			return $fields;
		}

		return $fields;
	}

	/**
	 * As Phone field is mandatory for Tamara checking out
	 *  therefore, we need to put back the default Phone field
	 * @param mixed $fields
	 * @param mixed $country
	 * @return array
	 */
	public function force_shipping_address_phone_field( $fields, $country ): array {
		if ( is_wc_endpoint_url( 'edit-address' ) || ! empty( $fields['shipping_phone'] ) ) {
			return $fields;
		} elseif ( empty( $fields['shipping_phone'] ) ) {
			$fields['shipping_phone'] = [
				'label' => __( 'Phone', 'woocommerce' ),
				'required' => true,
			];

			return $fields;
		}

		return $fields;
	}

	/**
	 * As Email field is mandatory for Tamara checking out
	 *  therefore, we need to put back the default Email field
	 * @param mixed $fields
	 * @param mixed $country
	 * @return array
	 */
	public function force_billing_address_email_field( $fields, $country ): array {
		if ( is_wc_endpoint_url( 'edit-address' ) || ! empty( $fields['billing_email'] ) ) {
			return $fields;
		} elseif ( empty( $fields['billing_email'] ) ) {
			$fields['billing_email'] = [
				'label' => __( 'Email', 'woocommerce' ),
				'required' => true,
			];

			return $fields;
		}

		return $fields;
	}

	/**
	 * As Email field is mandatory for Tamara checking out
	 *  therefore, we need to put back the default Email field
	 * @param mixed $fields
	 * @param mixed $country
	 * @return array
	 */
	public function force_shipping_address_email_field( $fields, $country ): array {
		if ( is_wc_endpoint_url( 'edit-address' ) || ! empty( $fields['shipping_email'] ) ) {
			return $fields;
		} elseif ( empty( $fields['shipping_email'] ) ) {
			$fields['shipping_email'] = [
				'label' => __( 'Email', 'woocommerce' ),
				'required' => true,
			];

			return $fields;
		}

		return $fields;
	}

	public function add_block_support_for_payment_methods() {
		if ( class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new Tamara_Block_Support_WC_Payment_Method() );
				}
			);
		}
	}

	/**
	 * Localize the plugin
	 */
	public function load_text_domain() {
		$locale = determine_locale();
		$mofile = $locale . '.mo';
		$text_domain = 'tamara';
		load_textdomain( $text_domain, $this->get_base_path() . '/languages/' . $text_domain . '-' . $mofile );
	}

	/**
	 * We want to register all services with this plugin here
	 *
	 * @return void
	 *
	 */
	protected function register_services(): void {
		// Init default Tamara payment gateway
		//  We need to do this first before other services
		wp_app()->singleton(
			Tamara_WC_Payment_Gateway::class,
			Closure::fromCallable( [ $this, 'build_tamara_gateway_instance' ] )
		);
		wp_app()->singleton(
			Tamara_Client::class,
			Closure::fromCallable( [ $this, 'build_tamara_client_instance' ] )
		);
		wp_app()->singleton(
			Tamara_Notification::class,
			Closure::fromCallable( [ $this, 'build_tamara_notification_instance' ] )
		);
		wp_app()->singleton(
			Tamara_Widget::class,
			Closure::fromCallable( [ $this, 'build_tamara_widget_instance' ] )
		);
		wp_app()->singleton(
			WC_Order_Repository_Contract::class,
			Closure::fromCallable( [ $this, 'build_wc_order_repository_instance' ] )
		);
	}

	protected function build_tamara_gateway_instance( WP_Application $app ) {
		return Tamara_WC_Payment_Gateway::instance();
	}

	protected function build_tamara_client_instance() {
		$gateway_settings = $this->get_tamara_gateway_service()->get_settings_vo();

		return Tamara_Client::instance( $gateway_settings->api_token, $gateway_settings->api_url );
	}

	protected function build_tamara_notification_instance() {
		$gateway_settings = $this->get_tamara_gateway_service()->get_settings_vo();

		return Tamara_Notification::instance( $gateway_settings->notification_key );
	}

	protected function build_tamara_widget_instance() {
		$gateway_settings = $this->get_tamara_gateway_service()->get_settings_vo();

		return Tamara_Widget::instance( $gateway_settings->public_key, $gateway_settings->is_live_mode );
	}

	protected function build_wc_order_repository_instance() {
		if ( version_compare( WC()->version, '8.0.0', '<' ) ) {
			return new WC_Order_Woo7_Repository( get_current_blog_id() );
		}

		return new WC_Order_Repository( get_current_blog_id() );
	}
}
