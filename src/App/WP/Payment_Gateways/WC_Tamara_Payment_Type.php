<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP\Payment_Gateways;

use Enpii_Base\Foundation\Shared\Traits\Config_Trait;
use Tamara_Checkout\App\Queries\Process_Payment_With_Tamara;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class WC_Tamara_Payment_Type extends \WC_Payment_Gateway {
	protected $payment_type;
	protected $instalment;
	protected $description_en;
	protected $description_ar;

	use Config_Trait;
	use Tamara_Trans_Trait;

	/**
	 * @throws \Exception
	 */
	public function __construct( array $config ) {
		$this->bind_config( $config );
		$current_language = Tamara_Checkout_Helper::get_current_language_code();
		$this->title = ( $current_language === 'ar' ? $this->description_ar : $this->description_en );
		$this->title = ! empty( $this->title ) ? $this->title : $this->__( 'Tamara Pay In 3' );

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
		return Process_Payment_With_Tamara::execute_now(
			wc_get_order( $wc_order_id ),
			$this->payment_type,
			$this->instalment
		);
	}

	public function get_checkout_widget_type() {
		return 'tamara-summary';
	}
}
