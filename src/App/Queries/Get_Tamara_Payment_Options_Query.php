<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Enpii_Base\Foundation\Shared\Base_Query;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\Support\Helpers\WC_Order_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Payment_Types_Trait;
use Tamara_Checkout\App\WP\Payment_Gateways\Pay_Now_WC_Payment_Gateway;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Model\Checkout\PaymentOptionsAvailability;
use Tamara_Checkout\Deps\Tamara\Model\Money;
use Tamara_Checkout\Deps\Tamara\Request\Checkout\CheckPaymentOptionsAvailabilityRequest;

class Get_Tamara_Payment_Options_Query extends Base_Query {

	use Executable_Trait;
	use Tamara_Payment_Types_Trait;

	/**
	 * @throws \Tamara_Checkout\Deps\Tamara\Exception\RequestDispatcherException
	 */
	public function handle() {
		$remote_available_payment_types = $this->fetch_payment_options_availability();
		$available_payment_methods = $this->convert_tamara_payment_types_to_wc_payment_methods($remote_available_payment_types);
//		$build_available_payment_options = $this->build_payment_options($available_payment_methods);
		$available_payment_options = [];
//		if ( $get_from_cache ) {
//			$current_cart_info = WC_Order_Helper::get_current_cart_info() ?? [];
//			$cart_total        = $current_cart_info['cart_total'] ?? null;
//			$customer_phone    = $current_cart_info['customer_phone'] ?? '';
//			$country_code      = $current_cart_info['country_code'] ?? Tamara_Checkout_WP_Plugin::DEFAULT_COUNTRY_CODE;
//			$country_payment_types_cache_key = $this->build_country_payment_types_cache_key($cart_total, $customer_phone,
//				$country_code, $is_vip);
//			$available_payment_options = get_transient($country_payment_types_cache_key);
//
//			if (empty($available_payment_options) && Tamara_Order_Helper::is_supported_country($country_code)) {
//
//
//				if ($check_payment_options_availability_response->isSuccess()) {
//					$available_payment_options = $this->get_payment_options_availability($check_payment_options_availability_response);
//					set_transient($country_payment_types_cache_key, $available_payment_options, 600);
//				} else {
//					$errors = $check_payment_options_availability_response->getMessage();
//					General_Helper::log_message(
//						sprintf("Tamara Checkout Payment Options Availibility Check Failed.\nError message: ' %s'",
//							$errors)
//					);
//
//					return false;
//				}
//			}
//		}

		return $available_payment_options;
	}

	/**
	 * @throws \Tamara_Checkout\Deps\Tamara\Exception\RequestDispatcherException
	 */
	protected function fetch_payment_options_availability(): array {
		$current_cart_info = WC_Order_Helper::get_current_cart_info() ?? [];
			$cart_total        = $current_cart_info['cart_total'] ?? null;
			$customer_phone    = $current_cart_info['customer_phone'] ?? '';
			$country_code      = $current_cart_info['country_code'] ?? Tamara_Checkout_WP_Plugin::DEFAULT_COUNTRY_CODE;
		$currency_by_country_code = array_flip(General_Helper::get_currency_country_mappings());
		$currency = $currency_by_country_code[$country_code];
		$order_total = new Money(General_Helper::format_tamara_number($cart_total), $currency);
		$payment_options_availability = new PaymentOptionsAvailability(
			$country_code,
			$order_total,
			$customer_phone,
			false);
		$request = new CheckPaymentOptionsAvailabilityRequest($payment_options_availability);
		$tamara_client = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->get_api_client();
		$response = $tamara_client->checkPaymentOptionsAvailability($request);

		return $response->getAvailablePaymentLabels();
	}

	/**
	 * @param $remote_available_payment_types
	 */
	protected function convert_tamara_payment_types_to_wc_payment_methods($remote_available_payment_types) {
		array_walk($remote_available_payment_types, function (&$item, $index) {
			$item['to_map'] = $item['payment_type'] . '_' . $item['instalment'];
		});

		$mappings = [
			'PAY_NOW_0' => Pay_Now_WC_Payment_Gateway::class,
		];

		$payment_methods = [];
		foreach ($remote_available_payment_types as $index => $payment_type) {
			$payment_methods[$index] = $mappings[$payment_type['to_map']] ?? null;
		}
		dev_error_log($payment_methods);
	}

}
