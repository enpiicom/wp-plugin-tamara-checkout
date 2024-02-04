<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Repositories;

use Tamara_Checkout\App\Entities\WC_Order_Entity;

interface WC_Order_Repository_Contract {
	/**
	 * We need to have the Repository to set the Site Id when being initiated
	 * @param int $site_id
	 * @return mixed
	 */
	public function __construct(int $site_id);

	/**
	 * We need this method to convert the data retrieved by the Repository
	 * 	to the WC_Order_Entity
	 * @param mixed $subject
	 * @return WC_Order_Entity
	 */
	public function convert_to_entity($subject): WC_Order_Entity;

	/**
	 * Get stuck approved orders
	 * @param int $page
	 * @param int $items_per_page
	 * @return WC_Order_Entity[]
	 */
	public function get_stuck_approved_wc_orders(int $page = 0, int $items_per_page = 20): array;

	/**
	 * Get stuck authorised orders
	 * @param int $page
	 * @param int $items_per_page
	 * @return WC_Order_Entity[]
	 */
	public function get_stuck_authorised_wc_orders(int $page = 0, int $items_per_page = 20): array;
}
