<?php

declare(strict_types=1);

namespace Enpii_Base\App\Http\Controllers;

use Enpii_Base\App\Http\Request;
use Enpii_Base\App\Jobs\Mark_Setup_WP_App_Done;
use Enpii_Base\App\Support\App_Const;
use Enpii_Base\App\WP\Enpii_Base_WP_Plugin;
use Enpii_Base\Foundation\Http\Base_Controller;
use Exception;
use Illuminate\Support\Facades\Auth;

class Main_Controller extends Base_Controller {
	public function index() {
		return Enpii_Base_WP_Plugin::wp_app_instance()->view(
			'main/index',
			[
				'message' => empty( Auth::user() ) ? 'Hello guest, welcome to WP App home screen' : sprintf( 'Logged-in user is here, username %s, user ID %s', Auth::user()->ID, Auth::user()->user_login ),
			]
		);
	}

	/**
	 * @throws \Exception
	 */
	public function setup_app( Request $request ) {
		try {
			ob_start();
			do_action( App_Const::ACTION_WP_APP_SETUP_APP );
			ob_end_flush();
		// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		} catch ( Exception $e ) {
		}

		if ( empty( $e ) ) {
			// If no exception thrown earlier, we can consider the setup script is done
			Mark_Setup_WP_App_Done::execute_now();
		} else {
			// We need to flag issue to the db
			update_option( App_Const::OPTION_SETUP_INFO, 'failed', false );

			do_action( App_Const::ACTION_WP_APP_MARK_SETUP_APP_FAILED );
		}

		// Then return to the previous URL
		$return_url = $request->get( 'return_url', home_url() );

		header( 'Location: ' . $return_url );
		exit( 0 );
	}
}
