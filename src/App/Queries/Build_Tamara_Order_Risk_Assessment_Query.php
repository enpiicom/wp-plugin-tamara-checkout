<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Enpii_Base\Foundation\Shared\Base_Query;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\Deps\Tamara\Model\Order\RiskAssessment;
use WC_Order;

class Build_Tamara_Order_Risk_Assessment_Query extends Base_Query {
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

	protected function get_current_user_has_delivered_order(): bool {
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

	protected function get_current_user_total_order_count() {
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

	protected function get_current_user_date_of_first_transaction() {
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

	protected function get_current_user_order_amount_last_3_months(): float {
		$total_amount = 0;
		$currency = $this->wc_order->get_currency();
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

		return Tamara_Checkout_Helper::format_price_number( $total_amount, $currency );
	}

	protected function get_current_user_order_count_last_3_months() {
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
