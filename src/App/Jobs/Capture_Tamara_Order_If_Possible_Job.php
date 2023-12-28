<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Shared\Base_Job;
use Enpii_Base\Foundation\Shared\Traits\Config_Trait;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\Support\Traits\Trans_Trait;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Request\Order\GetOrderByReferenceIdRequest;
use Tamara_Checkout\Deps\Tamara\Response\Payment\CaptureResponse;

class Capture_Tamara_Order_If_Possible_Job extends Base_Job implements ShouldQueue {
	use Dispatchable;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;
	use Config_Trait;
	use Trans_Trait;

	protected $wc_order_id;
	protected $status_from;
	protected $status_to;
	protected $to_capture_status;
	protected $tamara_wc_order;

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 * @throws \Exception
	 */
	public function __construct( array $config ) {
		$this->bind_config( $config );

		$this->tamara_wc_order = new Tamara_WC_Order( wc_get_order( $this->wc_order_id ) );
	}

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 * @throws \Exception
	 */
	public function handle() {
		if ( ! $this->check_capture_prerequisites() ) {
			return;
		}

		$capture_request = $this->tamara_wc_order->build_capture_request();
		$tamara_client_response = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->capture( $capture_request );
		dev_error_log($tamara_client_response);
		if (
			! is_object( $tamara_client_response )
		) {
			$this->process_captured_failed( $tamara_client_response );
		}

		if ( $tamara_client_response->isSuccess() ) {
			$this->process_captured_successfully( $tamara_client_response );
		}
	}

	/**
	 * We do needed thing on successful scenario
	 */
	protected function process_captured_successfully( CaptureResponse $tamara_client_response ): void {
		$tamara_wc_order = $this->tamara_wc_order;

		$capture_id = $tamara_client_response->getCaptureId();
		$wc_order_id = $this->wc_order_id;
		update_post_meta( $wc_order_id, 'tamara_capture_id', $capture_id );
		update_post_meta( $wc_order_id, '_tamara_capture_id', $capture_id );

		$order_note = 'Tamara - ';
		$order_note .= sprintf( $this->_t( 'Order captured successfully, Tamara Capture Id: %s' ), $capture_id );
		$tamara_wc_order->get_wc_order()->add_order_note( $order_note );
	}

	/**
	 * We do needed thing on failed scenario
	 * @param string $tamara_error_message Error message from Tamara API
	 * @return void
	 * @throws Exception
	 */
	protected function process_captured_failed( string $tamara_error_message ): void {
		$tamara_wc_order = $this->tamara_wc_order;

		$error_message = $this->_t( 'Error when trying to capture order with Tamara.' );
		$error_message .= "<br />\n";
		$error_message .= sprintf(
			$this->_t( 'Error with Tamara API: %s' ),
			$tamara_error_message
		);
		$tamara_wc_order->get_wc_order()->add_order_note( $error_message );
		throw new Tamara_Exception( wp_kses_post( $error_message ) );
	}

	/**
	 * We want to check if we want to start the capture request or not
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 * @throws \Exception
	 */
	protected function check_capture_prerequisites(): bool {
		$wc_order_id = $this->wc_order_id;
		$tamara_wc_order = new Tamara_WC_Order( wc_get_order( $wc_order_id ) );

		if ( ! $tamara_wc_order->is_paid_with_tamara() ) {
			return false;
		}

		if ( $this->to_capture_status !== 'wc-' . $this->status_to ) {
			return false;
		}

		$get_order_by_reference_id_request = new GetOrderByReferenceIdRequest( (string) $this->wc_order_id );
		$tamara_client_response = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->get_order_by_reference_id( $get_order_by_reference_id_request );

		if (
			! is_object( $tamara_client_response )
		) {
			$error_message = $this->_t( 'Error when trying to capture order with Tamara.' );
			$error_message .= "<br />\n";
			$error_message .= sprintf(
				$this->_t( 'Error with Tamara API: %s' ),
				$tamara_client_response
			);
			$tamara_wc_order->get_wc_order()->add_order_note( $error_message );
			throw new Tamara_Exception( wp_kses_post( $error_message ) );
		}

		// We may want to reupdate the meta for tamara_order_id if it is deleted
		update_post_meta( $wc_order_id, 'tamara_order_id', $tamara_client_response->getOrderId() );
		update_post_meta( $wc_order_id, '_tamara_order_id', $tamara_client_response->getOrderId() );

		// We don't want to proceed if the Tamara status is not relevant to the Capture process
		if (
			( $tamara_client_response->getStatus() !== General_Helper::TAMARA_ORDER_STATUS_AUTHORISED ) &&
			( $tamara_client_response->getStatus() !== General_Helper::TAMARA_ORDER_STATUS_PARTIALLY_CAPTURED )
		) {
			return false;
		}

		return true;
	}
}
