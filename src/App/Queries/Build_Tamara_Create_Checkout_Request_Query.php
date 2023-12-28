<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Enpii_Base\Foundation\Shared\Base_Query;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Model\Order\Address;
use Tamara_Checkout\Deps\Tamara\Model\Order\Consumer;
use Tamara_Checkout\Deps\Tamara\Model\Order\Discount;
use Tamara_Checkout\Deps\Tamara\Model\Order\MerchantUrl;
use Tamara_Checkout\Deps\Tamara\Model\Order\Order;
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
	protected $instalments = 0;

	public function __construct( WC_Order $wc_order, string $payment_type, int $instalments = 0 ) {
		$this->wc_order = $wc_order;
		$this->payment_type = $payment_type;
		$this->instalments = $instalments;

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
		$order->setTotalAmount(
			General_Helper::build_tamara_money(
				$wc_order->get_total(),
				$wc_order->get_currency()
			)
		);
		$order->setCountryCode(
			! empty( $wc_order->get_billing_country() ) ? $wc_order->get_billing_country()
			: General_Helper::get_current_country_code()
		);
		$order->setPaymentType( $this->payment_type );
		$order->setInstalments( $this->instalments );
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
			General_Helper::build_tamara_money(
				$wc_order->get_total_tax(),
				$wc_order->get_currency()
			)
		);
		$order->setShippingAmount(
			General_Helper::build_tamara_money(
				$wc_order->get_shipping_total(),
				$wc_order->get_currency()
			)
		);

		$used_coupons = ! empty( $wc_order->get_coupon_codes() ) ? implode( ',', $wc_order->get_coupon_codes() ) : '';
		$order->setDiscount(
			new Discount(
				$used_coupons,
				General_Helper::build_tamara_money(
					$wc_order->get_discount_total(),
					$wc_order->get_currency()
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
			'payment_type' => $this->payment_type,
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
		$tamara_wc_order = new Tamara_WC_Order( $wc_order );
		return $tamara_wc_order->build_tamara_order_items();
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
