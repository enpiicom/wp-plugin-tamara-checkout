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
return;
// We want to split all the bootstrapping code to a separate file
// 	for putting into composer autoload and
// 	for easier including on other section e.g. unit test
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tamara-checkout-bootstrap.php';

// We register Enpii_Base plugin as a Service Provider
wp_app()->register_plugin(
	\Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin::class,
	TAMARA_CHECKOUT_PLUGIN_SLUG,
	__DIR__,
	plugin_dir_url( __FILE__ )
);
