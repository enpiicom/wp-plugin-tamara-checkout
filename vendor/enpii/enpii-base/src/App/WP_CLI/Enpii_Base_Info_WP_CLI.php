<?php

declare(strict_types=1);

namespace Enpii_Base\App\WP_CLI;

use Enpii_Base\App\Jobs\WP_CLI\Show_Basic_Info;

class Enpii_Base_Info_WP_CLI {
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	public function __invoke( $args ) {
		Show_Basic_Info::execute_now();

		// Return 0 to tell that everything is alright
		exit( 0 );
	}
}
