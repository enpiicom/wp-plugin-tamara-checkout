<?php
/**
 * Plugin Name: Tamara Checkout
 * Plugin URI:  https://tamara.co/
 * Description: Allow to Buy Now Pay Later with Tamara payment gateway, based on Enpii Base
 * Author:      dev@tamara.co
 * Author URI:  https://tamara.co/
 * Version:     2.0.0
 * Text Domain: tamara
 */

use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;

// Update these constants whenever you bump the version
//	We put this constant here for the convenience when bump the version
defined( 'TAMARA_CHECKOUT_VERSION' ) || define( 'TAMARA_CHECKOUT_VERSION', '2.0.0' );

// We want to split all the bootstrapping code to a separate file
// 	for putting into composer autoload and
// 	for easier including on other section e.g. unit test
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tamara-checkout-bootstrap.php';

// We need to put this to the plugins loaded to have other plugins ready
add_action( 'plugins_loaded', function() {
	if ( \Tamara_Checkout\App\Support\Tamara_Checkout_Helper::check_mandatory_prerequisites() ) {
		// We register Tamara_Checkout_WP_Plugin as a Service Provider
		add_action( \Enpii_Base\App\Support\App_Const::ACTION_WP_APP_LOADED, function() {
			\Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin::init_with_wp_app(
				TAMARA_CHECKOUT_PLUGIN_SLUG,
				__DIR__,
				plugin_dir_url( __FILE__ )
			);
		});
	}
} );

// It's better to check the prerequisites using the `plugins_loaded`
//	rather than the activation hook because there is a case where this plugin is already
//	enabled but then the mandatory prerequisites are disabled after
// We need to use the hook `plugins_loaded` here rather than put to the WP Plugin class
//	because there is the posibility Enpii Base or WooCommerce not loaded
//	and the WP Plugin use the resources from these 2 therefore it may produce errrors
add_action( 'plugins_loaded', function() {
	$error_message = '';
	if (! Tamara_Checkout_Helper::check_enpii_base_plugin()) {
		$error_message .= $error_message ? '<br />' : '';
		$error_message .= sprintf( __( 'Plugin <strong>%s</strong> is required.', \Tamara_Checkout\App\Support\Tamara_Checkout_Helper::TEXT_DOMAIN ), 'Enpii Base');
	}

	if (! Tamara_Checkout_Helper::check_woocommerce_plugin()) {
		$error_message .= $error_message ? '<br />' : '';
		$error_message .= sprintf( __( 'Plugin <strong>%s</strong> is required.', \Tamara_Checkout\App\Support\Tamara_Checkout_Helper::TEXT_DOMAIN ), 'WooCommerce');
	}

	if ($error_message) {
		wp_admin_notice(
			sprintf(
				__( 'Plugin <strong>%s</strong> is disabled.', \Tamara_Checkout\App\Support\Tamara_Checkout_Helper::TEXT_DOMAIN ),
				'Tamara Checkout'
			) . '<br />' . $error_message,
			[
				'dismissible' => true,
				'type' => 'error',
			]
		);
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}
} );
