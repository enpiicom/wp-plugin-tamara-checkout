<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Http\Controllers\Api;

use Enpii_Base\Foundation\Http\Base_Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class Main_Controller extends Base_Controller {
	public function handle_tamara_success( Request $request, $wc_order_id ): void {
		$wc_order = wc_get_order( $wc_order_id );
		$payment_type = $request->get( 'payment_type' );
		$order_received_url = ! empty( $wc_order ) ? esc_url_raw( $wc_order->get_checkout_order_received_url() ) : home_url();
		$success_url_from_tamara = add_query_arg(
			[
				'wc_order_id' => $wc_order_id,
				'payment_method' => Tamara_Checkout_WP_Plugin::DEFAULT_TAMARA_GATEWAY_ID,
				'payment_type' => $payment_type,
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

	public function handle_tamara_notification(): JsonResponse {
		return wp_app_response()->json(
			[
				'message' => 'WP App API - Tamara IPN Url',
			]
		);
	}

	public function handle_tamara_webhook(): JsonResponse {
		return wp_app_response()->json(
			[
				'message' => 'WP App API - Tamara Webhook Url',
			]
		);
	}
}
