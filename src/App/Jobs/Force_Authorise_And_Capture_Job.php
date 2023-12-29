<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Shared\Base_Job;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Tamara_Checkout\App\Support\Helpers\Tamara_Order_Helper;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use WP_Query;

class Force_Authorise_And_Capture_Job extends Base_Job {

	use Executable_Trait;

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	public function handle() {
		if ( current_user_can( 'publish_posts' ) ) {
			$this->force_authorise_tamara_order();
			$this->force_capture_tamara_order();

			return wp_json_encode( true );
		}

		return wp_json_encode( false );
	}

	/**
	 * Force pending authorise payments within 180 days to be authorised
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	public function force_authorise_tamara_order() {
		$customer_orders = $this->build_authorise_orders_query_args();
		$customer_orders_query = new WP_Query( $customer_orders );
		$wc_order_ids = $customer_orders_query->posts;

		foreach ( $wc_order_ids as $wc_order_id ) {
			if ( ! Tamara_Order_Helper::is_order_authorised( $wc_order_id ) ) {

				$tamara_wc_order = new Tamara_WC_Order( $wc_order_id );
				$tamara_order_id = $tamara_wc_order->get_tamara_order_id_by_wc_order_id();

				Process_Tamara_Order_Approved_Job::dispatchSync(
					$tamara_order_id,
					$wc_order_id
				);
			}
		}
	}

	/**
	 * Force pending capture payments within 180 days to be captured
	 */
	public function force_capture_tamara_order() {
		$customer_orders = $this->build_capture_orders_query_args();
		$customer_orders_query = new WP_Query( $customer_orders );
		$wc_order_ids = $customer_orders_query->posts;

		foreach ( $wc_order_ids as $wc_order_id ) {
			Capture_Tamara_Order_If_Possible_Job::dispatchSync(
				[
					'wc_order_id' => $wc_order_id,
				]
			);
		}
	}

	/**
	 * @return array
	 */
	protected function build_authorise_orders_query_args(): array {
		$status_to_be_authorised = 'wc-pending';
		return [
			'fields' => 'ids',
			'post_type' => 'shop_order',
			'post_status' => $status_to_be_authorised,
			'date_query' => [
				'after' => gmdate( 'Y-m-d', strtotime( '-180 days' ) ),
				'inclusive' => true,
			],
			'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
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

	/**
	 * @return array
	 */
	protected function build_capture_orders_query_args(): array {
		$status_to_be_captured = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_gateway_service()
			->get_settings()
			->status_to_capture_tamara_payment;
		return [
			'fields' => 'ids',
			'post_type' => 'shop_order',
			'post_status' => $status_to_be_captured,
			'date_query' => [
				'after' => gmdate( 'Y-m-d', strtotime( '-180 days' ) ),
				'inclusive' => true,
			],
			'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				[
					'key' => '_tamara_order_id',
					'compare' => 'EXISTS',
				],
				[
					'key' => '_tamara_capture_id',
					'compare' => 'NOT EXISTS',
				],
			],
		];
	}
}
