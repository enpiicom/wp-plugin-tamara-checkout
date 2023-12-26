<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Enpii_Base\Foundation\Shared\Base_Query;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;
use Exception;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use WC_Order;

class Process_Payment_With_Tamara_Query extends Base_Query {
	use Executable_Trait;

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
	protected $instalment_period = 0;

	public function __construct(
		WC_Order $wc_order,
		string $payment_type,
		int $instalment_period = 0
	) {
		$this->wc_order = $wc_order;
		$this->payment_type = $payment_type;
		$this->instalment_period = $instalment_period;

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
		$create_checkout_request = Build_Tamara_Create_Checkout_Request_Query::execute_now(
			$this->wc_order,
			$this->payment_type,
			$this->instalment_period
		);

		$create_checkout_response = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->create_checkout_request( $create_checkout_request );

		if ( ! is_object( $create_checkout_response ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( $create_checkout_response, 'error' );
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
			$this->instalment_period
		);

		return [
			'result' => 'success',
			'redirect' => $create_checkout_response->getCheckoutResponse()->getCheckoutUrl(),
			'tamara_checkout_url' => $create_checkout_response->getCheckoutResponse()->getCheckoutUrl(),
			'tamara_checkout_session_id' => $create_checkout_response->getCheckoutResponse()->getCheckoutId(),
		];
	}

	protected function store_meta_data_from_checkout_response( int $wc_order_id, $checkout_session_id, $checkout_url, $checkout_payment_type, $checkout_instalment_period ): void {
		update_post_meta( $wc_order_id, 'tamara_checkout_session_id', $checkout_session_id );
		update_post_meta( $wc_order_id, '_tamara_checkout_session_id', $checkout_session_id );
		update_post_meta( $wc_order_id, 'tamara_checkout_url', $checkout_url );
		update_post_meta( $wc_order_id, '_tamara_checkout_url', $checkout_url );
		update_post_meta( $wc_order_id, 'tamara_payment_type', $checkout_payment_type );
		update_post_meta( $wc_order_id, '_tamara_payment_type', $checkout_payment_type );
		if ( $checkout_payment_type === 'PAY_BY_INSTALMENTS' && ! empty( $checkout_instalment_period ) ) {
			update_post_meta( $wc_order_id, 'tamara_instalment_period', $checkout_instalment_period );
			update_post_meta( $wc_order_id, '_tamara_instalment_period', $checkout_instalment_period );
		}
	}
}
