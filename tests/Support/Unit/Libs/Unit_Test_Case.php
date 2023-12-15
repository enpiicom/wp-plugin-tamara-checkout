<?php

namespace Tamara_Checkout\Tests\Support\Unit\Libs;

use Codeception\Test\Unit;
use Mockery;
use Tamara_Checkout\Tests\Support\Helpers\Test_Utils_Trait;
use WP_Mock;

class Unit_Test_Case extends Unit {

	use Test_Utils_Trait;

	protected function setUp(): void {
		if ( ! class_exists('Tamara_WC_Payment_Gateway')) {
			Mockery::mock('Tamara_WC_Payment_Gateway');
			class_alias(
				'Tamara_WC_Payment_Gateway',
				'Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway'
			);
		}
		WP_Mock::setUp();
	}

	protected function tearDown(): void {
		WP_Mock::tearDown();
	}

	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, Generic.CodeAnalysis.UselessOverridingMethod.Found
	protected function _before(): void {
		parent::_before();
	}

	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, Generic.CodeAnalysis.UselessOverridingMethod.Found
	protected function _after(): void {
		parent::_after();
	}
}
