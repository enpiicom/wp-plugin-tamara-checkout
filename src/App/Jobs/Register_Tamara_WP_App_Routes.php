<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Support\Executable_Trait;
use Illuminate\Support\Facades\Route;
use Tamara_Checkout\App\Http\Controllers\Main_Controller;

class Register_Tamara_WP_App_Routes {
	use Executable_Trait;

	public function handle(): void {
		Route::get( 'tamara/download-log-file', [ Main_Controller::class, 'download_log_file' ] )->name( 'tamara-download-log-file' );
	}
}
