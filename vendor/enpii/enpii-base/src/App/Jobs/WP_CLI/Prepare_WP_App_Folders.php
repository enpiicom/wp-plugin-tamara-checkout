<?php

namespace Enpii_Base\App\Jobs\WP_CLI;

use Enpii_Base\App\Support\Enpii_Base_Helper;
use Enpii_Base\Foundation\Support\Executable_Trait;
use WP_CLI;

class Prepare_WP_App_Folders {
	use Executable_Trait;

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle(): void {
		Enpii_Base_Helper::prepare_wp_app_folders();

		WP_CLI::success( 'Preparing needed folders for WP App done!' );
	}
}
