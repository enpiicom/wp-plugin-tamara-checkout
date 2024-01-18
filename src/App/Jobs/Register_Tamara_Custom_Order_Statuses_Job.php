<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Shared\Base_Job;
use Enpii_Base\Foundation\Support\Executable_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;

class Register_Tamara_Custom_Order_Statuses_Job extends Base_Job {
	use Executable_Trait;
	use Tamara_Trans_Trait;

	public function handle() {
		register_post_status(
			'wc-tamara-canceled',
			[
				'label' => $this->_x( 'Tamara Cancelled', 'Order status' ),
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
				'show_in_admin_status_list' => true,
				'label_count' => $this->_n_noop(
					'Tamara Cancelled <span class="count">(%s)</span>',
					'Tamara Cancelled <span class="count">(%s)</span>'
				),
			]
		);

		register_post_status(
			'wc-tamara-failed',
			[
				'label' => $this->_x( 'Tamara Failed', 'Order status' ),
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
				'show_in_admin_status_list' => true,
				'label_count' => $this->_n_noop(
					'Tamara Failed <span class="count">(%s)</span>',
					'Tamara Failed <span class="count">(%s)</span>'
				),
			]
		);

		register_post_status(
			'wc-tamara-c-failed',
			[
				'label' => $this->_x( 'Tamara Capture Failed', 'Order status' ),
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
				'show_in_admin_status_list' => true,
				'label_count' => $this->_n_noop(
					'Tamara Capture Failed <span class="count">(%s)</span>',
					'Tamara Capture Failed <span class="count">(%s)</span>'
				),
			]
		);

		register_post_status(
			'wc-tamara-a-failed',
			[
				'label' => $this->_x( 'Tamara Authorise Failed', 'Order status' ),
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
				'show_in_admin_status_list' => true,
				'label_count' => $this->_n_noop(
					'Tamara Authorise Failed <span class="count">(%s)</span>',
					'Tamara Authorise Failed <span class="count">(%s)</span>'
				),
			]
		);
	}
}
