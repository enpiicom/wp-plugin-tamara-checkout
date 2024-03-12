<?php

declare(strict_types=1);

namespace Enpii_Base\App\Console\Commands;

use Enpii_Base\App\Jobs\Mark_Setup_WP_App_Done;
use Enpii_Base\App\Jobs\Setup_WP_App_In_Console;
use Illuminate\Console\Command;

class WP_App_Setup_Command extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'wp-app:setup';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Setup all needed things for the WP Application';

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function handle() {
		Setup_WP_App_In_Console::execute_now( $this );

		// If no exception thrown earlier, we can consider the setup script is done
		Mark_Setup_WP_App_Done::execute_now();
	}
}
