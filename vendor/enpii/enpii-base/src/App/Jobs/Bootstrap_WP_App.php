<?php

namespace Enpii_Base\App\Jobs;

use DateTime;
use DateTimeZone;
use Enpii_Base\App\Support\Enpii_Base_Helper;
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
		$config = wp_app_config();

		if ( Enpii_Base_Helper::is_console_mode() ) {
			/** @var \Enpii_Base\App\Console\Kernel $console_kernel */
			$console_kernel = $wp_app->make( \Illuminate\Contracts\Console\Kernel::class );
			$console_kernel->bootstrap();
		} else {
			// As we may not use Contracts\Kernel::handle(), we need to call bootstrap method
			//  to iinitialize all boostrappers
			/** @var \Enpii_Base\App\Http\Kernel $http_kernel */
			$http_kernel = $wp_app->make( \Illuminate\Contracts\Http\Kernel::class );
			$http_kernel->capture_request();
			$http_kernel->bootstrap();
		}

		// As we don't use the LoadConfiguration boostrapper, we need the below snippets
		//  taken from Illuminate\Foundation\Bootstrap\LoadConfiguration
		$wp_app->detectEnvironment(
			function () use ( $config ) {
				return $config->get( 'app.env', 'production' );
			}
		);
	}
}
