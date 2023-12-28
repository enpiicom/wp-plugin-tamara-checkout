<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP\Data;

use DateTimeImmutable;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\Support\Traits\Trans_Trait;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Model\Order\OrderItem;
use Tamara_Checkout\Deps\Tamara\Model\Order\OrderItemCollection;
use Tamara_Checkout\Deps\Tamara\Model\Payment\Capture;
use Tamara_Checkout\Deps\Tamara\Model\Payment\Refund;
use Tamara_Checkout\Deps\Tamara\Model\ShippingInfo;
use Tamara_Checkout\Deps\Tamara\Request\Order\CancelOrderRequest;
use Tamara_Checkout\Deps\Tamara\Request\Payment\CaptureRequest;
use Tamara_Checkout\Deps\Tamara\Request\Payment\RefundRequest;
use WC_Order;

/**
 * A service for Tamara related WC Orders
 * @package Tamara_Checkout\App\Services
 */
class Tamara_WC_Order {
	use Trans_Trait;

	protected $wc_order;
	protected $wc_refund;
	protected $tamara_order_id;
	protected $payment_method;

	public function __construct( WC_Order $wc_order, $wc_refund = null ) {
		if ( empty( $wc_order->get_id() ) ) {
			throw new Tamara_Exception( wp_kses_post( $this->_t( 'Invalid WC_Order' ) ) );
		}

		$this->wc_order = $wc_order;
		$this->wc_refund = $wc_refund;
	}

	/**
	 *
	 * @return WC_Order
	 */
	public function get_wc_order(): WC_Order {
		return $this->wc_order;
	}

	public function get_id() {
		return $this->wc_order->get_id();
	}

	public function is_paid_with_tamara(): bool {
		$payment_method = $this->get_payment_method();

		return strpos(
			$payment_method,
			Tamara_Checkout_WP_Plugin::DEFAULT_TAMARA_GATEWAY_ID
		) === 0;
	}

	public function get_payment_method(): string {
		if ( ! empty( $this->payment_method ) ) {
			return $this->payment_method;
		}

		$this->payment_method = $this->wc_order->get_payment_method();
		return $this->payment_method;
	}

	public function get_tamara_order_id(): string {
		if ( ! empty( $this->tamara_order_id ) ) {
			return $this->tamara_order_id;
		}

		$this->tamara_order_id = get_post_meta( $this->wc_order->get_id(), '_tamara_order_id', true );
		if ( empty( $this->tamara_order_id ) ) {
			$this->tamara_order_id = get_post_meta( $this->wc_order->get_id(), 'tamara_order_id', true );
		}
		$this->tamara_order_id = (string) $this->tamara_order_id;

		return $this->tamara_order_id;
	}

	public function get_tamara_capture_id(): string {
		return get_post_meta( $this->wc_order->get_id(), '_tamara_capture_id', true );
	}

	public function get_tamara_cancel_id(): string {
		return get_post_meta( $this->wc_order->get_id(), '_tamara_cancel_id', true );
	}

	public function build_tamara_order_items( $wc_order ): OrderItemCollection {
		$wc_order_items = $wc_order->get_items();
		$order_item_collection = new OrderItemCollection();

		foreach ( $wc_order_items as $item_id => $wc_order_item ) {
			$order_item = new OrderItem();
			/** @var \WC_Order_Item_Product $wc_order_item */
			/** @var \WC_Product_Simple $wc_order_item_product */
			$wc_order_item_product = $wc_order_item->get_product();

			if ( $wc_order_item_product ) {
				$wc_order_item_name = wp_strip_all_tags( $wc_order_item->get_name() );
				$wc_order_item_quantity = abs( $wc_order_item->get_quantity() );
				$wc_order_item_sku = empty( $wc_order_item_product->get_sku() ) ? (string) $item_id : $wc_order_item_product->get_sku();
				$wc_order_item_total_tax = $wc_order_item->get_total_tax();
				$wc_order_item_total = abs( (int) $wc_order_item->get_total() + (int) $wc_order_item_total_tax );

				$wc_order_item_categories = wp_strip_all_tags(
					wc_get_product_category_list( $wc_order_item_product->get_id() )
				);
				$wc_order_item_categories = empty( $wc_order_item_categories ) ? 'N/A' : $wc_order_item_categories;

				$wc_order_item_regular_price = $wc_order_item_product->get_regular_price();
				$wc_order_item_sale_price = $wc_order_item_product->get_sale_price();
				$item_price = $wc_order_item_sale_price ?? $wc_order_item_regular_price;
				$wc_order_item_discount_amount = (int) $item_price * $wc_order_item_quantity
												- ( (int) $wc_order_item_total - (int) $wc_order_item_total_tax );
				$order_item->setName( $wc_order_item_name );
				$order_item->setQuantity( $wc_order_item_quantity );
				$order_item->setUnitPrice(
					General_Helper::build_tamara_money(
						$item_price,
						$wc_order->get_currency()
					)
				);
				$order_item->setType( $wc_order_item_categories );
				$order_item->setSku( $wc_order_item_sku );
				$order_item->setTotalAmount(
					General_Helper::build_tamara_money(
						$wc_order_item_total,
						$wc_order->get_currency()
					)
				);
				$order_item->setTaxAmount(
					General_Helper::build_tamara_money(
						$wc_order_item_total_tax,
						$wc_order->get_currency()
					)
				);
				$order_item->setDiscountAmount(
					General_Helper::build_tamara_money(
						$wc_order_item_discount_amount,
						$wc_order->get_currency()
					)
				);
				$order_item->setReferenceId( (string) $item_id );
				$order_item->setImageUrl( wp_get_attachment_url( $wc_order_item_product->get_image_id() ) );
			} else {
				$wc_order_item_product = $wc_order_item->get_data();
				$wc_order_item_name = ! empty( $wc_order_item_product['name'] ) ? wp_strip_all_tags( $wc_order_item_product['name'] ) : 'N/A';
				$wc_order_item_quantity = $wc_order_item_product['quantity'] ?? 1;
				$wc_order_item_sku = ! empty( $wc_order_item_product['sku'] ) ? $wc_order_item_product['sku'] : (string) $item_id;
				$wc_order_item_total_tax = $wc_order_item_product['total_tax'] ?? 0;
				$wc_order_item_total = $wc_order_item_product['total'] ?? 0;
				$wc_order_item_categories = $wc_order_item_product['category'] ?? 'N/A';
				$item_price = $wc_order_item_product['subtotal'] ?? 0;
				$wc_order_item_discount_amount = (int) $item_price * $wc_order_item_quantity
												- ( (int) $wc_order_item_total - (int) $wc_order_item_total_tax );
				$order_item->setName( $wc_order_item_name );
				$order_item->setQuantity( $wc_order_item_quantity );
				$order_item->setUnitPrice(
					General_Helper::build_tamara_money(
						$item_price,
						$wc_order->get_currency()
					)
				);
				$order_item->setType( $wc_order_item_categories );
				$order_item->setSku( $wc_order_item_sku );
				$order_item->setTotalAmount(
					General_Helper::build_tamara_money(
						$wc_order_item_total,
						$wc_order->get_currency()
					)
				);
				$order_item->setTaxAmount(
					General_Helper::build_tamara_money(
						$wc_order_item_total_tax,
						$wc_order->get_currency()
					)
				);
				$order_item->setDiscountAmount(
					General_Helper::build_tamara_money(
						$wc_order_item_discount_amount,
						$wc_order->get_currency()
					)
				);
				$order_item->setReferenceId( $item_id );
				$order_item->setImageUrl( 'N/A' );
			}

			$order_item_collection->append( $order_item );
		}

		return $order_item_collection;
	}

