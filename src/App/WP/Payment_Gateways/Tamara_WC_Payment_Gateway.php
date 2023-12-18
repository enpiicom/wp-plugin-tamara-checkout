<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP\Payment_Gateways;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Tamara_Checkout\App\Jobs\Validate_Admin_Settings_Job;
use Tamara_Checkout\App\Queries\Get_Payment_Gateway_Admin_Form_Fields_Query;
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

	public const ENVIRONMENT_LIVE_MODE = 'live_mode';
	public const ENVIRONMENT_SANDBOX_MODE = 'sandbox_mode';
	public const LIVE_API_URL = 'https://api.tamara.co';
	public const SANDBOX_API_URL = 'https://api-sandbox.tamara.co';

	public const REGISTERED_WEBHOOKS = [
		'order_expired',
		'order_declined',
	];
	public const TAMARA_POPUP_WIDGET_POSITIONS = [
		'woocommerce_single_product_summary' => 'woocommerce_single_product_summary',
		'woocommerce_after_single_product_summary' => 'woocommerce_after_single_product_summary',
		'woocommerce_after_add_to_cart_form' => 'woocommerce_after_add_to_cart_form',
		'woocommerce_before_add_to_cart_form' => 'woocommerce_before_add_to_cart_form',
		'woocommerce_product_meta_end' => 'woocommerce_product_meta_end',
	];
	public const TAMARA_CART_POPUP_WIDGET_POSITIONS = [
		'woocommerce_before_cart' => 'woocommerce_before_cart',
		'woocommerce_after_cart_table' => 'woocommerce_after_cart_table',
		'woocommerce_cart_totals_before_order_total' => 'woocommerce_cart_totals_before_order_total',
		'woocommerce_proceed_to_checkout' => 'woocommerce_proceed_to_checkout',
		'woocommerce_after_cart_totals' => 'woocommerce_after_cart_totals',
		'woocommerce_after_cart' => 'woocommerce_after_cart',
	];
	public $id = 'tamara-gateway';

	public const PAYMENT_TYPE_PAY_BY_INSTALMENTS = 'PAY_BY_INSTALMENTS';

	/**
	 * Settings Value Object for this plugin
	 * @var Tamara_WC_Payment_Gateway_Settings_VO
	 */
	protected $settings_vo;

	public function __construct() {
		$this->title = $this->_t( 'Tamara - Buy Now Pay Later' );
		$this->description = $this->_t( 'Buy Now Pay Later, no hidden fees, with Tamara' );
		$this->method_title = $this->_t( 'Tamara Payment Method' );
		$this->method_description = $this->_t( 'Buy Now Pay Later, no hidden fees, with Tamara' );

		$this->init_form_fields();
		$this->init_settings();
	}

	/**
	 * @inheritDoc
	 * @return string Payment type for this Tamara payment gateway
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
	public function get_payment_type(): string {
		return static::PAYMENT_TYPE_PAY_BY_INSTALMENTS;
	}

	public function get_settings( $refresh = false ): Tamara_WC_Payment_Gateway_Settings_VO {
		// We need to re-pull settings from db if $refesh enabled
		if ( $refresh || empty( $this->settings ) ) {
			$this->init_settings();
			$this->settings_vo = new Tamara_WC_Payment_Gateway_Settings_VO( $this->settings );

			return $this->settings_vo;
		}

		// We want to init the Settings Value Object if value not set
		$this->settings_vo = empty( $this->settings_vo ) ? new Tamara_WC_Payment_Gateway_Settings_VO( $this->settings ) : $this->settings_vo;

		return $this->settings_vo;
	}

	/**
	 * Init admin form fields
	 */
	public function init_form_fields(): void {
		$form_fields = Get_Payment_Gateway_Admin_Form_Fields_Query::execute_now();

		$this->form_fields = $form_fields;
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
	public function process_admin_options(): void {
		Validate_Admin_Settings_Job::dispatchSync( $this );

		$saved = parent::process_admin_options();
	}

	/**
	 * Translate a text using the plugin's text domain
	 *
	 * @param mixed $untranslated_text Text to be translated
	 *
	 * @return string Translated tet
	 * @throws BindingResolutionException|\Exception
	 */
	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	public function _t( $untranslated_text ): string {
		return Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( $untranslated_text );
	}

	/**
	 * Update settings to db options (table options)
	 *
	 * @return void
	 */
	public function update_settings_to_options(): void {
		update_option(
			$this->get_option_key(),
			apply_filters(
				'woocommerce_settings_api_sanitized_fields_' . $this->id,
				$this->settings
			),
			'yes'
		);
	}
}
