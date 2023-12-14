<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Bus\Dispatchable_Trait;
use Enpii_Base\Foundation\Shared\Base_Job;
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

	public function handle( Tamara_WC_Payment_Gateway $tamara_gateway_service, Tamara_Client $tamara_client_service ) {
		// We need to get a refreshed settings from the Payment Gateway
		$gateway_settings = $tamara_gateway_service->get_settings( true );

		$tamara_client_service->reinit_tamara_client( $gateway_settings->api_token, $gateway_settings->api_url );

		$tamara_api_request = new RegisterWebhookRequest(
			'https://php72.enpii-demo.tnp-local.dev-srv.net/wp-app/tamara/webhook',
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
		$tamara_api_response = $tamara_client_service->get_api_client()->registerWebhook( $tamara_api_request );

		dev_error_log( $tamara_api_response->getErrors() );
	}
}
