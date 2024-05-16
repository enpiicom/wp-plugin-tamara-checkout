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
		$config = wp_app_config();

		// As we may not use Contracts\Kernel::handle(), we need to call bootstrap method
		//  to iinitialize all boostrappers
		/** @var \Enpii_Base\App\Http\Kernel $http_kernel */
		$http_kernel = $wp_app->make( \Illuminate\Contracts\Http\Kernel::class );
		$http_kernel->capture_request();
		$http_kernel->bootstrap();

		/** @var \Enpii_Base\App\Console\Kernel $console_kernel */
		$console_kernel = $wp_app->make( \Illuminate\Contracts\Console\Kernel::class );
		$console_kernel->bootstrap();

		// As we don't use the LoadConfiguration boostrapper, we need the below snippets
		//  taken from Illuminate\Foundation\Bootstrap\LoadConfiguration
		$wp_app->detectEnvironment(
			function () use ( $config ) {
				return $config->get( 'app.env', 'production' );
			}
		);

		// We want to set the timezone for the WP App
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
		date_default_timezone_set( $config->get( 'app.timezone', 'UTC' ) );
		mb_internal_encoding( 'UTF-8' );
	}
}
