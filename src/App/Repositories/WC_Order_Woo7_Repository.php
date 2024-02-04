<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Repositories;

use Tamara_Checkout\App\Models\Post;
use Illuminate\Database\Query\JoinClause;
use Tamara_Checkout\App\Entities\WC_Order_Entity;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;

class WC_Order_Woo7_Repository implements WC_Order_Repository_Contract {
	use Tamara_Checkout_Trait;

	protected $site_id = 1;

	public function __construct(int $site_id)
	{
		$this->site_id = $site_id > 0 ? $site_id : 1;
	}

	public function convert_to_entity($subject): WC_Order_Entity {
		/** @var \Tamara_Checkout\App\Models\Post $subject */
		$config = [];
		$config['id'] = $subject->ID;
		$config['created_at'] = $subject->post_date_gmt;
		$config['status'] = $subject->post_status;
		$config['payment_method'] = $subject->attributesToArray()['payment_method'] ?? null;

		return new WC_Order_Entity($config);
	}

	/**
	 * Get stuck approved orders
	 * @param int $page
	 * @param int $items_per_page
	 * @return WC_Order_Entity[]
	 */
	public function get_stuck_approved_wc_orders(int $page = 0, int $items_per_page = 20): array {
		/** @var \Illuminate\Database\Eloquent\Builder $pending_orders_query */
		$wc_status_pending = 'wc-pending';
		$wc_status_payment_authorised_failed = $this->tamara_gateway()->get_settings_vo()->order_status_when_tamara_authorisation_fails;

		/** @var \Illuminate\Database\Eloquent\Builder $pending_orders_query */
		$pending_orders_query = Post::site( $this->site_id );
		$pending_orders_query = $pending_orders_query->select(['posts.ID', 'posts.post_date_gmt', 'posts.post_status', 'postmeta.meta_value as payment_method'])
			->join('postmeta', function (JoinClause $join) {
				$join->on('postmeta.post_id', '=', 'posts.ID')
					->where('postmeta.meta_key', '=', '_payment_method');
			})
			->where(
				[
					[ 'post_type', 'shop_order' ],
					[ 'post_date_gmt', '>=', now()->subDays( 90 )->startOfDay() ],
					[ 'post_date_gmt', '<=', now()->subDays( 15 )->startOfDay() ],
					[ 'postmeta.meta_value', 'LIKE', $this->default_payment_gateway_id() . '%' ],
				]
		   	)
			->where(
				function ( $query ) use (
					$wc_status_pending, $wc_status_payment_authorised_failed
				) {
					/** @var \Illuminate\Database\Eloquent\Builder $query */
					$query->where( 'post_status', $wc_status_pending )
						->orWhere( 'post_status', $wc_status_payment_authorised_failed );
				}
		   	)
			->orderBy( 'post_date_gmt', 'asc' )
			->limit( $items_per_page )
			->offset( $page * $items_per_page );

		$return_data = [];
		foreach ( $pending_orders_query->get() as $tmp_index => $wc_order ) {
			$return_data[$tmp_index] = $this->convert_to_entity($wc_order);
		}

		return $return_data;
	}

	/**
	 * Get stuck approved orders
	 * @param int $offset
	 * @param int $items_per_page
	 * @return WC_Order_Entity[]
	 */
	public function get_stuck_authorised_wc_orders(int $offset = 0, int $items_per_page = 20): array {
		/** @var \Illuminate\Database\Eloquent\Builder $pending_orders_query */
		$wc_status_processing = 'wc-processing';
		$wc_status_payment_captured_failed = $this->tamara_gateway()->get_settings_vo()->order_status_when_tamara_capture_fails;

		/** @var \Illuminate\Database\Eloquent\Builder $pending_orders_query */
		$pending_orders_query = Post::site( $this->site_id );
		$pending_orders_query = $pending_orders_query->select(['posts.ID', 'posts.post_date_gmt', 'posts.post_status', 'postmeta.meta_value as payment_method'])
			->join('postmeta', function (JoinClause $join) {
				$join->on('postmeta.post_id', '=', 'posts.ID')
					->where('postmeta.meta_key', '=', '_payment_method');
			})
			->where(
				[
					[ 'post_type', 'shop_order' ],
					[ 'post_date_gmt', '>=', now()->subDays( 90 )->startOfDay() ],
					[ 'post_date_gmt', '<=', now()->subDays( 15 )->startOfDay() ],
					[ 'postmeta.meta_value', 'LIKE', $this->default_payment_gateway_id() . '%' ],
				]
		   	)
			->where(
				function ( $query ) use (
					$wc_status_processing, $wc_status_payment_captured_failed
				) {
					/** @var \Illuminate\Database\Eloquent\Builder $query */
					$query->where( 'post_status', $wc_status_processing )
						->orWhere( 'post_status', $wc_status_payment_captured_failed );
				}
		   	)
			->orderBy( 'post_date_gmt', 'asc' )
			->limit( $items_per_page )
			->offset( $offset * $items_per_page );

		$return_data = [];
		foreach ( $pending_orders_query->get() as $tmp_index => $wc_order ) {
			$return_data[$tmp_index] = $this->convert_to_entity($wc_order);
		}

		return $return_data;
	}
}
