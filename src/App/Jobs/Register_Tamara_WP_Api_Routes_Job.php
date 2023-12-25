<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Shared\Base_Job;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Illuminate\Support\Facades\Route;
use Tamara_Checkout\App\Http\Controllers\Api\Main_Controller;

class Register_Tamara_WP_Api_Routes_Job extends Base_Job {
	use Executable_Trait;

	public function handle(): void {
		Route::post( 'tamara/success', [ Main_Controller::class, 'handle_tamara_success' ] )->name( 'tamara-success' );
		Route::get( 'tamara/orders/{wc_order_id}/cancel', [ Main_Controller::class, 'handle_tamara_cancel' ] )->name( 'wp-api-tamara-cancel' );
		Route::get( 'tamara/orders/{wc_order_id}/failure', [ Main_Controller::class, 'handle_tamara_failure' ] )->name( 'wp-api-tamara-failure' );
		Route::post( 'tamara/ipn', [ Main_Controller::class, 'handle_tamara_ipn' ] )->name( 'tamara-ipn' );
		Route::post( 'tamara/webhook', [ Main_Controller::class, 'handle_tamara_webhook' ] )->name( 'tamara-webhook' );
	}
}
