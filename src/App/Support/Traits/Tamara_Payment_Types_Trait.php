<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Support\Traits;

use Tamara_Checkout\App\Queries\Get_Tamara_Payment_Options_Query;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

trait Tamara_Payment_Types_Trait {

	public function register_available_payment_options($available_gateways): array {
		$payment_options = $this->get_available_payment_labels();

		if (empty($payment_options)) {
			return $available_gateways;
		}

		$has_default_gateway = false;

		if (!$has_default_gateway) {
			$available_gateways = $this->unset_default_gateway($available_gateways);
		}

		return $available_gateways;
	}

	/**
	 * @return array|false|mixed
	 */
	public function get_available_payment_options() {
		return Get_Tamara_Payment_Options_Query::execute_now();
	}

	/**
	 * Check if there's any available payment options from remote
	 *
	 * @param  bool  $is_vip
	 * @param  bool  $get_from_cache
	 *
	 * @return bool
	 */
    public function has_available_payment_options($is_vip = false, $get_from_cache = true): bool {
	    $available_payment_options = $this->get_available_payment_options(
			    $is_vip,
			    $get_from_cache) ?? [];
	    if ( ! empty($available_payment_options)) {
		    return ! ! $available_payment_options['hasAvailablePaymentOptions'];
	    }

	    return false;
    }

	/**
	 * Check if single checkout option is enabled from remote
	 *
	 * @param  bool  $is_vip
	 * @param  bool  $get_from_cache
	 *
	 * @return bool
	 */
    public function is_single_checkout_enabled($is_vip = false, $get_from_cache = true): bool {
	    $available_payment_options = $this->get_available_payment_options(
			    $is_vip,
			    $get_from_cache) ?? [];
	    if (!empty($available_payment_options)) {
		    return !!$available_payment_options['isSingleCheckoutEnabled'];
	    }

        return false;
    }

	/**
	 * Get all available payment options by labels
	 *
	 * @param  bool  $is_vip
	 * @param  bool  $get_from_cache
	 *
	 * @return array
	 */
    public function get_available_payment_labels($is_vip = false, $get_from_cache = true): array {
	    $payment_options = [];
	    $available_payment_options = $this->get_available_payment_options(
			    $is_vip,
			    $get_from_cache) ?? [];
	    if (!empty($available_payment_options)) {
		    $payment_options = $available_payment_options['getAvailablePaymentLabels'] ?? [];
	    }

        return $payment_options;
    }

	/**
	 * Add Tamara Single Checkout to existing available gateways
	 *
	 * @param $available_gateways
	 *
	 * @return array
	 */
	protected function possibly_add_tamara_single_checkout($available_gateways): array {
		$single_checkout_service = [Tamara_Checkout_WP_Plugin::TAMARA_GATEWAY_SINGLE_CHECKOUT_ID => $this->get_tamara_gateway_single_checkout_service()];
		$available_gateways    = $this->merge_payment_methods_after_default_offset($single_checkout_service, $available_gateways);
		unset($available_gateways[Tamara_Checkout_WP_Plugin::DEFAULT_TAMARA_GATEWAY_ID]);
		return $available_gateways;
	}

	/**
	 * Add other Tamara payment methods right after default offset on checkout
	 *
	 * @param $array
	 * @param $available_gateways
	 *
	 * @return array
	 */
	protected function merge_payment_methods_after_default_offset($array, $available_gateways): array {
		$tamara_default_gateway_key = Tamara_Checkout_WP_Plugin::DEFAULT_TAMARA_GATEWAY_ID;
		$tamara_default_gateway_offset = array_search($tamara_default_gateway_key, array_keys(WC()->payment_gateways->payment_gateways()));

		return array_merge(
			array_slice($available_gateways, 0, $tamara_default_gateway_offset),
			$array,
			array_slice($available_gateways, $tamara_default_gateway_offset, null)
		);
	}

	/**
	 * @param $available_gateways
	 *
	 * @return mixed
	 */
	protected function unset_default_gateway ($available_gateways) {
		unset($available_gateways[Tamara_Checkout_WP_Plugin::DEFAULT_TAMARA_GATEWAY_ID]);
		return $available_gateways;
	}
}
