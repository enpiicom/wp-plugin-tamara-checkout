<?php

/**
 * Below functions are for development debugging
 */

declare(strict_types=1);

use Enpii_Base\App\Support\Enpii_Base_Helper;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

if ( ! function_exists( 'devd' ) ) {
	/**
	 * @throws \Exception
	 */
	function devd( ...$vars ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$dev_trace = debug_backtrace();

		echo "=== start of dump ===\n";
		dump( ...$vars );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $dev_trace[0]['file'] . ':' . $dev_trace[0]['line'] . ': ' . "\n";
		// We want to put the file name and the 7 steps trace to know where
		//  where the dump is produced
		if ( ! enpii_base_is_console_mode() && defined( 'DEV_LOG_TRACE' ) ) {
			echo 'Traceback: ';
			dump( $dev_trace );
		}
		echo "\n=== end of dump === ";
	}
}

if ( ! function_exists( 'devdd' ) ) {
	/**
	 * @throws \Exception
	 */
	function devdd( ...$vars ): void {
		devd( ...$vars );
		die( 1 );
	}
}

if ( ! function_exists( 'dev_var_dump' ) ) {
	function dev_var_dump( $var_to_be_dumped, int $max_depth = 5 ): string {
		$dumper = new CliDumper();
		$cloner = new VarCloner();
		$cloner->addCasters( ReflectionCaster::UNSET_CLOSURE_FILE_INFO );

		return $dumper->dump( $cloner->cloneVar( $var_to_be_dumped )->withMaxDepth( $max_depth ), true );
	}
}

if ( ! function_exists( 'dev_error_log' ) ) {
	function dev_error_log( ...$vars ): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$dev_trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 0 );

		$log_message = '';
		$log_message .= 'Debugging dev_error_log, url (' . Enpii_Base_Helper::get_current_url() . ") \n======= Dev logging start here \n" . $dev_trace[0]['file'] . ':' . $dev_trace[0]['line'] . " \n";
		unset( $dev_trace[0] );

		foreach ( $vars as $index => $var ) {
			$dump_content = null;
			if ( $var === null ) {
				$type = 'NULL';
			} else {
				$type = is_object( $var ) ? get_class( $var ) : gettype( $var );

				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
				$dump_content = dev_var_dump( $var );
			}
			$log_message .= "Var no $index: type " . $type . ' - ' . $dump_content . " \n";
		}

		if ( defined( 'DEV_LOG_TRACE' ) ) {
			$log_message .= 'Trace :' . dev_var_dump( $dev_trace ) . " \n";
			$log_message .= "\n======= Dev logging ends here =======\n";
			$log_message .= "\n=====================================\n\n\n\n";
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $log_message );
	}
}

if ( ! function_exists( 'dev_logger' ) ) {
	function dev_logger( ...$vars ): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$dev_trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 0 );

		$logger = wp_app_logger()->channel( 'single' );

		$log_message = '';
		$log_message .= 'Debugging dev_error_log, url (' . Enpii_Base_Helper::get_current_url() . ") \n======= Dev logging start here \n" . $dev_trace[0]['file'] . ':' . $dev_trace[0]['line'] . " \n";
		unset( $dev_trace[0] );
		foreach ( $vars as $index => $var ) {
			$dump_content = null;
			if ( $var === false ) {
				$type = 'NULL';
			} else {
				$type = is_object( $var ) ? get_class( $var ) : gettype( $var );

				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
				$dump_content = dev_var_dump( $var );
			}
			$log_message .= "Var no $index: type " . $type . ' - ' . $dump_content . " \n";
		}

		if ( defined( 'DEV_LOG_TRACE' ) ) {
			$log_message .= 'Trace :' . dev_var_dump( $dev_trace ) . " \n";
			$log_message .= "\n======= Dev logging ends here =======\n";
			$log_message .= "\n=====================================\n\n\n\n";
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
		$logger->debug( $log_message );
	}
}

if ( ! function_exists( 'dev_log' ) ) {
	function dev_log( ...$vars ): void {
		dev_error_log( ...$vars );
		dev_logger( ...$vars );
	}
}

if ( ! function_exists( 'dev_dump_log' ) ) {
	/**
	 * @throws \Exception
	 */
	function dev_dump_log( ...$vars ): void {
		devd( ...$vars );
		dev_error_log( ...$vars );
		dev_logger( ...$vars );
	}
}
