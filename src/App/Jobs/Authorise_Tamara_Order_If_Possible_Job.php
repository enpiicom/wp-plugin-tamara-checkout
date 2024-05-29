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
use Tamara_Checkout\App\DTOs\WC_Order_Tamara_Meta_DTO;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\VOs\Tamara_Api_Error_VO;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;
use Tamara_Checkout\Deps\Tamara\Request\Order\AuthoriseOrderRequest;
use Tamara_Checkout\Deps\Tamara\Request\Order\GetOrderByReferenceIdRequest;

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

	/**
	 *
	 * @param array $config shoud be ['wc_order_id' => $value]
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws Exception
	 * @throws Tamara_Exception
	 */
	public function __construct( array $config ) {
		$this->bind_config( $config );
		if ( empty( $this->wc_order_id ) ) {
			throw new Tamara_Exception( wp_kses_post( $this->__( 'Error! Incorrect Order.' ) ) );
		}

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
		return [ 'site_id_' . $this->site_id, 'tamara:api', 'tamara_order:authorise' ];
	}

	public function handle() {
		$this->before_handle();

		if ( ! $this->check_prerequisites() ) {
			return;
		}

		// At this stage, we have all Tamara meta data updated
		$this->tamara_wc_order = $this->build_tamara_wc_order( $this->wc_order_id );
		$this->tamara_order_id = $this->tamara_wc_order->get_tamara_order_id();

		$tamara_client_response = $this->tamara_client()->authorise_order( new AuthoriseOrderRequest( $this->tamara_order_id ) );

		$this->tamara_wc_order->set_authorise_checked();

		if ( $tamara_client_response instanceof Tamara_Api_Error_VO ) {
			$this->process_failed_action( $tamara_client_response );

			return;
		}

		$this->process_successful_action();
	}

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	protected function process_failed_action( Tamara_Api_Error_VO $tamara_api_error ): void {
		if ( (int) $tamara_api_error->status_code === 409 ) {
			return;
		}

		$wc_order = wc_get_order( $this->wc_order_id );
		$settings = $this->tamara_settings();

		$new_order_status = $settings->order_status_when_tamara_authorisation_fails;
		$update_order_status_note = 'Tamara - ';
		$update_order_status_note .= $this->__( 'Order authorisation process failed.' );
		$wc_order->update_status( $new_order_status, $update_order_status_note );

		throw new Tamara_Exception( wp_kses_post( $this->__( 'Order authorised failed.' ) ) );
	}

	/**
	 * @throws \WC_Data_Exception
	 */
	protected function process_successful_action(): void {
		// We want to re-build a new Tamara_WC_Order here
		//  to have refreshed meta data
		$this->tamara_wc_order = $this->build_tamara_wc_order( $this->wc_order_id );

		// We don't want to proceed if the order is processed with Tamara API
		//  based on meta in our database
		$tamara_payment_status = $this->tamara_wc_order->get_tamara_meta( 'tamara_payment_status' );
		if ( ! empty( $tamara_payment_status ) && ( $tamara_payment_status !== Tamara_Checkout_Helper::TAMARA_EVENT_TYPE_ORDER_APPROVED ) ) {
			return;
		}

		// Use the default Payment Gateway for the order after all
		$this->tamara_wc_order->get_wc_order()->set_payment_method( $this->default_payment_gateway_id() );
		$this->tamara_wc_order->get_wc_order()->save();

		$new_order_status = $this->tamara_settings()->order_status_on_tamara_authorised;
		$this->tamara_wc_order->add_tamara_order_note( $this->__( 'Order authorised successfully.' ) );

		if ( $this->tamara_wc_order->get_tamara_instalments() ) {
			$update_order_status_note = 'Tamara - ' . sprintf(
				$this->__( 'Payment Status: %s, Payment Type: %s, Payment Instalments: %s.' ),
				$this->tamara_wc_order->get_tamara_payment_status(),
				$this->tamara_wc_order->get_tamara_payment_type(),
				$this->tamara_wc_order->get_tamara_instalments()
			);
		} else {
			$update_order_status_note = 'Tamara - ' . sprintf(
				$this->__( 'Payment Status: %s, Payment Type: %s.' ),
				$this->tamara_wc_order->get_tamara_payment_status(),
				$this->tamara_wc_order->get_tamara_payment_type()
			);
		}
		$this->tamara_wc_order->get_wc_order()->update_status( $new_order_status, $update_order_status_note, false );
	}

	/**
	 * We want to check if we want to start the authorise request or not
	 *  By the way, we need to re-update Tamara's meta data to the db
	 * @throws Tamara_Exception
	 */
	protected function check_prerequisites(): bool {
		$wc_order_id = $this->wc_order_id;
		$this->tamara_wc_order = $this->build_tamara_wc_order( $this->wc_order_id );
		$tamara_wc_order = $this->tamara_wc_order;

		if ( ! $tamara_wc_order->is_paid_with_tamara() ) {
			return false;
		}

		$tamara_client_response = $this->tamara_client()->get_order_by_reference_id( new GetOrderByReferenceIdRequest( (string) $this->wc_order_id ) );

		if (
			$tamara_client_response instanceof Tamara_Api_Error_VO ||
			( $tamara_client_response->getStatus() !== Tamara_Checkout_Helper::TAMARA_ORDER_STATUS_APPROVED &&
			$tamara_client_response->getStatus() !== Tamara_Checkout_Helper::TAMARA_ORDER_STATUS_AUTHORISED )
		) {
			throw new Tamara_Exception( wp_kses_post( $this->__( 'Error! Incorrect Order. WC Order ID: ' . $this->wc_order_id . ', Tamara order Status: ' . $tamara_client_response->getStatus() ) ) );
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
