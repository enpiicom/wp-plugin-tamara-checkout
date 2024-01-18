<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create(
			'activity_logs',
			function ( Blueprint $table ) {
				$table->bigIncrements( 'id' );
				$table->unsignedBigInteger( 'user_id' )->index()->nullable();
				$table->string( 'url' )->length( 255 )->nullable();
				$table->string( 'method' )->length( 16 )->nullable();
				$table->string( 'type' )->length( 16 )->nullable();
				$table->text( 'params' )->nullable();
				$table->timestamp( 'created_at', 1 )->nullable();
			}
		);
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists( 'activity_logs' );
	}
}
