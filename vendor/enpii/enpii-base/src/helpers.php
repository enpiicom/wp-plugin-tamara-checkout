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

if ( ! function_exists( 'enpii_base_wp_app_get_asset_url' ) ) {
	function enpii_base_wp_app_get_asset_url( $full_url = false ) {
		if ( defined( 'ENPII_BASE_WP_APP_ASSET_URL' ) && ENPII_BASE_WP_APP_ASSET_URL ) {
			return ENPII_BASE_WP_APP_ASSET_URL;
		}

		$slug_to_wp_app = str_replace( ABSPATH, '', enpii_base_wp_app_get_base_path() );
		$slug_to_public_asset = '/' . $slug_to_wp_app . '/public';

		return $full_url ? trim( get_site_url(), '/' ) . $slug_to_public_asset : $slug_to_public_asset;
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
		if ( ! Enpii_Base_Helper::is_setup_app_completed() ) {
			// We only want to redirect if the setup did not fail previously
			if ( ! Enpii_Base_Helper::is_setup_app_failed() ) {
				Enpii_Base_Helper::redirect_to_setup_url();
			}
		}
	}
}

if ( ! function_exists( 'enpii_base_wp_app_get_timezone' ) ) {
	/**
	 * Get the correct timezone value for WP App (from WordPress and map to the date_default_timezone_set ids)
	 * @return stringß
	 */
	function enpii_base_wp_app_get_timezone(): string {
		$current_offset = (int) get_option( 'gmt_offset' );
		$timezone_string = get_option( 'timezone_string' );

		// Remove old Etc mappings. Fallback to gmt_offset.
		if ( strpos( $timezone_string, 'Etc/GMT' ) !== false ) {
			$timezone_string = '';
		}

		// Create Etc/GMT time zone id that match date_default_timezone_set function
		//  https://www.php.net/manual/en/timezones.others.php
		if ( empty( $timezone_string ) ) {
			if ( (int) $current_offset === 0 ) {
				$timezone_string = 'Etc/GMT';
			} elseif ( $current_offset < 0 ) {
				$timezone_string = 'Etc/GMT+' . abs( $current_offset );
			} else {
				$timezone_string = 'Etc/GMT-' . abs( $current_offset );
			}
		}

		if ( function_exists( 'wp_timezone' ) ) {
			return strpos( wp_timezone()->getName(), '/' ) !== false ? wp_timezone()->getName() : $timezone_string;
		}

		return defined( 'WP_APP_TIMEZONE' ) ? WP_APP_TIMEZONE : $timezone_string;
	}
}
