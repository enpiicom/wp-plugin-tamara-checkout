<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Exception;
use Tamara_Checkout\App\Jobs\Authorise_Tamara_Order_Job_Failed;
use Tamara_Checkout\App\Jobs\Authorise_Tamara_Order_Job_Success;
use Tamara_Checkout\App\Support\Helpers\Tamara_Order_Helper;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Notification\NotificationService;
use Tamara_Checkout\Deps\Tamara\Request\Order\AuthoriseOrderRequest;
use Tamara_Checkout\Deps\Tamara\Response\Order\AuthoriseOrderResponse;

class Tamara_Notification {
	use Static_Instance_Trait;

	protected $notification_key;

	protected $notification_service;

	protected function __construct( $notification_key, $working_mode = 'live' ) {
		$this->notification_key = $notification_key;
		$this->notification_service = $this->build_notification_service( (string) $notification_key );
	}

	public function handle_webhook_request() {
	}

	/**
	 * @throws \Tamara_Checkout\Deps\Tamara\Notification\Exception\ForbiddenException
	 * @throws \Tamara_Checkout\Deps\Tamara\Notification\Exception\NotificationException
	 * @throws \Exception
	 */
	public function handle_ipn_request() {
		$notification = $this->build_notification_service( $this->notification_key );
		$message = $notification->processAuthoriseNotification();

		if ( ! empty( $message ) ) {
			$wc_order_id = $message->getOrderReferenceId();
			$tamara_order_id = $message->getOrderId();

			Tamara_Order_Helper::update_tamara_order_id_to_wc_order( $wc_order_id, $tamara_order_id );

			if ( ! Tamara_Order_Helper::is_order_authorised( $wc_order_id ) && ( $message->getOrderStatus() === 'approved' ) ) {
				$this->authorise_order( $wc_order_id, $tamara_order_id );
			}
		}
	}

	protected function reinit_notification_service( $notification_key ): void {
		$notification_service = $this->build_notification_service( $notification_key );
		static::$instance->notification_service = $notification_service;
	}

	protected function build_notification_service( $notification_key ): NotificationService {
		return NotificationService::create( $notification_key );
	}

	/**
	 * @throws \Exception
	 */
	protected function authorise_order( $wc_order_id, $tamara_order_id ) {
		$authorise_order_response = $this->build_authorise_order_response( $tamara_order_id );
		if ( ! empty( $authorise_order_response ) ) {
			if ( $authorise_order_response->isSuccess() ) {
				Authorise_Tamara_Order_Job_Success::execute_now( $wc_order_id, $tamara_order_id );
			} elseif ( $this->is_authorized_response( $authorise_order_response ) ) {
				$error_message = Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'Tamara - Order authorised re-occurred, ignore it.' );
			} else {
				Authorise_Tamara_Order_Job_Failed::execute_now( $wc_order_id );
			}
		}
	}

	/**
	 * @throws \Tamara_Checkout\Deps\Tamara\Exception\RequestDispatcherException
	 */
	protected function build_authorise_order_response( $tamara_order_id ): AuthoriseOrderResponse {
		$tamara_client = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->get_api_client();
		return $tamara_client->authoriseOrder( new AuthoriseOrderRequest( $tamara_order_id ) );
	}

	/**
	 * Check if a response telling an order is authorised
	 *
	 * @param AuthoriseOrderResponse $response
	 *
	 * @return bool
	 */
	protected function is_authorized_response( AuthoriseOrderResponse $response ): bool {
		return $response->getStatusCode() === 409;
	}
}
