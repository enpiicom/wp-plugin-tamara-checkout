<?php

declare(strict_types=1);

namespace Tamara_Checkout\Tests\App\Jobs;

use Mockery;
use Tamara_Checkout\App\Queries\Get_Tamara_Payment_Options_Query;
use Tamara_Checkout\App\Services\Tamara_Client;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\WP\Payment_Gateways\Pay_In_10_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\Pay_In_11_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\Pay_In_12_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\Pay_In_2_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\Pay_In_3_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\Pay_In_4_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\Pay_In_5_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\Pay_In_6_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\Pay_In_7_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\Pay_In_8_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\Pay_In_9_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\Pay_Next_Month_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\Pay_Now_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Payment_Gateways\WC_Tamara_Payment_Type;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Client;
use Tamara_Checkout\Deps\Tamara\Model\Money;
use Tamara_Checkout\Deps\Tamara\Response\Checkout\CheckPaymentOptionsAvailabilityResponse;
use Tamara_Checkout\Tests\Support\Unit\Libs\Unit_Test_Case;

class Get_Tamara_Payment_Options_Query_Test extends Unit_Test_Case {

	public function test_handle() {
		// Create a mock object for the Get_Tamara_Payment_Options_Query class.
		$test_object = Mockery::mock( Get_Tamara_Payment_Options_Query::class )
			->shouldAllowMockingProtectedMethods()
			->shouldAllowMockingMethod( 'handle' )
			->makePartial();

		// Setup the expected outcomes for the methods called inside the handle method.
		$remote_available_payment_types = [ 'method1', 'method2' ];
		$remote_payment_methods = [ 'pay_in_3', 'pay_later' ];
		$processed_gateways = [ 'processed_method1', 'processed_method2' ];

		// Configure the mock to return the expected values when certain methods are called.
		$test_object->shouldReceive( 'fetch_payment_options_availability' )
			->andReturn( $remote_available_payment_types );

		$test_object->shouldReceive( 'convert_tamara_payment_types_to_wc_payment_methods' )
			->with( $remote_available_payment_types )
			->andReturn( $remote_payment_methods );

		$test_object->shouldReceive( 'process_available_gateways' )
			->with( $remote_payment_methods )
			->andReturn( $processed_gateways );

		// Calling the handle method on the mock object now uses the mocked methods.
		$result = $test_object->handle();

		// Assert that the result matches the expected processed gateways.
		$this->assertEquals( $processed_gateways, $result );
	}

	/**
	 * @throws \ReflectionException
	 */
	public function test_fetch_payment_options_availability(): void {
		$expected_result = [ 'label1', 'label2' ];

		$response_mock = \Mockery::mock( CheckPaymentOptionsAvailabilityResponse::class );
		$response_mock->shouldReceive( 'getAvailablePaymentLabels' )
			->andReturn( $expected_result );

		$tamara_client_mock = \Mockery::mock( Client::class );
		$tamara_client_mock->shouldReceive( 'checkPaymentOptionsAvailability' )
			->andReturn( $response_mock );

		$tamara_client_service_mock = \Mockery::mock( Tamara_Client::class );
		$tamara_client_service_mock->shouldReceive( 'get_api_client' )
			->andReturn( $tamara_client_mock );

		$plugin_instance = \Mockery::mock( Tamara_Checkout_WP_Plugin::class );
		$plugin_instance->shouldReceive( 'get_tamara_client_service' )
			->andReturn( $tamara_client_service_mock );

		$test_object = \Mockery::mock( Get_Tamara_Payment_Options_Query::class )
			->shouldAllowMockingProtectedMethods()
			->shouldAllowMockingMethod( 'fetch_payment_options_availability' )
			->makePartial();
		$test_object->shouldReceive( 'get_plugin_instance' )
			->andReturn( $plugin_instance );

		$this->set_property_value( $test_object, 'country_code', 'AE' );
		$this->set_property_value(
			$test_object,
			'order_total',
			new Money( General_Helper::format_tamara_number( 100 ), 'AED' )
		);
		$this->set_property_value( $test_object, 'customer_phone', '123456789' );
		$this->set_property_value( $test_object, 'is_vip', false );

		$result = $test_object->fetch_payment_options_availability();

		$this->assertEquals( $expected_result, $result );
	}

