<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Http\Controllers;

use Enpii_Base\Foundation\Http\Base_Controller;

class Main_Controller extends Base_Controller
{
	public function handle_tamara_webhook() {
		dev_error_log(wp_app_request());
		return wp_app_route_wp_url('tamara-webhook');
	}
}
