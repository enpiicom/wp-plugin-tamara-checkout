<?php

declare(strict_types=1);

use Enpii_Base\App\Support\App_Const;
use Enpii_Base\App\Support\Enpii_Base_Helper;

if ( ! function_exists( 'enpii_base_is_console_mode' ) ) {
	function enpii_base_is_console_mode(): bool {
		return ( \PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg' );
	}
}

if ( ! function_exists( 'enpii_base_get_major_version' ) ) {
	function enpii_base_get_major_version( $version ): int {
		$parts = explode( '.', $version );
		return (int) filter_var( $parts[0], FILTER_SANITIZE_NUMBER_INT );
	}
}

if ( ! function_exists( 'enpii_base_wp_app_prepare_folders' ) ) {
	/**
	 *
	 * @param string|null $wp_app_base_path
	 * @param int $chmod We may want to use `0755` if running this function in console
	 * @return void
	 */
	function enpii_base_wp_app_prepare_folders( $chmod = 0777, string $wp_app_base_path = null ): void {
		if ( empty( $wp_app_base_path ) ) {
			$wp_app_base_path = enpii_base_wp_app_get_base_path();
		}
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.chmod_chmod, WordPress.PHP.NoSilencedErrors.Discouraged
		@chmod( dirname( $wp_app_base_path ), $chmod );

		$file_system = new \Illuminate\Filesystem\Filesystem();

		$filepaths = [
			$wp_app_base_path,
			$wp_app_base_path . DIR_SEP . 'config',
			$wp_app_base_path . DIR_SEP . 'database',
			$wp_app_base_path . DIR_SEP . 'database' . DIR_SEP . 'migrations',
			$wp_app_base_path . DIR_SEP . 'bootstrap',
			$wp_app_base_path . DIR_SEP . 'bootstrap' . DIR_SEP . 'cache',
			$wp_app_base_path . DIR_SEP . 'lang',
			$wp_app_base_path . DIR_SEP . 'resources',
			$wp_app_base_path . DIR_SEP . 'storage',
			$wp_app_base_path . DIR_SEP . 'storage' . DIR_SEP . 'logs',
			$wp_app_base_path . DIR_SEP . 'storage' . DIR_SEP . 'framework',
			$wp_app_base_path . DIR_SEP . 'storage' . DIR_SEP . 'framework' . DIR_SEP . 'views',
			$wp_app_base_path . DIR_SEP . 'storage' . DIR_SEP . 'framework' . DIR_SEP . 'cache',
			$wp_app_base_path . DIR_SEP . 'storage' . DIR_SEP . 'framework' . DIR_SEP . 'cache' . DIR_SEP . 'data',
			$wp_app_base_path . DIR_SEP . 'storage' . DIR_SEP . 'framework' . DIR_SEP . 'sessions',
		];
		foreach ( $filepaths as $filepath ) {
			$file_system->ensureDirectoryExists( $filepath, $chmod );
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.chmod_chmod, WordPress.PHP.NoSilencedErrors.Discouraged
			@chmod( $filepath, $chmod );
		}
	}
}

if ( ! function_exists( 'enpii_base_wp_app_get_base_path' ) ) {
	function enpii_base_wp_app_get_base_path() {
		if ( defined( 'ENPII_BASE_WP_APP_BASE_PATH' ) && ENPII_BASE_WP_APP_BASE_PATH ) {
			return ENPII_BASE_WP_APP_BASE_PATH;
		} else {
			return WP_CONTENT_DIR . DIR_SEP . 'uploads' . DIR_SEP . 'wp-app';
		}
	}
}

if ( ! function_exists( 'enpii_base_wp_app_web_page_title' ) ) {
	function enpii_base_wp_app_web_page_title() {
		$title = empty( wp_title( '', false ) ) ? get_bloginfo( 'name' ) . ' | ' . ( get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : 'WP App' ) : wp_title( '', false );

		return apply_filters( App_Const::FILTER_WP_APP_WEB_PAGE_TITLE, $title );
	}
}

if ( ! function_exists( 'enpii_base_prepare' ) ) {
	function enpii_base_prepare() {
		WP_CLI::add_command(
			'enpii-base prepare',
			function () {
				enpii_base_wp_app_prepare_folders();
			}
		);
	}
}

if ( ! function_exists( 'enpii_base_maybe_redirect_to_setup_app' ) ) {
	/**
	 * Check the flag in the options to redirect to setup page if needed
	 * @return bool
	 */
	function enpii_base_maybe_redirect_to_setup_app(): void {
		$version_in_opton = get_option( Enpii_Base_Helper::VERSION_OPTION_FIELD );

		if ( ( empty( $version_in_opton ) ) ) {
			Enpii_Base_Helper::redirect_to_setup_url();
		}
	}
}

if ( ! function_exists( 'enpii_base_wp_app_check' ) ) {
	/**
	 * Check the mandatory prerequisites for the WP App
	 * @return bool
	 */
	function enpii_base_wp_app_check(): bool {
		$error_message = '';
		$wp_app_base_path = enpii_base_wp_app_get_base_path();
		if ( ! file_exists( $wp_app_base_path ) ) {
			enpii_base_wp_app_prepare_folders();

			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writable
			if ( ! is_writable( dirname( $wp_app_base_path ) ) ) {
				$error_message .= sprintf(
					// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain, WordPress.WP.I18n.MissingTranslatorsComment
					__( 'Folder <strong>%s</strong> must be writable, please make it 0777.', Enpii_Base_Helper::TEXT_DOMAIN ),
					dirname( $wp_app_base_path )
				);
			}
		}

		if ( $error_message ) {
			add_action(
				'admin_notices',
				function () use ( $error_message ) {
					?>
					<div class="notice notice-error">
						<p><?php echo wp_kses_post( $error_message ); ?></p>
					</div>
					<?php
				}
			);

			return false;
		}

		return true;
	}
}
