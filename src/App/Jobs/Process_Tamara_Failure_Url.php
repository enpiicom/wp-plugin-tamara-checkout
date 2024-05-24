<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Support\Executable_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;

/**
 * @method static void execute_now( $wc_order )
 * @package Tamara_Checkout\App\Jobs
 */
class Process_Tamara_Failure_Url extends Process_Tamara_Success_Url {
	use Executable_Trait;
	use Tamara_Checkout_Trait;

	protected $wc_order;

	public function __construct( $wc_order ) {
		$this->wc_order = $wc_order;
	}

	public function handle(): void {
		$tamara_wc_order = $this->to_valid_tamara_order();
		if ( $tamara_wc_order instanceof Tamara_WC_Order ) {
			// We only want to redirect to custom url because this is a public url
			//  we don't want people to use this URL to perform repeated actions
			$this->redirect_to_custom_url_if_possible( $tamara_wc_order );
		}
	}

	protected function redirect_to_custom_url_if_possible( Tamara_WC_Order $tamara_wc_order ): void {
		$custom_failure_url = $this->tamara_settings()->failure_url;
		if ( $custom_failure_url ) {
			// We ignore NonceVerification as we did it before calling this
			$params = array_merge(
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				! empty( $_GET ) ? (array) $_GET : [],
				[
					'wc_order_id' => $tamara_wc_order->get_id(),
					'tamara_order_id' => $tamara_wc_order->get_tamara_order_id(),
					'locale' => determine_locale(),
				]
			);
			unset( $params['cancel_order'] );

			wp_safe_redirect(
				add_query_arg(
					$params,
					$custom_failure_url
				)
			);
			exit;
		}
	}
}
