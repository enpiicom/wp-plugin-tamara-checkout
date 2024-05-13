<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Shared\Base_Job;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;

class Complete_Order_If_Tamara_Captured_Job extends Base_Job implements ShouldQueue {
	use Dispatchable;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;

	use Tamara_Trans_Trait;
	use Tamara_Checkout_Trait;

	protected $wc_order_id;
	protected $tamara_wc_order;

	/**
	 * @throws Tamara_Exception
	 * @throws \Exception
	 */
	public function __construct( int $wc_order_id ) {
		$this->wc_order_id = $wc_order_id;
		$this->tamara_wc_order = new Tamara_WC_Order( wc_get_order( $this->wc_order_id ) );

		parent::__construct();
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

		$tamara_wc_order = $this->tamara_wc_order;
		$tamara_capture_id = $this->tamara_wc_order->get_tamara_capture_id();
		$tamara_capture_amount = $this->tamara_wc_order->get_tamara_capture_amount();
		$new_order_status = $this->tamara_gateway()->get_settings_vo()->order_status_to_capture_tamara_payment;

		$tamara_wc_order->add_tamara_order_note(
			sprintf(
				$this->__( 'Update order status because the order is captured on Tamara. Capture Id: %s, Captured Amount %s.' ),
				$tamara_capture_id,
				$tamara_capture_amount
			)
		);
		$tamara_wc_order->get_wc_order()->update_status( $new_order_status );
	}

	/**
	 * We want to check if we want to start the capture request or not
	 * @throws Tamara_Exception
	 */
	protected function check_prerequisites(): bool {
		$this->tamara_wc_order->reupdate_tamara_meta_from_remote();
		if (
			! $this->tamara_wc_order->is_paid_with_tamara() ||
			empty( $this->tamara_wc_order->get_tamara_order_id() )
		) {
			return false;
		}

		if ( $this->tamara_wc_order->get_tamara_meta( 'tamara_payment_status' ) !== Tamara_Checkout_Helper::TAMARA_ORDER_STATUS_FULLY_CAPTURED ) {
			return false;
		}

		return true;
	}
}
