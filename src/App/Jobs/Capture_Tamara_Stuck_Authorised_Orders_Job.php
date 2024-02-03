<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\App\Models\WC_Order_Model;
use Enpii_Base\Foundation\Shared\Base_Job;
use Enpii_Base\Foundation\Shared\Traits\Config_Trait;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;

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

	/**
	 * We want to retry this job if it is not a succesful one
	 *  after this amount of seconds
	 * @return int
	 */
	public function backoff() {
		return 49000;
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

		$site_id = (int) get_current_blog_id();

		/** @var \Illuminate\Database\Eloquent\Builder $pending_orders_query */
		$wc_status_to_capture = $this->tamara_gateway()->get_settings_vo()->order_status_to_capture_tamara_payment;
		$wc_status_payment_captured_failed = $this->tamara_gateway()->get_settings_vo()->order_status_when_tamara_capture_fails;
		$to_capture_orders_query = WC_Order_Model::site( $site_id )
		->where(
			[
				[ 'type', 'shop_order' ],
				[ 'date_created_gmt', '>=', now()->subDays( 30 )->startOfDay() ],
				[ 'payment_method', 'LIKE', $this->default_payment_gateway_id() . '%' ],
			]
		)
		->where(
			function ( $query ) use ( $wc_status_to_capture, $wc_status_payment_captured_failed ) {
				/** @var \Illuminate\Database\Eloquent\Builder $query */
				$query->where( 'status', $wc_status_to_capture )
					->orWhere( 'status', $wc_status_payment_captured_failed );
			}
		)
		->orderBy( 'date_created_gmt', 'asc' )
		->limit( 7 );

		foreach ( $to_capture_orders_query->get() as $wc_order_model ) {
			$tamara_wc_order = $this->build_tamara_wc_order( $wc_order_model->id );
			if ( ! $tamara_wc_order->get_tamara_capture_id() ) {
				try {
					Capture_Tamara_Order_If_Possible_Job::dispatchSync(
						[
							'wc_order_id' => $wc_order_model->id,
						]
					);
				// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				} catch ( Exception $e ) {
				}
			}
		}
	}
}
