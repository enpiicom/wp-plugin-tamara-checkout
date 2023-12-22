<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Exception;
use Tamara_Checkout\App\Support\Helpers\Tamara_Order_Helper;
use Tamara_Checkout\App\Support\Helpers\WC_Order_Helper;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Notification\NotificationService;
use Tamara_Checkout\Deps\Tamara\Request\Order\AuthoriseOrderRequest;
use Tamara_Checkout\Deps\Tamara\Response\Order\AuthoriseOrderResponse;

class Tamara_Notification {
	use Static_Instance_Trait;

	protected $notification_key;

	protected $notification_service;

	protected function __construct( $notification_key, $working_mode = 'live' ) {
		if (!empty($notification_key)) {
			$this->notification_key = $notification_key;
			$this->notification_service = $this->build_notification_service( $notification_key );
		}
	}

	public function handle_webhook_request() {
	}

	/**
	 * @throws \Tamara_Checkout\Deps\Tamara\Notification\Exception\ForbiddenException
	 * @throws \Tamara_Checkout\Deps\Tamara\Notification\Exception\NotificationException
	 * @throws \Exception
	 */
	public function handle_ipn_request() {
		$notification = $this->build_notification_service($this->notification_key);
		$message = $notification->processAuthoriseNotification();

		if (!empty($message)) {
			$wc_order_id = $message->getOrderReferenceId();
			$tamara_order_id = $message->getOrderId();

			Tamara_Order_Helper::update_tamara_order_id_to_wc_order($wc_order_id, $tamara_order_id);

			if ( ! Tamara_Order_Helper::is_order_authorised($wc_order_id) && ('approved' === $message->getOrderStatus())) {
				$this->authorise_order($wc_order_id, $tamara_order_id);
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
	protected function authorise_order($wc_order_id, $tamara_order_id) {
		#res = remote-query
//		if res true();
//			true job
//		else
//			false job
		$authorise_order_response = $this->build_authorise_order_response($tamara_order_id);

		if ( !empty($authorise_order_response) ) {
			$wc_order = wc_get_order($wc_order_id);
			$setting = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_gateway_service()->get_settings();
			if ($authorise_order_response->isSuccess()) {
				// Empty cart if payment done
				WC()->cart->empty_cart();
				Tamara_Order_Helper::update_tamara_order_id_to_wc_order($wc_order_id, $tamara_order_id);

				$order_note = $this->_t('Tamara - Order authorised successfully with Tamara Notification');
				$new_order_status = $setting->get_payment_authorised_done_status();
				$update_order_status_note = $this->_t('Payment received. ');

				WC_Order_Helper::update_order_status_and_add_order_note(
					$wc_order,
					$order_note,
					$new_order_status,
					$update_order_status_note
				);

			} elseif ($this->is_authorized_response($authorise_order_response)) {
				$error_message = $this->_t('Tamara - Order authorised re-occurred, ignore it.');
			} else {
				$order_note = 'Tamara - Order authorised failed with Tamara Notification';
				$new_order_status = $setting->get_payment_authorised_failed_status();
				$update_order_status_note = $this->_t('Tamara - Order authorised failed.');
				WC_Order_Helper::update_order_status_and_add_order_note(
					$wc_order,
					$order_note,
					$new_order_status,
					$update_order_status_note
				);
			}
		}
	}

	/**
	 * @throws \Tamara_Checkout\Deps\Tamara\Exception\RequestDispatcherException
	 */
	protected function build_authorise_order_response($tamara_order_id) : AuthoriseOrderResponse {
		$tamara_client = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->get_api_client();
		return $tamara_client->authoriseOrder(new AuthoriseOrderRequest($tamara_order_id));
	}

	protected function update_wc_order_payment_method($wc_order_id) {
		$payment_type = '';
		update_post_meta($wc_order_id, '_tamara_payment_type', $payment_type);
	}

	/**
	 * Check if a response telling an order is authorised
	 *
	 * @param AuthoriseOrderResponse $response
	 *
	 * @return bool
	 */
	protected function is_authorized_response(AuthoriseOrderResponse $response): bool {
		return $response->getStatusCode() === 409;
	}


	/**
	 * @param $untranslated_text
	 *
	 * @return string
	 * @throws \Exception
	 */
	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	protected function _t( $untranslated_text ): string {
		return Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( $untranslated_text );
	}
}
