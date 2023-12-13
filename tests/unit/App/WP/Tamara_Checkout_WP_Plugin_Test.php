<?php

declare(strict_types=1);

namespace Tamara_Checkout\Tests\App\WP;

use Mockery;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Tests\Support\Unit\Libs\Unit_Test_Case;
use WP_Mock;

class Tamara_Checkout_WP_Plugin_Test extends Unit_Test_Case {

	protected function setUp(): void {
		WP_Mock::setUp();
	}

	protected function tearDown(): void {
		WP_Mock::tearDown();
	}

	public function test_register(): void {
		$tamara_checkout_wp_plugin_mock = Mockery::mock( Tamara_Checkout_WP_Plugin::class )
												->shouldAllowMockingMethod( 'register' )
												->shouldAllowMockingProtectedMethods()
												->makePartial();
		$tamara_checkout_wp_plugin_mock->shouldReceive( 'register_services' )->once();
		$tamara_checkout_wp_plugin_mock->shouldReceive( 'validate_needed_properties' )->once();

		// Call the register method
		$tamara_checkout_wp_plugin_mock->register();
	}

	public function test_manipulate_hooks(): void {
		// Create a partial mock of Tamara_Checkout_WP_Plugin with only manipulate_hooks method to be tested
		$tamara_checkout_wp_plugin_mock = $this->getMockBuilder( Tamara_Checkout_WP_Plugin::class )
												->disableOriginalConstructor()
												->onlyMethods(
													[
														'check_prerequisites',
														'add_payment_gateways',
														'init_woocommerce',
														'tamara_gateway_process_admin_options',
													]
												)
												->getMock();

		// Expectations for actions and filters to be added
		WP_Mock::expectActionAdded( 'plugins_loaded', [ $tamara_checkout_wp_plugin_mock, 'check_prerequisites' ] );
		WP_Mock::expectFilterAdded( 'woocommerce_payment_gateways', [ $tamara_checkout_wp_plugin_mock, 'add_payment_gateways' ] );
		WP_Mock::expectActionAdded( 'woocommerce_init', [ $tamara_checkout_wp_plugin_mock, 'init_woocommerce' ] );
		WP_Mock::expectActionAdded(
			'woocommerce_update_options_payment_gateways_' . Tamara_Checkout_WP_Plugin::DEFAULT_TAMARA_GATEWAY_ID,
			[ $tamara_checkout_wp_plugin_mock, 'tamara_gateway_process_admin_options' ]
		);

		// Call the method to be tested
		$tamara_checkout_wp_plugin_mock->manipulate_hooks();

		// Verify all the expectations
		WP_Mock::assertHooksAdded();
	}

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
		// Define the expected version
		$expected_version = '2.0.0';

		// Define the constant TAMARA_CHECKOUT_VERSION
		defined( 'TAMARA_CHECKOUT_VERSION' ) || define( 'TAMARA_CHECKOUT_VERSION', $expected_version );

		$tamara_checkout_wp_plugin_mock = $this->getMockBuilder( Tamara_Checkout_WP_Plugin::class )
												->disableOriginalConstructor()
												->onlyMethods( [ 'get_version' ] )
												->getMock();
		$tamara_checkout_wp_plugin_mock->expects( $this->once() )
										->method( 'get_version' )
										->willReturn( TAMARA_CHECKOUT_VERSION );
		$result = $tamara_checkout_wp_plugin_mock->get_version();

		$this->assertEquals( TAMARA_CHECKOUT_VERSION, $result );
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
										->will( $this->returnValue( $tamara_checkout_wp_plugin_mock::TEXT_DOMAIN ) );
		$result = $tamara_checkout_wp_plugin_mock->get_text_domain();

		$this->assertEquals( $expected_textdomain, $result );
	}

	public function test_get_tamara_client_service(): void {
		// Todo: We need to mock global function wp_app() and have it called within a mock class
		//      \WP_Mock::userFunction( 'Tamara_Checkout\Tests\App\WP\wp_app' )
		//              ->once()
		//              ->with( Tamara_Checkout_WP_Plugin::SERVICE_TAMARA_CLIENT )
		//              ->andReturn( 'test' );
		//      $tamara_checkout_wp_plugin_mock = $this->getMockBuilder( Tamara_Checkout_WP_Plugin::class )
		//                                              ->disableOriginalConstructor()
		//                                              ->onlyMethods( [ 'get_tamara_client_service' ] )
		//                                              ->getMock();
		//      $result = $tamara_checkout_wp_plugin_mock->get_tamara_client_service();
	}

	public function test_get_tamara_notification_service(): void {
		// Todo: We need to mock global function wp_app() and have it called within a mock class
	}

	public function test_get_tamara_widget_service(): void {
		// Todo: We need to mock global function wp_app() and have it called within a mock class
	}
}
