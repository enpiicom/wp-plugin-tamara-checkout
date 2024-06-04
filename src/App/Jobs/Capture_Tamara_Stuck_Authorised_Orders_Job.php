<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\App\Support\Traits\Queue_Trait;
use Enpii_Base\Foundation\Shared\Base_Job;
use Enpii_Base\Foundation\Shared\Traits\Config_Trait;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;

/**
 * We want to search for orders that have status `to capture` or `capture failed`
 *  that haven't been captured successfully
 *  then we try to re-capture them
 * @package Tamara_Checkout\App\Jobs
 */
class Capture_Tamara_Stuck_Authorised_Orders_Job extends Base_Job implements ShouldQueue {
	use Dispatchable;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;

	use Config_Trait;
	use Tamara_Trans_Trait;
	use Tamara_Checkout_Trait;
	use Queue_Trait;

	protected $page;
	protected $items_per_page = 20;

	public function __construct( $page = 1, $items_per_page = 20 ) {
		$this->page = $page;
		$this->items_per_page = $items_per_page;
	}

	/**
	 * We want to retry this job if it is not a succesful one
	 *  after this amount of seconds
	 * @return int
	 */
	public function backoff() {
		return 7000;
	}

	/**
	 * Set tag for filtering
	 * @return string[]
	 */
	public function tags() {
		return [ 'site_id_' . $this->site_id, 'tamara:api', 'tamara_order:capture' ];
	}

	public function handle() {
		$this->before_handle();

		$wc_status_processing = 'wc-processing';
		$wc_status_payment_captured_failed = $this->tamara_gateway()->get_settings_vo()->order_status_when_tamara_capture_fails;

		// We only want to get orders that has been created at least 7 days before
		$args = [
			'type' => 'shop_order',
			'date_created' => now()->subDays( 90 )->startOfDay()->timestamp . '...' . now()->subDays( 7 )->timestamp,
			'payment_method' => Tamara_Checkout_Helper::get_possible_tamara_gateway_ids(),
			'status' => [
				$wc_status_processing,
				$wc_status_payment_captured_failed,
			],
			'orderby' => 'date_created',
			'order' => 'ASC',
			'paged' => $this->page,
			'limit' => $this->items_per_page,
			'return' => 'ids',
		];
		$wc_orders = wc_get_orders( $args );

		if ( ! empty( $wc_orders ) ) {
			if ( count( $wc_orders ) === (int) $this->items_per_page ) {
				$this->enqueue_job(
					static::dispatch(
						$this->page + 1,
						$this->items_per_page,
					)
				);
			}

			foreach ( $wc_orders as $wc_order_id ) {
				try {
					Complete_Order_If_Tamara_Captured_Job::dispatchSync( $wc_order_id );
				// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				} catch ( Exception $e ) {
				}
			}
		}
	}
}
