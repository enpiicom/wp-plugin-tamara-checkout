<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Shared\Base_Job;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tamara_Checkout\App\Support\Helpers\Tamara_Order_Helper;
use Tamara_Checkout\App\Support\Helpers\WC_Order_Helper;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

class Authorise_Tamara_Order_Job_Success extends Base_Job implements ShouldQueue {
	use Executable_Trait;

	/**
	 * @throws Exception
	 */
	public function handle( $wc_order_id, $tamara_order_id ) {
		// Empty cart if payment done
		WC()->cart->empty_cart();
		$setting = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_gateway_service()->get_settings();
		Tamara_Order_Helper::update_tamara_order_id_to_wc_order( $wc_order_id, $tamara_order_id );
		$wc_order = wc_get_order( $wc_order_id );
		$order_note = Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'Tamara - Order authorised successfully with Tamara Notification' );
		$new_order_status = $setting->get_payment_authorised_done_status();
		$update_order_status_note = Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'Payment received. ' );

		WC_Order_Helper::update_order_status_and_add_order_note(
			$wc_order,
			$order_note,
			$new_order_status,
			$update_order_status_note
		);
	}
}
