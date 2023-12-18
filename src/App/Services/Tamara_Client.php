<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Exception;
use Tamara_Checkout\App\Support\Helpers\MoneyHelper;
use Tamara_Checkout\App\Support\Traits\Tamara_Order_Trait;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Client;
use Tamara_Checkout\Deps\Tamara\Configuration;
use Tamara_Checkout\Deps\Tamara\HttpClient\GuzzleHttpAdapter;
use Tamara_Checkout\Deps\Tamara\Model\Money;
use Tamara_Checkout\Deps\Tamara\Model\Order\Address;
use Tamara_Checkout\Deps\Tamara\Model\Order\Consumer;
use Tamara_Checkout\Deps\Tamara\Model\Order\Discount;
use Tamara_Checkout\Deps\Tamara\Model\Order\MerchantUrl;
use Tamara_Checkout\Deps\Tamara\Model\Order\Order;
use Tamara_Checkout\Deps\Tamara\Model\Order\RiskAssessment;
use WC_Order;

/**
 * A wrapper of Tamara Client
 * @package Tamara_Checkout\App\Services
 * static @method Tamara_Client create($configuration)
 */
class Tamara_Client {
	use Static_Instance_Trait;
	use Tamara_Order_Trait;

	protected $tamara_checkout_wp_plugin;
	protected $working_mode = 'live';
	protected $api_url;
	protected $api_token;
	protected $api_request_timeout;

	protected $api_client;

	protected function __construct( $api_token, $api_url = 'https://api.tamara.co', $api_request_timeout = 30 ) {
		$this->tamara_checkout_wp_plugin = Tamara_Checkout_WP_Plugin::wp_app_instance();
		$logger = null;
		$transport = new GuzzleHttpAdapter( $api_request_timeout, $logger );
		$configuration = Configuration::create( $api_url, $api_token, $api_request_timeout, $logger, $transport );
		$client = Client::create( $configuration );

		$this->api_token = $api_token;
		$this->api_url = $api_url;
		$this->api_request_timeout = $api_request_timeout;
		$this->api_client = $client;
		$this->define_working_mode();
	}

	public function get_working_mode(): string {
		return $this->working_mode;
	}

	public function get_api_client() {
		return $this->api_client;
	}

	public function reinit_tamara_client( $api_token, $api_url = 'https://api.tamara.co', $api_request_timeout = 30 ): void {
		$this->api_token = $api_token;
		$this->api_url = $api_url;
		$this->api_request_timeout = $api_request_timeout;
		$client = $this->build_tamara_client( $api_token, $api_url, $api_request_timeout );
		static::$instance->api_client = $client;
	}

	/**
	 * Filling all needed data for a Tamara Order used for Pay By Later
	 *
	 * @param  WC_Order  $wc_order
	 * @param string $payment_type | null
	 * @param string $instalment_period null
	 *
	 * @return Order
	 * @throws Exception
	 */
	protected function populate_tamara_order(WC_Order $wc_order, $payment_type = null, $instalment_period = null): Order {
		if (empty($payment_type)) {
			throw new Exception('Error! No Payment Type specified');
		}
		$usedCouponsStr = !empty($wc_order->get_coupon_codes()) ? implode(",", $wc_order->get_coupon_codes()) : '';
		$order = new Order();

		$order->setOrderReferenceId((string) $wc_order->get_id());
		$order->setLocale(get_locale());
		$order->setCurrency($wc_order->get_currency());
		$order->setTotalAmount(new Money(MoneyHelper::formatNumber($wc_order->get_total()), $order->getCurrency()));
		$order->setCountryCode(!empty($wc_order->get_billing_country()) ? $wc_order->get_billing_country()
			: $this->get_default_billing_country_code());
		$order->setPaymentType($payment_type);
		$order->setInstalments($instalment_period);
		$order->setPlatform(sprintf('WordPress %s, WooCommerce %s, Tamara Checkout %s',
			$GLOBALS['wp_version'], $GLOBALS['woocommerce']->version,
			$this->tamara_checkout_wp_plugin->get_version()));
		$order->setDescription(__('Use Tamara Gateway with WooCommerce',
			$this->tamara_checkout_wp_plugin->get_text_domain()));
		$order->setTaxAmount(new Money(MoneyHelper::formatNumber($wc_order->get_total_tax()), $order->getCurrency()));
		$order->setShippingAmount(new Money(MoneyHelper::formatNumber($wc_order->get_shipping_total()),
			$order->getCurrency()));
		$order->setDiscount(new Discount($usedCouponsStr, new Money(MoneyHelper::formatNumber(
			$wc_order->get_discount_total()), $order->getCurrency())));
		$order->setMerchantUrl($this->populate_tamara_merchant_url($wc_order));
		$order->setBillingAddress($this->populate_tamara_billing_address($wc_order));
		$order->setShippingAddress($this->populate_tamara_shipping_address($wc_order));
		$order->setConsumer($this->populate_tamara_consumer($wc_order));
		$order->setRiskAssessment($this->populate_tamara_risk_assessment());

		$order->setItems($this->populate_tamara_order_items($wc_order));

		return $order;
	}

