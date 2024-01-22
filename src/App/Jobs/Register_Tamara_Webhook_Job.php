<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Shared\Base_Job;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tamara_Checkout\App\Services\Tamara_Client;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\VOs\Tamara_Api_Error_VO;
use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;
use Tamara_Checkout\Deps\Tamara\Request\Webhook\RegisterWebhookRequest;
use Tamara_Checkout\Deps\Tamara\Response\Webhook\RegisterWebhookResponse;

class Register_Tamara_Webhook_Job extends Base_Job implements ShouldQueue {
	use Dispatchable;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;

	use Tamara_Trans_Trait;
	use Tamara_Checkout_Trait;

	/**
	 * We want to retry this job if it is not a succesful one
	 *  after this amount of seconds
	 * @return int
	 */
	public function backoff() {
		return 700;
	}

	/**
	 * Set tag for filtering
	 * @return string[]
	 */
	public function tags() {
		return [ 'site_id_' . $this->site_id, 'tamara:api', 'webhook:register' ];
	}

	/**
	 * @param  \Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway  $tamara_gateway_service
	 * @param  \Tamara_Checkout\App\Services\Tamara_Client  $tamara_client_service
	 *
	 * @throws \Exception
	 */
	public function handle( Tamara_WC_Payment_Gateway $tamara_gateway_service, Tamara_Client $tamara_client_service ) {
		// We must call this for the queue to be able to see
		//  which site (blog) the job belongs to
		$this->before_handle();

		// We need to get a refreshed settings from the Payment Gateway
		$gateway_settings = $tamara_gateway_service->get_settings( true );
		$tamara_client_service->init_tamara_client( $gateway_settings->api_token, $gateway_settings->api_url, $gateway_settings );

		$tamara_client_response = $tamara_client_service->register_webhook(
			new RegisterWebhookRequest(
				wp_app_route_wp_url( 'wp-api::tamara-webhook' ),
				$this->get_tamara_webhook_events()
			)
		);

		if ( $tamara_client_response instanceof Tamara_Api_Error_VO ) {
			$this->process_failed_action( $tamara_client_response );

			return;
		} else {
			$this->process_successful_action( $tamara_client_response );
		}
	}

	protected function process_failed_action( Tamara_Api_Error_VO $tamara_client_response ) {
		$error_code = $tamara_client_response->errors[0]['error_code'] ?? null;
		if ( $error_code === 'webhook_already_registered' ) {
			$this->tamara_gateway()->update_option( 'tamara_webhook_id', $tamara_client_response->errors[0]['data']['webhook_id'] ?? '' );
		} else {
			throw new Exception(
				wp_kses_post(
					sprintf(
						$this->_t( 'Tamara Service timeout or disconnected. Error message: "%s".' ),
						esc_attr( $tamara_client_response->error_message )
					)
				)
			);
		}
	}

	protected function process_successful_action( RegisterWebhookResponse $tamara_client_response ) {
		$this->tamara_gateway()->update_option( 'tamara_webhook_id', $tamara_client_response->getWebhookId() );

		$this->tamara_gateway()->get_settings( true );
	}

	/**
	 * @return array
	 */
	protected function get_tamara_webhook_events(): array {
		return [
			Tamara_Checkout_Helper::TAMARA_EVENT_TYPE_ORDER_APPROVED,
			Tamara_Checkout_Helper::TAMARA_EVENT_TYPE_ORDER_DECLINED,
			Tamara_Checkout_Helper::TAMARA_EVENT_TYPE_ORDER_CANCELED,
			Tamara_Checkout_Helper::TAMARA_EVENT_TYPE_ORDER_AUTHORISED,
			Tamara_Checkout_Helper::TAMARA_EVENT_TYPE_ORDER_CAPTURED,
			Tamara_Checkout_Helper::TAMARA_EVENT_TYPE_ORDER_REFUNDED,
			Tamara_Checkout_Helper::TAMARA_EVENT_TYPE_ORDER_EXPIRED,
		];
	}
}
