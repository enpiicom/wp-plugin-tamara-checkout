<?php

declare(strict_types=1);

namespace Tamara_Checkout\Tests\App\Jobs;

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
}
