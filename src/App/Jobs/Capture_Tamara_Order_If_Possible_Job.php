<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Shared\Base_Job;
use Enpii_Base\Foundation\Shared\Traits\Config_Trait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\VOs\Tamara_Api_Error_VO;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;
use Tamara_Checkout\Deps\Tamara\Request\Payment\CaptureRequest;
use Tamara_Checkout\Deps\Tamara\Response\Payment\CaptureResponse;

class Capture_Tamara_Order_If_Possible_Job extends Base_Job implements ShouldQueue {
	use Dispatchable;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;
	use Config_Trait;
	use Tamara_Trans_Trait;
	use Tamara_Checkout_Trait;

	protected $wc_order_id;
	protected $status_from;
	protected $status_to;
	protected $to_capture_status;

	/**
	 * @var Tamara_WC_Order
	 */
	protected $tamara_wc_order;

	/**
	 * @var CaptureRequest
	 */
	protected $tamara_capture_request;

	/**
	 * @throws Tamara_Exception
	 * @throws \Exception
	 */
	public function __construct( array $config ) {
		$this->bind_config( $config );
		$this->tamara_wc_order = new Tamara_WC_Order( wc_get_order( $this->wc_order_id ) );

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
		return [ 'site_id_' . $this->site_id, 'tamara:api', 'tamara_order:capture' ];
	}

	/**
	 * @throws Tamara_Exception
	 */
	public function handle() {
		$this->before_handle();

		if ( ! $this->check_prerequisites() ) {
			return;
		}

		$this->tamara_capture_request = $this->tamara_wc_order->build_capture_request();
		$tamara_client_response = $this->tamara_client()->capture( $this->tamara_capture_request );

		if ( $tamara_client_response instanceof Tamara_Api_Error_VO ) {
			$this->process_failed_action( $tamara_client_response );
		}

		// If Tamara Client returns an object, that would be a successful object
		$this->process_successful_action( $tamara_client_response );
	}

	/**
	 * We do needed thing on successful scenario
	 */
	protected function process_successful_action( CaptureResponse $tamara_client_response ): void {
		$tamara_wc_order = $this->tamara_wc_order;

		$capture_id = $tamara_client_response->getCaptureId();
		$tamara_wc_order->update_tamara_meta( 'tamara_capture_id', $tamara_client_response->getCaptureId() );

		$tamara_wc_order->add_tamara_order_note(
			sprintf(
				$this->_t( 'Order fully captured successfully. Capture Id: %s, Captured Amount %s.' ),
				$capture_id,
				$this->tamara_capture_request->getCapture()->getTotalAmount()->getAmount()
			)
		);
	}

	/**
	 * We do needed thing on failed scenario
	 *
	 * @param  Tamara_Api_Error_VO $tamara_api_error  Error message from Tamara API
	 * @return void
	 * @throws Tamara_Exception
	 */
	protected function process_failed_action( Tamara_Api_Error_VO $tamara_api_error ): void {
		$tamara_wc_order = $this->tamara_wc_order;

		$error_message = $this->_t( 'Error when trying to capture order with Tamara.' );
		$error_message .= "\n";
		$error_message .= sprintf(
			$this->_t( 'Error with Tamara API: %s' ),
			$tamara_api_error->error_message
		);
		$tamara_wc_order->get_wc_order()->add_order_note( $error_message );

		$new_order_status = $this->tamara_settings()->tamara_capture_failure;
		$tamara_wc_order->get_wc_order()->update_status( $new_order_status );

		throw new Tamara_Exception( wp_kses_post( $error_message ) );
	}

	/**
	 * We want to check if we want to start the capture request or not
	 * @throws Tamara_Exception
	 */
	protected function check_prerequisites(): bool {
		if (
			! $this->tamara_wc_order->is_paid_with_tamara() ||
			empty( $this->tamara_wc_order->get_tamara_order_id() )
		) {
			return false;
		}

		return true;
	}
}
