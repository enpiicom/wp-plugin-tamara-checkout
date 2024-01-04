<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Helpers;

use Exception;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use WC_Order;

class WC_Order_Helper {

	/**
	 * Prevent an order is cancelled from FE if its payment has been authorised from Tamara
	 *
	 * @param  WC_Order  $wc_order
	 * @param $wc_order_id
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function prevent_order_cancel_action( WC_Order $wc_order, $wc_order_id ): void {
		$order_note = sprintf(
			General_Helper::convert_message(
				'This order can not be cancelled because the payment was authorised from Tamara.
			Order ID: %s'
			),
			$wc_order_id
		);
		$wc_order->add_order_note( $order_note );
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}


	/**
	 * Update order status and add order note wrapper
	 *
	 * @param  WC_Order  $wc_order
	 * @param  string  $order_note
	 * @param  string  $new_order_status
	 * @param  string  $update_order_status_note
	 *
	 */
	public static function update_order_status_and_add_order_note( WC_Order $wc_order, string $order_note, string $new_order_status, string $update_order_status_note ): void {
		if ( $wc_order ) {
			General_Helper::log_message(
				sprintf(
					'Tamara - Prepare to Update Order Status - Order ID: %s, Order Note: %s, new order status: %s, order status note: %s',
					$wc_order->get_id(),
					$order_note,
					$new_order_status,
					$update_order_status_note
				)
			);
			try {
				$wc_order->add_order_note( $order_note );
				$wc_order->update_status( $new_order_status, $update_order_status_note, true );
			} catch ( Exception $exception ) {
				General_Helper::log_message(
					sprintf(
						'Tamara - Failed to Update Order Status - Order ID: %s, Order Note: %s, new order status: %s, order status note: %s. Error Message: %s',
						$wc_order->get_id(),
						$order_note,
						$new_order_status,
						$update_order_status_note,
						$exception->getMessage()
					)
				);
			}
		}
	}

	/**
	 * Define which total amount should be used for Tamara order on checkout page
	 *
	 * @param $amount
	 *
	 * @return mixed
	 */
	public static function define_total_amount_to_calculate( $amount ) {
		if ( is_checkout_pay_page() ) {
			global $wp;
			if ( isset( $wp->query_vars['order-pay'] ) && absint( $wp->query_vars['order-pay'] ) > 0 ) {
				$wc_order_id = absint( $wp->query_vars['order-pay'] );
				$wc_order = wc_get_order( $wc_order_id );
				if ( $wc_order ) {
					return $wc_order->get_total();
				}
			}
		}

		return $amount;
	}

	/**
	 * @return array
	 */
	public static function get_current_cart_info(): array {
		$current_cart_total = ! empty( WC()->cart->total ) ? WC()->cart->total : 0;
		$billing_customer_phone = ! empty( WC()->customer->get_billing_phone() ) ? WC()->customer->get_billing_phone() : '';
		$cart_total = static::define_total_amount_to_calculate( $current_cart_total );
		$country_mapping = General_Helper::get_currency_country_mappings()[ get_woocommerce_currency() ];
		$country_code = WC()->customer->get_shipping_country() ? WC()->customer->get_shipping_country() : $country_mapping;
		$customer_phone = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_customer_phone_number() ??
							$billing_customer_phone;

		return [
			'cart_total' => $cart_total,
			'customer_phone' => $customer_phone,
			'country_code' => $country_code,
		];
	}

	/**
	 * Get all product ids of items in cart, including parent and child ids.
	 *
	 * @return array
	 */
	public static function get_all_product_ids_in_cart(): array {
		$all_cart_items = WC()->cart->get_cart();
		$product_ids = [];

		foreach ( $all_cart_items as $item => $values ) {
			$item_id = $values['data']->get_id() ?? null;
			$product_ids[] = $item_id;
			$product = wc_get_product( $item_id );
			// Check if a product is a variation add add its parent id to the list.
			if ( $product instanceof \WC_Product_Variation ) {
				$product_parent_id = $product->get_parent_id() ?? null;
				if ( ! in_array( $product_parent_id, $product_ids ) ) {
					$productIds[] = $product_parent_id;
				}
			}
		}

		return $product_ids;
	}

	/**
	 * Get all category ids of items in cart, including ancestors and subcategories.
	 *
	 * @return array
	 */
	public static function get_all_product_category_ids_in_cart(): array {
		$all_cart_items = WC()->cart->get_cart();
		$all_product_category_ids = [];

		foreach ( $all_cart_items as $item => $values ) {
			$product_id = $values['data']->get_id() ?? null;
			$all_product_category_ids = array_merge( $all_product_category_ids, wc_get_product_cat_ids( $product_id ) );
		}

		return $all_product_category_ids;
	}

	/**
	 * @return bool
	 */
	public static function is_cart_valid(): bool {
		if ( ! is_checkout() || is_checkout_pay_page() ) {
			return true;
		}
		$gateway_service = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_gateway_service();
		$gateway_settings = $gateway_service->get_settings();
		$excluded_product_ids = $gateway_settings->get_excluded_products_ids() ?? [];
		$excluded_product_categories = $gateway_settings->get_excluded_product_category_ids() ?? [];
		$cart_item_ids = static::get_all_product_ids_in_cart();
		$cart_item_category_ids = static::get_all_product_category_ids_in_cart();
		$has_excluded_items = ! empty( array_intersect( $cart_item_ids, $excluded_product_ids ) );
		$has_excluded_categories = ! empty( array_intersect( $cart_item_category_ids, $excluded_product_categories ) );

		return ! ( $has_excluded_items || $has_excluded_categories );
	}
}
