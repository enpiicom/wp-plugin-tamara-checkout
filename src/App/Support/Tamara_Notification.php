<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support;

use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class Tamara_Notification {
	protected $api_url;
	protected $notification_token;

	public static function init_wp_app_instance($notification_token, $api_url = 'https://api-sandbox.tamara.co') {

	}
}
