<?php

namespace Tamara_Checkout\Tests\Support\Unit\Libs;

use Mockery;
use WP_Mock;

class Unit_Test_Case extends \PHPUnit\Framework\TestCase {
	protected function setUp(): void {
	}

	protected function tearDown(): void {
		Mockery::close();
	}
}
