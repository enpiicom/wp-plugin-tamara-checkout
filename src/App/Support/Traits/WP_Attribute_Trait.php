<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Traits;

use Tamara_Checkout\App\Support\Helpers\General_Helper;

trait WP_Attribute_Trait {

	public function get_current_user_register_date() {
		if ( is_user_logged_in() ) {
			$current_user_id = get_current_user_id() ?? null;

			return gmdate(
				'd-m-Y',
				strtotime( get_the_author_meta( 'user_registered', $current_user_id ) )
			)
					?? gmdate( 'd-m-Y', time() );
		} else {
			return gmdate( 'd-m-Y', time() );
		}
	}

	public function get_current_user_has_delivered_order(): bool {
		if ( is_user_logged_in() ) {
			$current_user_id  = get_current_user_id() ?? null;
			$args           = [
				'customer_id' => $current_user_id,
				'post_status' => [ 'shipped', 'completed', 'wc-shipped', 'wc-completed' ],
				'post_type'   => 'shop_order',
				'return'      => 'ids',
			];
			$order_completed = count( wc_get_orders( $args ) );

			return ! ! $order_completed > 0;
		} else {
			return false;
		}
	}

	public function get_current_user_total_order_count() {
		if ( is_user_logged_in() ) {
			$current_user_id = get_current_user_id() ?? null;
			$args          = [
				'customer_id' => $current_user_id,
				'post_type'   => 'shop_order',
				'return'      => 'ids',
			];

			return count( wc_get_orders( $args ) ) ?? 0;
		} else {
			return 0;
		}
	}

	public function get_current_user_date_of_first_transaction() {
		if ( is_user_logged_in() ) {
			$currentUserId = get_current_user_id() ?? null;
			$args          = [
				'customer_id' => $currentUserId,
				'post_type'   => 'shop_order',
				'orderby'     => 'date',
				'order'       => 'ASC',
			];
			$orders        = wc_get_orders( $args );
			if ( $orders ) {
				return gmdate( 'd-m-Y', strtotime( (string) $orders[0]->get_date_created() ) ) ?? gmdate( 'd-m-Y', time() );
			}
		}
		return gmdate( 'd-m-Y', time() );
	}

	public function get_current_user_order_amount_last_3_months(): float {
		$total_amount = 0;
		if ( is_user_logged_in() ) {
			$current_user_id = get_current_user_id() ?? null;
			$args          = [
				'customer_id' => $current_user_id,
				'post_type'   => 'shop_order',
				'date_query'  =>
					[
						[
							'after' => gmdate( 'Y-m-d', strtotime( '3 months ago' ) ),
						],
					],
				'inclusive'   => true,
			];

			$orders = wc_get_orders( $args );
			if ( $orders ) {
				foreach ( $orders as $order ) {
					$total_amount += $order->get_total();
				}
			}
		}

		return General_Helper::format_tamara_number( $total_amount );
	}

	public function get_current_user_order_count_last_3_months() {
		if ( is_user_logged_in() ) {
			$current_user_id = get_current_user_id() ?? null;
			$args          = [
				'customer_id' => $current_user_id,
				'post_type'   => 'shop_order',
				'date_query'  =>
					[
						[
							'after' => gmdate( 'Y-m-d', strtotime( '3 months ago' ) ),
						],
					],
				'inclusive'   => true,
			];

			return count( wc_get_orders( $args ) ) ?? 0;
		} else {
			return 0;
		}
	}
}
