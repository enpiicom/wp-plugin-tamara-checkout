<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Exception;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\VOs\Tamara_Api_Error_VO;
use Tamara_Checkout\App\VOs\Tamara_Api_Response_VO;
use Tamara_Checkout\App\VOs\Tamara_WC_Payment_Gateway_Settings_VO;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Client;
use Tamara_Checkout\Deps\Tamara\Configuration;
use Tamara_Checkout\Deps\Tamara\Exception\RequestDispatcherException;
use Tamara_Checkout\Deps\Tamara\HttpClient\GuzzleHttpAdapter;
use Tamara_Checkout\Deps\Tamara\Request\Checkout\CheckPaymentOptionsAvailabilityRequest;
use Tamara_Checkout\Deps\Tamara\Request\Checkout\CreateCheckoutRequest;
use Tamara_Checkout\Deps\Tamara\Request\Merchant\GetPublicConfigsRequest;
use Tamara_Checkout\Deps\Tamara\Request\Order\AuthoriseOrderRequest;
use Tamara_Checkout\Deps\Tamara\Request\Order\CancelOrderRequest;
use Tamara_Checkout\Deps\Tamara\Request\Order\GetOrderByReferenceIdRequest;
use Tamara_Checkout\Deps\Tamara\Request\Order\GetOrderRequest;
use Tamara_Checkout\Deps\Tamara\Request\Payment\CaptureRequest;
use Tamara_Checkout\Deps\Tamara\Request\Payment\RefundRequest;
use Tamara_Checkout\Deps\Tamara\Request\Webhook\RegisterWebhookRequest;
use Tamara_Checkout\Deps\Tamara\Response\Checkout\CheckPaymentOptionsAvailabilityResponse;
use Tamara_Checkout\Deps\Tamara\Response\Checkout\CreateCheckoutResponse;
use Tamara_Checkout\Deps\Tamara\Response\ClientResponse;
use Tamara_Checkout\Deps\Tamara\Response\Webhook\RegisterWebhookResponse;

/**
 * A wrapper of Tamara Client
 * @package Tamara_Checkout\App\Services
 * static @method Tamara_Client create($configuration)
 */
class Tamara_Client {
	use Static_Instance_Trait;
	use Tamara_Trans_Trait;

	protected $working_mode;
	protected $api_url;
	protected $api_token;
	protected $api_request_timeout;

	/**
	 * @var Logger
	 */
	protected $logger;

	/**
	 * @var null|Tamara_WC_Payment_Gateway_Settings_VO
	 */
	protected $settings;

	/**
	 *
	 * @var Client
	 */
	protected $api_client;

	protected function __construct(
		$api_token,
		$api_url = 'https://api.tamara.co',
		$settings = [],
		$api_request_timeout = 30
	) {
		$this->init_tamara_client( $api_token, $api_url, $settings, $api_request_timeout );
	}

	public function get_working_mode(): string {
		return $this->working_mode;
	}

	public function get_api_client() {
		return $this->api_client;
	}

	public function init_tamara_client(
		$api_token,
		$api_url = 'https://api.tamara.co',
		$settings = [],
		$api_request_timeout = 30
	): void {
		$this->api_token = $api_token;
		$this->api_url = $api_url;
		$this->api_request_timeout = $api_request_timeout;
		$this->settings = empty( $settings ) ? null : ( $settings instanceof Tamara_WC_Payment_Gateway_Settings_VO ? $settings : new Tamara_WC_Payment_Gateway_Settings_VO( $settings ) );

		$client = $this->build_tamara_client( $api_token, $api_url, $api_request_timeout );
		$this->api_client = $client;
		$this->define_working_mode();
	}

	/**
	 *
	 * @param RegisterWebhookRequest $client_request
	 * @return string|RegisterWebhookResponse
	 * @throws Exception
	 */
	public function register_webhook( RegisterWebhookRequest $client_request ) {
		return $this->perform_remote_request( 'registerWebhook', $client_request );
	}

	/**
	 *
	 * @param CheckPaymentOptionsAvailabilityRequest $client_request
	 * @return string|CheckPaymentOptionsAvailabilityResponse
	 * @throws Exception
	 */
	public function check_payment_options_availability( CheckPaymentOptionsAvailabilityRequest $client_request ) {
		return $this->perform_remote_request( 'checkPaymentOptionsAvailability', $client_request );
	}

	/**
	 *
	 * @param CreateCheckoutRequest $client_request
	 * @return string|CreateCheckoutResponse
	 * @throws Exception
	 */
	public function create_checkout( CreateCheckoutRequest $client_request ) {
		return $this->perform_remote_request( 'createCheckout', $client_request );
	}

	/**
	 *
	 * @param GetPublicConfigsRequest $client_request
	 * @return string|\Tamara_Checkout\Deps\Tamara\Response\Merchant\GetPublicConfigsResponse
	 * @throws Exception
	 */
	public function get_merchant_public_configs( GetPublicConfigsRequest $client_request ) {
		return $this->perform_remote_request( 'getMerchantPublicConfigs', $client_request );
	}

	/**
	 *
	 * @param GetOrderRequest $client_request
	 * @return string|\Tamara_Checkout\Deps\Tamara\Response\Order\GetOrderResponse
	 * @throws Exception
	 */
	public function get_order( GetOrderRequest $client_request ) {
		return $this->perform_remote_request( 'getOrder', $client_request );
	}

