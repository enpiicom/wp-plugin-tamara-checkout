<?php

namespace Enpii_Base\App\Jobs;

use Enpii_Base\Foundation\Support\Executable_Trait;

class Bootstrap_WP_App {
	use Executable_Trait;

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle(): void {
		/** @var \Enpii_Base\App\WP\WP_Application $wp_app  */
		$wp_app = wp_app();
		$wp_app['env'] = wp_app_config( 'app.env' );

		// As we may not use Contracts\Kernel::handle(), we need to call bootstrap method
		//  to iinitialize all boostrappers
		/** @var \Enpii_Base\App\Http\Kernel $http_kernel */
		$http_kernel = $wp_app->make( \Illuminate\Contracts\Http\Kernel::class );
		$http_kernel->capture_request();
		$http_kernel->bootstrap();

		/** @var \Enpii_Base\App\Console\Kernel $http_kernel */
		$console_kernel = $wp_app->make( \Illuminate\Contracts\Console\Kernel::class );
		$console_kernel->bootstrap();
	}
}
