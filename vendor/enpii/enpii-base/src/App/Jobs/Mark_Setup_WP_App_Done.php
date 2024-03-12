<?php

declare(strict_types=1);

namespace Enpii_Base\App\Jobs;

use Enpii_Base\App\Support\Enpii_Base_Helper;
use Enpii_Base\Foundation\Support\Executable_Trait;

class Mark_Setup_WP_App_Done {
	use Executable_Trait;

	public function handle() {
		update_option( Enpii_Base_Helper::VERSION_OPTION_FIELD, ENPII_BASE_PLUGIN_VERSION, false );
	}
}
