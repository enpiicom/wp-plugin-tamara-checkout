<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP;

use Tamara_Checkout\Tests\Support\Unit\Libs\Unit_Test_Case;

class Tamara_Checkout_WP_Plugin_Test extends Unit_Test_Case {

	public function test_get_name(): void {
		$plugin_name = 'Tamara Checkout';
		$tamara_checkout_wp_plugin_mock = $this->getMockBuilder( Tamara_Checkout_WP_Plugin::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_name' ] )
			->getMock();
		$tamara_checkout_wp_plugin_mock->expects( $this->once() )
		                               ->method( 'get_name' )
		                               ->willReturn( $plugin_name );
		$result = $tamara_checkout_wp_plugin_mock->get_name();

		$this->assertEquals( $plugin_name, $result );
	}

	public function test_get_version(): void {
		// Set up WP Mock
		\WP_Mock::setUp();

		// Define the expected version
		$expected_version = '2.0.0';

		// Define the constant TAMARA_CHECKOUT_VERSION
		defined('TAMARA_CHECKOUT_VERSION') || define('TAMARA_CHECKOUT_VERSION', $expected_version);

		$tamara_checkout_wp_plugin_mock = $this->getMockBuilder( Tamara_Checkout_WP_Plugin::class )
		                                       ->disableOriginalConstructor()
		                                       ->onlyMethods( [ 'get_version' ] )
		                                       ->getMock();
		$tamara_checkout_wp_plugin_mock->expects( $this->once() )
		                               ->method( 'get_version' )
		                               ->willReturn( TAMARA_CHECKOUT_VERSION );
		$result = $tamara_checkout_wp_plugin_mock->get_version();

		$this->assertEquals( TAMARA_CHECKOUT_VERSION, $result );

		// Clean up WP Mock
		\WP_Mock::tearDown();
	}

	public function test_get_domain(): void {
		// Define the expected text domain
		$expected_textdomain = 'tamara';

		$tamara_checkout_wp_plugin_mock = $this->getMockBuilder( Tamara_Checkout_WP_Plugin::class )
		                                       ->disableOriginalConstructor()
		                                       ->onlyMethods( [ 'get_text_domain' ] )
		                                       ->getMock();

		// Expect get_text_domain() method to return the value from real object's constant
		$tamara_checkout_wp_plugin_mock->expects( $this->once() )
		                               ->method( 'get_text_domain' )
		                               ->will($this->returnValue($tamara_checkout_wp_plugin_mock::TEXT_DOMAIN));
		$result = $tamara_checkout_wp_plugin_mock->get_text_domain();

		$this->assertEquals( $expected_textdomain, $result );
	}

	public function test_get_tamara_client_service() {

	}
}
