<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Client;
use Tamara_Checkout\Deps\Tamara\Configuration;
use Tamara_Checkout\Deps\Tamara\HttpClient\GuzzleHttpAdapter;

/**
 * A wrapper of Tamara Client
 * @package Tamara_Checkout\App\Services
 * static @method Tamara_Client create($configuration)
 */
class Tamara_Client {
	use Static_Instance_Trait;

	protected $working_mode = 'live';
	protected $api_url;
	protected $api_token;
	protected $api_request_timeout;

	protected $api_client;

	protected function __construct( $api_token, $api_url = 'https://api.tamara.co', $api_request_timeout = 30 ) {
		$logger = null;
		$transport = new GuzzleHttpAdapter($api_request_timeout, $logger);
		$configuration = Configuration::create($api_url, $api_token, $api_request_timeout, $logger, $transport);
		$client = Client::create($configuration);

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

	public function reinit_tamara_client($api_token, $api_url = 'https://api.tamara.co', $api_request_timeout = 30): void {
		$this->api_token = $api_token;
		$this->api_url = $api_url;
		$this->api_request_timeout = $api_request_timeout;
		$client = $this->build_tamara_client($api_token, $api_url, $api_request_timeout);
		static::$instance->api_client = $client;
	}

	protected function build_tamara_client($api_token, $api_url, $api_request_timeout): Client {
		$logger = null;
		$transport = new GuzzleHttpAdapter($api_request_timeout, $logger);
		$configuration = Configuration::create($api_url, $api_token, $api_request_timeout, $logger, $transport);
		return Client::create($configuration);
	}

	protected function define_working_mode(): void {
		if (strpos($this->api_url, '-sandbox')) {
			$this->working_mode = 'sandbox';
		}
	}
}
