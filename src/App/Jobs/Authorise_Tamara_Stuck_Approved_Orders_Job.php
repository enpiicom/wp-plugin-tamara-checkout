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
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;

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

	public function __construct( int $page = 1, int $items_per_page = 20 ) {
		$this->page = $page;
		$this->items_per_page = $items_per_page;
	}

	/**
	 *
	 * @param array $query
	 * @param array $query_vars
	 * @return mixed
	 */
	public static function add_meta_query( $query, $query_vars ) {
		if ( $query_vars['alias'] === 'authorise_stuck_approved_orders_query' ) {
			$query['meta_query'][] = [
				'key' => Tamara_Checkout_Helper::POST_META_AUTHORISE_CHECKED,
				'compare' => 'NOT EXISTS',
				'value' => 1,
			];
		}

		return $query;
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

	public function handle() {
		$this->before_handle();

		$wc_status_pending = 'wc-pending';
		$wc_status_cancelled = 'wc-cancelled';
		$wc_status_payment_authorised_failed = $this->tamara_gateway()->get_settings_vo()->order_status_when_tamara_authorisation_fails;

		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [ self::class, 'add_meta_query' ], 10, 2 );

		// We don't use the query var meta_query as it would be overriden
		//  use the hook woocommerce_order_data_store_cpt_get_orders_query instead
		$args = [
			'alias' => 'authorise_stuck_approved_orders_query', // for the filter condition
			'type' => 'shop_order',
			'date_created' => now()->subDays( 90 )->startOfDay()->timestamp . '...' . now()->subMinutes( 30 )->timestamp,
			'payment_method' => Tamara_Checkout_Helper::get_possible_tamara_gateway_ids(),
			'orderby' => 'date_created',
			'order' => 'ASC',
			'paged' => $this->page,
			'limit' => $this->items_per_page,
			'return' => 'ids',
		];
		$args['status'] = [
			$wc_status_pending,
			$wc_status_cancelled,
			$wc_status_payment_authorised_failed,
		];
		$wc_orders = wc_get_orders( $args );

		remove_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [ self::class, 'add_meta_query' ] );

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
					$tamara_wc_order = new Tamara_WC_Order( wc_get_order( $wc_order_id ) );
					$tamara_wc_order->set_authorise_checked();
					Authorise_Tamara_Order_If_Possible_Job::dispatchSync(
						[
							'wc_order_id' => $wc_order_id,
						]
					);
				// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
				} catch ( Exception $e ) {
				}
			}
		}
	}
}
