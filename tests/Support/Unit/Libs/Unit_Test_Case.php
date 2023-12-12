<?php

namespace Tamara_Checkout\Tests\Support\Unit\Libs;

use Codeception\Test\Unit;
use Tamara_Checkout\Tests\Support\Helpers\Test_Utils_Trait;

class Unit_Test_Case extends Unit {

	use Test_Utils_Trait;

	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, Generic.CodeAnalysis.UselessOverridingMethod.Found
	protected function _before(): void {
		parent::_before();
	}

	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, Generic.CodeAnalysis.UselessOverridingMethod.Found
	protected function _after(): void {
		parent::_after();
	}
}
