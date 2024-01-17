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

/**
 * We want to search for pending payment order that are paid by Tamara
 *  then we try to authorise them if possible
 * @package Tamara_Checkout\App\Jobs
 */
class Authorise_Tamara_Stuck_Approved_Orders_Job extends Base_Job implements ShouldQueue {
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
		return [ 'site_id_' . $this->site_id, 'tamara:api', 'tamara_order:authorise' ];
	}

	public function handle() {
		$this->before_handle();

		$site_id = (int) get_current_blog_id();

		/** @var \Illuminate\Database\Eloquent\Builder $pending_orders_query */
		$wc_status_pending = 'wc-pending';
		$pending_orders_query = WC_Order_Model::site( $site_id )->where(
			[
				[ 'type', 'shop_order' ],
				[ 'status', $wc_status_pending ],
				[ 'date_created_gmt', '>=', now()->subDays( 30 )->startOfDay() ],
				[ 'payment_method', 'LIKE', $this->default_payment_gateway_id() . '%' ],
			]
		)->limit( 7 );

		foreach ( $pending_orders_query->get() as $wc_order_model ) {
			try {
				Authorise_Tamara_Order_If_Possible_Job::dispatchSync(
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
