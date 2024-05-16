<?php

declare(strict_types=1);

namespace Enpii_Base\App\Console;

use Enpii_Base\App\Console\Commands\WP_App_Setup_Command;
use Enpii_Base\App\Support\App_Const;
use Enpii_Base\App\Support\Traits\Enpii_Base_Trans_Trait;
use Enpii_Base\App\WP\Enpii_Base_WP_Plugin;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel {
	use Enpii_Base_Trans_Trait;

	/**
	 * The bootstrap classes for the application.
	 *  As we are loading configurations from memory (array) with WP_Application
	 *  we don't need to load config from files.
	 *  So we exclude `\Illuminate\Foundation\Bootstrap\LoadConfiguration`
	 *
	 * @var array
	 */
	protected $bootstrappers = [
		\Illuminate\Foundation\Bootstrap\HandleExceptions::class,
		\Illuminate\Foundation\Bootstrap\RegisterFacades::class,
		\Illuminate\Foundation\Bootstrap\SetRequestForConsole::class,
		\Illuminate\Foundation\Bootstrap\RegisterProviders::class,
		\Illuminate\Foundation\Bootstrap\BootProviders::class,
	];

	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		WP_App_Setup_Command::class,
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule( Schedule $schedule ) {
		do_action( App_Const::ACTION_WP_APP_SCHEDULE_RUN, $schedule );
	}

	/**
	 * Register the commands for the application.
	 *
	 * @return void
	 */
	protected function commands() {
		$enpii_base_plugin = Enpii_Base_WP_Plugin::wp_app_instance();
		Artisan::command(
			'wp-app:hello',
			function () use ( $enpii_base_plugin ) {
				/** @var \Illuminate\Foundation\Console\ClosureCommand $this */
				$start_time = microtime( true );
				for ( $i = 0; $i < 500000; $i++ ) {
					$message = $enpii_base_plugin->__( 'Hello from Enpii Base wp_app()' );
					// $message = __( 'Hello from Enpii Base wp_app()' );
				}
				$end_time = microtime( true );
				$this->comment( $message );
				$this->info( $end_time - $start_time );
			}
		)->describe( 'Display a message from Enpii Base plugin' );
	}

	/**
	 * Get the bootstrap classes for the application.
	 *
	 * @return array
	 */
	protected function bootstrappers() {
		$bootstrappers = $this->bootstrappers;
		$script_name = ! empty( $_SERVER['SCRIPT_NAME'] ) ? sanitize_text_field( $_SERVER['SCRIPT_NAME'] ) : '';
		if ( strpos( $script_name, '/wp-admin/customize.php' ) !== false ) {
			// We need to exclude the HandleException bootstrapper
			//  provided that, it's at the index 0
			array_shift( $bootstrappers );
		}

		return $bootstrappers;
	}
}
