<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Exception;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\Support\Helpers\Tamara_Order_Helper;
use Tamara_Checkout\App\Support\Helpers\WC_Order_Helper;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Client;
use Tamara_Checkout\Deps\Tamara\Configuration;
use Tamara_Checkout\Deps\Tamara\Exception\RequestDispatcherException;
use Tamara_Checkout\Deps\Tamara\HttpClient\GuzzleHttpAdapter;
use Tamara_Checkout\Deps\Tamara\Model\Money;
use Tamara_Checkout\Deps\Tamara\Model\Order\Address;
use Tamara_Checkout\Deps\Tamara\Model\Order\Consumer;
use Tamara_Checkout\Deps\Tamara\Model\Order\Discount;
use Tamara_Checkout\Deps\Tamara\Model\Order\MerchantUrl;
use Tamara_Checkout\Deps\Tamara\Model\Order\Order;
use Tamara_Checkout\Deps\Tamara\Model\Order\OrderItem;
use Tamara_Checkout\Deps\Tamara\Model\Order\OrderItemCollection;
use Tamara_Checkout\Deps\Tamara\Model\Order\RiskAssessment;
use Tamara_Checkout\Deps\Tamara\Request\Checkout\CreateCheckoutRequest;
use Tamara_Checkout\Deps\Tamara\Response\Checkout\CreateCheckoutResponse;
use WC_Order;

/**
 * A wrapper of Tamara Client
 * @package Tamara_Checkout\App\Services
 * static @method Tamara_Client create($configuration)
 */
class Tamara_Client {
	use Static_Instance_Trait;

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
	 * Create Tamara Checkout session, if failed, put errors to `wc_notices`
	 *
	 * @param $wc_order_id
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
	public function process_payment( $wc_order_id ) {
		$wc_order = wc_get_order( $wc_order_id );
		$instalment_period = 3;
		$checkout_payment_type = 'PAY_BY_INSTALMENTS';
		try {
			$create_tamara_checkout_session_response = $this->build_checkout_session_request(
				$wc_order,
				$checkout_payment_type,
				$instalment_period
			);
		} catch ( RequestDispatcherException $tamara_request_dispatcher_exception ) {
			$error_message = General_Helper::convert_message( $tamara_request_dispatcher_exception->getMessage() );
		} catch ( Exception $tamara_checkout_exception ) {
			$error_message = $this->_t( 'Tamara Service unavailable! Please try again later.' )."<br />\n". $this->_t( $tamara_checkout_exception->getMessage() );
		}

		if ( isset( $create_tamara_checkout_session_response ) ) {
			if ( $create_tamara_checkout_session_response->isSuccess() ) {
				$tamara_checkout_url = $create_tamara_checkout_session_response->getCheckoutResponse()->getCheckoutUrl();
				$tamara_checkout_session_id = $create_tamara_checkout_session_response->getCheckoutResponse()->getCheckoutId();

				$this->store_meta_data_from_checkout_response(
					$wc_order_id,
					$tamara_checkout_session_id,
					$tamara_checkout_url,
					$checkout_payment_type,
					$instalment_period
				);

				return [
					'result' => 'success',
					'redirect' => $tamara_checkout_url,
					'tamara_checkout_url' => $tamara_checkout_url,
					'tamara_checkout_session_id' => $tamara_checkout_session_id,
				];
			} else {
				$error_message = $create_tamara_checkout_session_response->getMessage();
				$errors = $create_tamara_checkout_session_response->getErrors();

				array_walk($errors, function (&$tmp_item, $tmp_index) {
					$tmp_item = $tmp_item['error_code'] ?? null;
				});
				$error_message = $this->_t( $error_message );
				$error_message .= "<br />\n";
				$error_message .= implode("<br />\n", $errors);
			}
		}

		if ( function_exists( 'wc_add_notice' ) && !empty( $error_message ) ) {
			wc_add_notice( $error_message, 'error' );
		}

		// If this is the failed process, return false instead of ['result' => 'success']
		return false;
	}

	/**
	 * Create Tamara Checkout Session Request
	 *
	 * @param WC_Order $wc_order
	 * @param mixed $payment_type
	 * @param mixed $instalment_period
	 * @return CreateCheckoutResponse
	 * @throws Exception
	 * @throws RequestDispatcherException
	 */
	public function build_checkout_session_request( WC_Order $wc_order, $payment_type, $instalment_period ): CreateCheckoutResponse {
		$client = $this->api_client;
		$checkout_request = new CreateCheckoutRequest(
			$this->build_tamara_order( $wc_order, $payment_type, $instalment_period )
		);

		return $client->createCheckout( $checkout_request );
	}

