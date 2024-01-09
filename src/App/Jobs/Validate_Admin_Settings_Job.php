<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Shared\Base_Job;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;
use Tamara_Checkout\Deps\Tamara\Request\Merchant\GetPublicConfigsRequest;
use WC_Payment_Gateway;

class Validate_Admin_Settings_Job extends Base_Job {
	use Dispatchable;
	use Tamara_Trans_Trait;

	protected $plugin;
	protected $processed_post_data;

	public function __construct( WC_Payment_Gateway $plugin ) {
		$this->plugin = $plugin;
	}

	public function handle() {
		/** @var \Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway $this_plugin */
		$this_plugin = $this->plugin;

		$post_data = $this_plugin->get_post_data();
		$field_prefix = $this_plugin->plugin_id . $this_plugin->id . '_';
		$this->processed_post_data = $this->process_post_data( $post_data );
		$processed_post_data = $this->processed_post_data;

		// We want to use Laravel validation here instead of using function mentioned in
		//  parent::get_field_value() to validate
		//  The outcome of this validation should be:
		//      - We need to have errors of the failed fields
		//      - We need to remove failed fields out of the $_POST data
		//      (should assign $this->data) to exclude them out of the save to settings
		/** @var \Illuminate\Validation\Validator $validator */
		$validator = Validator::make(
			$processed_post_data,
			[
				'sandbox_api_token' => [
					'required',
					function ( $attribute, $value, $fail_callback ) use ( $processed_post_data ) {
						$this->validate_api_token_attribute( $processed_post_data, $attribute, $value, $fail_callback );
					},
				],
				'live_api_token' => [
					'required',
					function ( $attribute, $value, $fail_callback ) use ( $processed_post_data ) {
						$this->validate_api_token_attribute( $processed_post_data, $attribute, $value, $fail_callback );
					},
				],
			],
			[
				'required' => sprintf( $this->_t( '%s: required.' ), ':attribute' ),
			],
			[
				'sandbox_api_token' => $this->_t( 'Sandbox API Token' ),
				'live_api_token' => $this->_t( 'Live API Token' ),
			]
		);

		if ( ! empty( $validator->errors() ) ) {
			$errors = $validator->errors()->toArray();
			foreach ( $errors as $error_field => $error_message ) {
				Session::flash( 'warning', $error_message );
				unset( $post_data[ $field_prefix . $error_field ] );
			}
		}

		$this_plugin->set_post_data( $post_data );
	}

	protected function process_post_data( $post_data ) {
		/** @var \Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway $this_plugin */
		$this_plugin = $this->plugin;

		$field_prefix = $this_plugin->plugin_id . $this_plugin->id . '_';
		$processed_post_data = $post_data;

		// We need to strip off the prefix of the field in the form
		array_walk(
			$post_data,
			function ( $value, $key ) use ( $field_prefix, &$processed_post_data ) {
				$new_key = str_replace( $field_prefix, '', $key );
				if ( ! isset( $processed_post_data[ $new_key ] ) ) {
					$processed_post_data[ $new_key ] = $value;
					unset( $processed_post_data[ $key ] );
				}
			}
		);

		return $processed_post_data;
	}

	/**
	 * @throws \Tamara_Checkout\Deps\Tamara\Exception\RequestDispatcherException
	 */
	protected function validate_api_token_attribute( array $processed_post_data, string $attribute, string $value, \Closure $fail_callback ) {
		if ( $attribute === 'live_api_token' ) {
			if ( $processed_post_data['environment'] === 'live_mode' ) {
				$api_url = $processed_post_data['live_api_url'];
			} else {
				// We simply don't do validation it the environment is not `sand_mode`
				//  if we are validatating 'sandbox_api_token'
				return true;
			}
		}

		if ( $attribute === 'sandbox_api_token' ) {
			if ( $processed_post_data['environment'] === 'sandbox_mode' ) {
				$api_url = $processed_post_data['sandbox_api_url'];
			} else {
				// We simply don't do validation it the environment is not `sand_mode`
				//  if we are validatating 'sandbox_api_token'
				return true;
			}
		}

		// We validate the attribute `sandbox_api_token` and `live_api_token`
		$api_token = $value;

		$tamara_checkout_plugin = Tamara_Checkout_WP_Plugin::wp_app_instance();
		$tamara_checkout_plugin->get_tamara_client_service()->init_tamara_client( $api_token, $api_url, $this->processed_post_data );

		$tamara_client_response = $tamara_checkout_plugin->get_tamara_client_service()->get_merchant_public_configs( new GetPublicConfigsRequest() );

		if (
			! is_object( $tamara_client_response )
		) {
			return $fail_callback( sprintf( $this->_t( '%s is incorrect.' ), ':attribute' ) );
		}

		return true;
	}
}
