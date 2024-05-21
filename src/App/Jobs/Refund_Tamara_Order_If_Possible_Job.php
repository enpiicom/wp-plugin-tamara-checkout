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
use InvalidArgumentException;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\VOs\Tamara_Api_Error_VO;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order_Refund;
use Tamara_Checkout\Deps\Tamara\Request\Payment\RefundRequest;
use Tamara_Checkout\Deps\Tamara\Response\Payment\RefundResponse;

class Refund_Tamara_Order_If_Possible_Job extends Base_Job implements ShouldQueue {
	use Dispatchable;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;
	use Config_Trait;
	use Tamara_Trans_Trait;
	use Tamara_Checkout_Trait;

	/**
	 * @var \WC_Order_Refund
	 */
	protected $wc_order_refund;
	protected $wc_order_id;
	protected $refund_args;

	/**
	 * @var Tamara_WC_Order_Refund
	 */
	protected $tamara_wc_order_refund;

	/**
	 *
	 * @var RefundRequest
	 */
	protected $tamara_refund_request;

	/**
	 * Constructor
	 * @param array $config
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws Exception
	 * @throws Tamara_Exception
	 */
	public function __construct( array $config ) {
		$this->bind_config( $config );
		$this->tamara_wc_order_refund = new Tamara_WC_Order_Refund( $this->wc_order_refund, $this->wc_order_id );

		parent::__construct();
	}

	/**
	 * We want to retry this job if it is not a succesful one
	 *  after this amount of seconds
	 * @return int
	 */
	public function backoff() {
		return 700;
	}

	/**
	 * Set tag for filtering
	 * @return string[]
	 */
	public function tags() {
		return [ 'site_id_' . $this->site_id, 'tamara:api', 'tamara_order:refund' ];
	}

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 * @throws \Exception
	 */
	public function handle() {
		$this->before_handle();

		if ( ! $this->check_prerequisites() ) {
			return;
		}

		$this->tamara_refund_request = $this->tamara_wc_order_refund->build_refund_request();
		$tamara_client_response = $this->tamara_client()->refund( $this->tamara_refund_request );

		if ( $tamara_client_response instanceof Tamara_Api_Error_VO ) {
			$this->process_failed_action( $tamara_client_response );

			return;
		}

		$this->process_successful_action( $tamara_client_response );
	}

	/**
	 * We do needed thing on successful scenario
	 */
	protected function process_successful_action( RefundResponse $tamara_client_response ): void {
		$response_tamara_refunds = $tamara_client_response->getRefunds();
		$latest_response_tamara_refund = end( $response_tamara_refunds );

		$request_tamara_refunds = $this->tamara_refund_request->getRefunds();
		$latest_request_tamara_refund = end( $request_tamara_refunds );

		$this->tamara_wc_order_refund->add_tamara_refund_meta(
			$latest_response_tamara_refund->getRefundId(),
			$latest_response_tamara_refund->getCaptureId()
		);

		$response_body = json_decode( $tamara_client_response->getContent(), true );
		$tamara_payment_status = $response_body['status'] ?? Tamara_Checkout_Helper::TAMARA_ORDER_STATUS_PARTIALLY_REFUNDED;
		$this->tamara_wc_order_refund->update_tamara_meta( 'tamara_payment_status', $tamara_payment_status );

		$order_note = sprintf(
			$this->__( 'Order has been refunded successfully. Capture Id: %1$s, Refund Id: %2$s, Refunded amount: %3$s.' ),
			$latest_response_tamara_refund->getCaptureId(),
			$latest_response_tamara_refund->getRefundId(),
			$latest_request_tamara_refund->getTotalAmount()->getAmount()
		);
		$this->tamara_wc_order_refund->add_tamara_order_note( $order_note );
	}

	/**
	 * We do needed thing on failed scenario
	 * @param Tamara_Api_Error_VO $tamara_api_error Error message from Tamara API
	 * @return void
	 * @throws Tamara_Exception
	 */
	protected function process_failed_action( Tamara_Api_Error_VO $tamara_api_error ): void {
		$error_message = $this->__( 'Error when trying to refund with Tamara.' );
		$error_message .= "\n";
		$error_message .= sprintf(
			$this->__( 'Error with Tamara API: %s' ),
			$tamara_api_error->error_message
		);
		$this->tamara_wc_order_refund->add_tamara_order_note( $error_message );

		throw new Tamara_Exception( wp_kses_post( $error_message ) );
	}

	/**
	 * We want to check if we want to start the refund request or not
	 * @throws Tamara_Exception
	 */
	protected function check_prerequisites(): bool {
		$tamara_wc_order_refund = $this->tamara_wc_order_refund;
		if ( ! $tamara_wc_order_refund->is_paid_with_tamara() ) {
			return false;
		}

		if ( abs( $tamara_wc_order_refund->get_total_refund_amount() ) <= 0 ) {
			return false;
		}

		$tamara_capture_id = $tamara_wc_order_refund->get_tamara_capture_id();
		if ( empty( $tamara_capture_id ) ) {
			$error_message = $this->__( 'Unable to create a refund. Capture ID not found.' );
			$tamara_wc_order_refund->add_tamara_order_note( $error_message );

			throw new Tamara_Exception( wp_kses_post( $error_message ) );
		}

		return true;
	}
}
