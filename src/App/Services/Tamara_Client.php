<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Exception;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\Support\Traits\WP_Attribute_Trait;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Client;
use Tamara_Checkout\Deps\Tamara\Configuration;
use Tamara_Checkout\Deps\Tamara\Exception\RequestDispatcherException;
use Tamara_Checkout\Deps\Tamara\HttpClient\GuzzleHttpAdapter;
use Tamara_Checkout\Deps\Tamara\Request\Checkout\CreateCheckoutRequest;
use Tamara_Checkout\Deps\Tamara\Response\Checkout\CreateCheckoutResponse;
use Tamara_Checkout\Deps\Tamara\Response\ClientResponse;

/**
 * A wrapper of Tamara Client
 * @package Tamara_Checkout\App\Services
 * static @method Tamara_Client create($configuration)
 */
class Tamara_Client {
	use Static_Instance_Trait;
	use WP_Attribute_Trait;

	protected $working_mode;
	protected $api_url;
	protected $api_token;
	protected $api_request_timeout;

	protected $api_client;

	protected function __construct( $api_token, $api_url = 'https://api.tamara.co', $api_request_timeout = 30 ) {
		$logger = null;
		$transport = new GuzzleHttpAdapter( $api_request_timeout, $logger );
		$configuration = Configuration::create( $api_url, $api_token, $api_request_timeout, $logger, $transport );
		$client = Client::create( $configuration );

		$this->api_token = $api_token;
		$this->api_url = $api_url;
		$this->api_request_timeout = $api_request_timeout;
		$this->api_client = $client;
		$this->define_working_mode();
	}

	public function get_working_mode(): string {
		return $this->working_mode;
	}

	public function get_api_client() {
		return $this->api_client;
	}

	public function reinit_tamara_client( $api_token, $api_url = 'https://api.tamara.co', $api_request_timeout = 30 ): void {
		$this->api_token = $api_token;
		$this->api_url = $api_url;
		$this->api_request_timeout = $api_request_timeout;
		$client = $this->build_tamara_client( $api_token, $api_url, $api_request_timeout );
		static::$instance->api_client = $client;
	}

	/**
	 *
	 * @param CreateCheckoutRequest $create_checkout_request
	 * @return string|CreateCheckoutResponse return a string if error faced
	 * @throws Exception
	 */
	public function create_checkout_request( CreateCheckoutRequest $create_checkout_request ) {
		try {
			$create_checkout_response = $this->api_client->createCheckout( $create_checkout_request );
		} catch ( RequestDispatcherException $tamara_request_dispatcher_exception ) {
			$error_message = $this->_t( $tamara_request_dispatcher_exception->getMessage() );
		} catch ( Exception $tamara_checkout_exception ) {
			$error_message = $this->_t( 'Tamara Service unavailable! Please try again later.' ) . "<br />\n" . $this->_t( $tamara_checkout_exception->getMessage() );
		}

		if ( empty( $create_checkout_response ) ) {
			return $error_message;
		}

		if ( ! $create_checkout_response->isSuccess() ) {
			return $this->build_client_response_errors( $create_checkout_response );
		}

		return $create_checkout_response;
	}

	protected function define_working_mode(): void {
		if ( strpos( $this->api_url, '-sandbox' ) ) {
			$this->working_mode = 'sandbox';
		}
		$this->working_mode = 'live';
	}

	protected function build_tamara_client( $api_token, $api_url, $api_request_timeout ): Client {
		$logger = null;
		$transport = new GuzzleHttpAdapter( $api_request_timeout, $logger );
		$configuration = Configuration::create( $api_url, $api_token, $api_request_timeout, $logger, $transport );
		return Client::create( $configuration );
	}

	protected function build_client_response_errors( ClientResponse $tamara_client_response ): string {
		$error_message = $tamara_client_response->getMessage();
		$errors = $tamara_client_response->getErrors();

		array_walk(
			$errors,
			function ( &$tmp_item ) {
				$tmp_item = General_Helper::convert_message( $tmp_item['error_code'] ) ?? null;
			}
		);
		$error_message = General_Helper::convert_message( $error_message );
		$error_message .= "<br />\n";
		$error_message .= implode( "<br />\n", $errors );

		return $error_message;
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
