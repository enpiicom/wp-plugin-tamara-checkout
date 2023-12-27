<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Bus\Dispatchable_Trait;
use Enpii_Base\Foundation\Shared\Base_Job;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\Support\Helpers\Tamara_Order_Helper;
use Tamara_Checkout\App\Support\Traits\Trans_Trait;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Request\Order\GetOrderByReferenceIdRequest;
use Tamara_Checkout\Deps\Tamara\Request\Payment\CaptureRequest;

class Process_Tamara_Capture_Job extends Base_Job {
	use Dispatchable_Trait;
	use Trans_Trait;

	protected $wc_order_id;
	protected $from_status;
	protected $to_status;
	protected $wc_order;
	protected $tamara_order_id;

	public function __construct( $wc_order_id, $from_status, $to_status, $wc_order  ) {
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
			empty($tamara_client_response->getOrderId() ||
			empty($tamara_client_response->getStatus())
			)
		) {
			throw new Tamara_Exception( wp_kses_post( $this->_t( 'Error! Incorrect Order.' ) ) );
		}

		$this->tamara_order_id = $tamara_client_response->getOrderId();
		$tamara_order_status = $tamara_client_response->getStatus();

		if ($tamara_order_status === Tamara_Checkout_WP_Plugin::TAMARA_FULLY_CAPTURED_STATUS) {
			$this->process_capture_successfully();
		} else {
			$tamara_client_response = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->capture_order(
				new CaptureRequest( $this->prepare_wc_order_to_capture() )
			);
		}
	}

	protected function process_capture_successfully(): bool {
		$this->wc_order->add_order_note($this->_t('Tamara - The payment has been captured successfully.'));
		return true;
	}

	protected function prepare_wc_order_to_capture() {
		$wc_order = $this->wc_order;
		$wc_order_total = General_Helper::format_tamara_number(($wc_order->get_total()), $wc_order->get_currency());
		$wc_shipping_total = General_Helper::format_tamara_number(($wc_order->get_shipping_total()), $wc_order->get_currency());
		$wc_tax_total = General_Helper::format_tamara_number(($wc_order->get_total_tax()), $wc_order->get_currency());
		$wc_discount_total = General_Helper::format_tamara_number(($wc_order->get_discount_total()), $wc_order->get_currency());
		$wc_order_shipping_info = Tamara_Order_Helper::get_tamara_shipping_info();
		$wc_order_items = $this->populateTamaraOrderItems($wcOrder);
		$tamara_order_id = $this->tamara_order_id;
	}
}
