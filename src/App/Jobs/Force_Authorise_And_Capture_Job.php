<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Shared\Base_Job;
use Enpii_Base\Foundation\Shared\Traits\Config_Trait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tamara_Checkout\App\Support\Helpers\Tamara_Order_Helper;
use Tamara_Checkout\App\Support\Helpers\WC_Order_Helper;
use Tamara_Checkout\App\Support\Traits\Trans_Trait;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;
use Tamara_Checkout\Deps\Http\Client\Exception;

class Force_Authorise_And_Capture_Job extends Base_Job implements ShouldQueue {
	use Dispatchable;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;
	use Config_Trait;
	use Trans_Trait;

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	public function handle() {
		$this->force_authorise_tamara_order();
	}

	/**
	 * Force pending authorise payments within 180 days to be authorised
	 *
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	public function force_authorise_tamara_order() {
		$customer_orders = $this->build_customer_orders_query_args();

		$customer_orders_query = new \WP_Query( $customer_orders );

		$wc_order_ids = $customer_orders_query->posts;

		foreach ( $wc_order_ids as $wc_order_id ) {
			if ( ! Tamara_Order_Helper::is_order_authorised( $wc_order_id ) ) {
				$tamara_wc_order = new Tamara_WC_Order( $wc_order_id );
				$tamara_order_id = $tamara_wc_order->get_tamara_order_id_by_wc_order_id();
				try {
					Process_Tamara_Order_Approved_Job::dispatchSync(
						$tamara_order_id,
						$wc_order_id
					);
					// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				} catch ( Exception $e ) {
				}
			}
		}
	}

	protected function build_customer_orders_query_args(): array {
		$to_authorise_status = 'wc-pending';
		return [
			'fields' => 'ids',
			'post_type' => 'shop_order',
			'post_status' => $to_authorise_status,
			'date_query' => [
				'after' => date( 'Y-m-d', strtotime( '-180 days' ) ),
				'inclusive' => true,
			],
			'meta_query' => [
				'relation' => 'AND',
				[
					'key' => '_tamara_checkout_session_id',
					'compare' => 'EXISTS',
				],
				[
					'key' => '_tamara_order_id',
					'compare' => 'NOT EXISTS',
				],
			],
		];
	}
}
