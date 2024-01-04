<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Bus\Dispatchable_Trait;
use Enpii_Base\Foundation\Shared\Base_Job;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Support\Traits\Trans_Trait;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Request\Order\AuthoriseOrderRequest;
use Tamara_Checkout\Deps\Tamara\Request\Order\GetOrderRequest;

class Process_Tamara_Order_Approved_Job extends Base_Job {
	use Dispatchable_Trait;
	use Trans_Trait;

	protected $wc_order_id;
	protected $tamara_order_id;
	protected $tamara_order_number;
	protected $tamara_order_payment_type;
	protected $tamara_order_instalments;

	public function __construct( $tamara_order_id, $wc_order_id ) {
		$this->tamara_order_id = $tamara_order_id;
		$this->wc_order_id = $wc_order_id;
	}

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 * @throws \WC_Data_Exception
	 * @throws \Exception
	 */
	public function handle() {
		$tamara_order_id =
		$get_order_request = new GetOrderRequest( $this->tamara_order_id );
		/** @var \Tamara_Checkout\Deps\Tamara\Response\Order\GetOrderResponse $tamara_client_response */
		$tamara_client_response = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->get_order( $get_order_request );

		if (
			! is_object( $tamara_client_response ) ||
			$tamara_client_response->getStatus() !== 'approved'
		) {
			throw new Tamara_Exception( wp_kses_post( $this->_t( 'Error! Incorrect Order.' ) ) );
		}

		$this->tamara_order_id = $tamara_client_response->getOrderId();
		$this->tamara_order_number = $tamara_client_response->getOrderNumber();
		$this->tamara_order_payment_type = $tamara_client_response->getPaymentType();
		$this->tamara_order_instalments = $tamara_client_response->getInstalments();

		// We only want to authorise the order if the current status is 'approved'
		/** @var \Tamara_Checkout\Deps\Tamara\Response\Order\AuthoriseOrderResponse $tamara_client_response */
		$tamara_client_response = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->authorise_order( new AuthoriseOrderRequest( $this->tamara_order_id ) );

		if (
			! is_object( $tamara_client_response )
		) {
			$this->process_authorise_failed();
		}

		if ( ! $tamara_client_response->isSuccess() &&
			$tamara_client_response->getStatusCode() !== 409
		) {
			$this->process_authorise_failed();
		}

		$this->process_authorise_successfully();
	}

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	protected function process_authorise_failed(): void {
		$wc_order = wc_get_order( $this->wc_order_id );
		$settings = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_gateway_service()->get_settings();

		$new_order_status = $settings->get_payment_authorised_failed_status();
		$update_order_status_note = 'Tamara - ';
		$update_order_status_note .= $this->_t( 'Order authorised failed.' );
		$wc_order->update_status( $new_order_status, $update_order_status_note, true );

		throw new Tamara_Exception( wp_kses_post( $this->_t( 'Order authorised failed.' ) ) );
	}

	/**
	 * @throws \WC_Data_Exception
	 */
	protected function process_authorise_successfully(): void {
		$wc_order_id = $this->wc_order_id;
		$wc_order = wc_get_order( $wc_order_id );
		$settings = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_gateway_service()->get_settings();

		$new_order_status = $settings->get_payment_authorised_done_status();
		$update_order_status_note = 'Tamara - ';
		$update_order_status_note .= $this->_t( 'Order authorised successfully.' );
		$update_order_status_note .= "\n<br/>";
		$update_order_status_note .= sprintf( $this->_t( 'Tamara Payment Type %s, Instalments %d.' ), $this->tamara_order_payment_type, $this->tamara_order_instalments );
		$update_order_status_note .= "\n<br/>";
		$wc_order->update_status( $new_order_status, $update_order_status_note, true );

		// We may want to reupdate the meta for tamara_order_id if it is deleted
		update_post_meta( $wc_order_id, 'tamara_order_id', $this->tamara_order_id );
		update_post_meta( $wc_order_id, '_tamara_order_id', $this->tamara_order_id );
		update_post_meta( $wc_order_id, 'tamara_order_number', $this->tamara_order_number );
		update_post_meta( $wc_order_id, '_tamara_order_number', $this->tamara_order_number );
		update_post_meta( $wc_order_id, 'tamara_payment_type', $this->tamara_order_payment_type );
		update_post_meta( $wc_order_id, '_tamara_payment_type', $this->tamara_order_payment_type );
		update_post_meta( $wc_order_id, 'tamara_instalments', $this->tamara_order_instalments );
		update_post_meta( $wc_order_id, '_tamara_instalments', $this->tamara_order_instalments );

		// Use the default Payment Gateway for the order after all
		$wc_order->set_payment_method( Tamara_Checkout_WP_Plugin::DEFAULT_TAMARA_GATEWAY_ID );
		$wc_order->save();
	}
}
