<?php

namespace Tamara_Checkout\App\WP\Payment_Gateways;

use Enpii_Base\Foundation\Shared\Traits\Config_Trait;
use Tamara_Checkout\App\Queries\Process_Payment_With_Tamara_Query;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class WC_Tamara_Payment_Type extends \WC_Payment_Gateway {
	protected $payment_type;
	protected $instalment;
	protected $description_en;
	protected $description_ar;

	use Config_Trait;

	/**
	 * @throws \Exception
	 */
	public function __construct( array $config ) {
		$this->bind_config( $config );
		$current_language = General_Helper::get_current_language();
		$this->title = $current_language === 'ar' ? $this->description_ar : $this->description_en;
		$this->title = ! empty( $this->title ) ? $this->title : $this->_t( 'Tamara Pay In 3' );

		$this->method_title = $this->title;
	}

	public function get_default_payment_gateway_id(): string {
		return Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_gateway_service()->id;
	}

	/**
	 * @inheritDoc
	 * @throws \Exception
	 */
	public function process_payment( $wc_order_id ) {
		return Process_Payment_With_Tamara_Query::execute_now(
			wc_get_order( $wc_order_id ),
			$this->payment_type,
			$this->instalment
		);
	}

	/**
	 * @param $untranslated_text
	 *
	 * @return string
	 * @throws \Exception
	 */
	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	protected function _t( $untranslated_text ): string {
		return Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( $untranslated_text );
	}
}
