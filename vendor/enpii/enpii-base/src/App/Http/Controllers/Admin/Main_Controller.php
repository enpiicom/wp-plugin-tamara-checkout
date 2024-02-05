<?php

declare(strict_types=1);

namespace Enpii_Base\App\Http\Controllers\Admin;

use Enpii_Base\App\Http\Request;
use Enpii_Base\App\Support\App_Const;
use Enpii_Base\App\Support\Enpii_Base_Helper;
use Enpii_Base\App\WP\Enpii_Base_WP_Plugin;
use Enpii_Base\Foundation\Http\Base_Controller;
use Exception;
use Illuminate\Support\Facades\Auth;

class Main_Controller extends Base_Controller {
	public function home() {
		$user = Auth::user();
		return sprintf( 'Logged in use is here, username %s, user ID %s', $user->ID, $user->user_login );
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
			if ( ! wp_app_config( 'app.debug' ) ) {
				$message = '';
			}
		// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		} catch ( Exception $e ) {
		}

		if ( empty( $e ) ) {
			update_option( Enpii_Base_Helper::VERSION_OPTION_FIELD, ENPII_BASE_PLUGIN_VERSION, false );

			$return_url = $request->get( 'return_url', get_admin_url() );

			$message .= 'Complete Setup. Redirecting back to the Previous URL...' . "\n";
			$message .= sprintf( 'Click %s if you are not redirected automatically', '<a href="' . $return_url . '">' . $return_url . '</a>' ) . '<br />';
		} else {
			$message .= 'Please resolve the following errors then refresh this page' . "\n";
			$message .= $e->getMessage() . "\n";

			$return_url = null;
		}

		return Enpii_Base_WP_Plugin::wp_app_instance()->view(
			'main/setup-app',
			[
				'message' => nl2br( $message ),
				'return_url' => $return_url,
			]
		);
	}
}
