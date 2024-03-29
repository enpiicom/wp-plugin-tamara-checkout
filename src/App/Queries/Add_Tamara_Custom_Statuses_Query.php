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

	public function __construct( array $order_statuses ) {
		$this->order_statuses = $order_statuses;
	}

	public function handle() {
		$order_statuses = $this->order_statuses;
		$order_statuses['wc-tamara-canceled'] = $this->_x(
			'Tamara Cancelled',
			'Order status'
		);
		$order_statuses['wc-tamara-failed'] = $this->_x(
			'Tamara Failed',
			'Order status'
		);
		$order_statuses['wc-tamara-c-failed'] = $this->_x(
			'Tamara Capture Failed',
			'Order status'
		);
		$order_statuses['wc-tamara-a-failed'] = $this->_x(
			'Tamara Authorise Failed',
			'Order status'
		);

		return $order_statuses;
	}
}
