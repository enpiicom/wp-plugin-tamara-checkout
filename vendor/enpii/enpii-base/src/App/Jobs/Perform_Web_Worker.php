<?php

namespace Enpii_Base\App\Jobs;

use Enpii_Base\App\Support\Enpii_Base_Helper;
use Enpii_Base\App\Support\Traits\Queue_Trait;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Illuminate\Support\Facades\Artisan;

class Perform_Web_Worker {
	use Executable_Trait;
	use Queue_Trait;

	public function handle() {
		if ( Enpii_Base_Helper::disable_web_worker() ) {
			return;
		}

		// We want to try a job 1 time
		//  and we only want to retry a job 7 minutes after
		Artisan::call(
			'queue:work',
			[
				'connection' => $this->get_site_database_queue_connection(),
				'--queue' => $this->get_site_default_queue(),
				'--tries' => 1,
				'--backoff' => $this->get_queue_backoff(),
				'--quiet' => true,
				'--stop-when-empty' => true,
				'--timeout' => 60,
				'--memory' => 256,
			]
		);
	}
}
