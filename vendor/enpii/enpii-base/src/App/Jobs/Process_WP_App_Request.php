<?php

namespace Enpii_Base\App\Jobs;

use Enpii_Base\Foundation\Support\Executable_Trait;

class Process_WP_App_Request {
	use Executable_Trait;

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle(): void {
		/** @var \Enpii_Base\App\Http\Kernel $kernel */
		$kernel = wp_app()->make( \Illuminate\Contracts\Http\Kernel::class );

		/** @var \Enpii_Base\App\Http\Request $request */
		$request = wp_app_request();
		$response = $kernel->handle( $request );
		$response->send();

		$kernel->terminate( $request, $response );

		// We want to end up the execution here to conclude the request
		exit( 0 );
	}
}
