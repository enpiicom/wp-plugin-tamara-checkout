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
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;
use Tamara_Checkout\Deps\Tamara\Response\Payment\CancelResponse;

class Cancel_Tamara_Order_If_Possible_Job extends Base_Job implements ShouldQueue {
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
	protected $to_cancel_status;

	/**
	 * @var Tamara_WC_Order
	 */
	protected $tamara_wc_order;

	/**
	 * @throws Tamara_Exception
	 * @throws \Exception
	 */
	public function __construct( array $config ) {
		$this->bind_config( $config );
		$this->tamara_wc_order = $this->build_tamara_wc_order( $this->wc_order_id );
	}

	/**
	 * @throws Tamara_Exception
	 */
	public function handle() {
		if ( ! $this->check_prerequisites() ) {
			return;
		}

		$cancel_request = $this->tamara_wc_order->build_cancel_request();
		$tamara_client_response = $this->tamara_client()->cancel_order( $cancel_request );

		if ( ! is_object( $tamara_client_response ) ) {
			$this->process_failed_action( $tamara_client_response );
		}

		// If Tamara Client returns an object, that would be a successful object
		$this->process_successful_action( $tamara_client_response );
	}

	/**
	 * We do needed thing on successful scenario
	 */
	protected function process_successful_action( CancelResponse $tamara_client_response ): void {
		$tamara_wc_order = $this->tamara_wc_order;

		$cancel_id = $tamara_client_response->getCancelId();
		if ( $cancel_id ) {
			$tamara_wc_order->update_tamara_meta( 'tamara_cancel_id', $cancel_id );
			$order_note = sprintf( $this->_t( 'Order cancelled successfully, Tamara Cancel Id: %s' ), $cancel_id );
			$tamara_wc_order->add_tamara_order_note( $order_note );
		}
	}

	/**
	 * We do needed thing on failed scenario
	 * @param string $tamara_error_message Error message from Tamara API
	 * @return void
	 * @throws Tamara_Exception
	 */
	protected function process_failed_action( string $tamara_error_message ): void {
		$tamara_wc_order = $this->tamara_wc_order;

		$error_message = $this->_t( 'Error when trying to cancel order with Tamara.' );
		$error_message .= "<br />\n";
		$error_message .= sprintf(
			$this->_t( 'Error with Tamara API: %s' ),
			$tamara_error_message
		);
		$tamara_wc_order->add_tamara_order_note( $error_message );

		throw new Tamara_Exception( wp_kses_post( $error_message ) );
	}

	/**
	 * We want to check if we want to start the cancel request or not
	 * @throws Tamara_Exception
	 */
	protected function check_prerequisites(): bool {
		if ( ! $this->tamara_wc_order->is_paid_with_tamara() ) {
			return false;
		}

		return true;
	}
}
