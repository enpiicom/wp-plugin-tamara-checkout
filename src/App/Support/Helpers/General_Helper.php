<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Helpers;

use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class General_Helper {
	public static function convert_message( $tamara_message ): string {
		return Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( $tamara_message );
	}
}
