<?php

declare(strict_types=1);

namespace Enpii_Base\App\Http\Middleware;

use Closure;
use Enpii_Base\App\Support\Traits\Enpii_Base_Trans_Trait;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate_Is_WP_User_Administrator {
	use Enpii_Base_Trans_Trait;

	/**
	 * Perform if there's a logged in user in the current session
	 * @param Illuminate\Http\Request $request
	 * @param Closure $next
	 * @return Enpii_Base\DepsSymfony\Component\HttpFoundation\Response
	 * @throws BindingResolutionException
	 */
	public function handle( Request $request, Closure $next ): Response {
		$message = wp_app_config( 'app.debug' ) ?
			$this->__( 'Access Denied! Administrator Permission required to perform the action!' ) :
			$this->__( 'Access Denied!' );

		// phpcs:ignore WordPress.WP.Capabilities.RoleFound
		if ( ! current_user_can( 'administrator' ) ) {
			wp_app_abort( 403, $message );
		}

		return $next( $request );
	}
}
