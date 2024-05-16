<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP\Data;

use Exception;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\Deps\Tamara\Model\Payment\Refund;
use Tamara_Checkout\Deps\Tamara\Request\Payment\RefundRequest;
use WC_Order;
use WC_Order_Refund;

/**
 * A service for Tamara related WC Orders
 * @package Tamara_Checkout\App\Services
 */
class Tamara_WC_Order_Refund extends Tamara_WC_Order {
	/**
	 * @var WC_Order
	 */
	protected $wc_order;
	/**
	 * @var WC_Order_Refund
	 */
	protected $wc_order_refund;

	/**
	 *
	 * @param WC_Order_Refund $wc_order_refund
	 * @param mixed $wc_order_id
	 * @return void
	 * @throws Exception
	 * @throws Tamara_Exception
	 */
	public function __construct( WC_Order_Refund $wc_order_refund, $wc_order_id ) {
		if ( empty( $wc_order_refund->get_id() ) ) {
			throw new Tamara_Exception( wp_kses_post( $this->__( 'Invalid WC_Order_Refund' ) ) );
		}
		$this->wc_order_refund = $wc_order_refund;

		parent::__construct( wc_get_order( $wc_order_id ) );
	}

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	public function build_refund_request(): RefundRequest {
		$wc_order_refund = $this->wc_order_refund;
		$wc_refund_total_amount = Tamara_Checkout_Helper::build_money_object(
			abs( (float) $wc_order_refund->get_total() ),
			$wc_order_refund->get_currency()
		);
		$wc_refund_shipping_amount = Tamara_Checkout_Helper::build_money_object(
			abs( (float) $wc_order_refund->get_shipping_total() ),
			$wc_order_refund->get_currency()
		);
		$wc_refund_tax_amount = Tamara_Checkout_Helper::build_money_object(
			abs( (float) $wc_order_refund->get_total_tax() ),
			$wc_order_refund->get_currency()
		);
		$wc_refund_discount_amount = Tamara_Checkout_Helper::build_money_object(
			$wc_order_refund->get_discount_total(),
			$wc_order_refund->get_currency()
		);

		$capture_id = $this->get_tamara_capture_id();
		$wc_refund_items = $this->build_tamara_order_items( $this->wc_order_refund );
		$payment_refund = new Refund(
			$capture_id,
			$wc_refund_total_amount,
			$wc_refund_shipping_amount,
			$wc_refund_tax_amount,
			$wc_refund_discount_amount,
			$wc_refund_items
		);

		$payment_refunds = [];
		array_push( $payment_refunds, $payment_refund );

		return new RefundRequest(
			$this->get_tamara_order_id(),
			$payment_refunds
		);
	}

	/**
	 * Get the total refund amount
	 * @return float
	 */
	public function get_total_refund_amount() {
		return (float) $this->wc_order_refund->get_total();
	}
}
