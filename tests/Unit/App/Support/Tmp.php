<?php

declare(strict_types=1);

namespace Tests\Unit\App\Support;

use Mockery;

class Tmp extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void {
	}

	protected function tearDown(): void {
		Mockery::close();
	}

	public function test_something() {
		// Assetions go here
	}
}