	/**
	 * @throws \Exception
	 */
	protected function populate_tamara_merchant_url( $wc_order ): MerchantUrl {
		$merchant_url = new MerchantUrl();

		$wc_order_id = $wc_order->get_id();

		$tamara_success_url = $this->get_tamara_success_url(
			$wc_order,
			[
				'wc_order_id' => $wc_order_id,
				'payment_method' => Tamara_Checkout_WP_Plugin::TAMARA_CHECKOUT,
			]
		);
		$tamara_cancel_url = $this->get_tamara_cancel_url(
			[
				'wc_order_id' => $wc_order_id,
			]
		);
		$tamara_failure_url = $this->get_tamara_failure_url(
			[
				'wc_order_id' => $wc_order_id,
			]
		);

		$merchant_url->setSuccessUrl( $tamara_success_url );
		$merchant_url->setCancelUrl( $tamara_cancel_url );
		$merchant_url->setFailureUrl( $tamara_failure_url );
		$merchant_url->setNotificationUrl( $this->get_tamara_ipn_url() );

		return $merchant_url;
	}

	/**
	 * @param $wc_order
	 *
	 * @return Consumer
	 */
	protected function populate_tamara_consumer( $wc_order ): Consumer {
		$wc_billing_address = $wc_order->get_address( 'billing' );

		$first_name = $wc_billing_address['first_name'] ?? 'N/A';
		$last_name = $wc_billing_address['last_name'] ?? 'N/A';
		$email = $wc_billing_address['email'] ?? 'notavailable@email.com';
		$phone = $wc_billing_address['phone'] ?? 'N/A';

		$consumer = new Consumer();
		$consumer->setFirstName( $first_name );
		$consumer->setLastName( $last_name );
		$consumer->setEmail( $email );
		$consumer->setPhoneNumber( $phone );

		return $consumer;
	}

