<?php

namespace Enpii_Base\App\Jobs;

use Enpii_Base\Foundation\Support\Executable_Trait;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;

class Setup_WP_App_In_Console {
	use Executable_Trait;

	protected $console_command;

	public function __construct( $console_command ) {
		if ( ! ( $console_command instanceof Command ) ) {
			throw new InvalidArgumentException( 'It must be a Console Command instance' );
		}
		$this->console_command = $console_command;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle(): void {
		enpii_base_wp_app_prepare_folders();

		/** @var \Illuminate\Console\Command $console_command */
		$console_command = $this->console_command;

		// We need to publish Laravel assets and Migrations latest
		//  to be able to override other assets
		$console_command->comment( 'Publishing Laravel Migrations...' );
		$console_command->call(
			'vendor:publish',
			[
				'--tag' => 'laravel-migrations',
				'--force' => true,
			]
		);

		$console_command->comment( 'Publishing Laravel Assets...' );
		$console_command->call(
			'vendor:publish',
			[
				'--tag' => 'laravel-assets',
				'--force' => true,
			]
		);

		// We need to publish Enpii Base assets and Migrations latest
		//  to be able to override other assets
		$console_command->comment( 'Publishing Enpii Base Migrations...' );
		$console_command->call(
			'vendor:publish',
			[
				'--tag' => 'enpii-base-migrations',
				'--force' => true,
			]
		);

		$console_command->comment( 'Publishing Enpii Base Assets...' );
		$console_command->call(
			'vendor:publish',
			[
				'--tag' => 'enpii-base-assets',
				'--force' => true,
			]
		);

		// Perform the migration, we need to have --force to work on production
		$console_command->comment( 'Doing Migrations...' );
		$console_command->call(
			'migrate',
			[
				'--no-interaction' => true,
				'--force' => true,
				'--step' => true,
			]
		);

		// We need to cleanup the migrations file in fake base path database folder
		//  for security reason
		$console_command->comment( 'Cleanup migrations rule' );
		$filesystem = new Filesystem();
		$filesystem->cleanDirectory( wp_app()->databasePath( 'migrations' ) );
	}
}