	/**
	 * @throws \Exception
	 */
	protected function populate_tamara_merchant_url($wc_order): MerchantUrl {
		$merchant_url = new MerchantUrl();

		$wc_order_id = $wc_order->get_id();

		$tamara_success_url = $this->get_tamara_success_url($wc_order, [
			'wc_order_id' => $wc_order_id,
			'payment_method' => Tamara_Checkout_WP_Plugin::TAMARA_CHECKOUT,
		]);
		$tamara_cancel_url = $this->get_tamara_cancel_url([
			'wc_order_id' => $wc_order_id,
		]);
		$tamara_failure_url = $this->get_tamara_failure_url([
			'wc_order_id' => $wc_order_id,
		]);

		$merchant_url->setSuccessUrl($tamara_success_url);
		$merchant_url->setCancelUrl($tamara_cancel_url);
		$merchant_url->setFailureUrl($tamara_failure_url);
		$merchant_url->setNotificationUrl($this->get_tamara_ipn_url());

		return $merchant_url;
	}

	/**
	 * @param $wc_order
	 *
	 * @return Consumer
	 */
	protected function populate_tamara_consumer($wc_order): Consumer {
		$wc_billing_address = $wc_order->get_address('billing');

		$first_name = $wc_billing_address['first_name'] ?? 'N/A';
		$last_name = $wc_billing_address['last_name'] ?? 'N/A';
		$email = $wc_billing_address['email'] ?? 'notavailable@email.com';
		$phone = $wc_billing_address['phone'] ?? 'N/A';

		$consumer = new Consumer();
		$consumer->setFirstName($first_name);
		$consumer->setLastName($last_name);
		$consumer->setEmail($email);
		$consumer->setPhoneNumber($phone);

		return $consumer;
	}

	/**
	 * @return RiskAssessment
	 */
	protected function populate_tamara_risk_assessment() : RiskAssessment{
		$risk_assessment = new RiskAssessment();
//
//		$risk_assessment->setAccountCreationDate( TamaraCheckout::getInstance()->getCurrentUserRegisterDate() );
//		$risk_assessment->setHasDeliveredOrder( TamaraCheckout::getInstance()->currentUserHasDeliveredOrder() );
//		$risk_assessment->setTotalOrderCount( TamaraCheckout::getInstance()->getCurrentUserTotalOrderCount() );
//		$risk_assessment->setDateOfFirstTransaction( TamaraCheckout::getInstance()->getCurrentUserDateOfFirstTransaction() );
//		$risk_assessment->setIsExistingCustomer( is_user_logged_in() );
//		$risk_assessment->setOrderAmountLast3months( TamaraCheckout::getInstance()->getCurrentUserOrderAmountLast3Months() );
//		$risk_assessment->setOrderCountLast3months( TamaraCheckout::getInstance()->getCurrentUserOrderCountLast3Months() );

		return $risk_assessment;
	}

	protected function populate_tamara_order_items($wc_order) {

	}

	/**
	 * Get Merchant Success Url
	 *
	 * @param $wc_order
	 * @param  array  $params
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function get_tamara_success_url($wc_order, $params = []): string {
		$tamara_success_url = !empty($wc_order)
			? esc_url_raw($wc_order->get_checkout_order_received_url())
			: wp_app_route_wp_url('tamara-success');
		$tamara_success_url = add_query_arg($params, $tamara_success_url);

		return $this->remove_trailing_slashes($tamara_success_url);
	}

	/**
	 * Get Merchant Cancel Url
	 *
	 * @param $params
	 *
	 * @return string
	 */
	protected function get_tamara_cancel_url($params = []): string {
		$tamara_cancel_url = $this->tamara_checkout_wp_plugin->get_tamara_gateway_service()->get_option('cancel_url')
			? $this->tamara_checkout_wp_plugin->get_tamara_gateway_service()->get_option('cancel_url')
			: wp_app_route_wp_url('tamara-cancel');

		$tamara_cancel_url = add_query_arg($params, $tamara_cancel_url);

		return $this->remove_trailing_slashes($tamara_cancel_url);
	}

	/**
	 * Get Merchant Failure Url
	 *
	 * @param $params
	 *
	 * @return string
	 */
	protected function get_tamara_failure_url($params = []): string {
		$tamara_failure_url = $this->tamara_checkout_wp_plugin->get_tamara_gateway_service()->get_option('failure_url')
			? $this->tamara_checkout_wp_plugin->get_tamara_gateway_service()->get_option('failure_url')
			: wp_app_route_wp_url('tamara-failure');

		$tamara_failure_url = add_query_arg($params, $tamara_failure_url);

		return $this->remove_trailing_slashes($tamara_failure_url);
	}