	/**
	 * @return RiskAssessment
	 */
	protected function populate_tamara_risk_assessment(): RiskAssessment {
		$risk_assessment = new RiskAssessment();
		// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		// Todo: Tamara PHP SDK should be updated to perform further actions for Risk Assessment
		//
		//      $risk_assessment->setAccountCreationDate( TamaraCheckout::getInstance()->getCurrentUserRegisterDate() );
		//      $risk_assessment->setHasDeliveredOrder( TamaraCheckout::getInstance()->currentUserHasDeliveredOrder() );
		//      $risk_assessment->setTotalOrderCount( TamaraCheckout::getInstance()->getCurrentUserTotalOrderCount() );
		//      $risk_assessment->setDateOfFirstTransaction( TamaraCheckout::getInstance()->getCurrentUserDateOfFirstTransaction() );
		//      $risk_assessment->setIsExistingCustomer( is_user_logged_in() );
		//      $risk_assessment->setOrderAmountLast3months( TamaraCheckout::getInstance()->getCurrentUserOrderAmountLast3Months() );
		//      $risk_assessment->setOrderCountLast3months( TamaraCheckout::getInstance()->getCurrentUserOrderCountLast3Months() );

		return $risk_assessment;
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
				$wc_order_item_categories = empty($wc_order_item_categories) ? 'N/A' : $wc_order_item_categories;

				$wc_order_item_regular_price = $wc_order_item_product->get_regular_price();
				$wc_order_item_sale_price = $wc_order_item_product->get_sale_price();
				$item_price = $wc_order_item_sale_price ?? $wc_order_item_regular_price;
				$wc_order_item_discount_amount = (int) $item_price * $wc_order_item_quantity
												- ( (int) $wc_order_item_total - (int) $wc_order_item_total_tax );
				$order_item->setName( $wc_order_item_name );
				$order_item->setQuantity( $wc_order_item_quantity );
				$order_item->setUnitPrice( new Money( General_Helper::format_tamara_number( $item_price ), $wc_order->get_currency() ) );
				$order_item->setType( $wc_order_item_categories );
				$order_item->setSku( $wc_order_item_sku );
				$order_item->setTotalAmount(
					new Money(
						General_Helper::format_tamara_number( $wc_order_item_total ),
						$wc_order->get_currency()
					)
				);
				$order_item->setTaxAmount(
					new Money(
						General_Helper::format_tamara_number( $wc_order_item_total_tax ),
						$wc_order->get_currency()
					)
				);
				$order_item->setDiscountAmount(
					new Money(
						General_Helper::format_tamara_number( $wc_order_item_discount_amount ),
						$wc_order->get_currency()
					)
				);
				$order_item->setReferenceId( (string) $item_id );
				$order_item->setImageUrl( wp_get_attachment_url( $wc_order_item_product->get_image_id() ) );
			} else {
				$wc_order_item_product = $wc_order_item->get_data();
				$wc_order_item_name = wp_strip_all_tags( $wc_order_item_product['name'] ) ?? 'N/A';
				$wc_order_item_quantity = $wc_order_item_product['quantity'] ?? 1;
				$wc_order_item_sku = empty($wc_order_item_product['sku']) ? (string) $item_id : $wc_order_item_product['sku'];
				$wc_order_item_total_tax = $wc_order_item_product['total_tax'] ?? 0;
				$wc_order_item_total = $wc_order_item_product['total'] ?? 0;
				$wc_order_item_categories = $wc_order_item_product['category'] ?? 'N/A';
				$item_price = $wc_order_item_product['subtotal'] ?? 0;
				$wc_order_item_discount_amount = (int) $item_price * $wc_order_item_quantity
												- ( (int) $wc_order_item_total - (int) $wc_order_item_total_tax );
				$order_item->setName( $wc_order_item_name );
				$order_item->setQuantity( $wc_order_item_quantity );
				$order_item->setUnitPrice( new Money( General_Helper::format_tamara_number( $item_price ), $wc_order->get_currency() ) );
				$order_item->setType( $wc_order_item_categories );
				$order_item->setSku( $wc_order_item_sku );
				$order_item->setTotalAmount(
					new Money(
						General_Helper::format_tamara_number( $wc_order_item_total ),
						$wc_order->get_currency()
					)
				);
				$order_item->setTaxAmount(
					new Money(
						General_Helper::format_tamara_number( $wc_order_item_total_tax ),
						$wc_order->get_currency()
					)
				);
				$order_item->setDiscountAmount(
					new Money(
						General_Helper::format_tamara_number( $wc_order_item_discount_amount ),
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
	 * Get Merchant Success Url
	 *
	 * @param  WC_Order  $wc_order
	 * @param  array  $params
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function get_tamara_success_url( WC_Order $wc_order, $params = [] ): string {
		$tamara_success_url = ! empty( $wc_order )
			? esc_url_raw( $wc_order->get_checkout_order_received_url() )
			: wp_app_route_wp_url( 'tamara-success' );
		$tamara_success_url = add_query_arg( $params, $tamara_success_url );

		return General_Helper::remove_trailing_slashes( $tamara_success_url );
	}

	/**
	 * Get Merchant Cancel Url
	 *
	 * @param $params
	 *
	 * @return string
	 */
	protected function get_tamara_cancel_url( $params = [] ): string {
		$tamara_cancel_url = !empty(Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_gateway_service()->get_settings()->cancel_url)
			? Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_gateway_service()->get_settings()->cancel_url
			: wp_app_route_wp_url( 'wp-api-tamara-cancel', $params );
		$tamara_cancel_url = add_query_arg( $params, $tamara_cancel_url );

		return General_Helper::remove_trailing_slashes( $tamara_cancel_url );
	}

	/**
	 * Get Merchant Failure Url
	 *
	 * @param $params
	 *
	 * @return string
	 */
	protected function get_tamara_failure_url( $params = [] ): string {
		$tamara_failure_url = $this->tamara_checkout_wp_plugin->get_tamara_gateway_service()->get_settings()->failure_url
			? $this->tamara_checkout_wp_plugin->get_tamara_gateway_service()->get_settings()->failure_url
			: wp_app_route_wp_url( 'wp-api-tamara-failure', $params );

		$tamara_failure_url = add_query_arg( $params, $tamara_failure_url );

		return General_Helper::remove_trailing_slashes( $tamara_failure_url );
	}

	/**
	 * @throws \Exception
	 */
	public function handle_tamara_payment_cancel_url ($wc_order_id)
	{
		$wc_order = wc_get_order($wc_order_id);
		$gateway_settings = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_gateway_service()->get_settings();

		if (Tamara_Order_Helper::is_order_authorised($wc_order_id)) {
			WC_Order_Helper::prevent_order_cancel_action($wc_order, $wc_order_id);
		} elseif (!empty($wc_order_id)) {
			$new_order_status = $gateway_settings->get_payment_cancel_status();
			$order_note = General_Helper::convert_message('The payment for this order has been cancelled from Tamara.');
			WC_Order_Helper::update_order_status_and_add_order_note($wc_order, $order_note, $new_order_status, '');
			$cancel_url_from_tamara = add_query_arg(
				[
					'tamara_custom_status' => 'tamara-p-canceled',
					'redirect_from' => 'tamara',
					'cancel_order' => 'true',
					'order' => $wc_order->get_order_key(),
					'order_id' => $wc_order_id,
					'_wpnonce' => wp_create_nonce('woocommerce-cancel_order'),
				],
				$wc_order->get_cancel_order_url_raw()
			);
			wp_redirect($cancel_url_from_tamara);
		}
	}
//
//	/**
//	 * @throws \Exception
//	 */
//	public function handle_tamara_payment_failure_url ($wc_order_id)
//	{
//		$wc_order = wc_get_order($wc_order_id);
//		$gateway_settings = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_gateway_service()->get_settings();
//
//		if (Tamara_Order_Helper::is_order_authorised($wc_order_id)) {
//			WC_Order_Helper::prevent_order_cancel_action($wc_order, $wc_order_id);
//		} elseif (!empty($wc_order_id)) {
//			$new_order_status = $gateway_settings->get_payment_failure_status();
//			$order_note = General_Helper::convert_message('The payment for this order has been declined from Tamara');
//			WC_Order_Helper::update_order_status_and_add_order_note($wc_order, $order_note, $new_order_status, '');
//			$failure_url_from_tamara = add_query_arg(
//				[
//					'tamara_custom_status' => 'tamara-p-failed',
//					'redirect_from' => 'tamara',
//					'cancel_order' => 'true',
//					'order' => $wc_order->get_order_key(),
//					'order_id' => $wc_order_id,
//					'_wpnonce' => wp_create_nonce('woocommerce-cancel_order'),
//				],
//				$wc_order->get_cancel_order_url_raw()
//			);
//			wp_redirect($failure_url_from_tamara);
//		}
//	}

	/**
	 * Get Tamara Ipn Url to handle notification
	 */
	public function get_tamara_ipn_url(): string {
		return wp_app_route_wp_url( 'tamara-ipn' );
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

	// phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
	public function _t( $text ) {
		return Tamara_Checkout_WP_Plugin::wp_app_instance()->_t( $text );
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

	protected function store_meta_data_from_checkout_response( int $wc_order_id, $checkout_session_id, $checkout_url, $checkout_payment_type, $checkout_instalment_period ): void {
		update_post_meta( $wc_order_id, '_tamara_checkout_session_id', $checkout_session_id );
		update_post_meta( $wc_order_id, '_tamara_checkout_url', $checkout_url );
		update_post_meta( $wc_order_id, '_tamara_payment_type', $checkout_payment_type );
		if ( $checkout_payment_type === 'PAY_BY_INSTALMENTS' && ! empty( $checkout_instalment_period ) ) {
			update_post_meta( $wc_order_id, '_tamara_instalment_period', $checkout_instalment_period );
		}
	}

	/**
	 * Filling all needed data for a Tamara Order used for checkout
	 *
	 * @param  WC_Order  $wc_order
	 * @param string $payment_type | null
	 * @param string $instalment_period null
	 *
	 * @return Order
	 * @throws Exception
	 */
	protected function build_tamara_order( WC_Order $wc_order, $payment_type = null, $instalment_period = null ): Order {
		if ( empty( $payment_type ) ) {
			throw new Exception( 'Error! No Payment Type specified' );
		}
		$usedCouponsStr = ! empty( $wc_order->get_coupon_codes() ) ? implode( ',', $wc_order->get_coupon_codes() ) : '';
		$order = new Order();

		$order->setOrderReferenceId( (string) $wc_order->get_id() );
		$order->setLocale( get_locale() );
		$order->setCurrency( $wc_order->get_currency() );
		$order->setTotalAmount( new Money( General_Helper::format_tamara_number( $wc_order->get_total() ), $order->getCurrency() ) );
		$order->setCountryCode(
			! empty( $wc_order->get_billing_country() ) ? $wc_order->get_billing_country()
			: General_Helper::get_current_country_code()
		);
		$order->setPaymentType( $payment_type );
		$order->setInstalments( $instalment_period );
		$order->setPlatform(
			sprintf(
				'WordPress %s, WooCommerce %s, Tamara Checkout %s',
				$GLOBALS['wp_version'],
				$GLOBALS['woocommerce']->version,
				$this->tamara_checkout_wp_plugin->get_version()
			)
		);
		$order->setDescription( $this->tamara_checkout_wp_plugin->_t( 'Use Tamara Gateway with WooCommerce' ) );
		$order->setTaxAmount(
			new Money(
				General_Helper::format_tamara_number( $wc_order->get_total_tax() ),
				$order->getCurrency()
			)
		);
		$order->setShippingAmount(
			new Money(
				General_Helper::format_tamara_number( $wc_order->get_shipping_total() ),
				$order->getCurrency()
			)
		);
		$order->setDiscount(
			new Discount(
				$usedCouponsStr,
				new Money(
					General_Helper::format_tamara_number(
						$wc_order->get_discount_total()
					),
					$order->getCurrency()
				)
			)
		);
		$order->setMerchantUrl( $this->populate_tamara_merchant_url( $wc_order ) );
		$order->setBillingAddress( $this->populate_tamara_billing_address( $wc_order ) );
		$order->setShippingAddress( $this->populate_tamara_shipping_address( $wc_order ) );
		$order->setConsumer( $this->populate_tamara_consumer( $wc_order ) );
		$order->setRiskAssessment( $this->populate_tamara_risk_assessment() );

		$order->setItems( $this->populate_tamara_order_items( $wc_order ) );

		return $order;
	}
}
