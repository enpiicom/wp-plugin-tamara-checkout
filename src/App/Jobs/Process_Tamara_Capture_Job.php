<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Shared\Base_Job;
use Illuminate\Foundation\Bus\Dispatchable;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\Support\Helpers\Tamara_Order_Helper;
use Tamara_Checkout\App\Support\Traits\Trans_Trait;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Model\Money;
use Tamara_Checkout\Deps\Tamara\Model\Payment\Capture;
use Tamara_Checkout\Deps\Tamara\Request\Order\GetOrderByReferenceIdRequest;
use Tamara_Checkout\Deps\Tamara\Request\Payment\CaptureRequest;

class Process_Tamara_Capture_Job extends Base_Job {
	use Dispatchable;
	use Trans_Trait;

	protected $wc_order_id;
	protected $from_status;
	protected $to_status;
	protected $wc_order;
	protected $tamara_order_id;

	public function __construct( $wc_order_id, $from_status, $to_status, $wc_order ) {
		$this->wc_order_id = $wc_order_id;
		$this->from_status = $from_status;
		$this->to_status = $to_status;
		$this->wc_order = $wc_order;
	}

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	public function handle() {
		$get_order_request = new GetOrderByReferenceIdRequest( $this->wc_order_id );
		/** @var \Tamara_Checkout\Deps\Tamara\Response\Order\GetOrderByReferenceIdResponse $tamara_client_response */
		$tamara_client_response = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->get_order_by_wc_order_id( $get_order_request );
		if (
			! is_object( $tamara_client_response ) ||
			empty(
				$tamara_client_response->getOrderId() ||
				empty( $tamara_client_response->getStatus() )
			)
		) {
			throw new Tamara_Exception( wp_kses_post( $this->_t( 'Error! Incorrect Order.' ) ) );
		}

		$this->tamara_order_id = $tamara_client_response->getOrderId();
		$tamara_order_status = $tamara_client_response->getStatus();

		if ( $tamara_order_status === Tamara_Checkout_WP_Plugin::TAMARA_FULLY_CAPTURED_STATUS ) {
			$this->process_capture_successfully();
		} else {
			$capture_request = $this->build_capture_request();
			$tamara_client_response = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->capture_order(
				new CaptureRequest( $capture_request )
			);
		}
	}

	protected function process_capture_successfully(): bool {
		$this->wc_order->add_order_note( $this->_t( 'Tamara - The payment has been captured successfully.' ) );
		return true;
	}

	protected function build_capture_request(): CaptureRequest {
		$tamara_order_id = $this->tamara_order_id;
		$wc_order = $this->wc_order;
		$wc_order_total = new Money( General_Helper::format_tamara_number( $wc_order->get_total() ), $wc_order->get_currency() );
		$wc_shipping_total = new Money( General_Helper::format_tamara_number( $wc_order->get_shipping_total() ), $wc_order->get_currency() );
		$wc_tax_total = new Money( General_Helper::format_tamara_number( $wc_order->get_total_tax() ), $wc_order->get_currency() );
		$wc_discount_total = new Money( General_Helper::format_tamara_number( $wc_order->get_discount_total() ), $wc_order->get_currency() );
		// Todo: Add build order items
		$wc_order_items = populate_tamara_order_items( $wc_order );
		$wc_order_shipping_info = Tamara_Order_Helper::get_tamara_shipping_info();
		$capture = new Capture(
			$tamara_order_id,
			$wc_order_total,
			$wc_shipping_total,
			$wc_tax_total,
			$wc_discount_total,
			$wc_order_items,
			$wc_order_shipping_info
		);
		return new CaptureRequest( $capture );
	}
}
