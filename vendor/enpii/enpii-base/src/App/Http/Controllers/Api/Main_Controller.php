<?php

declare(strict_types=1);

namespace Enpii_Base\App\Http\Controllers\Api;

use Enpii_Base\App\Queries\Get_WP_App_Info;
use Enpii_Base\App\Support\App_Const;
use Symfony\Component\HttpFoundation\JsonResponse;
use Enpii_Base\Foundation\Http\Base_Controller;

class Main_Controller extends Base_Controller {
	public function home(): JsonResponse {
		$data = Get_WP_App_Info::execute_now();
		if ( ! empty( wp_get_current_user()->ID ) ) {
			$data['current_logged_in_user'] = wp_get_current_user();
		}

		return wp_app_response()->json(
			[
				'message' => 'Welcome to Enpii Base WP App API',
				'data' => $data,
			]
		);
	}

	public function web_worker() {
		do_action( App_Const::ACTION_WP_APP_WEB_WORKER );

		return wp_app_response()->json(
			[
				'message' => 'Web worker executed',
			]
		);
	}
}
