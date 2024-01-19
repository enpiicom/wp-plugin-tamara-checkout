<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP;

use Enpii_Base\App\Support\App_Const;
use Enpii_Base\App\Support\Traits\Queue_Trait;
use Enpii_Base\App\WP\WP_Application;
use Enpii_Base\Foundation\WP\WP_Plugin;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use RuntimeException;
use Tamara_Checkout\App\Jobs\Authorise_Tamara_Stuck_Approved_Orders_Job;
use Tamara_Checkout\App\Jobs\Cancel_Tamara_Order_If_Possible_Job;
use Tamara_Checkout\App\Jobs\Capture_Tamara_Order_If_Possible_Job;
use Tamara_Checkout\App\Jobs\Capture_Tamara_Stuck_Authorised_Orders_Job;
use Tamara_Checkout\App\Jobs\Refund_Tamara_Order_If_Possible_Job;
use Tamara_Checkout\App\Jobs\Register_Tamara_Custom_Order_Statuses_Job;
use Tamara_Checkout\App\Jobs\Register_Tamara_Webhook_Job;
use Tamara_Checkout\App\Jobs\Register_Tamara_WP_Api_Routes_Job;
use Tamara_Checkout\App\Jobs\Register_Tamara_WP_App_Routes_Job;
use Tamara_Checkout\App\Queries\Add_Tamara_Custom_Statuses_Query;
use Tamara_Checkout\App\Queries\Get_Tamara_Payment_Options_Query;
use Tamara_Checkout\App\Services\Tamara_Client;
use Tamara_Checkout\App\Services\Tamara_Notification;
use Tamara_Checkout\App\Services\Tamara_Widget;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;
use Tamara_Checkout\Deps\Tamara\Model\Money;

/**
 * @inheritDoc
 * @package Tamara_Checkout\App\WP
 * @method static Tamara_Checkout_WP_Plugin wp_app_instance()
 */
class Tamara_Checkout_WP_Plugin extends WP_Plugin {
	use Queue_Trait;

	public const DEFAULT_TAMARA_GATEWAY_ID = 'tamara-gateway';
	public const DEFAULT_COUNTRY_CODE = 'SA';
	public const MESSAGE_LOG_FILE_NAME = 'tamara-custom.log';

	public const TAMARA_AUTHORISED_STATUS = 'authorised',
				TAMARA_CANCELED_STATUS = 'canceled',
				TAMARA_PARTIALLY_CAPTURED_STATUS = 'partially_captured',
				TAMARA_FULLY_CAPTURED_STATUS = 'fully_captured',
				TAMARA_PARTIALLY_REFUNDED_STATUS = 'partially_refunded',
				TAMARA_FULLY_REFUNDED_STATUS = 'fully_refunded';

	const TAMARA_CHECKOUT = 'tamara-checkout';

	protected $customer_phone_number;

