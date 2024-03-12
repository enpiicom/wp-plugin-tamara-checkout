<?php
// We only want to initiate the wp_app() when the WP loaded
//	When we use the composer to load the plugin, this file may be loaded
//	with the composer autoload before the WP loaded

if (defined( 'WP_CONTENT_DIR' )) {
	add_action( 'cli_init', 'enpii_base_prepare' );

	if (! enpii_base_wp_app_check() ) {
		// We do nothing but still keep the plugin enabled
		return;
	}

	if ( ! class_exists('WP_CLI') ) {
		// We want to redirect to setup app before the WP App init
		add_action( ENPII_BASE_SETUP_HOOK_NAME, 'enpii_base_maybe_redirect_to_setup_app', -200 );
	}

	add_action( \Enpii_Base\App\Support\App_Const::ACTION_WP_APP_LOADED, function() {
		\Enpii_Base\App\WP\Enpii_Base_WP_Plugin::init_with_wp_app(
			ENPII_BASE_PLUGIN_SLUG,
			__DIR__,
			plugin_dir_url( __FILE__ )
		);
	});

	// We init wp_app() here
	//	then initialization in `enpii-base-init.php` file is for non-plugin mode
	\Enpii_Base\App\WP\WP_Application::load_instance();
}
