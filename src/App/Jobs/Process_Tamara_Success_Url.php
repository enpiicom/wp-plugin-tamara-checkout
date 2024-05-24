<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Support\Executable_Trait;
use Exception;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;
use WC_Order;

/**
 * @method static void execute_now( $wc_order )
 * @package Tamara_Checkout\App\Jobs
 */
class Process_Tamara_Success_Url {
	use Executable_Trait;
	use Tamara_Checkout_Trait;

	protected $wc_order;

	public function __construct( $wc_order ) {
		$this->wc_order = $wc_order;
	}

	public function handle(): void {
		$tamara_wc_order = $this->to_valid_tamara_order();
		if ( $tamara_wc_order instanceof Tamara_WC_Order ) {
			// We authorise the order
			try {
				Authorise_Tamara_Order_If_Possible_Job::dispatchSync(
					[
						'wc_order_id' => $tamara_wc_order->get_id(),
					]
				);
			// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			} catch ( Exception $e ) {
				// We do nothing for the exception here,
				//  just want to catch all the exception to have the redirect work
			}

			$this->redirect_to_custom_url_if_possible( $tamara_wc_order );
		}
	}

	/**
	 * Convert the static::$wp_order to Tamara_WC_Order
	 * @return Tamara_WC_Order|null
	 */
	protected function to_valid_tamara_order() {
		if ( $this->wc_order instanceof WC_Order && $this->wc_order->get_id() ) {
			$tamara_wc_order = new Tamara_WC_Order( $this->wc_order );

			if ( ! empty( $tamara_wc_order ) && $tamara_wc_order->is_paid_with_tamara() ) {
				return $tamara_wc_order;
			}
		}

		return null;
	}

	protected function redirect_to_custom_url_if_possible( Tamara_WC_Order $tamara_wc_order ): void {
		$custom_success_url = $this->tamara_settings()->success_url;
		if ( $custom_success_url ) {
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

			wp_safe_redirect(
				add_query_arg(
					$params,
					$custom_success_url
				)
			);
			exit;
		}
	}
}
