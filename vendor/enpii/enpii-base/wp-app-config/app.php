<?php

return [
	/**
	|--------------------------------------------------------------------------
	| Application Name
	|--------------------------------------------------------------------------
	|
	| This value is the name of your application. This value is used when the
	| framework needs to place the application's name in a notification or
	| any other location as required by the application or its packages.
	|
	*/

	'name' => defined( 'WP_APP_NAME' ) ? WP_APP_NAME : 'Enpii Base Web App',

	/**
	|--------------------------------------------------------------------------
	| Application Environment
	|--------------------------------------------------------------------------
	|
	| This value determines the "environment" your application is currently
	| running in. This may determine how you prefer to configure various
	| services the application utilizes. Set this in your ".env" file.
	|
	*/

	'env' => defined( 'WP_ENV' ) ? WP_ENV : 'production',

	/**
	|--------------------------------------------------------------------------
	| Application Debug Mode
	|--------------------------------------------------------------------------
	|
	| When your application is in debug mode, detailed error messages with
	| stack traces will be shown on every error that occurs within your
	| application. If disabled, a simple generic error page is shown.
	|
	*/

	'debug' => defined( 'WP_DEBUG' ) ? WP_DEBUG : false,

	/**
	|--------------------------------------------------------------------------
	| Application URL
	|--------------------------------------------------------------------------
	|
	| This URL is used by the console to properly generate URLs when using
	| the Artisan command line tool. You should set this to the root of
	| your application so that it is used when running Artisan tasks.
	|
	*/

	'url' => defined( 'WP_SITEURL' ) ? WP_SITEURL : site_url(),

	'asset_url' => env( 'ASSET_URL', '/wp-content/uploads/wp-app/public' ),

	/**
	|--------------------------------------------------------------------------
	| Application Timezone
	|--------------------------------------------------------------------------
	|
	| Here you may specify the default timezone for your application, which
	| will be used by the PHP date and date-time functions. We have gone
	| ahead and set this to a sensible default for you out of the box.
	|
	*/

	'timezone' => get_option( 'timezone_string', ( defined( 'WP_APP_TIMEZONE' ) ? WP_APP_TIMEZONE : 'UTC' ) ),

	/**
	|--------------------------------------------------------------------------
	| Application Locale Configuration
	|--------------------------------------------------------------------------
	|
	| The application locale determines the default locale that will be used
	| by the translation service provider. You are free to set this value
	| to any of the locales which will be supported by the application.
	|
	*/

	'locale' => get_locale(),

	/**
	|--------------------------------------------------------------------------
	| Application Fallback Locale
	|--------------------------------------------------------------------------
	|
	| The fallback locale determines the locale to use when the current one
	| is not available. You may change the value to correspond to any of
	| the language folders that are provided through your application.
	|
	*/

	'fallback_locale' => 'en_US',

	/**
	|--------------------------------------------------------------------------
	| Faker Locale
	|--------------------------------------------------------------------------
	|
	| This locale will be used by the Faker PHP library when generating fake
	| data for your database seeds. For example, this will be used to get
	| localized telephone numbers, street address information and more.
	|
	*/

	'faker_locale' => get_locale(),

	/**
	|--------------------------------------------------------------------------
	| Encryption Key
	|--------------------------------------------------------------------------
	|
	| This key is used by the Illuminate encrypter service and should be set
	| to a random, 32 character string, otherwise these encrypted strings
	| will not be safe. Please do this before deploying an application!
	|
	*/

	'key' => defined( 'WP_APP_AUTH_KEY' ) ? WP_APP_AUTH_KEY : get_option( 'wp_app_auth_key' ),

	'cipher' => 'AES-256-CBC',

	'providers' => [
		\Enpii_Base\App\Providers\Auth_Service_Provider::class,
		\Enpii_Base\App\Providers\Broadcast_Service_Provider::class,
		\Enpii_Base\App\Providers\Cache_Service_Provider::class,
		\Enpii_Base\App\Providers\Console_Support_Service_Provider::class,
		\Enpii_Base\App\Providers\Cookie_Service_Provider::class,
		\Enpii_Base\App\Providers\Database_Service_Provider::class,
		\Enpii_Base\App\Providers\Encryption_Service_Provider::class,
		\Enpii_Base\App\Providers\Filesystem_Service_Provider::class,
		\Enpii_Base\App\Providers\Foundation_Service_Provider::class,
		\Enpii_Base\App\Providers\Hash_Service_Provider::class,
		\Enpii_Base\App\Providers\Mail_Service_Provider::class,
		\Enpii_Base\App\Providers\Notification_Service_Provider::class,
		\Enpii_Base\App\Providers\Pagination_Service_Provider::class,
		\Enpii_Base\App\Providers\Pipeline_Service_Provider::class,
		\Enpii_Base\App\Providers\Queue_Service_Provider::class,
		\Enpii_Base\App\Providers\Redis_Service_Provider::class,
		\Enpii_Base\App\Providers\Password_Reset_Service_Provider::class,
		\Enpii_Base\App\Providers\Session_Service_Provider::class,
		\Enpii_Base\App\Providers\Translation_Service_Provider::class,
		\Enpii_Base\App\Providers\Validation_Service_Provider::class,
		\Enpii_Base\App\Providers\View_Service_Provider::class,

		\Enpii_Base\App\Providers\Support\App_Service_Provider::class,
		\Enpii_Base\App\Providers\Support\Artisan_Service_Provider::class,
		\Enpii_Base\App\Providers\Support\Broadcast_Service_Provider::class,
		\Enpii_Base\App\Providers\Support\Auth_Service_Provider::class,
		\Enpii_Base\App\Providers\Support\Event_Service_Provider::class,
		\Enpii_Base\App\Providers\Support\Route_Service_Provider::class,
		\Enpii_Base\App\Providers\Support\Html_Service_Provider::class,
	],

	'aliases' => [
		'App' => \Illuminate\Support\Facades\App::class,
		'Artisan' => \Illuminate\Support\Facades\Artisan::class,
		'Auth' => \Illuminate\Support\Facades\Auth::class,
		'Blade' => \Illuminate\Support\Facades\Blade::class,
		'Broadcast' => \Illuminate\Support\Facades\Broadcast::class,
		'Bus' => \Illuminate\Support\Facades\Bus::class,
		'Cache' => \Illuminate\Support\Facades\Cache::class,
		'Config' => \Illuminate\Support\Facades\Config::class,
		'Cookie' => \Illuminate\Support\Facades\Cookie::class,
		'Crypt' => \Illuminate\Support\Facades\Crypt::class,
		'DB' => \Illuminate\Support\Facades\DB::class,
		'Eloquent' => \Illuminate\Database\Eloquent\Model::class,
		'Event' => \Illuminate\Support\Facades\Event::class,
		'File' => \Illuminate\Support\Facades\File::class,
		'Gate' => \Illuminate\Support\Facades\Gate::class,
		'Hash' => \Illuminate\Support\Facades\Hash::class,
		'Lang' => \Illuminate\Support\Facades\Lang::class,
		'Log' => \Illuminate\Support\Facades\Log::class,
		'Mail' => \Illuminate\Support\Facades\Mail::class,
		'Notification' => \Illuminate\Support\Facades\Notification::class,
		'Password' => \Illuminate\Support\Facades\Password::class,
		'Queue' => \Illuminate\Support\Facades\Queue::class,
		'Redirect' => \Illuminate\Support\Facades\Redirect::class,
		'Redis' => \Illuminate\Support\Facades\Redis::class,
		'Request' => \Illuminate\Support\Facades\Request::class,
		'Response' => \Illuminate\Support\Facades\Response::class,
		'Route' => \Illuminate\Support\Facades\Route::class,
		'Schema' => \Illuminate\Support\Facades\Schema::class,
		'Session' => \Illuminate\Support\Facades\Session::class,
		'Storage' => \Illuminate\Support\Facades\Storage::class,
		'URL' => \Illuminate\Support\Facades\URL::class,
		'Validator' => \Illuminate\Support\Facades\Validator::class,
		'View' => \Illuminate\Support\Facades\View::class,
	],
];
