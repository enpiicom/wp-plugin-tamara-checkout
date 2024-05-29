<?php

namespace Enpii_Base\App\Jobs;

use Enpii_Base\App\Queries\Get_WP_App_Info;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * static @method execute_now($message) void
 * @package Enpii_Base\App\Jobs
 */
class Put_Setup_Error_Message_To_Log_File {
	use Executable_Trait;

	protected $message;

	public function __construct( $message ) {
		$this->message = $message;
	}

	public function handle() {
		$monolog = new Logger( 'setup_app' );
		$stream_handler = new StreamHandler( wp_app_storage_path( 'logs/setup-app.log' ) );
		$monolog->pushHandler( $stream_handler );
		$monolog->warning( '========= Errors from Setup app ============' );
		$monolog->error( $this->message );

		/** @var array $wp_app_info */
		$wp_app_info = Get_WP_App_Info::execute_now();
		$monolog->info( dev_var_dump( $wp_app_info ) );
		$monolog->info( dev_var_dump( get_loaded_extensions() ) );
		$monolog->warning( '========= /Errors from Setup app ===========' );
	}
}
