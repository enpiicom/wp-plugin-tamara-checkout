<?php

declare(strict_types=1);

namespace Tamara_Checkout\Tests\App\Jobs;

use Tamara_Checkout\App\Queries\Get_Tamara_Payment_Options_Query;
use Tamara_Checkout\App\Services\Tamara_Client;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Client;
use Tamara_Checkout\Deps\Tamara\Model\Money;
use Tamara_Checkout\Deps\Tamara\Response\Checkout\CheckPaymentOptionsAvailabilityResponse;
use Tamara_Checkout\Tests\Support\Unit\Libs\Unit_Test_Case;

class Get_Tamara_Payment_Options_Query_Test extends Unit_Test_Case {

	/**
	 * @throws \ReflectionException
	 */
	public function test_fetch_payment_options_availability() {
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
}
