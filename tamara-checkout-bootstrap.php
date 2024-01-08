<?php
$plugin_existed = defined( 'TAMARA_CHECKOUT_VERSION' );

// Update these constants whenever you bump the version
defined( 'TAMARA_CHECKOUT_VERSION' ) || define( 'TAMARA_CHECKOUT_VERSION', '2.0.0' );

// We set the slug for the plugin here.
// This slug will be used to identify the plugin instance from the WP_Application container
defined( 'TAMARA_CHECKOUT_PLUGIN_SLUG' ) || define( 'TAMARA_CHECKOUT_PLUGIN_SLUG', 'tamara-checkout' );

$autoload_file = __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if ( file_exists( $autoload_file ) && !$plugin_existed ) {
	require_once $autoload_file;
}
