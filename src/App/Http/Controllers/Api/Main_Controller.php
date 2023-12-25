<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Http\Controllers\Api;

use Enpii_Base\Foundation\Http\Base_Controller;
use Illuminate\Http\JsonResponse;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Http\Client\Exception;
use Tamara_Checkout\Deps\Tamara\Notification\Exception\ForbiddenException;
use Tamara_Checkout\Deps\Tamara\Notification\Exception\NotificationException;

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

	/**
	 * @throws \Exception
	 */
	public function handle_tamara_ipn() {
		$error = false;
		try {
			$tamara_notification = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_notification_service();
			$tamara_notification->handle_ipn_request();
		} catch ( ForbiddenException $forbidden_exception ) {
			$error = true;
		} catch ( NotificationException $notification_exception ) {
			$error = true;
		} catch ( Exception $exception ) {
			$error = true;
		}

		if ( $error ) {
			wp_app_response()->json(
				[
					'message' => 'failure',
				],
				400
			);
		}

		wp_app_response()->json(
			[
				'message' => 'success',
			]
		);
	}

	public function handle_tamara_webhook(): void {
		//      $tamara_notification = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_notification_service();
		//      $tamara_notification->handle_webhook_request();
	}
}
