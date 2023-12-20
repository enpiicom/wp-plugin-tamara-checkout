<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Helpers;

use Exception;
use WC_Order;

class WC_Order_Helper {

	/**
	 * Prevent an order is cancelled from FE if its payment has been authorised from Tamara
	 *
	 * @param  WC_Order  $wc_order
	 * @param $wc_order_id
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function prevent_order_cancel_action( WC_Order $wc_order, $wc_order_id ): void {
		$order_note = sprintf(
			General_Helper::convert_message(
				'This order can not be cancelled because the payment was authorised from Tamara.
			Order ID: %s'
			),
			$wc_order_id
		);
		$wc_order->add_order_note( $order_note );
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}


	/**
	 * Update order status and add order note wrapper
	 *
	 * @param  WC_Order  $wc_order
	 * @param  string  $order_note
	 * @param  string  $new_order_status
	 * @param  string  $update_order_status_note
	 *
	 */
	public static function update_order_status_and_add_order_note( WC_Order $wc_order, string $order_note, string $new_order_status, string $update_order_status_note ): void {
		if ( $wc_order ) {
			General_Helper::log_message(
				sprintf(
					'Tamara - Prepare to Update Order Status - Order ID: %s, Order Note: %s, new order status: %s, order status note: %s',
					$wc_order->get_id(),
					$order_note,
					$new_order_status,
					$update_order_status_note
				)
			);
			try {
				$wc_order->add_order_note( $order_note );
				$wc_order->update_status( $new_order_status, $update_order_status_note, true );
			} catch ( Exception $exception ) {
				General_Helper::log_message(
					sprintf(
						'Tamara - Failed to Update Order Status - Order ID: %s, Order Note: %s, new order status: %s, order status note: %s. Error Message: %s',
						$wc_order->get_id(),
						$order_note,
						$new_order_status,
						$update_order_status_note,
						$exception->getMessage()
					)
				);
			}
		}
	}
}
