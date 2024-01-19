<?php

declare(strict_types=1);

namespace Tamara_Checkout\Tests\App\WP;

use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\WP\Tamara_Checkout_To_Test_Init_Woocommerce;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Tests\Support\Helpers\Test_Utils_Trait;
use Tamara_Checkout\Tests\Support\Helpers\Unit_Test_Case;

class Tamara_Checkout_WP_Plugin_Test extends Unit_Test_Case {
	use Test_Utils_Trait;

	/**
	 * @var Tamara_Checkout_WP_Plugin
	 */
	protected $main_object;

	protected function setUp(): void {
		parent::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	protected function build_main_object(): Tamara_Checkout_WP_Plugin {
		$this->main_object = new Tamara_Checkout_WP_Plugin( null );

		return $this->main_object;
	}

	public function test_get_name(): void {
		$main_object = $this->build_main_object();

		$expected_value = Tamara_Checkout_Helper::PLUGIN_NAME;
		$actual_result = $main_object->get_name();

		$this->assertEquals( $expected_value, $actual_result );
	}

	public function test_get_version(): void {
		defined( 'TAMARA_CHECKOUT_VERSION' ) || define( 'TAMARA_CHECKOUT_VERSION', '1.0.1' );
		$main_object = $this->build_main_object();
		$expected_value = '1.0.1';
		$actual_result = $main_object->get_version();

		$this->assertEquals( $expected_value, $actual_result );
	}

	public function test_get_text_domain(): void {
		$main_object = $this->build_main_object();
		$expected_value = Tamara_Checkout_Helper::TEXT_DOMAIN;
		$actual_result = $main_object->get_text_domain();

		$this->assertEquals( $expected_value, $actual_result );
	}

	public function test_manipulate_hooks(): void {
		$mock = $this->getMockBuilder( \stdClass::class )
			->disableOriginalConstructor()
			->addMethods( [ 'register_api_routes', 'register_routes' ] )
			->getMock();
		$mock->method( 'register_api_routes' )
			->willReturn( '' );
		$mock->expects( $this->atLeastOnce() )
			->method( 'register_routes' )
			->willReturn( '' );
		$GLOBALS['mock'] = $mock;

		$main_object = $this->build_main_object();
		$this->set_property_value( $main_object, 'base_path', '' );
		$this->set_property_value( $main_object, 'plugin_slug', '' );

		$actual_result = $main_object->manipulate_hooks();

		$this->assertNull( $actual_result );
	}

	public function test_init_woocommerce(): void {
		$mock = $this->getMockBuilder( \stdClass::class )
			->disableOriginalConstructor()
			->addMethods( [ 'singleton' ] )
			->getMock();
		$mock->expects( $this->exactly( 1 ) )
			->method( 'singleton' )
			->willReturn( '' );
		$GLOBALS['mock'] = $mock;

		$main_object = new Tamara_Checkout_To_Test_Init_Woocommerce( null );
		$actual_result = $main_object->init_woocommerce();

		$this->assertNull( $actual_result );
	}
}

namespace Enpii_Base\Foundation\WP;

function plugin_basename() {
	return '';
}

namespace Tamara_Checkout\App\WP;

function wp_app() {
	return $GLOBALS['mock'];
}

class Tamara_Checkout_To_Test_Init_Woocommerce extends Tamara_Checkout_WP_Plugin {
	protected function register_services(): void {
	}
}
