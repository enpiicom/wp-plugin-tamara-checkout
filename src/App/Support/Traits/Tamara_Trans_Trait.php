<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Traits;

use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

trait Tamara_Trans_Trait {
	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	protected function _t( $untranslated_text ) {
		return Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( $untranslated_text );
	}

	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	protected function _x( $untranslated_text, $context ): string {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralContext, WordPress.WP.I18n.NonSingularStringLiteralDomain
		return Tamara_Checkout_WP_Plugin::wp_app_instance()->_x( $untranslated_text, $context );
	}

	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	protected function _n_noop( string $singular, string $plural ): array {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingular, WordPress.WP.I18n.NonSingularStringLiteralPlural, WordPress.WP.I18n.NonSingularStringLiteralDomain
		return Tamara_Checkout_WP_Plugin::wp_app_instance()->_n_noop( $singular, $plural );
	}
}
