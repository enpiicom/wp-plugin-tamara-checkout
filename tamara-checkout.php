<?php
/**
 * Plugin Name: Tamara Checkout - Alpha
 * Plugin URI:  https://tamara.co/
 * Description: Allow to Buy Now Pay Later with Tamara payment gateway
 * Author:      dev@tamara.co
 * Author URI:  https://tamara.co/
 * Version:     2.0.0-alpha
 * Text Domain: tamara
 */

use Enpii_Base\App\Support\App_Const;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;

// Update these constants whenever you bump the version
//  We put this constant here for the convenience when bump the version
defined( 'TAMARA_CHECKOUT_VERSION' ) || define( 'TAMARA_CHECKOUT_VERSION', '2.0.0' );

// We want to split all the bootstrapping code to a separate file
//  for putting into composer autoload and
//  for easier including on other section e.g. unit test
require_once __DIR__ . DIRECTORY_SEPARATOR . 'tamara-checkout-bootstrap.php';

/**
| We need to check the plugin mandatory requirements first
 */
// It's better to check the prerequisites using the `plugins_loaded`, low priority,
//  rather than the activation hook because there is a case where this plugin is already
//  enabled but then the mandatory prerequisites are disabled after
// We need to use the hook `plugins_loaded` here rather than put to the WP Plugin class
//  because there is the posibility Enpii Base or WooCommerce not loaded
//  and the WP Plugin use the resources from these 2 therefore it may produce errrors
add_action(
	'plugins_loaded',
	function () {
		$error_message = '';
		$plugin_slug = plugin_basename( __DIR__ );
		if ( $plugin_slug !== TAMARA_CHECKOUT_PLUGIN_SLUG ) {
			$error_message .= $error_message ? '<br />' : '';
			// translators: %1$s is the plugin name, %2$s is the plugin slug
			$error_message .= sprintf( __( 'Plugin <strong>%1$s</strong> folder name must be %2$s.', 'tamara' ), 'Tamara Checkout', TAMARA_CHECKOUT_PLUGIN_SLUG );
		}

		if ( ! Tamara_Checkout_Helper::check_enpii_base_plugin() ) {
			$error_message .= $error_message ? '<br />' : '';
			// translators: %s is for the name of the plugin
			$error_message .= sprintf( __( 'Plugin <strong>%s</strong> is required.', 'tamara' ), 'Enpii Base' );
		}

		if ( ! Tamara_Checkout_Helper::check_woocommerce_plugin() ) {
			$error_message .= $error_message ? '<br />' : '';
			// translators: %s is for the name of the plugin
			$error_message .= sprintf( __( 'Plugin <strong>%s</strong> is required.', 'tamara' ), 'WooCommerce' );
		}

		if ( $error_message ) {
			add_action(
				'admin_notices',
				function () use ( $error_message ) {
					$error_message = sprintf(
						// translators: %s is for the name of the plugin
						__( 'Plugin <strong>%s</strong> is disabled.', 'tamara' ),
						'Tamara Checkout'
					) . '<br />' . $error_message;

					?>
					<div class="notice notice-warning is-dismissible">
						<p><?php echo wp_kses_post( $error_message ); ?></p>
					</div>
					<?php
				}
			);
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}

		/**
		| We initiate the plugin later
		*/
		if ( Tamara_Checkout_Helper::check_mandatory_prerequisites() ) {
			// We register Tamara_Checkout_WP_Plugin as a Service Provider
			add_action(
				App_Const::ACTION_WP_APP_LOADED,
				function () {
					\Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin::init_with_wp_app(
						TAMARA_CHECKOUT_PLUGIN_SLUG,
						__DIR__,
						plugin_dir_url( __FILE__ )
					);
				}
			);
		}
	},
	-111
);
