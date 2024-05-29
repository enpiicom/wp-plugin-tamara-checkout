<?php

declare(strict_types=1);

namespace Enpii_Base\App\Jobs;

use Enpii_Base\App\Support\App_Const;
use Enpii_Base\Foundation\Support\Executable_Trait;

/**
 * static @method execute_now($message) void
 * @package Enpii_Base\App\Jobs
 */
class Mark_Setup_WP_App_Failed {
	use Executable_Trait;

	protected $message;

	public function __construct( $message ) {
		$this->message = $message;
	}

	public function handle() {
		// We need to flag issue to the db
		update_option( App_Const::OPTION_SETUP_INFO, 'failed', false );

		do_action( App_Const::ACTION_WP_APP_MARK_SETUP_APP_FAILED, $this->message );
	}
}
