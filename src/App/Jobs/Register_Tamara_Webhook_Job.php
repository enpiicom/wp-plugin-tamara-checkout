<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Bus\Dispatchable_Trait;
use Enpii_Base\Foundation\Shared\Base_Job;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tamara_Checkout\App\Services\Tamara_Client;
use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;
use Tamara_Checkout\Deps\Tamara\Request\Webhook\RegisterWebhookRequest;

class Register_Tamara_Webhook_Job extends Base_Job implements ShouldQueue {
	use Dispatchable_Trait;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;

	/**
	 * @throws \Exception
	 */
	public function handle( Tamara_WC_Payment_Gateway $tamara_gateway_service, Tamara_Client $tamara_client_service ) {
		// We need to get a refreshed settings from the Payment Gateway
		$gateway_settings = $tamara_gateway_service->get_settings( true );
		$webhook_id = $gateway_settings->tamara_webhook_id ?? null;
		$tamara_client_service->reinit_tamara_client( $gateway_settings->api_token, $gateway_settings->api_url );

		if ( empty( $webhook_id ) || ! is_string( $webhook_id ) ) {
			$tamara_api_request = new RegisterWebhookRequest(
				wp_app_route_wp_url( 'tamara-webhook' ),
				[
					'order_approved',
					'order_declined',
					'order_authorised',
					'order_canceled',
					'order_captured',
					'order_refunded',
					'order_expired',
				]
			);

			try {
				$tamara_api_response = $tamara_client_service->get_api_client()->registerWebhook( $tamara_api_request );
				if ( $tamara_api_response->isSuccess() ) {
					$this->update_webhook_id_to_options(
						$tamara_gateway_service,
						$tamara_api_response->getWebhookId()
					);
				} elseif ( $tamara_api_response->getErrors()[0]['error_code'] === 'webhook_already_registered' ) {
					$this->update_webhook_id_to_options(
						$tamara_gateway_service,
						$tamara_api_response->getErrors()[0]['data']['webhook_id']
					);
				} else {
					throw new Exception(
						$tamara_gateway_service->_t( $tamara_api_response->getMessage() ),
						$tamara_api_response->getStatusCode()
					);
				}
			} catch ( Exception $RegisterWebhookException ) {
				throw new Exception(
					sprintf(
						"Tamara Service timeout or disconnected.\nError message: ' %s'.\nTrace: %s",
						esc_html( $RegisterWebhookException->getMessage() ),
						esc_html( $RegisterWebhookException->getTraceAsString() )
					)
				);
			}
		}
	}

	/**
	 * @param  \Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway  $tamara_gateway_service
	 * @param  null  $webhook_id
	 */
	protected function update_webhook_id_to_options( Tamara_WC_Payment_Gateway $tamara_gateway_service, $webhook_id = null ): void {
		$tamara_gateway_service->settings['tamara_webhook_id'] = $webhook_id;
		$tamara_gateway_service->update_settings_to_options();
	}
}
