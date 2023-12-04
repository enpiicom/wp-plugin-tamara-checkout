<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Bus\Dispatchable_Trait;
use Enpii_Base\Foundation\Shared\Base_Job;

class Show_Admin_Notice_And_Disable_Plugin_Job extends Base_Job {
	use Dispatchable_Trait;

	/**
	 * @var \Enpii_Base\Foundation\WP\WP_Plugin
	 */
	protected $plugin;

	public function __construct($plugin)
	{
		$this->plugin = $plugin;
	}

	public function handle() {
		if (!class_exists(WooCommerce::class)) {
			wp_admin_notice(
				sprintf(
					__('Plugin <strong>%s</strong> needs Woocommerce to be activated then it is disabled.', $this->plugin->get_text_domain()),
					$this->plugin->get_name(). ' ' . $this->plugin->get_version()),
					[
						'dismissible' => false,
						'type' => 'warning',
					]
			);

			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( $this->plugin->get_plugin_basename() );

			return;
		}
	}
}
