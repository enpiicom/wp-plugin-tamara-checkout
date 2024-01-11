<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP\Data;

use DateTimeImmutable;
use Tamara_Checkout\App\DTOs\WC_Order_Tamara_Meta_DTO;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Queries\Build_Tamara_Order_Risk_Assessment_Query;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Model\Order\Address;
use Tamara_Checkout\Deps\Tamara\Model\Order\Consumer;
use Tamara_Checkout\Deps\Tamara\Model\Order\Discount;
use Tamara_Checkout\Deps\Tamara\Model\Order\MerchantUrl;
use Tamara_Checkout\Deps\Tamara\Model\Order\Order;
use Tamara_Checkout\Deps\Tamara\Model\Order\OrderItem;
use Tamara_Checkout\Deps\Tamara\Model\Order\OrderItemCollection;
use Tamara_Checkout\Deps\Tamara\Model\Payment\Capture;
use Tamara_Checkout\Deps\Tamara\Model\Payment\Refund;
use Tamara_Checkout\Deps\Tamara\Model\ShippingInfo;
use Tamara_Checkout\Deps\Tamara\Request\Order\CancelOrderRequest;
use Tamara_Checkout\Deps\Tamara\Request\Order\GetOrderByReferenceIdRequest;
use Tamara_Checkout\Deps\Tamara\Request\Payment\CaptureRequest;
use Tamara_Checkout\Deps\Tamara\Request\Payment\RefundRequest;
use Tamara_Checkout\Deps\Tamara\Response\Order\GetOrderByReferenceIdResponse;
use WC_Order;
use WC_Order_Item_Product;

/**
 * A service for Tamara related WC Orders
 * @package Tamara_Checkout\App\Services
 */
class Tamara_WC_Order {
	use Tamara_Trans_Trait;

	/**
	 * @var WC_Order
	 */
	protected $wc_order;
	protected $wc_refund;

	protected $wc_order_id;
	protected $payment_method;

	/**
	 * @var WC_Order_Tamara_Meta_DTO
	 */
	protected $tamara_meta_dto;

	public function __construct( WC_Order $wc_order, $wc_refund = null ) {
		if ( empty( $wc_order->get_id() ) ) {
			throw new Tamara_Exception( wp_kses_post( $this->_t( 'Invalid WC_Order' ) ) );
		}

		$this->wc_order = $wc_order;
		$this->wc_refund = $wc_refund;
		$this->wc_order_id = $wc_order->get_id();
		$this->tamara_meta_dto = new WC_Order_Tamara_Meta_DTO();
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
		$tamara_order_id = (string) $this->get_tamara_meta( 'tamara_order_id' );
		if ( empty( $tamara_order_id ) ) {
			$tamara_order = $this->get_tamara_order_by_reference_id();
			$tamara_order_id = $tamara_order->getOrderId();
			$this->update_tamara_meta( 'tamara_order_id', $tamara_order_id );
		}

		return $tamara_order_id;
	}

	public function get_tamara_order_number(): string {
		return (string) $this->get_tamara_meta( 'tamara_order_number' );
	}

	public function get_tamara_payment_type(): string {
		return (string) $this->get_tamara_meta( 'tamara_payment_type' );
	}

	public function get_tamara_instalments(): string {
		return (string) $this->get_tamara_meta( 'tamara_instalments' );
	}

	public function get_tamara_payment_status(): string {
		return (string) $this->get_tamara_meta( 'tamara_payment_status' );
	}

	public function get_tamara_cancel_id(): string {
		return (string) $this->get_tamara_meta( 'tamara_cancel_id' );
	}

	public function get_tamara_meta( $meta_key ) {
		if ( ! empty( $this->tamara_meta_dto->$meta_key ) ) {
			return $this->tamara_meta_dto->$meta_key;
		}

		$this->tamara_meta_dto->$meta_key = get_post_meta( $this->wc_order->get_id(), '_' . $meta_key, true );
		if ( empty( $this->tamara_meta_dto->$meta_key ) ) {
			$this->tamara_meta_dto->$meta_key = get_post_meta( $this->wc_order->get_id(), $meta_key, true );
		}

		return $this->tamara_meta_dto->$meta_key;
	}

