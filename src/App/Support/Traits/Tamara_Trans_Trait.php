<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Traits;

trait Tamara_Trans_Trait {
	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	public function __( $untranslated_text ) {
		return __( $untranslated_text, 'tamara' );
	}

	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	public function _x( $untranslated_text, $context ): string {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralContext, WordPress.WP.I18n.NonSingularStringLiteralDomain
		return _x( $untranslated_text, $context, 'tamara' );
	}

	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	public function _n_noop( string $singular, string $plural ): array {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingular, WordPress.WP.I18n.NonSingularStringLiteralPlural, WordPress.WP.I18n.NonSingularStringLiteralDomain
		return _n_noop( $singular, $plural, 'tamara' );
	}
}
