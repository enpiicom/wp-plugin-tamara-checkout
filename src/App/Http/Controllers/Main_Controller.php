<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Http\Controllers;

use Enpii_Base\Foundation\Http\Base_Controller;
use Illuminate\Http\Request;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class Main_Controller extends Base_Controller {
	public function download_log_file( Request $request ) {
		$filepath = realpath( $request->get( 'filepath' ) );
		if ( ! $filepath ) {
			throw new Tamara_Exception(
				wp_kses_post( Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'Log file not found or it contains nothing' ) )
			);
		}

		$headers = [
			'Content-Type: text/plain',
		];

		return wp_app_response()->download( $filepath, basename( $filepath ), $headers );
	}
}
