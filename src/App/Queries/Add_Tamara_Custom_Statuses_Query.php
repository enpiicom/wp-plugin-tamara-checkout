<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Enpii_Base\Foundation\Shared\Base_Query;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;

class Add_Tamara_Custom_Statuses_Query extends Base_Query {
	use Executable_Trait;
	use Tamara_Trans_Trait;

	protected $order_statuses;

	public function __construct(array $order_statuses)
	{
		$this->order_statuses = $order_statuses;
	}

	public function handle() {
		$order_statuses = $this->order_statuses;
		$order_statuses['wc-tamara-p-canceled'] = $this->_x(
			'Tamara Payment Cancelled',
			'Order status'
		);
		$order_statuses['wc-tamara-p-failed'] = $this->_x(
			'Tamara Payment Failed',
			'Order status'
		);
		$order_statuses['wc-tamara-c-failed'] = $this->_x(
			'Tamara Capture Failed',
			'Order status'
		);
		$order_statuses['wc-tamara-a-done'] = $this->_x(
			'Tamara Authorise Done',
			'Order status'
		);
		$order_statuses['wc-tamara-a-failed'] = $this->_x(
			'Tamara Authorise Failed',
			'Order status'
		);
		$order_statuses['wc-tamara-o-canceled'] = $this->_x(
			'Tamara Order Cancelled',
			'Order status'
		);
		$order_statuses['wc-tamara-p-capture'] = $this->_x(
			'Tamara Payment Capture',
			'Order status'
		);

		return $order_statuses;
	}
}
