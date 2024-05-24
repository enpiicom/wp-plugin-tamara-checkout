<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Queries;

use Enpii_Base\Foundation\Support\Executable_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use WC_Order;

class Build_Tamara_Success_Url {
	use Executable_Trait;
	use Tamara_Trans_Trait;
	use Tamara_Checkout_Trait;

	protected $wc_order;
	protected $params;

	public function __construct( WC_Order $wc_order, $params = [] ) {
		$this->wc_order = $wc_order;
		$this->params = $params;
	}

	public function handle() {
		// We want to use get_checkout_order_received_url() for the success_url
		//  then we use a hook for that order_received url to process the needed actions
		//  and redirect to custom url if needed
		return add_query_arg(
			array_merge( $this->params, [] ),
			esc_url_raw( $this->wc_order->get_checkout_order_received_url() )
		);
	}
}
