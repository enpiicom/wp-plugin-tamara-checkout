<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Http\Controllers\Api;

use Enpii_Base\App\Models\User;
use Enpii_Base\App\Support\Traits\Queue_Trait;
use Enpii_Base\Foundation\Http\Base_Controller;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tamara_Checkout\App\Jobs\Authorise_Tamara_Order_If_Possible_Job;
use Tamara_Checkout\App\Jobs\Update_Tamara_Webhook_Event_Job;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\Deps\Http\Client\Exception;

class Main_Controller extends Base_Controller {
	use Tamara_Checkout_Trait;
	use Queue_Trait;

	public function handle_tamara_success( Request $request, $wc_order_id ): void {
		$custom_success_url = $this->tamara_settings()->success_url;
		$tamara_order_id = $request->get( 'orderId' );
		$wc_order = wc_get_order( $wc_order_id );

		// We authorise the order
		try {
			Authorise_Tamara_Order_If_Possible_Job::dispatchSync(
				[
					'wc_order_id' => $wc_order_id,
					'tamara_order_id' => $tamara_order_id,
				]
			);
		// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		} catch ( Exception $e ) {
			// We do nothing for the exception here,
			//  just want to catch all the exception to have the redirect work
		}

		// Then redirect to the URL we want
		$order_received_url = ! empty( $wc_order ) ? esc_url_raw( $wc_order->get_checkout_order_received_url() ) : home_url();
		$success_url_from_tamara = add_query_arg(
			[
				'wc_order_id' => $wc_order_id,
				'tamara_order_id' => $tamara_order_id,
			],
			$custom_success_url ? $custom_success_url : $order_received_url
		);

		wp_safe_redirect( $success_url_from_tamara );
		exit;
	}

	/**
	 * @throws \Exception
	 */
	public function handle_tamara_cancel( Request $request, $wc_order_id, User $user ): void {
		$custom_cancel_url = $this->tamara_settings()->cancel_url;
		$wc_order = wc_get_order( $wc_order_id );
		$cancel_url_from_tamara = add_query_arg(
			[
				'redirect_from' => 'tamara',
				'cancel_order' => 'true',
				'order' => $wc_order->get_order_key(),
				'order_id' => $wc_order_id,
				'payment_type' => $request->get( 'payment_type' ),
				'tamara_order_id' => $request->get( 'orderId' ),
				'tamara_payment_status' => $request->get( 'paymentStatus' ),
			],
			$custom_cancel_url ? $custom_cancel_url : $wc_order->get_cancel_order_url_raw()
		);

		wp_safe_redirect( $cancel_url_from_tamara );
		exit;
	}

	/**
	 * @throws \Exception
	 */
	public function handle_tamara_failure( Request $request, $wc_order_id ): void {
		$custom_failure_url = $this->tamara_settings()->failure_url;
		$wc_order = wc_get_order( $wc_order_id );
		$failure_url_from_tamara = add_query_arg(
			[
				'redirect_from' => 'tamara',
				'cancel_order' => 'true',
				'order' => $wc_order->get_order_key(),
				'order_id' => $wc_order_id,
				'payment_type' => $request->get( 'payment_type' ),
				'tamara_order_id' => $request->get( 'orderId' ),
				'tamara_payment_status' => $request->get( 'paymentStatus' ),
			],
			$custom_failure_url ? $custom_failure_url : $wc_order->get_cancel_order_url_raw()
		);

		wp_safe_redirect( $failure_url_from_tamara );
		exit;
	}

	/**
	 * @throws \Exception
	 */
	public function handle_tamara_ipn(): JsonResponse {
		$authorise_message = $this->tamara_notification()->process_authorise_message();
		$this->process_authorise_tamara_order( $authorise_message->getOrderReferenceId(), $authorise_message->getOrderId() );

		return wp_app_response()->json(
			[
				'message' => 'success',
			]
		);
	}

	public function handle_tamara_webhook(): JsonResponse {
		$webhook_message = $this->tamara_notification()->process_webhook_message();

		switch ( $webhook_message->getEventType() ) {
			case 'order_approved':
				$this->process_authorise_tamara_order( $webhook_message->getOrderReferenceId(), $webhook_message->getOrderId() );
				break;
			default:
				$this->process_other_tamara_webhook_events(
					$webhook_message->getOrderReferenceId(),
					$webhook_message->getOrderId(),
					$webhook_message->getEventType()
				);
				break;
		}

		return wp_app_response()->json(
			[
				'message' => 'success',
			]
		);
	}

	/**
	 * We want to executure the Authorise Job right away but we need to add the job
	 *  to the queue as the fallback to authorise later in case the Tamara API
	 *  doesn't work at that moment
	 * @param mixed $wc_order_id
	 * @param mixed $tamara_order_id
	 * @return void
	 * @throws BindingResolutionException
	 */
	protected function process_authorise_tamara_order( $wc_order_id, $tamara_order_id ) {
		$args = [
			'wc_order_id' => $wc_order_id,
			'tamara_order_id' => $tamara_order_id,
		];
		try {
			Authorise_Tamara_Order_If_Possible_Job::dispatchSync( $args );
		} catch ( Exception $e ) {
			$this->enqueue_job_later( Authorise_Tamara_Order_If_Possible_Job::dispatch( $args ) );
		}
	}

	protected function process_other_tamara_webhook_events( $wc_order_id, $tamara_order_id, $event_type ) {
		$args = [
			'wc_order_id' => $wc_order_id,
			'tamara_order_id' => $tamara_order_id,
			'event_type' => $event_type,
		];
		try {
			Update_Tamara_Webhook_Event_Job::dispatchSync( $args );
		// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		} catch ( Exception $e ) {
		}
	}
}
