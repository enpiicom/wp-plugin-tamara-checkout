<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support;

use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Client;
use Tamara_Checkout\Deps\Tamara\Configuration;
use Tamara_Checkout\Deps\Tamara\HttpClient\GuzzleHttpAdapter;

class Tamara_Client extends Client {
	protected $api_url;
	protected $api_token;
	protected $api_request_timeout;

	public static function init_wp_app_instance($api_token, $api_url = 'https://api-sandbox.tamara.co', $api_request_timeout = 30) {
		$logger = null;
		$transport = new GuzzleHttpAdapter($api_request_timeout, $logger);
		$configuration = Configuration::create($api_url, $api_token, $api_request_timeout, $logger, $transport);
		$client = static::create($configuration);

		$client->api_token = $api_token;
		$client->api_url = $api_url;
		$client->api_request_timeout = $api_request_timeout;

		wp_app()->instance(Tamara_Checkout_WP_Plugin::SERVICE_TAMARA_CLIENT, $client);
	}
}