	/**
	 *
	 * @param GetOrderByReferenceIdRequest $client_request
	 * @return string|\Tamara_Checkout\Deps\Tamara\Response\Order\GetOrderByReferenceIdResponse
	 * @throws Exception
	 */
	public function get_order_by_reference_id( GetOrderByReferenceIdRequest $client_request ) {
		return $this->perform_remote_request( 'getOrderByReferenceId', $client_request );
	}

	/**
	 *
	 * @param AuthoriseOrderRequest $client_request
	 * @return string|\Tamara_Checkout\Deps\Tamara\Response\Order\AuthoriseOrderResponse
	 * @throws Exception
	 */
	public function authorise_order( AuthoriseOrderRequest $client_request ) {
		return $this->perform_remote_request( 'authoriseOrder', $client_request );
	}

	/**
	 *
	 * @param CaptureRequest $client_request
	 * @return string|\Tamara_Checkout\Deps\Tamara\Response\Payment\CaptureResponse
	 * @throws Exception
	 */
	public function capture( CaptureRequest $client_request ) {
		return $this->perform_remote_request( 'capture', $client_request );
	}

	/**
	 *
	 * @param CancelOrderRequest $client_request
	 *
	 * @return string|\Tamara_Checkout\Deps\Tamara\Response\Payment\CancelResponse
	 */
	public function cancel_order( CancelOrderRequest $client_request ) {
		return $this->perform_remote_request( 'cancelOrder', $client_request );
	}

	/**
	 *
	 * @param  \Tamara_Checkout\Deps\Tamara\Request\Payment\RefundRequest  $client_request
	 *
	 * @return string|\Tamara_Checkout\Deps\Tamara\Response\Payment\RefundResponse
	 */
	public function refund( RefundRequest $client_request ) {
		return $this->perform_remote_request( 'refund', $client_request );
	}

	protected function define_working_mode(): void {
		if ( strpos( $this->api_url, '-sandbox' ) ) {
			$this->working_mode = 'sandbox';
		}
		$this->working_mode = 'live';
	}

	protected function build_tamara_client( $api_token, $api_url, $api_request_timeout ): Client {
		$this->logger = $this->build_logger();
		$transport = new GuzzleHttpAdapter( $api_request_timeout, null );
		$configuration = Configuration::create( $api_url, $api_token, $api_request_timeout, null, $transport );
		return Client::create( $configuration );
	}

	/**
	 *
	 * @param mixed $remote_action
	 * @param mixed $client_request
	 * @return Tamara_Api_Response_VO|mixed
	 * @throws Exception
	 */
	protected function perform_remote_request( $remote_action, $client_request ) {
		$this->log_message( sprintf( "Tamara API URL: %s, Tamara remote_action: %s, Tamara client_request: %s, Tamara API Token \n %s", dev_var_dump( $this->api_url ), dev_var_dump( $remote_action ), dev_var_dump( $client_request ), dev_var_dump( $this->api_token ) ) );

		try {
			$api_response = $this->api_client->$remote_action( $client_request );
			$this->log_message( sprintf( 'Tamara API response: %s', dev_var_dump( $api_response ) ) );
		} catch ( RequestDispatcherException $tamara_request_dispatcher_exception ) {
			$error_message = $this->__( $tamara_request_dispatcher_exception->getMessage() );
			$this->log_message( sprintf( 'Tamara API error: %s', dev_var_dump( $tamara_request_dispatcher_exception ) ), 'error' );
		} catch ( Exception $tamara_checkout_exception ) {
			$error_message = $this->__( 'Tamara Service unavailable! Please try again later.' ) . "<br />\n" . $this->__( $tamara_checkout_exception->getMessage() );
			$this->log_message( sprintf( 'Tamara API error: %s', dev_var_dump( $tamara_checkout_exception ) ), 'error' );
		}

		if ( empty( $api_response ) ) {
			return new Tamara_Api_Error_VO(
				[
					'error_message' => $error_message,
				]
			);
		}

		if ( ! $api_response->isSuccess() ) {
			return new Tamara_Api_Error_VO(
				[
					'error_message' => $this->build_client_response_errors( $api_response ),
					'message' => $api_response->getMessage(),
					'errors' => $api_response->getErrors(),
					'status_code' => $api_response->getStatusCode(),
				]
			);
		}

		return $api_response;
	}

	protected function build_client_response_errors( ClientResponse $tamara_client_response ): string {
		$error_message = $tamara_client_response->getMessage();
		$errors = $tamara_client_response->getErrors();

		array_walk(
			$errors,
			function ( &$tmp_item ) {
				$tmp_item = Tamara_Checkout_Helper::convert_message( $tmp_item['error_code'] ) ?? null;
			}
		);
		$error_message = Tamara_Checkout_Helper::convert_message( $error_message );
		$error_message .= $error_message ? "\n" : '';
		$error_message .= implode( "\n", $errors );

		if ( empty( $error_message ) ) {
			$error_message = $tamara_client_response->getStatusCode();
		}

		return (string) $error_message;
	}

	/**
	 * Build the logger for the client request
	 *
	 * @return Logger|null
	 */
	protected function build_logger() {
		$settings = empty( $this->settings ) ? Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_gateway_service()->get_settings_vo() : $this->settings;

		if ( $settings->custom_log_message_enabled && $settings->custom_log_message ) {
			/** @var \Illuminate\Log\Logger $logger */
			$logger = Log::build(
				[
					'driver' => 'single',
					'path' => $settings->custom_log_message,
				]
			);
		} else {
			$logger = null;
		}

		return $logger;
	}

	protected function log_message( $message, $context = 'info' ): void {
		if ( ! empty( $this->logger ) ) {
			$this->logger->$context( $message );
		}
	}
}
