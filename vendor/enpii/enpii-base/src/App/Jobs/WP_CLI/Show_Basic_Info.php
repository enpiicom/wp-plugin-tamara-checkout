<?php

namespace Enpii_Base\App\Jobs\WP_CLI;

use Enpii_Base\App\Queries\Get_WP_App_Info;
use Enpii_Base\Foundation\Support\Executable_Trait;
use WP_CLI;

class Show_Basic_Info {
	use Executable_Trait;

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle(): void {
		/** @var array $wp_app_info */
		$wp_app_info = Get_WP_App_Info::execute_now();

		foreach ( $wp_app_info as $info_key => $info_value ) {
			WP_CLI::success( "Key $info_key: " . $info_value );
		}

		// Exit 0 for telling that the command is a successful one
		exit( 0 );
	}
}
