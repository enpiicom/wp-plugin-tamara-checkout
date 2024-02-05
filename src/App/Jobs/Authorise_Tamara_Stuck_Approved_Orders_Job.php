<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\App\Support\Traits\Queue_Trait;
use Enpii_Base\Foundation\Shared\Base_Job;
use Enpii_Base\Foundation\Shared\Traits\Config_Trait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tamara_Checkout\App\Repositories\WC_Order_Repository_Contract;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;

/**
 * We want to search for pending payment or authorised failed orders that are paid by Tamara
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
	use Queue_Trait;

	protected $page;
	protected $items_per_page = 20;

	public function __construct( $page = 0, $items_per_page = 20 ) {
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
		return [ 'site_id_' . $this->site_id, 'tamara:api', 'tamara_order:authorise' ];
	}

	public function handle( WC_Order_Repository_Contract $wc_order_reposity ) {
		$this->before_handle();

		$wc_order_entities = $wc_order_reposity->get_stuck_approved_wc_orders( $this->page, $this->items_per_page );

		if ( ! empty( $wc_order_entities ) ) {
			if ( count( $wc_order_entities ) === (int) $this->items_per_page ) {
				$this->enqueue_job( static::dispatch( $this->page + 1, $this->items_per_page ) );
			}

			foreach ( $wc_order_entities as $wc_order_entity ) {
				Authorise_Tamara_Order_If_Possible_Job::dispatchSync(
					[
						'wc_order_id' => $wc_order_entity->id,
					]
				);
			}
		}
	}
}