	/**
	 * Update Tamara meta data for orders
	 * @param mixed $meta_key
	 * @param mixed $meta_value
	 * @return bool false if the update action to db fails
	 */
	public function update_tamara_meta( $meta_key, $meta_value ) {
		update_post_meta( $this->wc_order_id, $meta_key, $meta_value );
		if ( update_post_meta( $this->wc_order_id, '_' . $meta_key, $meta_value ) ) {
			$this->tamara_meta_dto->$meta_key = $meta_value;

			return true;
		}

		return false;
	}

	public function add_tamara_order_note( $order_note ) {
		$order_note = 'Tamara - ' . $order_note;
		$this->get_wc_order()->add_order_note( $order_note );
	}

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	public function get_tamara_order_id_by_wc_order_id(): ?string {
		$tamara_client_response = $this->get_tamara_order_by_reference_id();
		return ! empty( $tamara_client_response->getOrderId() ) ? $tamara_client_response->getOrderId() : null;
	}

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	public function get_tamara_capture_id(): ?string {
		$tamara_client_response = $this->get_tamara_order_by_reference_id();
		/** @var \Tamara_Checkout\Deps\Tamara\Model\Order\CaptureItem $capture_item */
		$capture_item = $tamara_client_response->getTransactions()->getCaptures()->getIterator()[0] ?? [];
		if ( ! empty( $capture_item ) ) {
			return $capture_item->getCaptureId();
		}

		return null;
	}

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	public function reupdate_meta_for_tamara_order_id(): void {
		$wc_order_id = $this->wc_order_id;
		$tamara_client_response = $this->get_tamara_order_by_reference_id();
		if ( ! empty( $tamara_client_response ) ) {
			update_post_meta( $wc_order_id, 'tamara_order_id', $tamara_client_response->getOrderId() );
			update_post_meta( $wc_order_id, '_tamara_order_id', $tamara_client_response->getOrderId() );
		}
	}

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 * @throws \Exception
	 */
	public function get_tamara_order_by_reference_id(): GetOrderByReferenceIdResponse {
		$wc_order_id = $this->wc_order_id;
		$get_order_by_reference_id_request = new GetOrderByReferenceIdRequest( (string) $wc_order_id );
		$tamara_client_response = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->get_order_by_reference_id( $get_order_by_reference_id_request );

		if ( ! is_object( $tamara_client_response ) ) {
			throw new Tamara_Exception( wp_kses_post( $tamara_client_response ) );
		}

		return $tamara_client_response;
	}

