<?php

declare(strict_types=1);

namespace Tamara_Checkout\Tests\App\Jobs;

use Exception;
use Tamara_Checkout\Deps\Tamara\Response\Webhook\RegisterWebhookResponse;
use Tamara_Checkout\Tests\Support\Unit\Libs\Unit_Test_Case;
use Mockery;
use Tamara_Checkout\App\Jobs\Register_Tamara_Webhook_Job;

class Register_Tamara_Webhook_Job_Test extends Unit_Test_Case {

	/**
	 * @throws \ReflectionException
	 */
	public function test_update_webhook_id_to_options(): void {
		// Mock the Tamara WC payment gateway service
		$tamara_gateway_service_mock = Mockery::mock( 'Tamara_WC_Payment_Gateway' )
												->shouldAllowMockingProtectedMethods()
												->makePartial();
		// Arrange: Prepare expectations
		$webhook_id = 'example_webhook_id';
		$tamara_gateway_service_mock->settings['tamara_webhook_id'] = ''; // default to empty
		$tamara_gateway_service_mock->shouldReceive( 'update_settings_to_options' )->once();

		$register_tamara_webhook_job = new Register_Tamara_Webhook_Job();
		$this->invoke_protected_method(
			$register_tamara_webhook_job,
			'update_webhook_id_to_options',
			[ $tamara_gateway_service_mock, $webhook_id ]
		);
		// Assert: Verify the tamara_webhook_id setting is updated
		$this->assertEquals( $webhook_id, $tamara_gateway_service_mock->settings['tamara_webhook_id'] );
	}

	/**
	 * @throws \ReflectionException
	 */
	public function test_get_tamara_webhook_events(): void {
		$expected_events = [
			'order_approved',
			'order_declined',
			'order_authorised',
			'order_canceled',
			'order_captured',
			'order_refunded',
			'order_expired',
		];

		$register_tamara_webhook_job = new Register_Tamara_Webhook_Job();
		$tamara_webhook_events = $this->invoke_protected_method(
			$register_tamara_webhook_job,
			'get_tamara_webhook_events',
			[]
		);
		// Assert: Verify the expected events are returned correctly
		$this->assertEquals( $expected_events, $tamara_webhook_events );
	}

	public function test_handle_tamara_register_webhook_response_is_success(): void {
		// Mock the Tamara WC payment gateway service
		$tamara_gateway_service_mock = Mockery::mock( 'Tamara_WC_Payment_Gateway' )
												->shouldAllowMockingProtectedMethods()
												->makePartial();
		$webhook_id = 'example_webhook_id';
		$tamara_register_webhook_api_response = $this->getMockBuilder( RegisterWebhookResponse::class )
			->disableOriginalConstructor()->onlyMethods( [ 'isSuccess', 'getWebhookId' ] )
			->getMock();
		$tamara_register_webhook_api_response->method( 'isSuccess' )->willReturn( true );
		$tamara_register_webhook_api_response->method( 'getWebhookId' )->willReturn( $webhook_id );

		// Arrange: Prepare expectations
		$register_tamara_webhook_job = Mockery::mock( Register_Tamara_Webhook_Job::class )
												->shouldAllowMockingProtectedMethods()
												->shouldAllowMockingMethod( 'handle_tamara_register_webhook_response' )
												->makePartial();
		$register_tamara_webhook_job->shouldReceive( 'update_webhook_id_to_options' )
									->with( $tamara_gateway_service_mock, $webhook_id )->once();
		$register_tamara_webhook_job->handle_tamara_register_webhook_response(
			$tamara_register_webhook_api_response,
			$tamara_gateway_service_mock
		);
	}

