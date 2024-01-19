<?php

namespace Tamara_Checkout\Tests\Support\Helpers;

use Codeception\Test\Unit;
use Mockery;
use WP_Mock;

class Unit_Test_Case extends Unit {
	protected function setUp(): void {
		WP_Mock::setUp();
	}

	protected function tearDown(): void {
		WP_Mock::tearDown();
	}
}
