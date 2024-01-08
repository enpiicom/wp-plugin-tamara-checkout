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

// We want to split all the bootstrapping code to a separate file
// 	for putting into composer autoload and
// 	for easier including on other section e.g. unit test
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tamara-checkout-bootstrap.php';

if ( class_exists( \Enpii_Base\App\WP\WP_Application::class ) ) {
	// We register Tamara_Checkout_WP_Plugin as a Service Provider
	add_action( \Enpii_Base\App\Support\App_Const::ACTION_WP_APP_LOADED, function() {
		\Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin::init_with_wp_app(
			TAMARA_CHECKOUT_PLUGIN_SLUG,
			__DIR__,
			plugin_dir_url( __FILE__ )
		);
	});
} else {
	wp_admin_notice(
		sprintf(
			__( 'Plugin <strong>%s</strong> is disabled.', 'enpii' ),
			'Tamara Checkout'
		) . '<br />' .
		__( 'Enpii Base plugin does not exists, please install and activate Enpii Base plugin first', 'enpii' ),
		[
			'dismissible' => true,
			'type' => 'error',
		]
	);

	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	deactivate_plugins( plugin_basename( __FILE__ ) );
}
