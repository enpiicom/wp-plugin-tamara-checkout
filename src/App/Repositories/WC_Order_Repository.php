<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Repositories;

use Tamara_Checkout\App\Entities\WC_Order_Entity;
use Tamara_Checkout\App\Models\WC_Order;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;

class WC_Order_Repository implements WC_Order_Repository_Contract {
	use Tamara_Checkout_Trait;

	protected $site_id = 1;

	public function __construct( int $site_id ) {
		$this->site_id = $site_id > 0 ? $site_id : 1;
	}

	public function convert_to_entity( $subject ): WC_Order_Entity {
		/** @var Tamara_Checkout\App\Models\WC_Order $subject */
		$config = [];
		$config['id'] = $subject->id;
		$config['created_at'] = $subject->date_created_gmt;
		$config['updated_at'] = $subject->date_updated_gmt;
		$config['status'] = $subject->status;
		$config['payment_method'] = $subject->payment_method;
		$config['total_amount'] = $subject->total_amount;
		$config['currency'] = $subject->currency;

		return new WC_Order_Entity( $config );
	}

	/**
	 * Get stuck approved orders
	 * @param int $page
	 * @param int $items_per_page
	 * @return WC_Order_Entity[]
	 */
	public function get_stuck_approved_wc_orders( int $page = 0, int $items_per_page = 20 ): array {
		$wc_status_pending = 'wc-pending';
		$wc_status_payment_authorised_failed = $this->tamara_gateway()->get_settings_vo()->order_status_when_tamara_authorisation_fails;

		/** @var \Illuminate\Database\Eloquent\Builder $pending_orders_query */
		$pending_orders_query = WC_Order::site( $this->site_id );
		$pending_orders_query = $pending_orders_query->where(
			[
				[ 'type', 'shop_order' ],
				[ 'date_created_gmt', '>=', now()->subDays( 90 )->startOfDay() ],
				[ 'payment_method', 'LIKE', $this->default_payment_gateway_id() . '%' ],
			]
		)
		->where(
			function ( $query ) use ( $wc_status_pending, $wc_status_payment_authorised_failed ) {
				/** @var \Illuminate\Database\Eloquent\Builder $query */
				$query->where( 'status', $wc_status_pending )
					->orWhere( 'status', $wc_status_payment_authorised_failed );
			}
		)
		->orderBy( 'date_created_gmt', 'asc' )
		->limit( $items_per_page )
		->offset( $page * $items_per_page );

		$return_data = [];
		foreach ( $pending_orders_query->get() as $tmp_index => $wc_order ) {
			$return_data[ $tmp_index ] = $this->convert_to_entity( $wc_order );
		}

		return $return_data;
	}

	/**
	 * Get stuck approved orders
	 * @param int $page
	 * @param int $items_per_page
	 * @return WC_Order_Entity[]
	 */
	public function get_stuck_authorised_wc_orders( int $page = 0, int $items_per_page = 20 ): array {
		$wc_status_processing = 'wc-processing';
		$wc_status_payment_captured_failed = $this->tamara_gateway()->get_settings_vo()->order_status_when_tamara_capture_fails;

		/** @var \Illuminate\Database\Eloquent\Builder $processing_orders_query */
		$processing_orders_query = WC_Order::site( $this->site_id );
		$processing_orders_query = $processing_orders_query->where(
			[
				[ 'type', 'shop_order' ],
				[ 'date_created_gmt', '>=', now()->subDays( 90 )->startOfDay() ],
				[ 'payment_method', 'LIKE', $this->default_payment_gateway_id() . '%' ],
			]
		)
		->where(
			function ( $query ) use ( $wc_status_processing, $wc_status_payment_captured_failed ) {
				/** @var \Illuminate\Database\Eloquent\Builder $query */
				$query->where( 'status', $wc_status_processing )
					->orWhere( 'status', $wc_status_payment_captured_failed );
			}
		)
		->orderBy( 'date_created_gmt', 'asc' )
		->limit( $items_per_page )
		->offset( $page * $items_per_page );

		$return_data = [];
		foreach ( $processing_orders_query->get() as $tmp_index => $wc_order ) {
			$return_data[ $tmp_index ] = $this->convert_to_entity( $wc_order );
		}

		return $return_data;
	}
}
