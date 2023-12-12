<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP;

use Tamara_Checkout\Tests\Support\Unit\Libs\Unit_Test_Case;

class Tamara_Checkout_WP_Plugin_Test extends Unit_Test_Case {

	public function test_get_name() {
		$plugin_name = 'Tamara Checkout';
		$tamara_checkout_wp_plugin = $this->getMockBuilder(Tamara_Checkout_WP_Plugin::class)
			->disableOriginalConstructor()->onlyMethods(['get_name'])->getMock();
		$tamara_checkout_wp_plugin->expects($this->once())->method('get_name')->willReturn($plugin_name);
		$result = $tamara_checkout_wp_plugin->get_name();

		$this->assertEquals($plugin_name, $result);
	}
}
