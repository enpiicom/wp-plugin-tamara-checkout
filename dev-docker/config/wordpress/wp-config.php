<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings ** //
/** The name of the database for WordPress */
define( 'DB_NAME', getenv( 'DB_NAME' ) );

/** MySQL database username */
define( 'DB_USER', getenv( 'DB_USER' ) );

/** MySQL database password */
define( 'DB_PASSWORD', getenv( 'DB_PASSWORD' ) );

/** MySQL hostname */
define( 'DB_HOST', getenv( 'DB_HOST' ) );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

define( 'DB_TABLE_PREFIX', getenv( 'DB_TABLE_PREFIX' ) ? getenv( 'DB_TABLE_PREFIX' ) : 'wp_' );

// Extra db params
if ( getenv( 'DB_PORT' ) ) {
	define( 'DB_PORT', getenv( 'DB_PORT' ) );
}

if ( getenv( 'DB_SOCKET' ) && ! DB_HOST ) {
	define( 'DB_SOCKET', getenv( 'DB_SOCKET' ) );
}

if ( getenv( 'DB_STRICT_MODE' ) ) {
	define( 'DB_STRICT_MODE', getenv( 'DB_STRICT_MODE' ) );
}

if ( getenv( 'DB_ENGINE' ) ) {
	define( 'DB_ENGINE', getenv( 'DB_ENGINE' ) );
}

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY', getenv( 'AUTH_KEY' ) ?: hash( 'sha256', md5( php_uname( 'n' ).'1' ) ) );
define( 'SECURE_AUTH_KEY', getenv( 'SECURE_AUTH_KEY' ) ?: hash( 'sha256', md5( php_uname( 'n' ).'2' ) ) );
define( 'LOGGED_IN_KEY', getenv( 'LOGGED_IN_KEY' ) ?: hash( 'sha256', md5( php_uname( 'n' ).'3' ) ) );
define( 'NONCE_KEY', getenv( 'NONCE_KEY' ) ?: hash( 'sha256', md5( php_uname( 'n' ).'4' ) ) );
define( 'AUTH_SALT', getenv( 'AUTH_SALT' ) ?: hash( 'sha256', md5( php_uname( 'n' ).'5' ) ) );
define( 'SECURE_AUTH_SALT', getenv( 'SECURE_AUTH_SALT' ) ?: hash( 'sha256', md5( php_uname( 'n' ).'6' ) ) );
define( 'LOGGED_IN_SALT', getenv( 'LOGGED_IN_SALT' ) ?: hash( 'sha256', md5( php_uname( 'n' ).'7' ) ) );
define( 'NONCE_SALT', getenv( 'NONCE_SALT' ) ?: hash( 'sha256', md5( php_uname( 'n' ).'8' ) ) );
define( 'WP_CACHE_KEY_SALT', getenv( 'WP_CACHE_KEY_SALT' ) ?: hash( 'sha256', md5( php_uname( 'n' ).'9' ) ) );

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = DB_TABLE_PREFIX;

/* That's all, stop editing! Happy blogging. */
define( 'WP_ENV', getenv( 'WP_ENV' ) );
define( 'WP_DEBUG', isset($debug_override) ? $debug_override : ! ! getenv( 'WP_DEBUG' ) );
define( 'WP_DEBUG_DISPLAY', ! ! getenv( 'WP_DEBUG_DISPLAY' )  );
define( 'WP_DEBUG_LOG', ( getenv( 'WP_DEBUG_LOG' ) ? getenv( 'WP_DEBUG_LOG' ) : 1 ) ); // set to 'true' or 1 means the default debug.log file would be wp-content/debug.log

define( 'SAVEQUERIES', ! ! getenv( 'SAVEQUERIES' ) );

// For Multisite
// https://wordpress.org/documentation/article/nginx/
define( 'WP_ALLOW_MULTISITE', isset($_ENV['WP_ALLOW_MULTISITE']) ? ! ! getenv( 'WP_ALLOW_MULTISITE' ) : false );
define( 'MULTISITE', isset($_ENV['MULTISITE']) ? ! ! getenv( 'MULTISITE' ) : false );
define( 'SUBDOMAIN_INSTALL', isset($_ENV['SUBDOMAIN_INSTALL']) ? ! ! getenv( 'SUBDOMAIN_INSTALL' ) : false );
define( 'DOMAIN_CURRENT_SITE', isset($_ENV['DOMAIN_CURRENT_SITE']) ? getenv( 'DOMAIN_CURRENT_SITE' ) : '' );
define( 'PATH_CURRENT_SITE', isset($_ENV['PATH_CURRENT_SITE']) ? getenv( 'PATH_CURRENT_SITE' ) : '/' );
define( 'SITE_ID_CURRENT_SITE', isset($_ENV['SITE_ID_CURRENT_SITE']) ? (int) getenv( 'SITE_ID_CURRENT_SITE' ) : 1 );
define( 'BLOG_ID_CURRENT_SITE', isset($_ENV['BLOG_ID_CURRENT_SITE']) ? (int) getenv( 'BLOG_ID_CURRENT_SITE' ) : 1 );

// Important on using different domain
// The domain structure should be: a domain for the main site of the network and sub-domains for the sub-site or other domains for the sub site e.g.:
//	- demo.dev-srv.net for the main site
//  - sub1.demo.dev-srv.net sub2.demo.dev-srv.net ... for the sub sites
//	- or abc.com, xyz.dev ... for the sub sites, this time, the cookie domain
//		should be set to that domain
if (MULTISITE) {
	$current_domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
	if ($current_domain and strpos($current_domain, DOMAIN_CURRENT_SITE) === false) {
		define( 'COOKIE_DOMAIN', $current_domain );
	}
}

// ## Below snippets are for installing plugins, themes from the Admin Dashboard
// define( 'FS_METHOD', 'direct' );
// define( 'FS_CHMOD_DIR', (0755 & ~ umask()) );
// define( 'FS_CHMOD_FILE', (0664 & ~ umask()) );

// For https
// If we're behind a proxy server and using HTTPS, we need to alert WordPress of that fact
// see also http://codex.wordpress.org/Administration_Over_SSL#Using_a_Reverse_Proxy
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
	$_SERVER['HTTPS'] = 'on';
}

if ( isset( $_SERVER['HTTP_HOST'] ) ) {
	$http_protocol = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http';
	define( 'WP_HOME', $http_protocol . '://' . $_SERVER['HTTP_HOST'] );
	define( 'WP_SITEURL', $http_protocol . '://' . $_SERVER['HTTP_HOST'] );
}

// For WP App
define( 'ENPII_BASE_WP_APP_BASE_PATH', getenv( 'ENPII_BASE_WP_APP_BASE_PATH' ) ?: '' );
define( 'WP_APP_TELESCOPE_ENABLED', ! ! getenv( 'WP_APP_TELESCOPE_ENABLED' ) );
define( 'WP_APP_TINKER_ENABLED', ! ! getenv( 'WP_APP_TINKER_ENABLED' ) );
define( 'WP_APP_PASSPORT_ENABLED', ! ! getenv( 'WP_APP_PASSPORT_ENABLED' ) );
define( 'ARTISAN_BINARY', isset($_ENV['ARTISAN_BINARY']) ? (string) getenv( 'ARTISAN_BINARY' ) : 'artisan' );

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
