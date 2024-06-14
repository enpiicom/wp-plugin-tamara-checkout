<?php

namespace Enpii_Base\App\Jobs;

use Enpii_Base\App\Support\Enpii_Base_Helper;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Illuminate\Support\Facades\Artisan;

class Perform_Setup_WP_App {
	use Executable_Trait;

	public function handle() {
		Enpii_Base_Helper::prepare_wp_app_folders();

		Artisan::call(
			'wp-app:setup',
			[]
		);

		$output = Artisan::output();
		echo( esc_html( $output ) );
	}
}
