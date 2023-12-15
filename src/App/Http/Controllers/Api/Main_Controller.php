<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Http\Controllers\Api;

use Enpii_Base\Foundation\Http\Base_Controller;
use Illuminate\Http\JsonResponse;

class Main_Controller extends Base_Controller {
	public function handle_tamara_webhook(): JsonResponse {
		return wp_app_response()->json(
			[
				'message' => 'WP App API - Tamara Webhook Url',
			]
		);
	}
}
