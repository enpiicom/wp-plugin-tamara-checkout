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
use Tamara_Checkout\App\DTOs\WC_Order_Tamara_Meta_DTO;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;
use Tamara_Checkout\Deps\Tamara\Request\Order\AuthoriseOrderRequest;
use Tamara_Checkout\Deps\Tamara\Request\Order\GetOrderRequest;

class Authorise_Tamara_Order_If_Possible_Job extends Base_Job implements ShouldQueue {
	use Dispatchable;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;
	use Config_Trait;
	use Tamara_Trans_Trait;
	use Tamara_Checkout_Trait;

	protected $wc_order_id;
	protected $tamara_order_id;

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
	}

	public function handle() {
		if ( ! $this->check_prerequisites() ) {
			return;
		}

		// At this stage, we have all Tamara meta data updated
		$this->tamara_wc_order = $this->build_tamara_wc_order( $this->wc_order_id );
		$this->tamara_order_id = $this->tamara_wc_order->get_tamara_order_id();

		$tamara_client_response = $this->tamara_client()->authorise_order( new AuthoriseOrderRequest( $this->tamara_order_id ) );

		if ( ! is_object( $tamara_client_response ) ) {
			$this->process_authorise_failed();
		}

		if (
			! $tamara_client_response->isSuccess() &&
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
		$settings = $this->tamara_settings();

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
		// We want to re-build a new Tamara_WC_Order here
		//  to have refreshed meta data
		$this->tamara_wc_order = $this->build_tamara_wc_order( $this->wc_order_id );

		$new_order_status = $this->tamara_settings()->get_payment_authorised_done_status();
		$update_order_status_note = 'Tamara - ';
		$update_order_status_note .= $this->_t( 'Order authorised successfully.' );
		$update_order_status_note .= "\n<br/>";
		$update_order_status_note .= 'Tamara - ' . sprintf( $this->_t( 'Payment Type: %s, Instalments: %d, Payment Status: %s.' ), $this->tamara_wc_order->get_tamara_payment_type(), $this->tamara_wc_order->get_tamara_instalments(), $this->tamara_wc_order->get_tamara_payment_status() );
		$update_order_status_note .= "\n<br/>";
		$this->tamara_wc_order->get_wc_order()->update_status( $new_order_status, $update_order_status_note, true );

		// Use the default Payment Gateway for the order after all
		$this->tamara_wc_order->get_wc_order()->set_payment_method( $this->default_payment_gateway_id() );
		$this->tamara_wc_order->get_wc_order()->save();
	}

	/**
	 * We want to check if we want to start the authorise request or not
	 *  By the way, we need to re-update Tamara's meta data to the db
	 * @throws Tamara_Exception
	 */
	protected function check_prerequisites(): bool {
		$wc_order_id = $this->wc_order_id;
		$tamara_wc_order = new Tamara_WC_Order( wc_get_order( $wc_order_id ) );

		if ( ! $tamara_wc_order->is_paid_with_tamara() ) {
			return false;
		}

		$tamara_client_response = $this->tamara_client()->get_order( new GetOrderRequest( $this->tamara_order_id ) );

		if (
			! is_object( $tamara_client_response ) ||
			( $tamara_client_response->getStatus() !== Tamara_Checkout_Helper::TAMARA_ORDER_STATUS_APPROVED &&
			$tamara_client_response->getStatus() !== Tamara_Checkout_Helper::TAMARA_ORDER_STATUS_AUTHORISED )
		) {
			throw new Tamara_Exception( wp_kses_post( $this->_t( 'Error! Incorrect Order.' ) ) );
		}

		$tamara_meta_dto = new WC_Order_Tamara_Meta_DTO();
		$tamara_meta_dto->tamara_order_id = $tamara_client_response->getOrderId();
		$tamara_meta_dto->tamara_order_number = $tamara_client_response->getOrderNumber();
		$tamara_meta_dto->tamara_payment_type = $tamara_client_response->getPaymentType();
		$tamara_meta_dto->tamara_instalments = $tamara_client_response->getInstalments();
		$tamara_meta_dto->tamara_payment_status = Tamara_Checkout_Helper::TAMARA_ORDER_STATUS_AUTHORISED;

		// We may want to reupdate the meta for tamara_order_id if it is deleted
		$this->update_tamara_meta_data( (int) $wc_order_id, $tamara_meta_dto );

		return true;
	}
}
