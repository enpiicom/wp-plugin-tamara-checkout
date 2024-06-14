<?php

declare(strict_types=1);

namespace Enpii_Base\App\Support;

class Enpii_Base_Helper {
	public static $version_option;
	public static $setup_info;
	public static $wp_app_check = null;

	public static function get_current_url(): string {
		if ( empty( $_SERVER['SERVER_NAME'] ) && empty( $_SERVER['HTTP_HOST'] ) ) {
			return '';
		}

		if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
			$_SERVER['HTTPS'] = 'on';
		}

		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			$http_protocol = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
		}

		$current_url = $http_protocol ?? '';
		$current_url .= $current_url ? '://' : '//';

		if ( ! empty( $_SERVER['HTTP_HOST'] ) ) {
			$current_url .= sanitize_text_field( $_SERVER['HTTP_HOST'] ) . ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '' );

			return $current_url;
		}

		if ( isset( $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_PORT'] != '80' ) {
			$current_url .= sanitize_text_field( $_SERVER['SERVER_NAME'] ) . ':' . sanitize_text_field( $_SERVER['SERVER_PORT'] ) . ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '' );
		} else {
			$current_url .= sanitize_text_field( $_SERVER['SERVER_NAME'] ) . ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '' );
		}

		return $current_url;
	}

	public static function get_setup_app_uri( $full_url = false ): string {
		$uri = 'wp-app/setup-app/?force_app_running_in_console=1';

		return $full_url ? trim( site_url(), '/' ) . '/' . $uri : $uri;
	}

	public static function get_admin_setup_app_uri( $full_url = false ): string {
		$uri = 'wp-app/admin/setup-app/?force_app_running_in_console=1';

		return $full_url ? trim( site_url(), '/' ) . '/' . $uri : $uri;
	}

	public static function get_wp_login_url( $return_url = '', $force_reauth = false ): string {
		return wp_login_url( $return_url, $force_reauth );
	}

	public static function at_setup_app_url(): bool {
		$current_url = static::get_current_url();
		$setup_app_uri = static::get_setup_app_uri();

		return ( strpos( $current_url, $setup_app_uri ) !== false );
	}

	public static function at_admin_setup_app_url(): bool {
		$current_url = static::get_current_url();
		$redirect_uri = static::get_admin_setup_app_uri();

		return ( strpos( $current_url, $redirect_uri ) !== false );
	}

	public static function at_wp_login_url(): bool {
		$current_url = static::get_current_url();
		$login_url = wp_login_url();

		return ( strpos( $current_url, $login_url ) !== false );
	}

	public static function redirect_to_setup_url(): void {
		$redirect_uri = static::get_setup_app_uri();
		if ( ! static::at_setup_app_url() && ! static::at_admin_setup_app_url() ) {
			$redirect_url = add_query_arg(
				[
					'return_url' => urlencode( static::get_current_url() ),
				],
				site_url( $redirect_uri )
			);
			header( 'Location: ' . $redirect_url );
			exit( 0 );
		}
	}

	public static function get_base_url_path(): string {
		$site_url_parts = wp_parse_url( site_url() );

		return empty( $site_url_parts['path'] ) ? '' : $site_url_parts['path'];
	}

	public static function get_current_blog_path() {
		$site_url = site_url();
		$network_site_url = network_site_url();

		if ( $site_url === $network_site_url ) {
			return null;
		}

		$reverse_pos = strpos( strrev( $site_url ), strrev( $network_site_url ) );
		if ( $reverse_pos === false ) {
			return null;
		}

		return trim( substr( $site_url, $reverse_pos * ( -1 ) ), '/' );
	}

	public static function get_version_option() {
		if ( empty( static::$version_option ) ) {
			static::$version_option = (string) get_option( App_Const::OPTION_VERSION, '0.0.0' );
		}

		return static::$version_option;
	}

	public static function get_setup_info() {
		if ( empty( static::$setup_info ) ) {
			static::$setup_info = (string) get_option( App_Const::OPTION_SETUP_INFO );
		}

		return static::$setup_info;
	}

	public static function is_setup_app_completed() {
		// We have migration for session with db from '0.7.0'
		return apply_filters( 'enpii_base_is_setup_app_completed', version_compare( static::get_version_option(), '0.7.0', '>=' ) );
	}

	public static function is_setup_app_failed() {
		return static::get_setup_info() === 'failed';
	}

	/**
	 * We want to check if the wp_app setup has been done correctly
	 *  If the setup process failed, we should return false and raise the notice in the Admin
	 */
	public static function perform_wp_app_check(): bool {
		// We only want to perform the checking once
		if ( static::$wp_app_check !== null ) {
			return (bool) static::$wp_app_check;
		}

		if ( ! extension_loaded( 'pdo_mysql' ) ) {
			$error_message = sprintf(
				// translators: %1$s is replaced by a string, extension name
				__( 'Error with PHP extention %1$s. Please enable PHP extension %1$s via your hosting Control Panel or contact your hosting Admin for that.', 'enpii' ),
				'PDO MySQL'
			);
			static::add_wp_app_setup_errors( $error_message );
		}

		if ( empty( static::get_wp_app_setup_errors() ) && static::is_setup_app_completed() ) {
			static::$wp_app_check = apply_filters( App_Const::FILTER_WP_APP_CHECK, true );

			return static::$wp_app_check;
		}

		// We only want to check if it's not in the setup url
		if ( ! static::at_setup_app_url() && ! static::at_admin_setup_app_url() && static::is_setup_app_failed() ) {
			$error_message = sprintf(
				// translators: %1$s is replaced by a string, url
				__( 'The setup has not been done correctly. Please go to this URL <a href="%1$s">%1$s</a> to complete the setup', 'enpii' ),
				static::get_admin_setup_app_uri( true )
			);
			static::add_wp_app_setup_errors( $error_message );
		}

		if ( ! empty( $GLOBALS['wp_app_setup_errors'] ) ) {
			static::put_messages_to_wp_admin_notice( $GLOBALS['wp_app_setup_errors'] );
			static::$wp_app_check = apply_filters( App_Const::FILTER_WP_APP_CHECK, false );

			return static::$wp_app_check;
		}

		static::$wp_app_check = apply_filters( App_Const::FILTER_WP_APP_CHECK, true );

		return apply_filters( App_Const::FILTER_WP_APP_CHECK, true );
	}

	public static function put_messages_to_wp_admin_notice( array &$error_messages ): void {
		add_action(
			'admin_notices',
			function () use ( $error_messages ) {
				$error_content = '';
				foreach ( $error_messages as $error_message => $displayed ) {
					if ( ! $displayed && $error_message ) {
						$error_content .= '<p>' . $error_message . '</p>';
						$error_messages[ $error_message ] = true;
					}
				}
				if ( $error_content ) {
					echo '<div class="notice notice-error">' . wp_kses_post( $error_content ) . '</div>';
				}
			}
		);
	}

	public static function is_console_mode() {
		return ( (string) php_sapi_name() === 'cli' || (string) php_sapi_name() === 'phpdbg' || (string) php_sapi_name() === 'cli-server' );
	}

	public static function add_wp_app_setup_errors( $error_message ) {
		if ( ! isset( $GLOBALS['wp_app_setup_errors'] ) ) {
			$GLOBALS['wp_app_setup_errors'] = [];
		}

		if ( ! isset( $GLOBALS['wp_app_setup_errors'][ $error_message ] ) ) {
			$GLOBALS['wp_app_setup_errors'][ $error_message ] = false;
		}
	}

	public static function get_wp_app_setup_errors() {
		return isset( $GLOBALS['wp_app_setup_errors'] ) ? (array) $GLOBALS['wp_app_setup_errors'] : [];
	}

	public static function use_enpii_base_error_handler() {
		$use_error_handler = defined( 'ENPII_BASE_USE_ERROR_HANDLER' ) ? (bool) ENPII_BASE_USE_ERROR_HANDLER : ( getenv( 'ENPII_BASE_USE_ERROR_HANDLER' ) !== false ? (bool) getenv( 'ENPII_BASE_USE_ERROR_HANDLER' ) : false );

		return apply_filters( 'enpii_base_use_error_handler', $use_error_handler );
	}

	public static function use_blade_for_wp_template() {
		$blade_for_template = defined( 'ENPII_BASE_USE_BLADE_FOR_WP_TEMPLATE' ) ? (bool) ENPII_BASE_USE_BLADE_FOR_WP_TEMPLATE : ( getenv( 'ENPII_BASE_USE_BLADE_FOR_WP_TEMPLATE' ) !== false ? (bool) getenv( 'ENPII_BASE_USE_BLADE_FOR_WP_TEMPLATE' ) : false );

		return apply_filters( 'enpii_base_use_blade_for_wp_template', $blade_for_template );
	}

	public static function disable_web_worker() {
		$disable_web_worker = defined( 'ENPII_BASE_DISABLE_WEB_WORKER' ) ? (bool) ENPII_BASE_DISABLE_WEB_WORKER : ( getenv( 'ENPII_BASE_DISABLE_WEB_WORKER' ) !== false ? (bool) getenv( 'ENPII_BASE_DISABLE_WEB_WORKER' ) : false );

		return apply_filters( 'enpii_base_disable_web_worker', $disable_web_worker );
	}

	public static function get_wp_app_base_path() {
		if ( defined( 'ENPII_BASE_WP_APP_BASE_PATH' ) && ENPII_BASE_WP_APP_BASE_PATH ) {
			return ENPII_BASE_WP_APP_BASE_PATH;
		} else {
			return WP_CONTENT_DIR . DIR_SEP . 'uploads' . DIR_SEP . 'wp-app';
		}
	}

	public static function get_wp_app_base_folders_paths( string $wp_app_base_path ) {
		return [
			'base_path' => $wp_app_base_path,
			'config_path' => $wp_app_base_path . DIR_SEP . 'config',
			'database_path' => $wp_app_base_path . DIR_SEP . 'database',
			'database_migrations_path' => $wp_app_base_path . DIR_SEP . 'database' . DIR_SEP . 'migrations',
			'bootstrap_path' => $wp_app_base_path . DIR_SEP . 'bootstrap',
			'bootstrap_cache_path' => $wp_app_base_path . DIR_SEP . 'bootstrap' . DIR_SEP . 'cache',
			'lang_path' => $wp_app_base_path . DIR_SEP . 'lang',
			'resources_path' => $wp_app_base_path . DIR_SEP . 'resources',
			'storage_path' => $wp_app_base_path . DIR_SEP . 'storage',
			'storage_logs_path' => $wp_app_base_path . DIR_SEP . 'storage' . DIR_SEP . 'logs',
			'storage_framework_path' => $wp_app_base_path . DIR_SEP . 'storage' . DIR_SEP . 'framework',
			'storage_framework_views_path' => $wp_app_base_path . DIR_SEP . 'storage' . DIR_SEP . 'framework' . DIR_SEP . 'views',
			'storage_framework_cache_path' => $wp_app_base_path . DIR_SEP . 'storage' . DIR_SEP . 'framework' . DIR_SEP . 'cache',
			'storage_framework_cache_data_path' => $wp_app_base_path . DIR_SEP . 'storage' . DIR_SEP . 'framework' . DIR_SEP . 'cache' . DIR_SEP . 'data',
			'storage_framework_sessions_path' => $wp_app_base_path . DIR_SEP . 'storage' . DIR_SEP . 'framework' . DIR_SEP . 'sessions',
		];
	}

	/**
	 *
	 * @param string $wp_app_base_path
	 * @param int $chmod We may want to use `0755` if running this function in console
	 * @return void
	 */
	public static function prepare_wp_app_folders( $chmod = 0777, string $wp_app_base_path = '' ): void {
		if ( empty( $wp_app_base_path ) ) {
			$wp_app_base_path = static::get_wp_app_base_path();
		}
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.chmod_chmod, WordPress.PHP.NoSilencedErrors.Discouraged
		@chmod( dirname( $wp_app_base_path ), $chmod );

		$file_system = new \Illuminate\Filesystem\Filesystem();

		foreach ( static::get_wp_app_base_folders_paths( $wp_app_base_path ) as $filepath ) {
			$file_system->ensureDirectoryExists( $filepath, $chmod );
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.chmod_chmod, WordPress.PHP.NoSilencedErrors.Discouraged
			@chmod( $filepath, $chmod );
		}
	}

	public static function wp_cli_init(): void {
		\WP_CLI::add_command(
			'enpii-base prepare',
			[ self::class, 'wp_cli_prepare' ]
		);
	}

	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	public static function wp_cli_prepare( $args, $assoc_args ): void {
		static::prepare_wp_app_folders();
	}
}
