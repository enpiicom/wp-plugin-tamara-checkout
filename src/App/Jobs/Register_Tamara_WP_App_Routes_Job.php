<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Bus\Dispatchable_Trait;
use Enpii_Base\Foundation\Shared\Base_Job;
use Illuminate\Support\Facades\Route;
use Tamara_Checkout\App\Http\Controllers\Main_Controller;

class Register_Tamara_WP_App_Routes_Job extends Base_Job
{
	use Dispatchable_Trait;

	public function handle(): void {
		Route::post( '/tamara/webhook', [ Main_Controller::class, 'handle_tamara_webhook' ] )->name( 'tamara-webhook' );
	}
}
