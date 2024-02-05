<?php

declare(strict_types=1);

namespace Enpii_Base\App\Http;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Facade;
use LogicException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class Kernel extends HttpKernel {
	public $pipeline;

	/**
	 * The application's global HTTP middleware stack.
	 *
	 * These middleware are run during every request to your application.
	 *
	 * @var array
	 */
	protected $middleware = [];

	/**
	 * The bootstrap classes for the application.
	 * As we are loading configurations from memory (array) with WP_Application
	 *  we don't need to load config from files.
	 *  So we exclude `\Illuminate\Foundation\Bootstrap\LoadConfiguration`
	 *
	 * @var array
	 */
	protected $bootstrappers = [
		\Illuminate\Foundation\Bootstrap\HandleExceptions::class,
		\Illuminate\Foundation\Bootstrap\RegisterFacades::class,
		\Illuminate\Foundation\Bootstrap\RegisterProviders::class,
		\Illuminate\Foundation\Bootstrap\BootProviders::class,
	];

	/**
	 * The priority-sorted list of middleware.
	 *
	 * Forces non-global middleware to always be in the given order.
	 *
	 * @var string[]
	 */
	protected $middlewarePriority = [
		\Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
		\Illuminate\Cookie\Middleware\EncryptCookies::class,
		\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
		\Illuminate\Session\Middleware\StartSession::class,
		\Illuminate\View\Middleware\ShareErrorsFromSession::class,
		\Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
		\Illuminate\Routing\Middleware\ThrottleRequests::class,
		\Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
		\Illuminate\Contracts\Session\Middleware\AuthenticatesSessions::class,
		\Illuminate\Routing\Middleware\SubstituteBindings::class,
		\Illuminate\Auth\Middleware\Authorize::class,
	];

	/**
	 * The application's route middleware groups.
	 *
	 * @var array
	 */
	protected $middlewareGroups = [
		'web' => [
			\Illuminate\Cookie\Middleware\EncryptCookies::class,
			\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
			\Illuminate\Session\Middleware\StartSession::class,
			\Illuminate\View\Middleware\ShareErrorsFromSession::class,
			\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
			\Illuminate\Routing\Middleware\SubstituteBindings::class,
		],
		'api' => [
			\Illuminate\Routing\Middleware\SubstituteBindings::class,
		],
	];

	/**
	 * The application's middleware aliases.
	 *
	 * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
	 *
	 * @var array<string, class-string|string>
	 */
	protected $middlewareAliases = [
		'auth' => \Enpii_Base\App\Http\Middleware\Authenticate::class,
		'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
		'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
		'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
		'can' => \Illuminate\Auth\Middleware\Authorize::class,
		'guest' => \Enpii_Base\App\Http\Middleware\Redirect_If_Authenticated::class,
		'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
		'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
		'signed' => \Enpii_Base\App\Http\Middleware\Validate_Signature::class,
		'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
		'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
	];

	/**
	 * The application's route middleware.
	 *
	 * These middleware may be assigned to groups or used individually.
	 *
	 * @var array
	 */
	protected $routeMiddleware = [
		'authenticate_is_wp_user_admin' => \Enpii_Base\App\Http\Middleware\Authenticate_Is_WP_User_Administrator::class,
		'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
		'auth' => \Enpii_Base\App\Http\Middleware\Authenticate::class,
	];

	public function send_request_through_middleware( Request $request, array $middleware, Closure $closure ) {
		/** @var \Illuminate\Pipeline\Pipeline $this->pipeline */
		$this->pipeline = ( new Pipeline( $this->app ) );
		return $this->pipeline
			->send( $request )
			->through( $middleware )
			->then( $closure );
	}

	/**
	 * We capture the request and attach to the Container
	 * @return void
	 * @throws LogicException
	 * @throws BadRequestException
	 * @throws BindingResolutionException
	 */
	public function capture_request(): void {
		$this->app->instance( 'request', \Enpii_Base\App\Http\Request::capture() );
		Facade::clearResolvedInstance( 'request' );
	}
}
