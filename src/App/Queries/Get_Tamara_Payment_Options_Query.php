<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Enpii_Base\Foundation\Shared\Base_Query;
use Enpii_Base\Foundation\Shared\Traits\Config_Trait;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Tamara_Checkout\App\Support\Helpers\General_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Payment_Types_Trait;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Model\Checkout\PaymentOptionsAvailability;
use Tamara_Checkout\Deps\Tamara\Request\Checkout\CheckPaymentOptionsAvailabilityRequest;

class Get_Tamara_Payment_Options_Query extends Base_Query {

	use Executable_Trait;
	use Tamara_Payment_Types_Trait;
	use Config_Trait;

	protected $available_gateways;
	protected $order_total;
	protected $country_code;
	protected $customer_phone;
	protected $is_vip = false;

	/**
	 * @throws \Exception
	 */
	public function __construct( array $config ) {
		$this->bind_config( $config );
	}

	/**
	 * @throws \Tamara_Checkout\Deps\Tamara\Exception\RequestDispatcherException
	 */
	public function handle(): array {
		$remote_available_payment_types = $this->fetch_payment_options_availability();
		$remote_payment_methods = $this->convert_tamara_payment_types_to_wc_payment_methods( $remote_available_payment_types );

		return $this->process_available_gateways( $remote_payment_methods );
	}

	/**
	 * @throws \Tamara_Checkout\Deps\Tamara\Exception\RequestDispatcherException
	 */
	protected function fetch_payment_options_availability(): array {
		$payment_options_availability = new PaymentOptionsAvailability(
			$this->country_code,
			$this->order_total,
			$this->customer_phone,
			$this->is_vip
		);
		$request = new CheckPaymentOptionsAvailabilityRequest( $payment_options_availability );
		$tamara_client = Tamara_Checkout_WP_Plugin::wp_app_instance()->get_tamara_client_service()->get_api_client();
		$response = $tamara_client->checkPaymentOptionsAvailability( $request );

		return $response->getAvailablePaymentLabels();
	}

	/**
	 * @param $remote_available_payment_types
	 *
	 * @return array
	 */
	protected function convert_tamara_payment_types_to_wc_payment_methods( $remote_available_payment_types ): array {
		array_walk(
			$remote_available_payment_types,
			function ( &$item ) {
				$item['to_map'] = $item['payment_type'] . '_' . $item['instalment'];
			}
		);

		$mappings = General_Helper::get_payment_type_to_service_mappings();

		$payment_methods = [];
		foreach ( $remote_available_payment_types as $index => $payment_type ) {
			if ( ! empty( $mappings[ $payment_type['to_map'] ] ) ) {
				$class_name = $mappings[ $payment_type['to_map'] ];
				$tmp_payment_method = new $class_name( $payment_type );
				$payment_methods[ $tmp_payment_method->id ] = $tmp_payment_method;
			}
		}

		return $payment_methods;
	}

	/**
	 * @param  array  $remote_payment_methods
	 *
	 * @return array
	 */
	protected function process_available_gateways( array $remote_payment_methods ): array {
		$available_gateways = $this->available_gateways;
		$tamara_default_gateway_key = Tamara_Checkout_WP_Plugin::DEFAULT_TAMARA_GATEWAY_ID;
		$tamara_default_gateway_offset = array_search( $tamara_default_gateway_key, array_keys( WC()->payment_gateways->payment_gateways() ) );
		$available_gateways = array_merge(
			array_slice( $available_gateways, 0, $tamara_default_gateway_offset ),
			$remote_payment_methods,
			array_slice( $available_gateways, $tamara_default_gateway_offset, null )
		);
		unset( $available_gateways[ $tamara_default_gateway_key ] );

		return $available_gateways;
	}
}
