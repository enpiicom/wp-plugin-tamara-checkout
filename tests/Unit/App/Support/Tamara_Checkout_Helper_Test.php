<?php

declare(strict_types=1);

namespace Tamara_Checkout\Tests\App\WP;

use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\Tests\Support\Helpers\Test_Utils_Trait;
use Tamara_Checkout\Tests\Support\Unit\Libs\Unit_Test_Case;

class Tamara_Checkout_Helper_Test extends Unit_Test_Case {
	use Test_Utils_Trait;

	public function test_get_currency_country_mappings(): void {
		$this->assertEquals(
			[
				'SAR' => 'SA',
				'AED' => 'AE',
				'KWD' => 'KW',
				'BHD' => 'BH',
			],
			Tamara_Checkout_Helper::get_currency_country_mappings()
		);
	}
}
