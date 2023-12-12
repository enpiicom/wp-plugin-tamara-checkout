<?php

namespace Tamara_Checkout\Tests\Support\Unit\Libs;

use Codeception\Test\Unit;

class Unit_Test_Case extends Unit {

	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, Generic.CodeAnalysis.UselessOverridingMethod.Found
	protected function _before(): void {
		parent::_before();
	}

	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore, Generic.CodeAnalysis.UselessOverridingMethod.Found
	protected function _after(): void {
		parent::_after();
	}
}
