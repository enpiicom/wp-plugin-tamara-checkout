<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Enpii_Base\Foundation\Support\Executable_Trait;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\Deps\Tamara\Model\Order\RiskAssessment;
use WC_Order;

class Build_Tamara_Order_Risk_Assessment {
	use Executable_Trait;

	/**
	 * @var WC_Order
	 */
	protected $wc_order;

	public function __construct( WC_Order $wc_order ) {
		$this->wc_order = $wc_order;
	}

	public function handle() {
		$data = [
			'account_creation_date' => $this->get_current_user_register_date(),
			'platform_account_creation_date' => $this->get_current_user_register_date(),
			'has_delivered_order' => $this->get_current_user_has_delivered_order(),
			'total_order_count' => $this->get_current_user_total_order_count(),
			'date_of_first_transaction' => $this->get_current_user_date_of_first_transaction(),
			'order_amount_last3months' => $this->get_current_user_order_amount_last_3_months(),
			'order_count_last3months' => $this->get_current_user_order_count_last_3_months(),
			'is_existing_customer' => is_user_logged_in(),
		];

		return new RiskAssessment( $data );
	}

	protected function get_current_user_register_date() {
		if ( is_user_logged_in() ) {
			$user_registered_date = get_the_author_meta( 'user_registered', get_current_user_id() );
			if ( $user_registered_date && strtotime( $user_registered_date ) !== false ) {
				return gmdate( 'd-m-Y', strtotime( $user_registered_date ) );
			}
		}

		return gmdate( 'd-m-Y', time() );
	}

	protected function get_current_user_has_delivered_order(): bool {
		if ( is_user_logged_in() ) {
			$args           = [
				'customer_id' => get_current_user_id(),
				'post_status' => [ 'shipped', 'completed', 'wc-shipped', 'wc-completed' ],
				'post_type' => 'shop_order',
				'return' => 'ids',
			];
			$order_completed = count( wc_get_orders( $args ) );

			return (bool) $order_completed > 0;
		} else {
			return false;
		}
	}

	protected function get_current_user_total_order_count() {
		if ( is_user_logged_in() ) {
			$args = [
				'customer_id' => get_current_user_id(),
				'post_type' => 'shop_order',
				'return' => 'ids',
			];

			return count( wc_get_orders( $args ) );
		} else {
			return 0;
		}
	}

	protected function get_current_user_date_of_first_transaction() {
		if ( is_user_logged_in() ) {
			$args = [
				'customer_id' => get_current_user_id(),
				'post_type' => 'shop_order',
				'orderby' => 'date',
				'order' => 'ASC',
			];
			$orders = wc_get_orders( $args );
			if ( ! empty( $orders[0] ) ) {
				$first_order_date = (string) $orders[0]->get_date_created();
				if ( $first_order_date && strtotime( $first_order_date ) !== false ) {
					return gmdate( 'd-m-Y', strtotime( $first_order_date ) );
				}
			}
		}

		return gmdate( 'd-m-Y', time() );
	}

	protected function get_current_user_order_amount_last_3_months(): float {
		$total_amount = 0;
		$currency = $this->wc_order->get_currency();
		if ( is_user_logged_in() ) {
			$args = [
				'customer_id' => get_current_user_id(),
				'post_type' => 'shop_order',
				'date_query' =>
					[
						[
							'after' => gmdate( 'Y-m-d', strtotime( '3 months ago' ) ),
						],
					],
				'inclusive'   => true,
			];

			$orders = wc_get_orders( $args );
			if ( ! empty( $orders ) ) {
				foreach ( $orders as $order ) {
					$total_amount += $order->get_total();
				}
			}
		}

		return Tamara_Checkout_Helper::format_price_number( $total_amount, $currency );
	}

	protected function get_current_user_order_count_last_3_months() {
		if ( is_user_logged_in() ) {
			$args = [
				'customer_id' => get_current_user_id(),
				'post_type' => 'shop_order',
				'date_query' =>
					[
						[
							'after' => gmdate( 'Y-m-d', strtotime( '3 months ago' ) ),
						],
					],
				'inclusive'   => true,
			];

			return count( wc_get_orders( $args ) );
		}

		return 0;
	}
}
