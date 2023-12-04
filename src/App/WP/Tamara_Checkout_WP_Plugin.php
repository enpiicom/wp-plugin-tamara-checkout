<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\WP;

use Enpii_Base\Foundation\WP\WP_Plugin;
use Tamara_Checkout\App\Jobs\Show_Admin_Notice_And_Disable_Plugin_Job;

/**
 * @inheritDoc
 * @package Tamara_Checkout\App\WP
 */
class Tamara_Checkout_WP_Plugin extends WP_Plugin {
	public const TEXT_DOMAIN = 'tamara';

	public function manipulate_hooks(): void
	{
		add_action( 'plugins_loaded', [$this, 'check_prerequisites'] );
	}

	public function get_name(): string
	{
		return 'Tamara Checkout';
	}

	public function get_version(): string
	{
		return TAMARA_CHECKOUT_VERSION;
	}

	public function get_text_domain(): string
	{
		return static::TEXT_DOMAIN;
	}

	/**
     * We want to check the needed dependency for this plugin to work
     */
    public function check_prerequisites()
    {
		if (!class_exists(\WooCommerce::class)) {
			Show_Admin_Notice_And_Disable_Plugin_Job::dispatchSync($this);

			return;
		}
    }
}
