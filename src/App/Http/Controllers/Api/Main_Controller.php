<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Http\Controllers\Api;

use Enpii_Base\Foundation\Http\Base_Controller;
use Illuminate\Http\JsonResponse;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class Main_Controller extends Base_Controller {
	public function handle_tamara_success(): JsonResponse {
		return wp_app_response()->json(
			[
				'message' => 'WP App API - Tamara Success Url',
			]
		);
	}

	/**
	 * @throws \Exception
	 */
	public function handle_tamara_cancel( string $wc_order_id ): void {
		$tamara_client = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service();
		$tamara_client->handle_tamara_payment_cancel_url( $wc_order_id );
	}

	/**
	 * @throws \Exception
	 */
	public function handle_tamara_failure( string $wc_order_id ): void {
		$tamara_client = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service();
		$tamara_client->handle_tamara_payment_failure_url( $wc_order_id );
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