	/**
	 * @throws \Exception
	 */
	public function test_convert_tamara_payment_types_to_wc_payment_methods(): void {
		// Mock the Tamara WC payment gateway service
		Mockery::mock( 'WC_Payment_Gateway' );
		$mappings = [
			'PAY_NOW_0' => Pay_Now_WC_Payment_Gateway::class,
			'PAY_NEXT_MONTH_0' => Pay_Next_Month_WC_Payment_Gateway::class,
			'PAY_BY_INSTALMENTS_3' => Pay_In_3_WC_Payment_Gateway::class,
		];

		// Todo: We need to mock method _t and return somevalue
		$wc_tamara_payment_type_mock = \Mockery::mock( WC_Tamara_Payment_Type::class )
			->shouldAllowMockingProtectedMethods()
			->makePartial();

		$wc_tamara_payment_type_mock->shouldReceive( '_t' )->andReturn( 'value' );

		// phpcs::ignore Squiz.PHP.CommentedOutCode.Found
		//      \WP_Mock::userFunction( 'get_locale' )
		//                    ->once()
		//                    ->andReturn( 'en' );

		$remote_available_payment_types = [
			0 => [
				'payment_type' => 'PAY_NOW',
				'instalment' => 0,
			],
			1 => [
				'payment_type' => 'PAY_NEXT_MONTH',
				'instalment' => 0,
			],
			2 => [
				'payment_type' => 'PAY_BY_INSTALMENTS',
				'instalment' => 3,
			],
		];

		$test_object = Mockery::mock( Get_Tamara_Payment_Options_Query::class )
			->shouldAllowMockingMethod( 'convert_tamara_payment_types_to_wc_payment_methods' )
			->shouldAllowMockingProtectedMethods()
			->makePartial();

		$test_object->shouldReceive( 'get_payment_type_to_service_mappings' )->andReturn( $mappings );
		// phpcs::ignore Squiz.PHP.CommentedOutCode.Found
		// $payment_methods_result = $test_object->convert_tamara_payment_types_to_wc_payment_methods($remote_available_payment_types);
	}

	/**
	 * @throws \ReflectionException
	 */
	public function test_process_available_gateways(): void {
		$available_gateways = [
			'tamara-gateway' => 'Tamara',
		];

		$new_gateways = [
			'tamara-gateway-pay-later' => 'Pay_Later',
			'tamara-gateway-pay-now' => 'Pay_Now',
		];

		$test_object = Mockery::mock( Get_Tamara_Payment_Options_Query::class )
			->shouldAllowMockingProtectedMethods()
			->shouldAllowMockingMethod( 'process_available_gateways' )
			->makePartial();
		$this->set_property_value( $test_object, 'available_gateways', $available_gateways );

		// Invoke the method and get the result
		$result = $test_object->process_available_gateways( $new_gateways );

		// Verify the result returns the expected array
		$this->assertEquals( $new_gateways, $result );
	}

	/**
	 * @throws \ReflectionException
	 */
	public function test_get_plugin_instance(): void {
		$test_object = $this->getMockBuilder( Get_Tamara_Payment_Options_Query::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'get_plugin_instance' ] )->getMock();

		// Invoke the method and get the result
		$result = $this->invoke_protected_method( $test_object, 'get_plugin_instance' );

		// Verify the result is an instance of Tamara_Checkout_WP_Plugin
		$this->assertInstanceOf( Tamara_Checkout_WP_Plugin::class, $result );
	}

	public function test_get_payment_type_to_service_mappings(): void {
		$test_object = Mockery::mock( Get_Tamara_Payment_Options_Query::class )
			->shouldAllowMockingProtectedMethods()
			->shouldAllowMockingMethod( 'get_payment_type_to_service_mappings' )
			->makePartial();
		$expected = [
			'PAY_LATER_0' => Tamara_WC_Payment_Gateway::class,
			'PAY_NOW_0' => Pay_Now_WC_Payment_Gateway::class,
			'PAY_NEXT_MONTH_0' => Pay_Next_Month_WC_Payment_Gateway::class,
			'PAY_BY_INSTALMENTS_2' => Pay_In_2_WC_Payment_Gateway::class,
			'PAY_BY_INSTALMENTS_3' => Pay_In_3_WC_Payment_Gateway::class,
			'PAY_BY_INSTALMENTS_4' => Pay_In_4_WC_Payment_Gateway::class,
			'PAY_BY_INSTALMENTS_5' => Pay_In_5_WC_Payment_Gateway::class,
			'PAY_BY_INSTALMENTS_6' => Pay_In_6_WC_Payment_Gateway::class,
			'PAY_BY_INSTALMENTS_7' => Pay_In_7_WC_Payment_Gateway::class,
			'PAY_BY_INSTALMENTS_8' => Pay_In_8_WC_Payment_Gateway::class,
			'PAY_BY_INSTALMENTS_9' => Pay_In_9_WC_Payment_Gateway::class,
			'PAY_BY_INSTALMENTS_10' => Pay_In_10_WC_Payment_Gateway::class,
			'PAY_BY_INSTALMENTS_11' => Pay_In_11_WC_Payment_Gateway::class,
			'PAY_BY_INSTALMENTS_12' => Pay_In_12_WC_Payment_Gateway::class,
		];

		$mapping_result = $test_object->get_payment_type_to_service_mappings();

		// Verify the result is as expected
		$this->assertEquals( $expected, $mapping_result );
	}
}
