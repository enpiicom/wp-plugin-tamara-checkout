<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Enpii_Base\Foundation\Support\Executable_Trait;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\VOs\Tamara_WC_Payment_Gateway_Settings_VO;
use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class Build_Payment_Gateway_Admin_Form_Fields {
	use Executable_Trait;
	use Tamara_Trans_Trait;
	use Tamara_Checkout_Trait;

	/**
	 * @var Tamara_WC_Payment_Gateway_Settings_VO
	 */
	protected $current_settings;
	protected $working_mode;

	public function __construct( $settings ) {
		$this->current_settings = new Tamara_WC_Payment_Gateway_Settings_VO( $settings );
		$this->working_mode = $this->current_settings->environment;
	}

	public function handle(): array {
		return [
			'enabled' => [
				'title' => $this->__( 'Enable/Disable' ),
				'label' => $this->__( 'Enable Tamara Gateway' ),
				'type' => 'checkbox',
			],
			'tamara_settings_help_texts' => [
				'title' => $this->__( 'Tamara Settings Help Texts' ),
				'type' => 'title',
				'description' => $this->get_help_text_html(),
			],
			'tamara_confidential_config' => [
				'title' => $this->__( 'Confidential Configuration' ),
				'type' => 'title',
				'description' => '<p>' . $this->__( 'Update Your Confidential Configuration Received From Tamara. You can find that on http://partners.tamara.co.' ) . '</p>' . $this->handle_working_mode_fields_display(),
			],
			'environment' => [
				'title' => $this->__( 'Tamara Working Mode' ),
				'label' => $this->__( 'Choose Tamara Working Mode' ),
				'type' => 'select',
				'default' => Tamara_WC_Payment_Gateway::ENVIRONMENT_LIVE_MODE,
				'options' => [
					Tamara_WC_Payment_Gateway::ENVIRONMENT_LIVE_MODE => $this->__( 'Live Mode' ),
					Tamara_WC_Payment_Gateway::ENVIRONMENT_SANDBOX_MODE => $this->__( 'Sandbox Mode' ),
				],
				'description' => $this->__( 'This setting specifies whether you will process live transactions, or whether you will process simulated transactions using the Tamara Sandbox.' ),
			],
			'live_api_url' => [
				'title' => $this->__( 'Live API URL' ),
				'type' => 'text',
				'class' => 'live-field',
				'description' => $this->__( 'The Tamara Live API URL <span class="tamara-highlight">(https://api.tamara.co)</span>' ),
				'default' => Tamara_WC_Payment_Gateway::LIVE_API_URL,
				'custom_attributes' => [
					'value' => Tamara_WC_Payment_Gateway::LIVE_API_URL,
					'required' => 'required',
				],
				'css' => 'width: 300px',
			],
			'live_api_token' => [
				'title' => $this->__( 'Live API Token (Merchant Token)' ),
				'type' => 'textarea',
				'class' => 'live-field',
				'css' => 'height: 200px;',
				'description' => $this->__( 'Get your API token from Tamara.' ),
				'custom_attributes' => [
					'required' => 'required',
				],
			],
			'live_notification_token' => [
				'title' => $this->__( 'Live Notification Key' ),
				'type' => 'text',
				'class' => 'live-field',
				'description' => $this->__( 'Get your Notification key from Tamara.' ),
				'default' => '',
				'custom_attributes' => [
					'required' => 'required',
				],
			],
			'live_public_key' => [
				'title' => $this->__( 'Live Public Key' ),
				'type' => 'text',
				'class' => 'live-field',
				'description' => $this->__( 'Get your Public key from Tamara.' ),
				'custom_attributes' => [
					'required' => 'required',
				],
			],
			'sandbox_api_url' => [
				'title' => $this->__( 'Sandbox API URL' ),
				'type' => 'text',
				'class' => 'sandbox-field',
				'description' => $this->__( 'The Tamara Sandbox API URL <span class="tamara-highlight">(https://api-sandbox.tamara.co)</span>' ),
				'default' => Tamara_WC_Payment_Gateway::SANDBOX_API_URL,
				'custom_attributes' => [
					'value' => Tamara_WC_Payment_Gateway::SANDBOX_API_URL,
					'required' => 'required',
				],
			],
			'sandbox_api_token' => [
				'title' => $this->__( 'Sandbox API Token (Merchant Token)' ),
				'type' => 'textarea',
				'css' => 'height: 200px;',
				'class' => 'sandbox-field',
				'description' => $this->__( 'Get your API token for testing from Tamara.' ),
				'custom_attributes' => [
					'required' => 'required',
				],
			],
			'sandbox_notification_token' => [
				'title' => $this->__( 'Sandbox Notification Key' ),
				'type' => 'text',
				'class' => 'sandbox-field',
				'description' => $this->__( 'Get your Notification key for testing from Tamara.' ),
				'custom_attributes' => [
					'required' => 'required',
				],
			],
			'sandbox_public_key' => [
				'title' => $this->__( 'Sandbox Public Key' ),
				'type' => 'text',
				'class' => 'sandbox-field',
				'description' => $this->__( 'Get your Public key for testing from Tamara.' ),
				'custom_attributes' => [
					'required' => 'required',
				],
			],
			'tamara_order_statuses_mapping' => [
				'title' => $this->__( 'Order Statuses Mappings' ),
				'type' => 'title',
				'description' => '<p>' . $this->__( 'Mapping status for order according to Tamara action result.' ) . '</p>
                <div class="tamara-order-statuses-mappings-manage button-primary">' . $this->__( 'Manage Order Statuses Mappings' ) . '<i class="tamara-toggle-btn fa-solid fa-chevron-down"></i></div>',
			],
			'tamara_authorise_done' => [
				'title' => $this->__( 'Order Status when Tamara Order is Authorised' ),
				'type' => 'select',
				'default' => 'wc-processing',
				'options' => wc_get_order_statuses(),
				'description' => $this->__( 'Mapping status for orders that have Tamara Payment authorised successfully on Tamara side. This process happens after the Customers complete the checkout on Tamara side successfully.' ),
			],
			'tamara_order_cancel' => [
				'title' => $this->__( 'Order Status when Tamara Order is Canceled' ),
				'type' => 'select',
				'default' => 'wc-cancelled',
				'options' => wc_get_order_statuses(),
				'description' => $this->__( 'Mapping status for orders that are cancelled (Tamara calls `canceled`). The cancellation may happen because the customer actively cancel the order or the cancellation happens on the Partners portal or from Tamara side. Tamara triggers the Cancellation process and send to the website (via the webhook).' ),
			],
			'tamara_payment_failure' => [
				'title' => $this->__( 'Order Status when Tamara Order Fails' ),
				'type' => 'select',
				'default' => 'wc-tamara-failed',
				'options' => wc_get_order_statuses(),
				'description' => $this->__( 'Mapping status for orders that are declined by Tamara. The Order failure may happen because the customer does not have a good credit history or having on-going orders with Tamara. Tamara triggers the Decline process and send to the website (via the webhook).' ),
			],
			'tamara_authorise_failure' => [
				'title' => $this->__( 'Order Status when Tamara Authorisation fails' ),
				'type' => 'select',
				'default' => 'wc-tamara-a-failed',
				'options' => wc_get_order_statuses(),
				'description' => $this->__( 'Mapping status for orders when the payment failed at the Authorisation process on Tamara.' ),
			],
			'tamara_capture_failure' => [
				'title' => $this->__( 'Order Status when Capture fails on Tamara' ),
				'type' => 'select',
				'default' => 'wc-tamara-c-failed',
				'options' => wc_get_order_statuses(),
				'description' => $this->__( 'Mapping status for orders when the payment failed at the Capture process on Tamara.' ),
			],
			'tamara_order_statuses_trigger' => [
				'title' => $this->__( 'Order Statuses to Trigger Tamara Events' ),
				'type' => 'title',
				'description' => '<p>' . $this->__( 'Update order statuses used to trigger events to Tamara' ) . '</p>
                <div class="tamara-order-statuses-trigger-manage button-primary">' . $this->__( 'Manage Order Statuses Trigger' ) . '<i class="tamara-toggle-btn fa-solid fa-chevron-down"></i></div>',
			],
			'tamara_cancel_order' => [
				'title' => $this->__( 'Order Status that triggers the Tamara Cancel process for an order' ),
				'type' => 'select',
				'default' => 'wc-cancelled',
				'options' => wc_get_order_statuses(),
				'description' => $this->__( 'When you update an order to this status, Tamara Checkout plugin would connect to Tamara API to trigger the Cancel process for the correspoding order on Tamara side.' ),
			],
			'tamara_payment_capture' => [
				'title' => $this->__( 'Order Status that triggers Tamara Capture process for an order' ),
				'type' => 'select',
				'default' => 'wc-completed',
				'options' => wc_get_order_statuses(),
				'description' => $this->__( 'When you update an order to this status it would connect to Tamara API to trigger the Capture payment process on Tamara. Only when the Capture process done, the payment is available for the settlement.' ),
			],
			'tamara_custom_settings' => [
				'title' => $this->__( 'Tamara Custom Settings' ),
				'type' => 'title',
				'description' => $this->__( 'Configure Tamara Custom Settings' )
								. '<div class="tamara-custom-settings-manage button-primary">' . $this->__( 'Show Tamara Custom Settings' ) . '<i class="tamara-toggle-btn fa-solid fa-chevron-down"></i></div>',
			],
			'excluded_products' => [
				'title' => $this->__( 'Excluded Product Ids' ),
				'type' => 'text',
				'description' => $this->__( 'Enter the product ids that you want to exclude from using Tamara to checkout (These ids are separated by commas e.g. `101, 205`).' ),
				'default' => '',
			],
			'excluded_product_categories' => [
				'title' => $this->__( 'Excluded Product Category Ids' ),
				'type' => 'text',
				'description' => $this->__( 'Enter the product category ids that you want to exclude from using Tamara to checkout (These ids are separated by commas e.g. `26, 104`).' ),
				'default' => '',
			],
			'tamara_general_settings' => [
				'title' => $this->__( 'Tamara Advanced Settings' ),
				'type' => 'title',
				'description' => $this->__( 'Configure Tamara Advanced Settings <br> <p class="tamara-highlight">Please read the descriptions of these settings carefully before making a change or please contact Tamara Team for more details.</p>' )
				. '<div class="tamara-advanced-settings-manage button-primary">' . $this->__( 'Show Tamara Advanced Settings' ) . '<i class="tamara-toggle-btn fa-solid fa-chevron-down"></i></div>',
			],
			'force_checkout_phone' => [
				'title' => $this->__( 'Force Enable Phone' ),
				'label' => $this->__( 'Enable Billing/Shipping Phone Field at Checkout step' ),
				'default' => 'yes',
				'type' => 'checkbox',
				'description' => $this->__( 'If you tick on this setting, the billing/shipping Phone field will be forced to display on the checkout screen, which is required to use for Tamara payment gateway.' ),
			],
			'force_checkout_email' => [
				'title' => $this->__( 'Force Enable Email' ),
				'label' => $this->__( 'Enable Email Field at Checkout step' ),
				'default' => 'yes',
				'type' => 'checkbox',
				'description' => $this->__( 'If you tick on this setting, the billing/shipping Email field will be forced to display on the checkout screen, which is required to use for Tamara payment gateway.' ),
			],
			'popup_widget_disabled' => [
				'title' => $this->__( 'Disable Single Product Details Popup (PDP) Widget' ),
				'label' => $this->__( 'Disable PDP Widget on Single Product Page' ),
				'default' => 'no',
				'type' => 'checkbox',
				'description' => $this->__( 'In you tick on this setting, the PDP widget will be hidden on the single product page.' ),
			],
			'popup_widget_position' => [
				'title' => $this->__( 'PDP Widget Position' ),
				'type' => 'select',
				'options' => $this->get_pdp_widget_positions(),
				'description' => $this->__( 'Choose a position where you want to display the Tamara Payment Popup Widget on single product page (https://www.businessbloomer.com/woocommerce-visual-hook-guide-single-product-page/). Or, you can use shortcode with attributes to show it on custom pages e.g. [tamara_show_popup price="199" currency="SAR" language="en"]' ),
				'default' => 'woocommerce_before_add_to_cart_form',
			],
			'cart_popup_widget_disabled' => [
				'title' => $this->__( 'Disable Cart Popup Widget' ),
				'label' => $this->__( 'Force to Disable Cart Popup Widget' ),
				'default' => 'no',
				'type' => 'checkbox',
				'description' => $this->__( 'In you tick on this setting, the popup widget will be hidden on the cart page.' ),
			],
			'cart_popup_widget_position' => [
				'title' => $this->__( 'Cart Popup Widget Position' ),
				'type' => 'select',
				'options' => $this->get_cart_widget_positions(),
				'description' => $this->__( 'Choose a position where you want to display the Tamara Payment Popup Widget on cart page.' ),
				'default' => 'woocommerce_proceed_to_checkout',
			],
			'webhook_enabled' => [
				'type' => 'checkbox',
				'default' => 'yes',
				'custom_attributes' => [
					'readonly' => 'readonly',
				],
			],
			'success_url' => [
				'title' => $this->__( 'Tamara Payment Success Url' ),
				'type' => 'text',
				'description' => $this->__( 'Enter the custom SUCCESS url for customers to be redirected to after PAYMENT is SUCCESSFUL (leave it blank to use the default one).' ),
				'default' => null,
			],
			'cancel_url' => [
				'title' => $this->__( 'Tamara Payment Cancel Url' ),
				'type' => 'text',
				'description' => $this->__( 'Enter the custom CANCEL url for customers to be redirected to after PAYMENT is CANCELLED (leave it blank to use the default one).' ),
				'default' => null,
			],
			'failure_url' => [
				'title' => $this->__( 'Tamara Payment Failure Url' ),
				'type' => 'text',
				'description' => $this->__( 'Enter the custom FAILURE url for customers to be redirected to after PAYMENT is FAILED (leave it blank to use the default one).' ),
				'default' => null,
			],
			'debug_info' => [
				'title' => $this->__( 'Debug Info' ),
				'type' => 'title',
				'description' =>
					'<div class="debug-info-manage button-primary" >' . $this->__( 'Show Debug Info' ) . '<i class="tamara-toggle-btn fa-solid fa-chevron-down"></i></div>',
			],
			'debug_info_text' => [
				'type' => 'debug_info_text',
				'title' => $this->__( 'Platform & Extensions:' ),
				'description' =>
					'<table class="tamara-debug-info-table"><tr><td>' . sprintf( '<strong>PHP Version:</strong> %s', PHP_VERSION ) . '</td></tr>'
					. '<tr><td>' . sprintf( '<strong>' . $this->__( 'PHP loaded extensions' ) . ':</strong> %s', implode( ', ', get_loaded_extensions() ) ) . '</td></tr>'
					. '<tr><td><h4>Default Merchant URLs:</h4></td></tr>'
					. '<tr><td><ul>'
					. '<li>' . $this->__( 'Tamara Notification URL: ' ) . ( $this->get_tamara_ipn_url() ? $this->get_tamara_ipn_url() : 'N/A' ) . '</li>'
					. '<li>' . $this->__( 'Tamara Webhook Id: ' ) . ( $this->get_tamara_webhook_id() ? $this->get_tamara_webhook_id() : 'N/A' ) . '</li>'
					. '<li>' . $this->__( 'Tamara Webhook URL: ' ) . ( $this->get_tamara_webhook_url() ? $this->get_tamara_webhook_url() : 'N/A' ) . '</li>'
					. '</ul></td></tr></table>',
			],
			'custom_log_message_enabled' => [
				'title' => $this->__( 'Enable Tamara Custom Log Message' ),
				'type' => 'checkbox',
				// phpcs:ignore Generic.Strings.UnnecessaryStringConcat.Found
				'description' => $this->__( 'In you tick on this setting, all the message logs will be written and saved to the Tamara custom log file in your upload directory.' ),
			],
			'custom_log_message' => [
				'title' => $this->__( 'Tamara Custom Log file' ),
				'type' => 'text',
				'custom_attributes' => [
					'readonly' => 'readonly',
				],
				// phpcs:ignore Generic.Strings.UnnecessaryStringConcat.Found
				'description' => $this->__( 'The log download link will be <strong>available below</strong>, if the value is not empty' ) . '<br />' . $this->build_debug_log_download_link(),
			],
			'plugin_version' => [
				'type' => 'title',
				'description' =>
					'<p style="margin-top: 2.6rem;">' . sprintf( $this->__( 'Tamara Checkout Plugin Version: %s' ), TAMARA_CHECKOUT_VERSION ) . ', ' . sprintf( $this->__( 'WooCommerce Version: %s' ), WC()->version ) . ', ' . sprintf( $this->__( 'WordPress Version: %s' ), get_bloginfo( 'version' ) ) . ', ' . sprintf( $this->__( 'PHP Version: %s' ), phpversion() ) . '</p>',
			],
		];
	}

	/**
	 * get settings help texts template
	 *
	 * @return string
	 */
	protected function get_help_text_html(): string {
		return '<div class="tamara-settings-help-texts-description">
					<p>' . $this->__( 'Here you can browse some help texts and find solutions for common issues with our plugin.' ) . '</p>
					<ul>
						<li><p class="tamara-highlight">' . $this->__( 'If there is any issue with your API URL, API Token, Notification Key or Public Key please contact Tamara Team for support at <a href="mailto:merchant.support@tamara.co">merchant.support@tamara.co</a>' ) . '</p></li>
					</ul>
				</div>' .
				'<div class="tamara-settings-help-texts">
                    <div class="tamara-settings-help-texts__manage button-primary">' . $this->__( 'Show More Help Texts' ) . '<i class="tamara-toggle-btn fa-solid fa-chevron-down"></i></div>
                    <div class="tamara-settings-help-texts__content">
                        <ul>
                            <li>' . $this->__( 'Please make sure the Tamara payment status of the order is <strong>captured</strong> before making a refund.' ) . '</li>
                            <li>' . $this->__( 'You can use the shortcode with attributes to show Tamara product widget on custom pages e.g. <strong>[tamara_show_popup price="600" currency="SAR" language="en"].</strong>' ) . '</li>
                            <li>' . $this->__( 'For Tamara payment success URL, you can use action <strong>after_tamara_success</strong> to handle further actions.' ) . '</li>
                            <li>' . $this->__( 'For Tamara payment cancel URL, you can use action <strong>after_tamara_cancel</strong> to handle further actions.' ) . '</li>
                            <li>' . $this->__( 'For Tamara payment failed URL, you can use action <strong>after_tamara_failure</strong> to handle further actions.' ) . '</li>
                            <li>' . $this->__( 'All the debug log messages sent from Tamara will be written and saved to the Tamara custom log file on your server.' ) . '</li>' .
							( $this->current_settings->api_token ? '<li>' . sprintf( $this->__( 'If you want to solve stuck orders (when Tamara inform you, you may want to click %s to enable to process).' ), $this->build_solve_stuck_orders_link() ) . '</li>' : '' )
							. '
                        </ul>
                    </div>
                </div>';
	}

	protected function get_debug_log_download_link(): string {
		$custom_log_message = $this->current_settings->custom_log_message;
		return ! empty( $custom_log_message ) ?
			wp_app_route_wp_url(
				'wp-app::tamara-download-log-file',
				[
					'filepath' => $custom_log_message,
				]
			) :
			'';
	}

	protected function build_solve_stuck_orders_link(): string {
		$api_token = $this->current_settings->api_token;
		return ! empty( $api_token ) ? '<a href="' . wp_app_route_wp_url(
			'wp-api::tamara-solve-stuck-orders'
		) . '" data-click-to-solve-stuck-orders="true"> ' . $this->__( 'Solve Stuck Orders' ) . '</a>' : '';
	}

	protected function build_debug_log_download_link(): string {
		return $this->get_debug_log_download_link() ? '<a href="' . $this->get_debug_log_download_link() . '" target="_blank"> ' . $this->__( 'Download Custom Log file' ) . '</a>' : '';
	}

	protected function get_tamara_webhook_url(): string {
		return wp_app_route_wp_url( 'wp-api::tamara-webhook' );
	}

	protected function get_tamara_webhook_id(): string {
		return (string) $this->current_settings->tamara_webhook_id;
	}

	protected function get_tamara_ipn_url(): string {
		return wp_app_route_wp_url(
			'wp-api::tamara-ipn',
			[
				'wc_order_id' => 1,
			]
		);
	}

	protected function get_tamara_failure_url(): string {
		return wp_app_route_wp_url(
			'wp-api::tamara-failure',
			[
				'wc_order_id' => 1,
			]
		);
	}

	protected function get_tamara_cancel_url(): string {
		return wp_app_route_wp_url(
			'wp-api::tamara-cancel',
			[
				'wc_order_id' => 1,
			]
		);
	}

	protected function get_tamara_success_url(): string {
		return wp_app_route_wp_url(
			'wp-api::tamara-success',
			[
				'wc_order_id' => 1,
			]
		);
	}

	protected function get_pdp_widget_positions(): array {
		return [
			'woocommerce_single_product_summary' => 'woocommerce_single_product_summary',
			'woocommerce_after_single_product_summary' => 'woocommerce_after_single_product_summary',
			'woocommerce_after_add_to_cart_form' => 'woocommerce_after_add_to_cart_form',
			'woocommerce_before_add_to_cart_form' => 'woocommerce_before_add_to_cart_form',
			'woocommerce_product_meta_end' => 'woocommerce_product_meta_end',
		];
	}

	protected function handle_working_mode_fields_display(): void {
		wp_register_script(
			'tamara-custom-admin',
			'',
			[],
			Tamara_Checkout_WP_Plugin::wp_app_instance()->get_version(),
			true
		);
		wp_enqueue_script( 'tamara-custom-admin' );

		$success_message = esc_attr( $this->__( 'Done! The script to resolve the Stuck Orders has been executed.' ) );

		$js_script = <<<JS_SCRIPT
			window.addEventListener('load', function() {
				get_confidential_value_selected_fn();
				document.getElementById('woocommerce_tamara-gateway_environment').onchange = get_confidential_value_selected_fn;
			})

			function get_confidential_value_selected_fn() {
			const live_api_url = 'https://api.tamara.co';
			const sandbox_api_url = 'https://api-sandbox.tamara.co';
			let live_api_url_el = document.getElementById('woocommerce_tamara-gateway_live_api_url');
			let live_api_token_el = document.getElementById('woocommerce_tamara-gateway_live_api_token');
			let live_notif_token_el = document.getElementById('woocommerce_tamara-gateway_live_notification_token');
			let live_public_key_el = document.getElementById('woocommerce_tamara-gateway_live_public_key');
			let sandbox_api_url_el = document.getElementById('woocommerce_tamara-gateway_sandbox_api_url');
			let sandbox_api_token_el = document.getElementById('woocommerce_tamara-gateway_sandbox_api_token');
			let sandbox_notif_token_el = document.getElementById('woocommerce_tamara-gateway_sandbox_notification_token');
			let sandbox_public_key_el = document.getElementById('woocommerce_tamara-gateway_sandbox_public_key');
	        let value_selected;
	        let tamara_env_toggle = document.getElementById('woocommerce_tamara-gateway_environment');
            value_selected = tamara_env_toggle.value;

            if ('live_mode' === value_selected) {
				sandbox_api_url_el.removeAttribute('required');
                sandbox_api_url_el.closest('tr').style.display = 'none'

				sandbox_api_token_el.removeAttribute('required');
                sandbox_api_token_el.closest('tr').style.display = 'none'

				sandbox_notif_token_el.removeAttribute('required');
                sandbox_notif_token_el.closest('tr').style.display = 'none'

				sandbox_public_key_el.removeAttribute('required');
                sandbox_public_key_el.closest('tr').style.display = 'none'

                live_api_url_el.closest('tr').style.display = 'table-row'
                live_api_url_el.setAttribute('required', true);

                live_api_token_el.closest('tr').style.display = 'table-row'
                live_api_token_el.setAttribute('required', true);

                live_notif_token_el.closest('tr').style.display = 'table-row'
                live_notif_token_el.setAttribute('required', true);

                live_public_key_el.closest('tr').style.display = 'table-row'
				live_public_key_el.setAttribute('required', true);

                if (!live_api_url_el.value) {
                    live_api_url_el.value = live_api_url;
                }
            } else if ('sandbox_mode' === value_selected) {
				live_api_url_el.removeAttribute('required');
                live_api_url_el.closest('tr').style.display = 'none';

				live_api_token_el.removeAttribute('required');
                live_api_token_el.closest('tr').style.display = 'none';

				live_notif_token_el.removeAttribute('required');
                live_notif_token_el.closest('tr').style.display = 'none';

				live_public_key_el.removeAttribute('required');
                live_public_key_el.closest('tr').style.display = 'none';

                sandbox_api_url_el.closest('tr').style.display = 'table-row';
                sandbox_api_url_el.setAttribute('required', true);

                sandbox_api_token_el.closest('tr').style.display = 'table-row';
                sandbox_api_token_el.setAttribute('required', true);

                sandbox_notif_token_el.closest('tr').style.display = 'table-row';
                sandbox_notif_token_el.setAttribute('required', true);

                sandbox_public_key_el.closest('tr').style.display = 'table-row';
				sandbox_public_key_el.setAttribute('required', true);

                if (!sandbox_api_url_el.value) {
                    sandbox_api_url_el.value = sandbox_api_url;
                }
            }

			document.querySelectorAll('[data-click-to-solve-stuck-orders="true"]').forEach((el) => {
				el.addEventListener('click', function (event) {
					// Don't follow the link
					event.preventDefault();

					var solve_stuck_orders_url = el.href;
					const settings = {
						method: 'POST',
						headers: {
							Accept: 'application/json',
							'Content-Type': 'application/json',
						}
					};

					const success_message = "{$success_message}";
					const response = fetch(solve_stuck_orders_url, settings)
						.then(function (response) {
							alert(success_message);
						})
						.catch(function (err) {
							// There was an error
							console.warn('Something went wrong.', err);
						});

					// console.log(response);
				}, false);
			});

        }
JS_SCRIPT;
		if ( Tamara_Checkout_Helper::is_tamara_admin_settings_screen() ) {
			wp_add_inline_script( 'tamara-custom-admin', $js_script, 'before' );
		}
	}

	protected function get_cart_widget_positions(): array {
		return [
			'woocommerce_before_cart' => 'woocommerce_before_cart',
			'woocommerce_after_cart_table' => 'woocommerce_after_cart_table',
			'woocommerce_cart_totals_before_order_total' => 'woocommerce_cart_totals_before_order_total',
			'woocommerce_proceed_to_checkout' => 'woocommerce_proceed_to_checkout',
			'woocommerce_after_cart_totals' => 'woocommerce_after_cart_totals',
			'woocommerce_after_cart' => 'woocommerce_after_cart',
		];
	}
}
