<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Enpii_Base\App\Support\Traits\Queue_Trait;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;
use Exception;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Jobs\Authorise_Tamara_Order_If_Possible_Job;
use Tamara_Checkout\App\VOs\Tamara_Api_Error_VO;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Request\Checkout\CreateCheckoutRequest;
use WC_Order;

class Process_Payment_With_Tamara {
	use Executable_Trait;
	use Queue_Trait;

	/**
	 * @var WC_Order
	 */
	protected $wc_order;

	/**
	 *
	 * @var string
	 */
	protected $payment_type;

	/**
	 *
	 * @var int
	 */
	protected $instalments = 0;

	public function __construct(
		WC_Order $wc_order,
		string $payment_type,
		int $instalments = 0
	) {
		$this->wc_order = $wc_order;
		$this->payment_type = $payment_type;
		$this->instalments = $instalments;

		if ( empty( $payment_type ) ) {
			throw new Tamara_Exception( 'Error! No Payment Type specified' );
		}
	}

	/**
	 * Connect to Tamara API to create a checkout session on Tamara
	 *  successful attempt should return an array with 'result' => 'success'
	 *  and 'redirect' => url for WooCommerce to proceed to checkout externally
	 *
	 * @return false|array Return false if failed
	 * @throws BindingResolutionException
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function handle() {
		$tamara_wc_order = new Tamara_WC_Order( $this->wc_order );
		$create_checkout_request = new CreateCheckoutRequest( $tamara_wc_order->build_tamara_order( $this->payment_type, $this->instalments ) );

		$create_checkout_response = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->create_checkout( $create_checkout_request );

		if ( $create_checkout_response instanceof Tamara_Api_Error_VO ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( $create_checkout_response->error_message, 'error' );
			}

			// If this is the failed process, return false instead of ['result' => 'success']
			return false;
		}

		/** @var \Tamara_Checkout\Deps\Tamara\Response\Checkout\CreateCheckoutResponse $create_checkout_response */
		$this->store_meta_data_from_checkout_response(
			$this->wc_order->get_id(),
			$create_checkout_response->getCheckoutResponse()->getCheckoutId(),
			$create_checkout_response->getCheckoutResponse()->getCheckoutUrl(),
			$this->payment_type,
			$this->instalments
		);

		// We want to enqueue a job to authorise the order 14 and 49 minutes later
		//	in case no notification or webhook sent by Tamara
		$this->enqueue_job(
			Authorise_Tamara_Order_If_Possible_Job::dispatch(
				[
					'wc_order_id' => $this->wc_order->get_id(),
				]
			)
		)->delay( now()->addMinutes( 14 ) );

		$this->enqueue_job(
			Authorise_Tamara_Order_If_Possible_Job::dispatch(
				[
					'wc_order_id' => $this->wc_order->get_id(),
				]
			)
		)->delay( now()->addMinutes( 49 ) );

		return [
			'result' => 'success',
			'redirect' => $create_checkout_response->getCheckoutResponse()->getCheckoutUrl(),
			'tamara_checkout_url' => $create_checkout_response->getCheckoutResponse()->getCheckoutUrl(),
			'tamara_checkout_session_id' => $create_checkout_response->getCheckoutResponse()->getCheckoutId(),
		];
	}

	protected function store_meta_data_from_checkout_response( int $wc_order_id, $checkout_session_id, $checkout_url, $checkout_payment_type, $checkout_instalments ): void {
		update_post_meta( $wc_order_id, 'tamara_checkout_session_id', $checkout_session_id );
		update_post_meta( $wc_order_id, '_tamara_checkout_session_id', $checkout_session_id );
		update_post_meta( $wc_order_id, 'tamara_checkout_url', $checkout_url );
		update_post_meta( $wc_order_id, '_tamara_checkout_url', $checkout_url );
		update_post_meta( $wc_order_id, 'tamara_payment_type', $checkout_payment_type );
		update_post_meta( $wc_order_id, '_tamara_payment_type', $checkout_payment_type );
		if ( $checkout_payment_type === 'PAY_BY_INSTALMENTS' && ! empty( $checkout_instalments ) ) {
			update_post_meta( $wc_order_id, 'tamara_instalments', $checkout_instalments );
			update_post_meta( $wc_order_id, '_tamara_instalments', $checkout_instalments );
		}
	}
}