	public function build_tamara_order_items( $wc_order = null ): OrderItemCollection {
		$wc_order = empty( $wc_order ) ? $this->wc_order : $wc_order;
		$wc_order_items = $wc_order->get_items();
		$order_item_collection = new OrderItemCollection();

		foreach ( $wc_order_items as $item_id => $wc_order_item ) {
			/** @var \WC_Order_Item_Product $wc_order_item */
			/** @var \WC_Product_Simple $wc_order_item_product_simple */
			$wc_order_item_product_simple = $wc_order_item->get_product();

			if ( $wc_order_item_product_simple ) {
				$order_item = $this->build_tamara_order_item_from_object( $wc_order_item, $wc_order, $item_id );
			} else {
				$order_item = $this->build_tamara_order_item_from_data( $wc_order_item->get_data(), $wc_order, $item_id );
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
		$wc_order_total_amount = Tamara_Checkout_Helper::build_money_object(
			$this->wc_order->get_total(),
			$this->wc_order->get_currency()
		);
		$wc_order_shipping_amount = Tamara_Checkout_Helper::build_money_object(
			$this->wc_order->get_shipping_total(),
			$this->wc_order->get_currency()
		);
		$wc_order_tax_amount = Tamara_Checkout_Helper::build_money_object(
			$this->wc_order->get_total_tax(),
			$this->wc_order->get_currency()
		);
		$wc_order_discount_amount = Tamara_Checkout_Helper::build_money_object(
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
				$this->build_tamara_order_items(),
				$this->build_shipping_info()
			)
		);
	}

	public function build_cancel_request(): CancelOrderRequest {
		$wc_order_total_amount = Tamara_Checkout_Helper::build_money_object(
			$this->wc_order->get_total(),
			$this->wc_order->get_currency()
		);
		$wc_order_items = $this->build_tamara_order_items();
		$wc_order_shipping_amount = Tamara_Checkout_Helper::build_money_object(
			$this->wc_order->get_shipping_total(),
			$this->wc_order->get_currency()
		);
		$wc_order_tax_amount = Tamara_Checkout_Helper::build_money_object(
			$this->wc_order->get_total_tax(),
			$this->wc_order->get_currency()
		);
		$wc_order_discount_amount = Tamara_Checkout_Helper::build_money_object(
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

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	public function build_refund_request(): RefundRequest {
		$wc_refund_total_amount = Tamara_Checkout_Helper::build_money_object(
			abs( (int) $this->wc_refund->get_total() ),
			$this->wc_refund->get_currency()
		);
		$wc_refund_shipping_amount = Tamara_Checkout_Helper::build_money_object(
			abs( (int) $this->wc_refund->get_shipping_total() ),
			$this->wc_refund->get_currency()
		);
		$wc_refund_tax_amount = Tamara_Checkout_Helper::build_money_object(
			abs( (int) $this->wc_refund->get_total_tax() ),
			$this->wc_refund->get_currency()
		);
		$wc_refund_discount_amount = Tamara_Checkout_Helper::build_money_object(
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

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 * @throws \Exception
	 */
	public function build_tamara_order( $payment_type, $instalments ): Order {
		$wc_order = $this->wc_order;
		$order = new Order();
		$order->setOrderReferenceId( (string) $wc_order->get_id() );
		$order->setLocale( get_locale() );
		$order->setCurrency( $wc_order->get_currency() );
		$order->setTotalAmount(
			Tamara_Checkout_Helper::build_money_object(
				$wc_order->get_total(),
				$wc_order->get_currency()
			)
		);
		$order->setCountryCode(
			! empty( $wc_order->get_billing_country() ) ? $wc_order->get_billing_country()
			: Tamara_Checkout_Helper::get_current_country_code()
		);
		$order->setPaymentType( $payment_type );
		$order->setInstalments( $instalments );
		$order->setPlatform(
			sprintf(
				'WordPress %s, WooCommerce %s, Tamara Checkout %s',
				$GLOBALS['wp_version'],
				$GLOBALS['woocommerce']->version,
				Tamara_Checkout_WP_Plugin::wp_app_instance()->get_version()
			)
		);
		$order->setDescription( 'Use Tamara Gateway with WooCommerce' );
		$order->setTaxAmount(
			Tamara_Checkout_Helper::build_money_object(
				$wc_order->get_total_tax(),
				$wc_order->get_currency()
			)
		);
		$order->setShippingAmount(
			Tamara_Checkout_Helper::build_money_object(
				$wc_order->get_shipping_total(),
				$wc_order->get_currency()
			)
		);

		$used_coupons = ! empty( $wc_order->get_coupon_codes() ) ? implode( ',', $wc_order->get_coupon_codes() ) : '';
		$order->setDiscount(
			new Discount(
				$used_coupons,
				Tamara_Checkout_Helper::build_money_object(
					$wc_order->get_discount_total(),
					$wc_order->get_currency()
				)
			)
		);
		$order->setMerchantUrl( $this->build_tamara_merchant_url( $payment_type ) );
		$order->setBillingAddress( $this->build_tamara_billing_address() );
		$order->setShippingAddress( $this->build_tamara_shipping_address() );
		$order->setConsumer( $this->build_tamara_consumer() );
		$order->setRiskAssessment(
			Build_Tamara_Order_Risk_Assessment_Query::execute_now(
				$wc_order
			)
		);

		$order->setItems( $this->build_tamara_order_items() );

		return $order;
	}

	/**
	 * @throws \Exception
	 */
	public function build_tamara_merchant_url( string $payment_type ): MerchantUrl {
		$wc_order = $this->wc_order;
		$merchant_url = new MerchantUrl();

		$params = [
			'wc_order_id' => $wc_order->get_id(),
			'payment_type' => $payment_type,
		];

		$merchant_url->setSuccessUrl( wp_app_route_wp_url( 'wp-api::tamara-success', $params ) );
		$merchant_url->setCancelUrl( wp_app_route_wp_url( 'wp-api::tamara-cancel', $params ) );
		$merchant_url->setFailureUrl( wp_app_route_wp_url( 'wp-api::tamara-failure', $params ) );
		$merchant_url->setNotificationUrl( wp_app_route_wp_url( 'wp-api::tamara-ipn', $params ) );

		return $merchant_url;
	}

	/**
	 * @param $wc_order
	 *
	 * @return Consumer
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 */
	public function build_tamara_consumer(): Consumer {
		$wc_order = $this->wc_order;
		$wc_billing_address = $wc_order->get_address( 'billing' );

		if ( empty( $wc_billing_address['email'] ) || empty( $wc_billing_address['phone'] ) ) {
			throw new Tamara_Exception( wp_kses_post( $this->_t( 'Phone and Email are mandatory when checking out with Tamara' ) ) );
		}

		$first_name = ! empty( $wc_billing_address['first_name'] ) ? $wc_billing_address['first_name'] : 'N/A';
		$last_name = ! empty( $wc_billing_address['last_name'] ) ? $wc_billing_address['last_name'] : 'N/A';
		$email = $wc_billing_address['email'];
		$phone = $wc_billing_address['phone'];

		$consumer = new Consumer();
		$consumer->setFirstName( $first_name );
		$consumer->setLastName( $last_name );
		$consumer->setEmail( $email );
		$consumer->setPhoneNumber( $phone );

		return $consumer;
	}

	/**
	 * Set Tamara Order Billing Addresses
	 *
	 * @param  WC_Order  $wc_order
	 *
	 * @return Address
	 */
	public function build_tamara_billing_address(): Address {
		$wc_billing_address = $this->wc_order->get_address( 'billing' );

		return $this->build_tamara_address( $wc_billing_address );
	}

	/**
	 * Set Tamara Order Shipping Addresses
	 *
	 * @param  WC_Order  $wc_order
	 *
	 * @return Address
	 */
	public function build_tamara_shipping_address(): Address {
		$wc_order = $this->wc_order;
		$wc_shipping_address = $wc_order->get_address( 'shipping' );
		$wc_billing_address = $wc_order->get_address( 'billing' );

		$wc_shipping_address['first_name'] = ! empty( $wc_shipping_address['first_name'] )
			? $wc_shipping_address['first_name']
			: ( ! empty( $wc_billing_address['first_name'] ) ? $wc_billing_address['first_name'] : null );

		$wc_shipping_address['last_name'] = ! empty( $wc_shipping_address['last_name'] )
			? $wc_shipping_address['last_name']
			: ( ! empty( $wc_billing_address['last_name'] ) ? $wc_billing_address['last_name'] : null );

		$wc_shipping_address['address_1'] = ! empty( $wc_shipping_address['address_1'] )
			? $wc_shipping_address['address_1']
			: ( ! empty( $wc_billing_address['address_1'] ) ? $wc_billing_address['address_1'] : null );

		$wc_shipping_address['address_2'] = ! empty( $wc_shipping_address['address_2'] )
			? $wc_shipping_address['address_2']
			: ( ! empty( $wc_billing_address['address_2'] ) ? $wc_billing_address['address_2'] : null );

		$wc_shipping_address['city'] = ! empty( $wc_shipping_address['city'] )
			? $wc_shipping_address['city']
			: ( ! empty( $wc_billing_address['city'] ) ? $wc_billing_address['city'] : null );

		$wc_shipping_address['state'] = ! empty( $wc_shipping_address['state'] )
			? $wc_shipping_address['state']
			: ( ! empty( $wc_billing_address['state'] ) ? $wc_billing_address['state'] : null );

		$wc_shipping_address['country'] = ! empty( $wc_shipping_address['country'] )
			? $wc_shipping_address['country']
			: ( ! empty( $wc_billing_address['country'] ) ? $wc_billing_address['country']
				: Tamara_Checkout_Helper::get_current_country_code() );

		$wc_shipping_address['phone'] = ! empty( $wc_shipping_address['phone'] )
			? $wc_shipping_address['phone']
			: ( ! empty( $wc_billing_address['phone'] ) ? $wc_billing_address['phone'] : null );

		return $this->build_tamara_address( $wc_shipping_address );
	}

	/**
	 * @param $wc_address
	 *
	 * @return \Tamara_Checkout\Deps\Tamara\Model\Order\Address
	 */
	protected function build_tamara_address( array $wc_address ): Address {
		$first_name = ! empty( $wc_address['first_name'] ) ? $wc_address['first_name'] : 'N/A';
		$last_name = ! empty( $wc_address['last_name'] ) ? $wc_address['first_name'] : 'N/A';
		$address1 = ! empty( $wc_address['address_1'] ) ? $wc_address['first_name'] : 'N/A';
		$address2 = ! empty( $wc_address['address_2'] ) ? $wc_address['first_name'] : 'N/A';
		$city = ! empty( $wc_address['city'] ) ? $wc_address['first_name'] : 'N/A';
		$state = ! empty( $wc_address['state'] ) ? $wc_address['first_name'] : 'N/A';
		$phone = ! empty( $wc_address['phone'] ) ? $wc_address['phone'] : null;
		$country = ! empty( $wc_address['country'] ) ? $wc_address['country'] : General_Helper::get_store_base_country_code();

		$tamara_address = new Address();
		$tamara_address->setFirstName( (string) $first_name );
		$tamara_address->setLastName( (string) $last_name );
		$tamara_address->setLine1( (string) $address1 );
		$tamara_address->setLine2( (string) $address2 );
		$tamara_address->setCity( (string) $city );
		$tamara_address->setRegion( (string) $state );
		$tamara_address->setPhoneNumber( (string) $phone );
		$tamara_address->setCountryCode( (string) $country );

		return $tamara_address;
	}

	protected function build_tamara_order_item_from_object( WC_Order_Item_Product $wc_order_item, WC_Order $wc_order, $item_id ): OrderItem {
		$order_item = new OrderItem();
		$wc_order_item_product = $wc_order_item->get_product();

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
			Tamara_Checkout_Helper::build_money_object(
				$item_price,
				$wc_order->get_currency()
			)
		);
		$order_item->setType( $wc_order_item_categories );
		$order_item->setSku( $wc_order_item_sku );
		$order_item->setTotalAmount(
			Tamara_Checkout_Helper::build_money_object(
				$wc_order_item_total,
				$wc_order->get_currency()
			)
		);
		$order_item->setTaxAmount(
			Tamara_Checkout_Helper::build_money_object(
				$wc_order_item_total_tax,
				$wc_order->get_currency()
			)
		);
		$order_item->setDiscountAmount(
			Tamara_Checkout_Helper::build_money_object(
				$wc_order_item_discount_amount,
				$wc_order->get_currency()
			)
		);
		$order_item->setReferenceId( (string) $item_id );
		$order_item->setImageUrl( ! empty( wp_get_attachment_url( $wc_order_item_product->get_image_id() ) ) ? wp_get_attachment_url( $wc_order_item_product->get_image_id() ) : 'N/A' );

		return $order_item;
	}

	protected function build_tamara_order_item_from_data( array $wc_order_item_product, WC_Order $wc_order, $item_id ): OrderItem {
		$order_item = new OrderItem();

		$wc_order_item_name = ! empty( $wc_order_item_product['name'] ) ? wp_strip_all_tags( $wc_order_item_product['name'] ) : 'N/A';
		$wc_order_item_quantity = ! empty( $wc_order_item_product['quantity'] ) ? $wc_order_item_product['quantity'] : 1;
		$wc_order_item_sku = empty( $wc_order_item_product['sku'] ) ? (string) $item_id : $wc_order_item_product['sku'];
		$wc_order_item_total_tax = $wc_order_item_product['total_tax'] ?? 0;
		$wc_order_item_total = $wc_order_item_product['total'] ?? 0;
		$wc_order_item_categories = $wc_order_item_product['category'] ?? 'N/A';
		$item_price = $wc_order_item_product['subtotal'] ?? 0;
		$wc_order_item_discount_amount = (int) $item_price * $wc_order_item_quantity
										- ( (int) $wc_order_item_total - (int) $wc_order_item_total_tax );
		$order_item->setName( $wc_order_item_name );
		$order_item->setQuantity( $wc_order_item_quantity );
		$order_item->setUnitPrice(
			Tamara_Checkout_Helper::build_money_object(
				$item_price,
				$wc_order->get_currency()
			)
		);
		$order_item->setType( $wc_order_item_categories );
		$order_item->setSku( $wc_order_item_sku );
		$order_item->setTotalAmount(
			Tamara_Checkout_Helper::build_money_object(
				$wc_order_item_total,
				$wc_order->get_currency()
			)
		);
		$order_item->setTaxAmount(
			Tamara_Checkout_Helper::build_money_object(
				$wc_order_item_total_tax,
				$wc_order->get_currency()
			)
		);
		$order_item->setDiscountAmount(
			Tamara_Checkout_Helper::build_money_object(
				$wc_order_item_discount_amount,
				$wc_order->get_currency()
			)
		);
		$order_item->setReferenceId( $item_id );
		$order_item->setImageUrl( 'N/A' );

		return $order_item;
	}
}
