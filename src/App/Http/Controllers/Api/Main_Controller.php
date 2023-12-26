<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Http\Controllers\Api;

use Enpii_Base\Foundation\Http\Base_Controller;
use Illuminate\Http\Request;
use Tamara_Checkout\App\Jobs\Process_Tamara_Order_Approved_Job;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Http\Client\Exception;

class Main_Controller extends Base_Controller {
	public function handle_tamara_success( Request $request, $wc_order_id ): void {
		$tamara_order_id = $request->get( 'orderId' );
		$wc_order = wc_get_order( $wc_order_id );

		// We do nothing for the exception here,
		//  just want to catch all the exception to have the redirect work
		try {
			Process_Tamara_Order_Approved_Job::dispatchSync(
				$tamara_order_id,
				$wc_order_id
			);
			// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		} catch ( Exception $e ) {
		}

		// Then redirect to the URL we want
		$order_received_url = ! empty( $wc_order ) ? esc_url_raw( $wc_order->get_checkout_order_received_url() ) : home_url();
		$success_url_from_tamara = add_query_arg(
			[
				'wc_order_id' => $wc_order_id,
				'tamara_order_id' => $tamara_order_id,
			],
			$order_received_url
		);

		wp_safe_redirect( $success_url_from_tamara );
		exit;
	}

	/**
	 * @throws \Exception
	 */
	public function handle_tamara_cancel( $wc_order_id ): void {
		$wc_order = wc_get_order( $wc_order_id );
		$cancel_url_from_tamara = add_query_arg(
			[
				'redirect_from' => 'tamara',
				'cancel_order' => 'true',
				'order' => $wc_order->get_order_key(),
				'order_id' => $wc_order_id,
				'_wpnonce' => wp_create_nonce( 'woocommerce-cancel_order' ),
			],
			$wc_order->get_cancel_order_url_raw()
		);
		wp_safe_redirect( $cancel_url_from_tamara );
		exit;
	}

	/**
	 * @throws \Exception
	 */
	public function handle_tamara_failure( $wc_order_id ): void {
		$wc_order = wc_get_order( $wc_order_id );
		$failure_url_from_tamara = add_query_arg(
			[
				'redirect_from' => 'tamara',
				'cancel_order' => 'true',
				'order' => $wc_order->get_order_key(),
				'order_id' => $wc_order_id,
				'_wpnonce' => wp_create_nonce( 'woocommerce-cancel_order' ),
			],
			$wc_order->get_cancel_order_url_raw()
		);
		wp_safe_redirect( $failure_url_from_tamara );
		exit;
	}

	/**
	 * @throws \Exception
	 */
	public function handle_tamara_ipn() {
		$authorise_message = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_notification_service()->process_authorise_message();

		Process_Tamara_Order_Approved_Job::dispatchSync(
			$authorise_message->getOrderId(),
			$authorise_message->getOrderReferenceId()
		);

		wp_app_response()->json(
			[
				'message' => 'success',
			]
		);
	}

	public function handle_tamara_webhook( Request $request ): void {
		$webhook_message = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_notification_service()->process_webhook_message();

		switch ( $webhook_message->getEventType() ) {
			case 'order_approved':
				Process_Tamara_Order_Approved_Job::dispatchSync(
					$webhook_message->getOrderId(),
					$webhook_message->getOrderReferenceId()
				);
				break;
			default:
		}

		wp_app_response()->json(
			[
				'message' => 'success',
			]
		);
	}
}
