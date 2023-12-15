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
use Tamara_Checkout\Deps\Tamara\Response\Webhook\RegisterWebhookResponse;

class Register_Tamara_Webhook_Job extends Base_Job implements ShouldQueue {

	use Dispatchable_Trait;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;

	/**
	 * @param  \Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway  $tamara_gateway_service
	 * @param  \Tamara_Checkout\App\Services\Tamara_Client  $tamara_client_service
	 *
	 * @throws \Exception
	 */
	public function handle( Tamara_WC_Payment_Gateway $tamara_gateway_service, Tamara_Client $tamara_client_service ) {
		// We need to get a refreshed settings from the Payment Gateway
		$gateway_settings = $tamara_gateway_service->get_settings( true );
		$tamara_client_service->reinit_tamara_client( $gateway_settings->api_token, $gateway_settings->api_url );

		$tamara_register_webhook_api_request = new RegisterWebhookRequest(
			wp_app_route_wp_url( 'tamara-webhook' ),
			$this->get_tamara_webhook_events()
		);

		try {
			$tamara_register_webhook_api_response = $tamara_client_service->get_api_client()->registerWebhook( $tamara_register_webhook_api_request );
			if ( $tamara_register_webhook_api_response ) {
				$this->handle_tamara_register_webhook_response( $tamara_register_webhook_api_response, $tamara_gateway_service );
			}
		} catch ( Exception $tamara_register_webhook_exception ) {
			$this->throw_tamara_register_webhook_exception( $tamara_gateway_service, $tamara_register_webhook_exception );
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

	/**
	 * @return array
	 */
	protected function get_tamara_webhook_events(): array {
		return [
			'order_approved',
			'order_declined',
			'order_authorised',
			'order_canceled',
			'order_captured',
			'order_refunded',
			'order_expired',
		];
	}

	/**
	 * @param  \Tamara_Checkout\Deps\Tamara\Response\Webhook\RegisterWebhookResponse  $tamara_register_webhook_api_response
	 * @param  \Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway  $tamara_gateway_service
	 *
	 * @return void
	 *
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 * @throws \Exception
	 */
	protected function handle_tamara_register_webhook_response(
		RegisterWebhookResponse $tamara_register_webhook_api_response,
		Tamara_WC_Payment_Gateway $tamara_gateway_service
	): void {
		$tamara_register_webhook_api_response_error_code =
			$tamara_register_webhook_api_response->getErrors()[0]['error_code'] ?? null;
		if ( $tamara_register_webhook_api_response->isSuccess() ) {
			$this->update_webhook_id_to_options(
				$tamara_gateway_service,
				$tamara_register_webhook_api_response->getWebhookId()
			);
		} elseif ( $tamara_register_webhook_api_response_error_code === 'webhook_already_registered' ) {
			$this->update_webhook_id_to_options(
				$tamara_gateway_service,
				$tamara_register_webhook_api_response->getErrors()[0]['data']['webhook_id']
			);
		} else {
			throw new Exception(
				esc_html( $tamara_gateway_service->_t( $tamara_register_webhook_api_response->getMessage() ) ),
				esc_html( $tamara_register_webhook_api_response->getStatusCode() )
			);
		}
	}

	/**
	 * @throws \Exception
	 */
	protected function throw_tamara_register_webhook_exception(
		Tamara_WC_Payment_Gateway $tamara_gateway_service,
		Exception $tamara_register_webhook_exception
	) {
		throw new Exception(
			sprintf(
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				$tamara_gateway_service->_t( 'Tamara Service timeout or disconnected.\nError message: " %s".\nTrace: %s' ),
				esc_html( $tamara_register_webhook_exception->getMessage() ),
				esc_html( $tamara_register_webhook_exception->getTraceAsString() )
			)
		);
	}
}
