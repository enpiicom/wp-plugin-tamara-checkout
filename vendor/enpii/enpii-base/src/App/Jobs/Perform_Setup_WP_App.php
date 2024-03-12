<?php

namespace Enpii_Base\App\Jobs;

use Enpii_Base\Foundation\Support\Executable_Trait;
use Illuminate\Support\Facades\Artisan;

class Perform_Setup_WP_App {
	use Executable_Trait;

	public function handle() {
		enpii_base_wp_app_prepare_folders();

		Artisan::call(
			'wp-app:setup',
			[]
		);

		if ( wp_app_config( 'app.debug' ) ) {
			$output = Artisan::output();
			echo( esc_html( $output ) );
		}
	}
}