	public function test_handle_tamara_register_webhook_response_webhook_already_register(): void {
		// Mock the Tamara WC payment gateway service
		$tamara_gateway_service_mock = Mockery::mock( 'Tamara_WC_Payment_Gateway' )
												->shouldAllowMockingProtectedMethods()
												->makePartial();
		$error_data = [
			0 => [
				'error_code' => 'webhook_already_registered',
				'data' => [
					'webhook_id' => 'example_webhook_id',
				],
			],
		];

		$tamara_register_webhook_api_response = $this->getMockBuilder( RegisterWebhookResponse::class )
													->disableOriginalConstructor()
													->onlyMethods( [ 'isSuccess', 'getErrors' ] )
													->getMock();
		$tamara_register_webhook_api_response->method( 'isSuccess' )->willReturn( false );
		$tamara_register_webhook_api_response->method( 'getErrors' )->willReturn( $error_data );

		// Arrange: Prepare expectations
		$register_tamara_webhook_job = Mockery::mock( Register_Tamara_Webhook_Job::class )
												->shouldAllowMockingProtectedMethods()
												->shouldAllowMockingMethod( 'handle_tamara_register_webhook_response' )
												->makePartial();
		$register_tamara_webhook_job->shouldReceive( 'update_webhook_id_to_options' )
									->with( $tamara_gateway_service_mock, $error_data[0]['data']['webhook_id'] )->once();
		$register_tamara_webhook_job->handle_tamara_register_webhook_response(
			$tamara_register_webhook_api_response,
			$tamara_gateway_service_mock
		);
	}

	/**
	 * @throws \ReflectionException
	 */
	public function test_handle_tamara_register_webhook_response_error(): void {
		$error_message = 'Register Error';
		$error_code = 401;
		// Mock the Tamara WC payment gateway service
		$tamara_gateway_service_mock = Mockery::mock( 'Tamara_WC_Payment_Gateway' )
												->shouldAllowMockingProtectedMethods()
												->makePartial();
		$tamara_gateway_service_mock->shouldReceive( '_t' )->andReturn( $error_message );

		$tamara_register_webhook_api_response = $this->getMockBuilder( RegisterWebhookResponse::class )
													->disableOriginalConstructor()
													->onlyMethods( [ 'isSuccess', 'getMessage', 'getStatusCode', 'getErrors' ] )
													->getMock();

		$tamara_register_webhook_api_response->method( 'isSuccess' )->willReturn( false );
		$tamara_register_webhook_api_response->method( 'getErrors' )->willReturn( [] );
		$tamara_register_webhook_api_response->method( 'getMessage' )->willReturn( $error_message );
		$tamara_register_webhook_api_response->method( 'getStatusCode' )->willReturn( $error_code );
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $error_message );
		$this->expectExceptionCode( $error_code );

		$register_tamara_webhook_job = new Register_Tamara_Webhook_Job();

		$this->invoke_protected_method(
			$register_tamara_webhook_job,
			'handle_tamara_register_webhook_response',
			[ $tamara_register_webhook_api_response, $tamara_gateway_service_mock ]
		);
	}

	/**
	 * @throws \Exception
	 */
	public function test_throw_tamara_register_webhook_exception(): void {
		// Create a mock object for Tamara_WC_Payment_Gateway
		$tamara_gateway_service_mock = Mockery::mock( 'Tamara_WC_Payment_Gateway' );

		// Create an Exception to pass to the function
		$original_exception_message = 'Test exception message';
		$original_exception        = new Exception( $original_exception_message );
		$tamara_gateway_service_mock->shouldReceive( '_t' )->
		andReturn(
			'Tamara Service timeout or disconnected.\nError message: " %s".\nTrace: %s',
			esc_html( $original_exception->getMessage() ),
			esc_html( $original_exception->getTraceAsString() )
		);
		// Create the expected exception message with an escaped version of the original exception message
		$expected_exception_message = sprintf(
			'Tamara Service timeout or disconnected.\nError message: " %s".\nTrace: %s',
			esc_html( $original_exception->getMessage() ),
			esc_html( $original_exception->getTraceAsString() )
		);

		// You can't directly assert exceptions thrown in constructors or functions with `throw`.
		// Instead, you capture the exception and assert its properties.
		try {
			// Call the method under test, which should throw the exception
			$register_tamara_webhook_job = new Register_Tamara_Webhook_Job();
			$this->invoke_protected_method(
				$register_tamara_webhook_job,
				'throw_tamara_register_webhook_exception',
				[ $tamara_gateway_service_mock, $original_exception ]
			);
			// If no exception is thrown, fail the test
			$this->fail( 'An expected exception has not been thrown.' );

		} catch ( Exception $e ) {
			// Assert that the correct exception is thrown with expected message
			$this->assertEquals( $expected_exception_message, $e->getMessage() );
		}
	}
}
