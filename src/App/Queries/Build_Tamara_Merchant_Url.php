<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Enpii_Base\Foundation\Support\Executable_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\Deps\Tamara\Model\Order\MerchantUrl;
use WC_Order;

/**
 * @method static \Tamara_Checkout\Deps\Tamara\Model\Order\MerchantUrl execute_now(WC_Order $wc_order, string $payment_type)
 * @package Tamara_Checkout\App\Queries
 */
class Build_Tamara_Merchant_Url {
	use Executable_Trait;
	use Tamara_Trans_Trait;
	use Tamara_Checkout_Trait;

	protected $wc_order;
	protected $payment_type;

	public function __construct( WC_Order $wc_order, $payment_type = '' ) {
		$this->wc_order = $wc_order;
		$this->payment_type = $payment_type;
	}

	public function handle() {
		$wc_order = $this->wc_order;
		$params = [
			'redirect_from' => 'tamara',
			'wc_order_id' => $wc_order->get_id(),
			'order_id' => $wc_order->get_id(),
			'key' => $wc_order->get_order_key(),
			'payment_type' => $this->payment_type,
			'locale' => determine_locale(),
		];

		$merchant_url = new MerchantUrl();
		$merchant_url->setSuccessUrl( Build_Tamara_Success_Url::execute_now( $wc_order, $params ) );
		$merchant_url->setCancelUrl( Build_Tamara_Cancel_Url::execute_now( $wc_order, $params ) );
		$merchant_url->setFailureUrl( Build_Tamara_Failure_Url::execute_now( $wc_order, $params ) );
		$merchant_url->setNotificationUrl( wp_app_route_wp_url( 'wp-api::tamara-ipn', $params ) );

		return $merchant_url;
	}
}
