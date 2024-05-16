<?php

declare(strict_types=1);

namespace Enpii_Base\App\Providers\Support;

use Enpii_Base\App\Support\App_Const;
use Enpii_Base\App\Support\Enpii_Base_Helper;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

class Route_Service_Provider extends RouteServiceProvider {

	/**
	 * This namespace is applied to your controller routes.
	 *
	 * In addition, it is set as the URL generator's root namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'Enpii_Base\App\Http\Controllers';

	/**
	 * The path to the "home" route for your application.
	 *
	 * @var string
	 */
	public const HOME = '/';

	/**
	 * Define the routes for the application.
	 *
	 * @return void
	 */
	public function map() {
		$path = Enpii_Base_Helper::get_current_blog_path();
		$prefix = $path ? '/' . $path . '/' : '/';
		Route::prefix( $prefix . wp_app()->get_wp_app_slug() )
			->as( 'wp-app::' )
			->middleware( [ 'web' ] )
			->group(
				function () {
					do_action( App_Const::ACTION_WP_APP_REGISTER_ROUTES );
				}
			);

		Route::prefix( $prefix . wp_app()->get_wp_api_slug() )
			->as( 'wp-api::' )
			->middleware( [ 'api' ] )
			->group(
				function () {
					do_action( App_Const::ACTION_WP_API_REGISTER_ROUTES );
				}
			);
	}
}
