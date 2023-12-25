<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Enpii_Base\Foundation\Shared\Base_Query;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Model\Money;
use Tamara_Checkout\Deps\Tamara\Model\Order\Address;
use Tamara_Checkout\Deps\Tamara\Model\Order\Consumer;
use Tamara_Checkout\Deps\Tamara\Model\Order\Discount;
use Tamara_Checkout\Deps\Tamara\Model\Order\MerchantUrl;
use Tamara_Checkout\Deps\Tamara\Model\Order\Order;
use Tamara_Checkout\Deps\Tamara\Model\Order\OrderItem;
use Tamara_Checkout\Deps\Tamara\Model\Order\OrderItemCollection;
use Tamara_Checkout\Deps\Tamara\Request\Checkout\CreateCheckoutRequest;
use WC_Order;

class Build_Tamara_Create_Checkout_Request_Query extends Base_Query {
	use Executable_Trait;

	/**
	 * @var WC_Order
	 */
	protected $wc_order;

	/**
	 *
	 * @var string
	 */
	protected $payment_type;

	/**
	 *
	 * @var int
	 */
	protected $instalment_period = 0;

	public function __construct( WC_Order $wc_order, string $payment_type, int $instalment_period = 0 ) {
		$this->wc_order = $wc_order;
		$this->payment_type = $payment_type;
		$this->instalment_period = $instalment_period;

		if ( empty( $payment_type ) ) {
			throw new Tamara_Exception( 'Error! No Payment Type specified' );
		}
	}

	public function handle() {
		$tamara_order = $this->build_tamara_order();
		return new CreateCheckoutRequest( $tamara_order );
	}

	public function build_tamara_order(): Order {
		$wc_order = $this->wc_order;
		$order = new Order();
		$order->setOrderReferenceId( (string) $wc_order->get_id() );
		$order->setLocale( get_locale() );
		$order->setCurrency( $wc_order->get_currency() );
		$order->setTotalAmount( new Money( General_Helper::format_tamara_number( $wc_order->get_total(), $order->getCurrency() ), $order->getCurrency() ) );
			$order->setCountryCode(
				! empty( $wc_order->get_billing_country() ) ? $wc_order->get_billing_country()
				: General_Helper::get_current_country_code()
			);
		$order->setPaymentType( $this->payment_type );
		$order->setInstalments( $this->instalment_period );
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
			new Money(
				General_Helper::format_tamara_number(
					$wc_order->get_total_tax(),
					$order->getCurrency()
				),
				$order->getCurrency()
			)
		);
		$order->setShippingAmount(
			new Money(
				General_Helper::format_tamara_number( $wc_order->get_shipping_total(), $order->getCurrency() ),
				$order->getCurrency()
			)
		);

		$used_coupons = ! empty( $wc_order->get_coupon_codes() ) ? implode( ',', $wc_order->get_coupon_codes() ) : '';
		$order->setDiscount(
			new Discount(
				$used_coupons,
				new Money(
					General_Helper::format_tamara_number(
						$wc_order->get_discount_total(),
						$order->getCurrency()
					),
					$order->getCurrency()
				)
			)
		);
		$order->setMerchantUrl( $this->populate_tamara_merchant_url( $wc_order ) );
		$order->setBillingAddress( $this->populate_tamara_billing_address( $wc_order ) );
		$order->setShippingAddress( $this->populate_tamara_shipping_address( $wc_order ) );
		$order->setConsumer( $this->populate_tamara_consumer( $wc_order ) );
		$order->setRiskAssessment(
			Build_Tamara_Order_RiskAssessment_Query::execute_now(
				$wc_order
			)
		);

		$order->setItems( $this->populate_tamara_order_items( $wc_order ) );

