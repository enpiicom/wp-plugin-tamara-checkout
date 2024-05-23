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
		$custom_success_url = $this->tamara_settings()->success_url;
		$success_url = ! empty( $this->wc_order ) ?
			esc_url_raw( $this->wc_order->get_checkout_order_received_url() ) :
			home_url();

		return add_query_arg(
			array_merge( $this->params, [] ),
			$custom_success_url ? : $success_url
		);
	}
}
