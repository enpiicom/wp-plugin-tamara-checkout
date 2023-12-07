<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Bus\Dispatchable_Trait;
use Enpii_Base\Foundation\Shared\Base_Job;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use WC_Payment_Gateway;

class Validate_Admin_Settings_Job extends Base_Job {
	use Dispatchable_Trait;

	protected $plugin;

	public function __construct(WC_Payment_Gateway $plugin)
	{
		$this->plugin = $plugin;
	}

	public function handle()
	{
		/** @var \Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway $this_plugin */
		$this_plugin = $this->plugin;

		$post_data = $this_plugin->get_post_data();
		$field_prefix = $this_plugin->plugin_id.$this_plugin->id.'_';
		$processed_post_data = $this->process_post_data($post_data);

		// We want to use Laravel validation here instead of using function mentioned in
		//	parent::get_field_value() to validate
		//	The outcome of this validation should be:
		//		- We need to have errors of the failed fields
		//		- We need to remove failed fields out of the $_POST data
		//		(should assign $this->data) to exclude them out of the save to settings
		/** @var \Illuminate\Validation\Validator $validator */
		$validator = Validator::make(
			$processed_post_data,
			[
				'sandbox_api_token' => [
					'required',
					function ($attribute, $value, $fail_callback) use ($processed_post_data, $this_plugin) {
						if ($processed_post_data['environment'] === 'sandbox_mode' && $value !== 'sandbox') {
							return $fail_callback(sprintf($this_plugin->_t('%s is incorrect.'), $attribute));
						}
					},
				],
			],
			[
				'required' => $this_plugin->_t(':attribute is a required value.'),
			],
			[
				'sandbox_api_token' => $this_plugin->_t('Sandbox API Token'),
			]
		);

		if (!empty($validator->errors())) {
			// die(' errors ');
			$errors = $validator->errors()->toArray();
			foreach ($errors as $error_field => $error_message) {
				Session::flash('warning', $error_message);
				unset($post_data[$field_prefix.$error_field]);
			}
		}

		$this_plugin->set_post_data($post_data);
	}

	protected function process_post_data($post_data) {
		/** @var \Tamara_Checkout\App\WP\Payment_Gateways\Tamara_WC_Payment_Gateway $this_plugin */
		$this_plugin = $this->plugin;

		$field_prefix = $this_plugin->plugin_id.$this_plugin->id.'_';
		$processed_post_data = $post_data;

		// We need to strip off the prefix of the field in the form
		array_walk($post_data, function($value, &$key) use ($field_prefix, &$processed_post_data) {
			$new_key = str_replace($field_prefix, '', $key);
			if (!isset($processed_post_data[$new_key])) {
				$processed_post_data[$new_key] = $value;
				unset($processed_post_data[$key]);
			}
		});

		return $processed_post_data;
	}
}