		return $order;
	}

	/**
	 * @throws \Exception
	 */
	protected function populate_tamara_merchant_url( $wc_order ): MerchantUrl {
		$merchant_url = new MerchantUrl();

		$params = [
			'wc_order_id' => $wc_order->get_id(),
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
	 */
	protected function populate_tamara_consumer( $wc_order ): Consumer {
		$wc_billing_address = $wc_order->get_address( 'billing' );

		if ( empty( $wc_billing_address['email'] ) || empty( $wc_billing_address['phone'] ) ) {
			throw new Tamara_Exception( wp_kses_post( Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( 'Phone and Email are mandatory when checking out with Tamara' ) ) );
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
	 * @param  WC_Order  $wc_order
	 *
	 * @return \Tamara_Checkout\Deps\Tamara\Model\Order\OrderItemCollection
	 */
	protected function populate_tamara_order_items( WC_Order $wc_order ): OrderItemCollection {
		$wc_order_items = $wc_order->get_items();
		$order_item_collection = new OrderItemCollection();

		foreach ( $wc_order_items as $item_id => $wc_order_item ) {
			$order_item = new OrderItem();
			/** @var \WC_Order_Item_Product $wc_order_item */
			/** @var \WC_Product_Simple $wc_order_item_product */
			$wc_order_item_product = $wc_order_item->get_product();

			if ( $wc_order_item_product ) {
				$wc_order_item_name = wp_strip_all_tags( $wc_order_item->get_name() );
				$wc_order_item_quantity = $wc_order_item->get_quantity();
				$wc_order_item_sku = empty( $wc_order_item_product->get_sku() ) ? (string) $item_id : $wc_order_item_product->get_sku();
				$wc_order_item_total_tax = $wc_order_item->get_total_tax();
				$wc_order_item_total = $wc_order_item->get_total() + $wc_order_item_total_tax;

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
					new Money(
						General_Helper::format_tamara_number(
							$item_price,
							$wc_order->get_currency()
						),
						$wc_order->get_currency()
					)
				);
				$order_item->setType( $wc_order_item_categories );
				$order_item->setSku( $wc_order_item_sku );
				$order_item->setTotalAmount(
					new Money(
						General_Helper::format_tamara_number(
							$wc_order_item_total,
							$wc_order->get_currency()
						),
						$wc_order->get_currency()
					)
				);
				$order_item->setTaxAmount(
					new Money(
						General_Helper::format_tamara_number(
							$wc_order_item_total_tax,
							$wc_order->get_currency()
						),
						$wc_order->get_currency()
					)
				);
				$order_item->setDiscountAmount(
					new Money(
						General_Helper::format_tamara_number(
							$wc_order_item_discount_amount,
							$wc_order->get_currency()
						),
						$wc_order->get_currency()
					)
				);
				$order_item->setReferenceId( (string) $item_id );
				$order_item->setImageUrl( wp_get_attachment_url( $wc_order_item_product->get_image_id() ) );
			} else {
				$wc_order_item_product = $wc_order_item->get_data();
				$wc_order_item_name = ! empty( $wc_order_item_product['name'] ) ? wp_strip_all_tags( $wc_order_item_product['name'] ) : 'N/A';
				$wc_order_item_quantity = $wc_order_item_product['quantity'] ?? 1;
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
					new Money(
						General_Helper::format_tamara_number(
							$item_price,
							$wc_order->get_currency()
						),
						$wc_order->get_currency()
					)
				);
				$order_item->setType( $wc_order_item_categories );
				$order_item->setSku( $wc_order_item_sku );
				$order_item->setTotalAmount(
					new Money(
						General_Helper::format_tamara_number(
							$wc_order_item_total,
							$wc_order->get_currency()
						),
						$wc_order->get_currency()
					)
				);
				$order_item->setTaxAmount(
					new Money(
						General_Helper::format_tamara_number(
							$wc_order_item_total_tax,
							$wc_order->get_currency()
						),
						$wc_order->get_currency()
					)
				);
				$order_item->setDiscountAmount(
					new Money(
						General_Helper::format_tamara_number(
							$wc_order_item_discount_amount,
							$wc_order->get_currency()
						),
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
	 * Set Tamara Order Billing Addresses
	 *
	 * @param  WC_Order  $wc_order
	 *
	 * @return Address
	 */
	public function populate_tamara_billing_address( WC_Order $wc_order ): Address {
		$wcBillingAddress = $wc_order->get_address( 'billing' );

		return $this->populate_tamara_address( $wcBillingAddress );
	}

	/**
	 * Set Tamara Order Shipping Addresses
	 *
	 * @param  WC_Order  $wc_order
	 *
	 * @return Address
	 */
	public function populate_tamara_shipping_address( WC_Order $wc_order ): Address {
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
				: General_Helper::get_current_country_code() );

		$wc_shipping_address['phone'] = ! empty( $wc_shipping_address['phone'] )
			? $wc_shipping_address['phone']
			: ( ! empty( $wc_billing_address['phone'] ) ? $wc_billing_address['phone'] : null );

		return $this->populate_tamara_address( $wc_shipping_address );
	}

	/**
	 * @param $wc_address
	 *
	 * @return \Tamara_Checkout\Deps\Tamara\Model\Order\Address
	 */
	public function populate_tamara_address( $wc_address ): Address {
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
}
