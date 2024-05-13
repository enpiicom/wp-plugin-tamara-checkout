<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP\Payment_Gateways;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Tamara_Checkout\App\Jobs\Validate_Admin_Settings_Job;
use Tamara_Checkout\App\Queries\Build_Payment_Gateway_Admin_Form_Fields;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\VOs\Tamara_WC_Payment_Gateway_Settings_VO;
use Tamara_Checkout\App\WP\Payment_Gateways\Contracts\Tamara_Payment_Gateway_Contract;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use WC_Payment_Gateway;

/**
 * Base payment gateway method for Tamara
 *  We should not implement __call method for this class because the parent implementation
 *  of the method get_field_value() use the `is_callable(method)` which always return true
 *  and it makes the logic goes wrong
 *  https://www.php.net/manual/en/function.is-callable.php#refsect1-function.is-callable-notes
 *
 * @package Tamara_Checkout\App\WooCommerce\Payment_Gateways
 * static @method instance() Tamara_WC_Payment_Gateway
 */
class Tamara_WC_Payment_Gateway extends WC_Payment_Gateway implements Tamara_Payment_Gateway_Contract {
	use Static_Instance_Trait;
	use Tamara_Trans_Trait;

	public const ENVIRONMENT_LIVE_MODE = 'live_mode';
	public const ENVIRONMENT_SANDBOX_MODE = 'sandbox_mode';
	public const LIVE_API_URL = 'https://api.tamara.co';
	public const SANDBOX_API_URL = 'https://api-sandbox.tamara.co';

	public const
		PAYMENT_TYPE_PAY_BY_INSTALMENTS = 'PAY_BY_INSTALMENTS',
		PAYMENT_TYPE_PAY_LATER = 'PAY_BY_LATER',
		PAYMENT_TYPE_PAY_NOW = 'PAY_NOW',
		PAYMENT_TYPE_PAY_NEXT_MONTH = 'PAY_NEXT_MONTH';

	public $id = 'tamara-gateway';

	protected $payment_type;
	protected $instalment = 0;

	/**
	 * Settings Value Object for this plugin
	 * @var Tamara_WC_Payment_Gateway_Settings_VO
	 */
	protected $settings_vo;

	/**
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function __construct() {
		$this->title = $this->__( 'Tamara - Buy Now Pay Later' );
		$this->description = $this->__( 'Buy Now Pay Later, no hidden fees, with Tamara' );
		$this->method_title = $this->__( 'Tamara Payment Method' );
		$this->method_description = $this->__( 'Buy Now Pay Later, no hidden fees, with Tamara' );

		if ( Tamara_Checkout_Helper::is_tamara_checkout_settings_page() ) {
			// TODO: we need to find a good hook to init settings form fields
			//  rather than init in the constructor like this
			$this->init_form_fields();
		}
	}

	/**
	 * @inheritDoc
	 * @return string Payment type for this Tamara payment gateway
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
	public function get_payment_type(): string {
		return static::PAYMENT_TYPE_PAY_BY_INSTALMENTS;
	}

	public function get_settings_vo( $refresh = false ): Tamara_WC_Payment_Gateway_Settings_VO {
		// We need to re-pull settings from db if $refesh enabled
		if ( $refresh || empty( $this->settings ) ) {
			$this->init_settings();
			$this->settings_vo = new Tamara_WC_Payment_Gateway_Settings_VO( $this->settings );

			return $this->settings_vo;
		}

		// We want to init the Settings Value Object if value not set
		return empty( $this->settings_vo ) ? new Tamara_WC_Payment_Gateway_Settings_VO( $this->settings ) : $this->settings_vo;
	}

	/**
	 * Init admin form fields
	 */
	public function init_form_fields() {
		$this->init_settings();
		$this->form_fields = Build_Payment_Gateway_Admin_Form_Fields::execute_now( $this->settings );
	}

	/**
	 * We need this method to process the Admin options, it has the parent one
	 * This method will process the post data, sanitize, validate fields (if proper method exists)
	 * Then save all settings values to the database.
	 * the `is_callable(method)` (invoked by this method) which always return true
	 * if this class implement `__call` magic method
	 * and it makes the logic goes wrong
	 * https://www.php.net/manual/en/function.is-callable.php#refsect1-function.is-callable-notes
	 *
	 * @return void
	 */
	public function process_admin_options() {
		Validate_Admin_Settings_Job::dispatchSync();
		parent::process_admin_options();
		if ( $this->get_option( 'custom_log_message_enabled' ) === 'yes' ) {
			if ( empty( $this->get_option( 'custom_log_message' ) ) ) {
				$this->update_option( 'custom_log_message', wp_app_storage_path( 'logs/tamara-custom' . uniqid() . '.log' ) );
			}
		} elseif ( ! empty( $this->get_option( 'custom_log_message' ) ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
			@unlink( $this->get_option( 'custom_log_message' ) );
			$this->update_option( 'custom_log_message', '' );
		}

		$this->init_form_fields();
	}

	/**
	 * Enqueue tamara general scripts on frontend
	 */
	public function enqueue_general_scripts(): void {
		$js_url_handle_id = 'tamara-checkout';

		wp_enqueue_style(
			$js_url_handle_id,
			Tamara_Checkout_WP_Plugin::wp_app_instance()->get_base_url() . 'public-assets/dist/css/main.css',
			[],
			Tamara_Checkout_WP_Plugin::wp_app_instance()->get_version()
		);
		wp_enqueue_script(
			$js_url_handle_id,
			Tamara_Checkout_WP_Plugin::wp_app_instance()->get_base_url() . 'public-assets/dist/js/main.js',
			[ 'jquery' ],
			Tamara_Checkout_WP_Plugin::wp_app_instance()->get_version(),
			true
		);
	}

	/**
	 * Enqueue tamara general scripts in admin
	 */
	public function enqueue_admin_scripts(): void {
		$js_url_handle_id = 'tamara-checkout-admin';

		// Only enqueue the setting scripts on the Tamara Checkout settings screen and shop order screen.
		if ( Tamara_Checkout_Helper::is_tamara_admin_settings_screen() ) {
			wp_enqueue_script(
				$js_url_handle_id,
				Tamara_Checkout_WP_Plugin::wp_app_instance()->get_base_url() . 'public-assets/dist/js/admin.js',
				[ 'jquery' ],
				Tamara_Checkout_WP_Plugin::wp_app_instance()->get_version(),
				false
			);

			wp_enqueue_style(
				$js_url_handle_id,
				Tamara_Checkout_WP_Plugin::wp_app_instance()->get_base_url() . 'public-assets/dist/css/admin.css',
				[],
				Tamara_Checkout_WP_Plugin::wp_app_instance()->get_version()
			);

		} elseif ( Tamara_Checkout_Helper::is_shop_order_screen() ) {
			wp_enqueue_style(
				$js_url_handle_id,
				Tamara_Checkout_WP_Plugin::wp_app_instance()->get_base_url() . 'public-assets/dist/css/admin.css',
				[],
				Tamara_Checkout_WP_Plugin::wp_app_instance()->get_version()
			);
		}
	}
}
