<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\VOs;

use Enpii_Base\Foundation\Shared\Base_VO;
use Enpii_Base\Foundation\Shared\Traits\Config_Trait;
use Enpii_Base\Foundation\Shared\Traits\Getter_Trait;

/**
 * Value Object for Tamara_WC_Payment_Gateway object setting
 *  to be able to use auto-complete faster
 * @package Tamara_Checkout\App\VOs
 * @property bool $enabled
 * @property string $environment live_mode|sandbox_mode
 * @property string $live_api_url
 * @property string $live_api_token
 * @property string $live_public_key
 * @property string $live_notification_key
 * @property string $sandbox_api_url
 * @property string $sandbox_api_token
 * @property string $sandbox_public_key
 * @property string $sandbox_notification_key
 * @property string $tamara_payment_cancel
 * @property string $tamara_payment_failure
 * @property string $tamara_authorise_done
 * @property string $tamara_authorise_failure
 * @property string $tamara_capture_failure
 * @property string $tamara_order_cancel
 * @property string $tamara_cancel_order
 * @property string $tamara_payment_capture
 * @property string $status_to_capture_tamara_payment
 * @property string $status_to_cancel_tamara_payment
 * @property array $excluded_products array or products' Ids that should be excluded
 * @property array $excluded_product_categories array or terms' Ids
 *                  (from 'product_category' taxonomy) that should be excluded
 * @property bool $cronjob_enabled
 * @property bool $force_checkout_phone
 * @property bool $force_checkout_email
 * @property bool $popup_widget_disabled
 * @property string $popup_widget_position
 * @property bool $cart_popup_widget_disabled
 * @property string $cart_popup_widget_position
 * @property bool $webhook_enabled
 * @property string $tamara_webhook_id
 * @property string $success_url
 * @property string $cancel_url
 * @property string $failure_url
 * @property bool $custom_log_message_enabled
 * @property string $custom_log_message
 * @property bool $is_live_mode
 * @property string $api_url Tamara URL based on the environment
 * @property string $api_token Tamara API Token based on the environment
 * @property string $notification_key Tamara Notification Key based on the environment
 * @property string $public_key Tamara Public Key based on the environment
 */
class Tamara_WC_Payment_Gateway_Settings_VO extends Base_VO {
	use Config_Trait;
	use Getter_Trait;

	protected $enabled;
	protected $environment;
	protected $live_api_url;
	protected $live_api_token;
	protected $live_public_key;
	protected $live_notification_key;
	protected $live_notification_token;
	protected $sandbox_api_url;
	protected $sandbox_api_token;
	protected $sandbox_public_key;
	protected $sandbox_notification_key;
	protected $sandbox_notification_token;
	protected $tamara_payment_cancel;
	protected $tamara_payment_failure;
	protected $tamara_authorise_done;
	protected $tamara_authorise_failure;
	protected $tamara_capture_failure;
	protected $tamara_order_cancel;
	protected $tamara_cancel_order;
	protected $tamara_payment_capture;
	protected $excluded_products;
	protected $excluded_product_categories;
	protected $cronjob_enabled;
	protected $force_checkout_phone;
	protected $force_checkout_email;
	protected $popup_widget_disabled;
	protected $popup_widget_position;
	protected $cart_popup_widget_disabled;
	protected $cart_popup_widget_position;
	protected $webhook_enabled;
	protected $tamara_webhook_id;
	protected $success_url;
	protected $cancel_url;
	protected $failure_url;
	protected $custom_log_message_enabled;
	protected $custom_log_message;

	public function __construct( array $config ) {
		$this->bind_config( $config );
		$this->live_notification_key = $this->live_notification_token;
		$this->sandbox_notification_key = $this->sandbox_notification_token;
	}

	public function get_enabled(): bool {
		return empty( $this->enabled ) || $this->enabled === 'no' ? false : true;
	}

	public function get_cronjob_enabled(): bool {
		return empty( $this->cronjob_enabled ) || $this->cronjob_enabled === 'no' ? false : true;
	}

	public function get_force_checkout_phone(): bool {
		return ! empty( $this->force_checkout_phone ) && $this->force_checkout_phone === 'no' ? false : true;
	}

