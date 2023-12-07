<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP\Payment_Gateways\Contracts;

/**
 * Base payment gateway method for Tamara
 * @package Tamara_Checkout\App\WP
 */
interface Tamara_Payment_Gateway_Contract {
	/**
	 * Get payment type for a Tamara Payment Gateway
	 * @return string
	 */
	public function get_payment_type(): string;

	/**
	 * We need to iniitialize the Admin forms field by using this method, it has the parent one
	 * @return void
	 */
	public function init_form_fields(): void;

	/**
	 * We need this method to process the Admin options, it has the parent one
	 * This method will process the post data, sanitize, validate fields (if proper method exists)
	 * Then save all settings values to the database
	 *
	 * @return void
	 */
	public function process_admin_options(): void;
}
