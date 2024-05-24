<?php

namespace Enpii_Base\App\Jobs;

use Enpii_Base\App\Http\Controllers\Api\Main_Controller;
use Illuminate\Support\Facades\Route;
use Enpii_Base\Foundation\Support\Executable_Trait;

class Register_Base_WP_Api_Routes {
	use Executable_Trait;

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle(): void {
		// For API
		Route::get( '/', [ Main_Controller::class, 'home' ] );
		Route::match( [ 'GET', 'POST' ], 'web-worker', [ Main_Controller::class, 'web_worker' ] )->name( 'web-worker' );
	}
}
