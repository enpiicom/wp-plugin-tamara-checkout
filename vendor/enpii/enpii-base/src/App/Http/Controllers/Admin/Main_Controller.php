<?php

declare(strict_types=1);

namespace Enpii_Base\App\Http\Controllers\Admin;

use Enpii_Base\App\Http\Request;
use Enpii_Base\App\Jobs\Mark_Setup_WP_App_Done;
use Enpii_Base\App\Support\App_Const;
use Enpii_Base\App\WP\Enpii_Base_WP_Plugin;
use Enpii_Base\Foundation\Http\Base_Controller;
use Exception;
use Illuminate\Support\Facades\Auth;

class Main_Controller extends Base_Controller {
	public function home() {
		return Enpii_Base_WP_Plugin::wp_app_instance()->view(
			'main/index',
			[
				'message' => sprintf( 'Logged-in user is here, username %s, user ID %s', Auth::user()->ID, Auth::user()->user_login ),
			]
		);
	}

	/**
	 * @throws \Exception
	 */
	public function setup_app( Request $request ) {
		$message = '';

		try {
			ob_start();
			do_action( App_Const::ACTION_WP_APP_SETUP_APP );
			$message = ob_get_clean();
			$message .= "\n";
		// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		} catch ( Exception $e ) {
		}

		if ( empty( $e ) ) {
			Mark_Setup_WP_App_Done::execute_now();

			$return_url = $request->get( 'return_url', get_admin_url() );

			$message .= 'Complete Setup. Redirecting back to the Previous URL...' . "\n";
			$message .= sprintf( 'Click %s if you are not redirected automatically', '<a href="' . $return_url . '">' . $return_url . '</a>' ) . '<br />';
		} else {
			$message .= 'Please resolve the following errors then refresh this page' . "\n";
			$message .= $e->getMessage() . "\n";

			$return_url = null;
		}

		return Enpii_Base_WP_Plugin::wp_app_instance()->view(
			'wp-admin/main/setup-app',
			[
				'message' => nl2br( $message ),
				'return_url' => $return_url,
			]
		);
	}
}
