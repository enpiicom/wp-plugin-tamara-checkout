<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Traits;

use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

trait Trans_Trait {
	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	protected function _t( $untranslated_text ) {
		return Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( $untranslated_text );
	}
}
