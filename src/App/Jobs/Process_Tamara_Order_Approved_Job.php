<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Shared\Base_Job;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Exception;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;

class Process_Tamara_Order_Approved_Job extends Base_Job {
	use Executable_Trait;
	use Tamara_Trans_Trait;

	protected $wc_order_id;
	protected $tamara_order_id;
	protected $tamara_order_number;
	protected $tamara_order_payment_type;
	protected $tamara_order_instalments;

	public function __construct( $tamara_order_id, $wc_order_id ) {
		$this->tamara_order_id = $tamara_order_id;
		$this->wc_order_id = $wc_order_id;
	}

	/**
	 * @throws \Tamara_Checkout\App\Exceptions\Tamara_Exception
	 * @throws \WC_Data_Exception
	 * @throws \Exception
	 */
	public function handle() {
		// We authorise the order
		Authorise_Tamara_Order_If_Possible_Job::dispatchSync(
			[
				'wc_order_id' => $this->wc_order_id,
				'tamara_order_id' => $this->tamara_order_id,
			]
		);
	}
}
