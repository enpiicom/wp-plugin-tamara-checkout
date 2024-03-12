<?php

namespace Enpii_Base\App\Jobs;

use Enpii_Base\App\Http\Controllers\Admin\Main_Controller as Admin_Main_Controller;
use Enpii_Base\App\Http\Controllers\Api\Main_Controller as Api_Main_Controller;
use Enpii_Base\App\Http\Controllers\Main_Controller;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Illuminate\Support\Facades\Route;

class Register_Base_WP_App_Routes {
	use Executable_Trait;

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle(): void {
		// For Frontend
		Route::get( '/', [ Main_Controller::class, 'index' ] );

		// For Admin
		Route::group(
			[
				'prefix' => '/wp-admin',
				'middleware' => [
					'auth',
				],
			],
			function () {
				Route::get( '/', [ Admin_Main_Controller::class, 'home' ] );
				Route::group(
					[
						'prefix' => '/admin',
						'middleware' => [
							'authenticate_is_wp_user_admin',
						],
					],
					function () {
						Route::get( 'setup-app', [ Admin_Main_Controller::class, 'setup_app' ] )->name( 'admin-setup-app' );
					}
				);
			}
		);

		// For API
		Route::group(
			[
				'prefix' => '/api',
			],
			function () {
				Route::get( '/', [ Api_Main_Controller::class, 'home' ] );
			}
		);
	}
}
