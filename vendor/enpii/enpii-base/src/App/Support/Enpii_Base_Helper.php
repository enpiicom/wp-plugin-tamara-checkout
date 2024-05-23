<?php

declare(strict_types=1);

namespace Enpii_Base\App\Support;

class Enpii_Base_Helper {
	public static $version_option;

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

		$current_url = $http_protocol;
		$current_url .= '://';

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
		$uri = 'wp-app/setup-app?force_app_running_in_console=1';

		return $full_url ? home_url() . '/' . $uri : $uri;
	}

	public static function get_admin_setup_app_uri( $full_url = false ): string {
		$uri = 'wp-app/admin/setup-app?force_app_running_in_console=1';

		return $full_url ? home_url() . '/' . $uri : $uri;
	}

	public static function get_wp_login_url( $return_url = '', $force_reauth = false ): string {
		return wp_login_url( $return_url, $force_reauth );
	}

	public static function at_setup_app_url(): bool {
		$current_url = static::get_current_url();
		$redirect_uri = static::get_setup_app_uri();

		return ( strpos( $current_url, $redirect_uri ) !== false );
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
			return false;
		}

		$reverse_pos = strpos( strrev( $site_url ), strrev( $network_site_url ) );
		if ( $reverse_pos === false ) {
			return false;
		}

		return trim( substr( $site_url, $reverse_pos * ( -1 ) ), '/' );
	}

	public static function is_setup_app_completed() {
		if ( empty( static::$version_option ) ) {
			static::$version_option = (string) get_option( App_Const::OPTION_VERSION, '0.0.0' );
		}

		// We have migration for session with db from '0.6.3'
		return version_compare( static::$version_option, '0.6.3', '>=' );
	}
}
