<?php

declare(strict_types=1);

namespace Enpii_Base\App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate_Is_WP_User_Administrator {
	/**
	 * Perform if there's a logged in user in the current session
	 * @param Illuminate\Http\Request $request
	 * @param Closure $next
	 * @return Enpii_Base\DepsSymfony\Component\HttpFoundation\Response
	 * @throws BindingResolutionException
	 */
	public function handle( Request $request, Closure $next ): Response {
		// phpcs:ignore WordPress.WP.Capabilities.RoleFound
		if ( ! current_user_can( 'administrator' ) ) {
			wp_app_abort( 403, 'Access Denied, Administrator Permission required to perform the setup!' );
		}

		return $next( $request );
	}
}