	public function manipulate_hooks(): void {
		add_action( 'init', [ $this, 'register_tamara_custom_order_statuses' ] );

		/** For WooCommerce */
		// Add more payment gateways
		add_filter( 'plugin_action_links_' . $this->get_plugin_basename(), [ $this, 'add_plugin_settings_link' ] );
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_payment_gateways' ] );
		add_action( 'woocommerce_init', [ $this, 'init_woocommerce' ] );

		add_action(
			'woocommerce_update_options_payment_gateways_' . static::DEFAULT_TAMARA_GATEWAY_ID,
			[ $this, 'tamara_gateway_process_admin_options' ],
			10
		);
		add_action(
			'woocommerce_update_options_payment_gateways_' . static::DEFAULT_TAMARA_GATEWAY_ID,
			[ $this, 'tamara_gateway_register_webhook' ],
			11
		);

		// Add Tamara custom statuses to wc order status list
		add_filter( 'wc_order_statuses', [ $this, 'add_tamara_custom_order_statuses' ] );
		add_action( 'wp_loaded', [ $this, 'cancel_order_uncomplete_payment' ], 21 );
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
		if ( $this->get_tamara_gateway_service()->get_settings()->enabled ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tamara_widget_client_scripts' ], 5 );
			add_shortcode( 'tamara_show_popup', [ $this, 'fetch_tamara_pdp_widget' ] );
			if ( ! $this->get_tamara_gateway_service()->get_settings()->popup_widget_disabled ) {
				add_action( $this->get_tamara_gateway_service()->get_settings()->popup_widget_position, [ $this, 'show_tamara_pdp_widget' ] );
			}

			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_tamara_widget_client_scripts' ], 5 );
			add_shortcode( 'tamara_show_cart_popup', [ $this, 'fetch_tamara_cart_widget' ] );
			if ( ! $this->get_tamara_gateway_service()->get_settings()->cart_popup_widget_disabled ) {
				add_action( $this->get_tamara_gateway_service()->get_settings()->cart_popup_widget_position, [ $this, 'show_tamara_cart_widget' ] );
			}

			add_action(
				'wp_head',
				[ $this, 'show_tamara_footprint' ]
			);
			add_action(
				'woocommerce_checkout_update_order_review',
				[ $this, 'get_updated_phone_number_on_checkout' ]
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
				'woocommerce_checkout_update_order_review',
				[ $this, 'get_updated_phone_number_on_checkout' ]
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

			if ( $this->get_tamara_gateway_service()->get_settings()->force_checkout_phone ) {
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

			if ( $this->get_tamara_gateway_service()->get_settings()->force_checkout_email ) {
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

			// For WP App
			if ( $this->get_tamara_gateway_service()->get_settings()->cronjob_enabled ) {
				add_action( App_Const::ACTION_WP_APP_WEB_WORKER, [ $this, 'process_tamara_stuck_orders' ] );
			}
		}
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
		return \Tamara_Checkout\App\Support\Tamara_Checkout_Helper::TEXT_DOMAIN;
	}

	public function provides() {
		return [
			Tamara_Client::class,
			Tamara_Notification::class,
			Tamara_Widget::class,
			Tamara_WC_Payment_Gateway::class,
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

	public function get_customer_phone_number() {
		return $this->customer_phone_number;
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
		Register_Tamara_WP_App_Routes_Job::execute_now();
	}

	public function tamara_gateway_register_wp_api_routes(): void {
		Register_Tamara_WP_Api_Routes_Job::execute_now();
	}

	/**
	 * Modify the available payment gateways by adding more
	 *
	 * @param mixed $gateways array of gateways before the filter
	 * @return array of added payment gateways
	 */
	public function add_payment_gateways( $gateways ): array {
		$gateways[] = $this->get_tamara_gateway_service();

		return $gateways;
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
		if ( is_checkout() && $this->get_tamara_gateway_service()->get_settings()->get_enabled() ) {
			$current_cart_info = Tamara_Checkout_Helper::get_current_cart_info() ?? [];
			$cart_total = $current_cart_info['cart_total'] ?? 0;
			$customer_phone = $current_cart_info['customer_phone'] ?? '';
			$country_code = ! empty( $current_cart_info['country_code'] )
				? $current_cart_info['country_code']
				: self::DEFAULT_COUNTRY_CODE;
			$currency_by_country_code = array_flip( Tamara_Checkout_Helper::get_currency_country_mappings() );
			if ( ! empty( $currency_by_country_code[ $country_code ] ) ) {
				$currency_code = $currency_by_country_code[ $country_code ];
				$order_total = new Money(
					Tamara_Checkout_Helper::format_price_number( $cart_total, $currency_code ),
					$currency_code
				);
				return Get_Tamara_Payment_Options_Query::execute_now(
					[
						'available_gateways' => $available_gateways,
						'order_total' => $order_total,
						'country_code' => $country_code,
						'customer_phone' => $customer_phone,
					]
				);
			} else {
				unset( $available_gateways[ static::DEFAULT_TAMARA_GATEWAY_ID ] );
			}
		}

		return $available_gateways;
	}

	/**
	 * Translate a text with gettext context using the plugin's text domain
	 *
	 * @param mixed $untranslated_text Text to be translated
	 *
	 * @return string Translated tet
	 * @throws \Exception
	 */
	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	public function _x( $untranslated_text, $context ): string {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralContext, WordPress.WP.I18n.NonSingularStringLiteralDomain
		return _x( $untranslated_text, $context, $this->get_text_domain() );
	}

	/**
	 *
	 * Registers plural strings in POT file using the plugin's text domain, but does not translate them
	 *
	 * @param  string  $singular
	 * @param  string  $plural
	 *
	 * @return array
	 */
	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	public function _n_noop( string $singular, string $plural ): array {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingular, WordPress.WP.I18n.NonSingularStringLiteralPlural, WordPress.WP.I18n.NonSingularStringLiteralDomain
		return _n_noop( $singular, $plural, $this->get_text_domain() );
	}

	/**
	 * Cancel a pending order and add Tamara payment cancelled/failed notice.
	 *
	 * @throws \Exception
	 */
	public function cancel_order_uncomplete_payment() {
		if (
			isset( $_GET['cancel_order'] ) &&
			isset( $_GET['order'] ) &&
			isset( $_GET['order_id'] ) &&
			( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'woocommerce-cancel_order' ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		) {
			wc_nocache_headers();
			$order_key = wp_unslash( $_GET['order'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$order_id = absint( $_GET['order_id'] );
			$order = wc_get_order( $order_id );
			$payment_method = $order->get_payment_method();
			$user_can_cancel = current_user_can( 'cancel_order', $order_id ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
			$order_can_cancel = $order->has_status( apply_filters( 'woocommerce_valid_order_statuses_for_cancel', [ 'pending', 'failed' ], $order ) );
			$redirect = isset( $_GET['redirect'] ) ? wp_unslash( $_GET['redirect'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			// Todo: Add verify if the payment method is Tamara
			if ( $user_can_cancel && ! $order_can_cancel ) {
				wc_clear_notices();
				wc_add_notice( $this->_t( 'Your payment via Tamara has failed, please try again with a different payment method.' ), 'error' );
			}

			if ( $redirect ) {
				wp_safe_redirect( $redirect );
				exit;
			}
		}
	}

	/**
	 * Register Tamara new statuses
	 *
	 * @throws \Exception
	 */
	public function register_tamara_custom_order_statuses() {
		Register_Tamara_Custom_Order_Statuses_Job::execute_now();
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
		return Add_Tamara_Custom_Statuses_Query::execute_now( $order_statuses );
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
	 * Add the Tamara Settings links to plugin links
	 * @param mixed $plugin_links
	 * @return array
	 * @throws Exception
	 */
	public function add_plugin_settings_link( $plugin_links ) {
		$settings_link = '<a href="' . Tamara_Checkout_Helper::get_admin_settings_section_url() . '">' . $this->_t( 'Settings' ) . '</a>';
		array_unshift( $plugin_links, $settings_link );

		return $plugin_links;
	}

	/**
	 * Update phone number on every ajax calls on checkout
	 *
	 * @param $posted_data
	 *
	 * @return void
	 */
	public function get_updated_phone_number_on_checkout( $posted_data ): void {
		global $woocommerce;

		// Parsing posted data on checkout
		$post = [];
		$vars = explode( '&', $posted_data );
		foreach ( $vars as $k => $value ) {
			$v = explode( '=', urldecode( $value ) );
			$post[ $v[0] ] = $v[1];
		}

		// Update phone number get from posted data
		$this->customer_phone_number = $post['billing_phone'];
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
		$to_capture_status = $this->get_tamara_gateway_service()->get_settings()->order_status_to_capture_tamara_payment;

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
		$to_cancel_status = $this->get_tamara_gateway_service()->get_settings()->order_status_to_cancel_tamara_payment;

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
	 * We want to process Tamara orders that being updated to specific statuses
	 *  but haven't had corresponding statuses on Tamara side
	 * @return void
	 * @throws BindingResolutionException
	 */
	public function process_tamara_stuck_orders(): void {
		Authorise_Tamara_Stuck_Approved_Orders_Job::dispatchSync();
		Capture_Tamara_Stuck_Authorised_Orders_Job::dispatchSync();
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
}