	/**
	 * Get Tamara Ipn Url to handle notification
	 */
	public function get_tamara_ipn_url() : string {
		return wp_app_route_wp_url('tamara-ipn');
	}

	/**
	 * Set Tamara Order Billing Addresses
	 *
	 * @param  WC_Order  $wc_order
	 *
	 * @return Address
	 */
	public function populate_tamara_billing_address(WC_Order $wc_order): Address {
		$wcBillingAddress = $wc_order->get_address('billing');

		return $this->populate_tamara_address($wcBillingAddress);
	}

	/**
	 * Set Tamara Order Shipping Addresses
	 *
	 * @param WC_Order $wcOrder
	 *
	 * @return Address
	 */
	public function populate_tamara_shipping_address($wcOrder): Address {
		$wc_shipping_address = $wcOrder->get_address('shipping');
		$wc_billing_address = $wcOrder->get_address('billing');

		$wc_shipping_address['first_name'] = !empty($wc_shipping_address['first_name'])
			? $wc_shipping_address['first_name']
			: (!empty($wc_billing_address['first_name']) ? $wc_billing_address['first_name'] : null);

		$wc_shipping_address['last_name'] = !empty($wc_shipping_address['last_name'])
			? $wc_shipping_address['last_name']
			: (!empty($wc_billing_address['last_name']) ? $wc_billing_address['last_name'] : null);

		$wc_shipping_address['address_1'] = !empty($wc_shipping_address['address_1'])
			? $wc_shipping_address['address_1']
			: (!empty($wc_billing_address['address_1']) ? $wc_billing_address['address_1'] : null);

		$wc_shipping_address['address_2'] = !empty($wc_shipping_address['address_2'])
			? $wc_shipping_address['address_2']
			: (!empty($wc_billing_address['address_2']) ? $wc_billing_address['address_2'] : null);

		$wc_shipping_address['city'] = !empty($wc_shipping_address['city'])
			? $wc_shipping_address['city']
			: (!empty($wc_billing_address['city']) ? $wc_billing_address['city'] : null);

		$wc_shipping_address['state'] = !empty($wc_shipping_address['state'])
			? $wc_shipping_address['state']
			: (!empty($wc_billing_address['state']) ? $wc_billing_address['state'] : null);

		$wc_shipping_address['country'] = !empty($wc_shipping_address['country'])
			? $wc_shipping_address['country']
			: (!empty($wc_billing_address['country']) ? $wc_billing_address['country']
				: $this->get_default_billing_country_code());

		$wc_shipping_address['phone'] = !empty($wc_shipping_address['phone'])
			? $wc_shipping_address['phone']
			: (!empty($wc_billing_address['phone']) ? $wc_billing_address['phone'] : null);

		return $this->populate_tamara_address($wc_shipping_address);
	}

	/**
	 * @param $wc_address
	 *
	 * @return \Tamara_Checkout\Deps\Tamara\Model\Order\Address
	 */
	public function populate_tamara_address($wc_address) : Address {
		$first_name = !empty($wc_address['first_name']) ? $wc_address['first_name'] : 'N/A';
		$last_name = !empty($wc_address['last_name']) ? $wc_address['first_name'] : 'N/A';
		$address1 = !empty($wc_address['address_1']) ? $wc_address['first_name'] : 'N/A';
		$address2 = !empty($wc_address['address_2']) ? $wc_address['first_name'] : 'N/A';
		$city = !empty($wc_address['city']) ? $wc_address['first_name'] : 'N/A';
		$state = !empty($wc_address['state']) ? $wc_address['first_name'] : 'N/A';
		$phone = !empty($wc_address['phone']) ? $wc_address['phone'] : null;
		$country = !empty($wc_address['country']) ? $wc_address['country'] : $this->get_default_billing_country_code();

		$tamara_address = new Address();
		$tamara_address->setFirstName((string)$first_name);
		$tamara_address->setLastName((string)$last_name);
		$tamara_address->setLine1((string)$address1);
		$tamara_address->setLine2((string)$address2);
		$tamara_address->setCity((string)$city);
		$tamara_address->setRegion((string)$state);
		$tamara_address->setPhoneNumber((string)$phone);
		$tamara_address->setCountryCode((string)$country);

		return $tamara_address;
	}

	protected function build_tamara_client( $api_token, $api_url, $api_request_timeout ): Client {
		$logger = null;
		$transport = new GuzzleHttpAdapter( $api_request_timeout, $logger );
		$configuration = Configuration::create( $api_url, $api_token, $api_request_timeout, $logger, $transport );
		return Client::create( $configuration );
	}

	protected function define_working_mode(): void {
		if ( strpos( $this->api_url, '-sandbox' ) ) {
			$this->working_mode = 'sandbox';
		}
	}
}