	/**
	 * Get Shipping Information
	 */
	public function build_shipping_info(): ShippingInfo {
		$shipped_at = new DateTimeImmutable();
		$shipping_company = 'N/A';
		$tracking_number = 'N/A';
		$tracking_url = 'N/A';

		return new ShippingInfo( $shipped_at, $shipping_company, $tracking_number, $tracking_url );
	}

	public function build_capture_request(): CaptureRequest {
		$wc_order_total_amount = General_Helper::build_tamara_money(
			$this->wc_order->get_total(),
			$this->wc_order->get_currency()
		);
		$wc_order_shipping_amount = General_Helper::build_tamara_money(
			$this->wc_order->get_shipping_total(),
			$this->wc_order->get_currency()
		);
		$wc_order_tax_amount = General_Helper::build_tamara_money(
			$this->wc_order->get_total_tax(),
			$this->wc_order->get_currency()
		);
		$wc_order_discount_amount = General_Helper::build_tamara_money(
			$this->wc_order->get_discount_total(),
			$this->wc_order->get_currency()
		);

		return new CaptureRequest(
			new Capture(
				$this->get_tamara_order_id(),
				$wc_order_total_amount,
				$wc_order_shipping_amount,
				$wc_order_tax_amount,
				$wc_order_discount_amount,
				$this->build_tamara_order_items( $this->wc_order ),
				$this->build_shipping_info()
			)
		);
	}

	public function build_cancel_request(): CancelOrderRequest {
		$wc_order_total_amount = General_Helper::build_tamara_money(
			$this->wc_order->get_total(),
			$this->wc_order->get_currency()
		);
		$wc_order_items = $this->build_tamara_order_items( $this->wc_order );
		$wc_order_shipping_amount = General_Helper::build_tamara_money(
			$this->wc_order->get_shipping_total(),
			$this->wc_order->get_currency()
		);
		$wc_order_tax_amount = General_Helper::build_tamara_money(
			$this->wc_order->get_total_tax(),
			$this->wc_order->get_currency()
		);
		$wc_order_discount_amount = General_Helper::build_tamara_money(
			$this->wc_order->get_discount_total(),
			$this->wc_order->get_currency()
		);

		return new CancelOrderRequest(
			$this->get_tamara_order_id(),
			$wc_order_total_amount,
			$wc_order_items,
			$wc_order_shipping_amount,
			$wc_order_tax_amount,
			$wc_order_discount_amount
		);
	}

	public function build_refund_request(): RefundRequest {
		$wc_refund_total_amount = General_Helper::build_tamara_money(
			abs( (int) $this->wc_refund->get_total() ),
			$this->wc_refund->get_currency()
		);
		$wc_refund_shipping_amount = General_Helper::build_tamara_money(
			abs( (int) $this->wc_refund->get_shipping_total() ),
			$this->wc_refund->get_currency()
		);
		$wc_refund_tax_amount = General_Helper::build_tamara_money(
			abs( (int) $this->wc_refund->get_total_tax() ),
			$this->wc_refund->get_currency()
		);
		$wc_refund_discount_amount = General_Helper::build_tamara_money(
			$this->wc_refund->get_discount_total(),
			$this->wc_refund->get_currency()
		);

		$capture_id = $this->get_tamara_capture_id();
		$refund_collection = [];

		$wc_refund_items = $this->build_tamara_order_items( $this->wc_refund );

		$refund_item = new Refund(
			$capture_id,
			$wc_refund_total_amount,
			$wc_refund_shipping_amount,
			$wc_refund_tax_amount,
			$wc_refund_discount_amount,
			$wc_refund_items
		);

		array_push( $refund_collection, $refund_item );

		return new RefundRequest(
			$this->get_tamara_order_id(),
			$refund_collection
		);
	}
}
