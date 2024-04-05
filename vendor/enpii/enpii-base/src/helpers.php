<?php

declare(strict_types=1);

use Enpii_Base\App\Jobs\Mark_Setup_WP_App_Done;
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
		$version_option = get_option( App_Const::OPTION_VERSION );
		if ( ( empty( $version_option ) ) ) {
			// We only want to redirect if the setup did not fail previously
			if ( ! enpii_base_wp_app_setup_failed() ) {
				Enpii_Base_Helper::redirect_to_setup_url();
			}
		}
	}
}

if ( ! function_exists( 'enpii_base_wp_app_check' ) ) {
	/**
	 * Check the mandatory prerequisites for the WP App
	 * @return bool
	 */
	function enpii_base_wp_app_check(): bool {
		if ( ! isset( $GLOBALS['wp_app_setup_errors'] ) ) {
			$GLOBALS['wp_app_setup_errors'] = [];
		}
		$wp_app_base_path = enpii_base_wp_app_get_base_path();
		if ( ! file_exists( $wp_app_base_path ) ) {
			enpii_base_wp_app_prepare_folders();

			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writable
			if ( ! is_writable( dirname( $wp_app_base_path ) ) ) {
				$error_message = sprintf(
					// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain, WordPress.WP.I18n.MissingTranslatorsComment
					__( 'Folder <strong>%s</strong> must be writable, please make it 0777.', Enpii_Base_Helper::TEXT_DOMAIN ),
					dirname( $wp_app_base_path )
				);
				if ( ! isset( $GLOBALS['wp_app_setup_errors'][ $error_message ] ) ) {
					$GLOBALS['wp_app_setup_errors'][ $error_message ] = false;
				}
			}
		}

		if ( enpii_base_wp_app_setup_failed() && ! Enpii_Base_Helper::at_setup_app_url() && ! Enpii_Base_Helper::at_admin_setup_app_url() ) {
			$error_message = sprintf(
				// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain, WordPress.WP.I18n.MissingTranslatorsComment
				__( 'The setup has not been done correctly. Please go to this URL <a href="%1$s">%1$s</a> to complete the setup', Enpii_Base_Helper::TEXT_DOMAIN ),
				Enpii_Base_Helper::get_admin_setup_app_uri( true )
			);
			if ( ! isset( $GLOBALS['wp_app_setup_errors'][ $error_message ] ) ) {
				$GLOBALS['wp_app_setup_errors'][ $error_message ] = false;
			}
		}

		if ( ! empty( $GLOBALS['wp_app_setup_errors'] ) ) {
			add_action(
				'admin_notices',
				function () {
					$error_content = '';
					foreach ( (array) $GLOBALS['wp_app_setup_errors'] as $error_message => $displayed ) {
						if ( ! $displayed && $error_message ) {
							$error_content .= '<p>' . $error_message . '</p>';
							$GLOBALS['wp_app_setup_errors'][ $error_message ] = true;
						}
					}
					if ( $error_content ) {
						echo '<div class="notice notice-error">' . wp_kses_post( $error_content ) . '</div>';
					}
				}
			);

			return apply_filters( App_Const::FILTER_WP_APP_CHECK, false );
		}

		return apply_filters( App_Const::FILTER_WP_APP_CHECK, true );
	}
}

if ( ! function_exists( 'enpii_base_wp_app_setup_failed' ) ) {
	/**
	 * Check the mandatory prerequisites for the WP App
	 * @return bool
	 */
	function enpii_base_wp_app_setup_failed(): bool {
		return (string) get_option( App_Const::OPTION_SETUP_INFO ) === 'failed';
	}
}

if ( ! function_exists( 'enpii_base_wp_app_get_timezone' ) ) {
	/**
	 * Get the correct timezone value for WP App (from WordPress and map to the date_default_timezone_set ids)
	 * @return string
	 */
	function enpii_base_wp_app_get_timezone(): string {
		$current_offset = (int) get_option( 'gmt_offset' );
		$timezone_string = get_option( 'timezone_string' );

		// Remove old Etc mappings. Fallback to gmt_offset.
		if ( false !== strpos( $timezone_string, 'Etc/GMT' ) ) {
			$timezone_string = '';
		}

		// Create Etc/GMT time zone id that match date_default_timezone_set function
		//	https://www.php.net/manual/en/timezones.others.php
		if ( empty( $timezone_string ) ) {
			if ( 0 == $current_offset ) {
				$timezone_string = 'Etc/GMT';
			} elseif ( $current_offset < 0 ) {
				$timezone_string = 'Etc/GMT+' . abs( $current_offset );
			} else {
				$timezone_string = 'Etc/GMT-' . abs( $current_offset );
			}
		}

		if ( function_exists('wp_timezone') ) {
			return false !== strpos( wp_timezone()->getName(), '/' ) ? wp_timezone()->getName() : $timezone_string;
		}

		return defined( 'WP_APP_TIMEZONE' ) ? WP_APP_TIMEZONE : $timezone_string;
	}
}
