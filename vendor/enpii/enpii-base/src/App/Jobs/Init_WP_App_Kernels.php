<?php

namespace Enpii_Base\App\Jobs;

use Enpii_Base\App\Support\Enpii_Base_Helper;
use Enpii_Base\Foundation\Support\Executable_Trait;

class Init_WP_App_Kernels {
	use Executable_Trait;

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle(): void {
		$wp_app = wp_app();
		$wp_app['env'] = wp_app_config( 'app.env' );

		$wp_app->singleton(
			\Illuminate\Contracts\Http\Kernel::class,
			\Enpii_Base\App\Http\Kernel::class
		);

		$wp_app->singleton(
			\Illuminate\Contracts\Console\Kernel::class,
			\Enpii_Base\App\Console\Kernel::class
		);

		if ( Enpii_Base_Helper::use_enpii_base_error_handler() ) {
			$wp_app->singleton(
				\Illuminate\Contracts\Debug\ExceptionHandler::class,
				\Enpii_Base\App\Exceptions\Handler::class
			);
		}
	}
}
