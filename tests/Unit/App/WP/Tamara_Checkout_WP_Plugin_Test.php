<?php

declare(strict_types=1);

namespace Tamara_Checkout\Tests\App\WP;

use Enpii_Base\App\WP\WP_Application;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Tests\Support\Helpers\Test_Utils_Trait;
use Tamara_Checkout\Tests\Support\Unit\Libs\Unit_Test_Case;
use WP_Mock;

class Tamara_Checkout_WP_Plugin_Test extends Unit_Test_Case {
	use Test_Utils_Trait;

	/** @var WP_Plugin_Tmp $mock_wp_plugin */
	protected $mock_tamara_checkout_wp_plugin;

	protected function setUp(): void {
		parent::setUp();

		$this->mock_tamara_checkout_wp_plugin = $this->build_tamara_checkout_wp_plugin_mock();
	}

	protected function tearDown(): void {
		$this->mock_tamara_checkout_wp_plugin = null;

		parent::tearDown();
	}

	protected function build_tamara_checkout_wp_plugin_mock( array $mocked_methods = [] ) {
		$mock_wp_app = $this->getMockBuilder( WP_Application::class );
		return $this->getMockForAbstractClass(
			Tmp_Tamara_Checkout_WP_Plugin::class,
			[ $mock_wp_app ],
			'',
			false,
			true,
			true,
			$mocked_methods
		);
	}

	public function test_manipulate_hooks(): void {
		$mock_tamara_checkout_wp_plugin = $this->build_tamara_checkout_wp_plugin_mock(
			[
				'get_plugin_basename',
			]
		);

		// Expected method calls
		$mock_tamara_checkout_wp_plugin->expects( $this->once() )->method( 'get_plugin_basename' )->willReturn( 'tamara-checkout/tamara-checkout.php' );

		$wp_app = new WP_Application_Tamara_Checkout_WP_Plugin();
		WP_Mock::userFunction( 'wp_app' )
			->times()
			->withAnyArgs()
			->andReturn( $wp_app );

		WP_Mock::userFunction( 'add_action' )
			->times( )
			->with( [ 'init', [ $mock_tamara_checkout_wp_plugin, 'register_tamara_custom_order_statuses' ] ] );

		$mock_tamara_checkout_wp_plugin->manipulate_hooks();
	}
}

class Tmp_Tamara_Checkout_WP_Plugin extends Tamara_Checkout_WP_Plugin {

}

class WP_Application_Tamara_Checkout_WP_Plugin {
	public function register_api_routes( $callback ) {
	}
	public function register_routes( $callback ) {
	}
}