	public function get_force_checkout_email(): bool {
		return ! empty( $this->force_checkout_email ) && $this->force_checkout_email === 'no' ? false : true;
	}

	public function get_popup_widget_disabled(): bool {
		return empty( $this->popup_widget_disabled ) || $this->popup_widget_disabled === 'no' ? false : true;
	}

	public function get_cart_popup_widget_disabled(): bool {
		return empty( $this->cart_popup_widget_disabled ) || $this->cart_popup_widget_disabled === 'no' ? false : true;
	}

	public function get_webhook_enabled(): bool {
		// We always want webhook to be enabled
		return true;
	}

	public function get_custom_log_message_enabled(): bool {
		return empty( $this->custom_log_message_enabled ) || $this->custom_log_message_enabled === 'no' ? false : true;
	}

	public function get_excluded_products(): array {
		/** @var string $excluded_products */
		$excluded_products = $this->excluded_products;
		$excluded_products_data = explode( ',', $excluded_products );
		if ( empty( trim( $excluded_products ) ) || empty( $excluded_products_data ) ) {
			return [];
		}

		array_walk(
			$excluded_products_data,
			function ( $value ) {
				return trim( $value );
			}
		);
		return $excluded_products_data;
	}

	public function get_excluded_products_ids(): array {
		$excluded_products = $this->excluded_products;

		return array_map( 'trim', explode( ',', (string) $excluded_products ) );
	}

	public function get_excluded_product_category_ids(): array {
		$excluded_product_categories = $this->excluded_product_categories;

		return array_map( 'trim', explode( ',', (string) $excluded_product_categories ) );
	}

	public function get_api_token(): string {
		return $this->environment === 'sandbox_mode' ? $this->sandbox_api_token : $this->live_api_token;
	}

	public function get_api_url(): string {
		return $this->environment === 'sandbox_mode' ? $this->sandbox_api_url : $this->live_api_url;
	}

	public function get_notification_key(): string {
		return $this->environment === 'sandbox_mode' ? (string) $this->sandbox_notification_key : (string) $this->live_notification_key;
	}

	public function get_public_key(): string {
		return $this->environment === 'sandbox_mode' ? $this->sandbox_public_key : $this->live_public_key;
	}

	public function get_is_live_mode(): bool {
		return ( $this->environment === 'live_mode' );
	}

	public function get_excluded_product_categories(): array {
		/** @var string $excluded_product_categories */
		$excluded_product_categories = $this->excluded_product_categories;
		$excluded_product_categories_data = explode( ',', $excluded_product_categories );
		if ( empty( trim( $excluded_product_categories ) ) || empty( $excluded_product_categories_data ) ) {
			return [];
		}

		array_walk(
			$excluded_product_categories_data,
			function ( $value ) {
				return trim( $value );
			}
		);
		return $excluded_product_categories_data;
	}

	public function get_tamara_payment_cancel(): string {
		return ! empty( $this->tamara_payment_cancel ) ? $this->tamara_payment_cancel : 'wc-tamara-p-canceled';
	}

	public function get_tamara_payment_failure(): string {
		return ! empty( $this->tamara_payment_failure ) ? $this->tamara_payment_failure : 'wc-tamara-p-failed';
	}

	public function get_tamara_authorise_done(): string {
		return ! empty( $this->tamara_authorise_done ) ? $this->tamara_authorise_done : 'wc-tamara-a-done';
	}

	public function get_tamara_authorise_failure(): string {
		return ! empty( $this->tamara_authorise_failure ) ? $this->tamara_authorise_failure : 'wc-tamara-a-failed';
	}

	public function get_tamara_capture_failure(): string {
		return ! empty( $this->tamara_capture_failure ) ? $this->tamara_capture_failure : 'wc-tamara-c-failed';
	}

	public function get_status_to_capture_tamara_payment(): string {
		return ! empty( $this->tamara_payment_capture ) ? $this->tamara_payment_capture : 'wc-completed';
	}

	public function get_status_to_cancel_tamara_payment(): string {
		return ! empty( $this->tamara_cancel_order ) ? $this->tamara_cancel_order : 'wc-cancelled';
	}
}
