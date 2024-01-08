<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Shared\Base_Job;
use Illuminate\Foundation\Bus\Dispatchable;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;

class Register_Tamara_Custom_Order_Statuses_Job extends Base_Job {
	use Dispatchable;
	use Tamara_Trans_Trait;

	public function handle() {
		register_post_status(
			'wc-tamara-p-canceled',
			[
				'label' => $this->_x( 'Tamara Payment Cancelled', 'Order status' ),
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
				'show_in_admin_status_list' => true,
				'label_count' => $this->_n_noop(
					'Tamara Payment Cancelled <span class="count">(%s)</span>',
					'Tamara Payment Cancelled <span class="count">(%s)</span>'
				),
			]
		);

		register_post_status(
			'wc-tamara-p-failed',
			[
				'label' => $this->_x( 'Tamara Payment Failed', 'Order status' ),
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
				'show_in_admin_status_list' => true,
				'label_count' => $this->_n_noop(
					'Tamara Payment Failed <span class="count">(%s)</span>',
					'Tamara Payment Failed <span class="count">(%s)</span>'
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
			'wc-tamara-a-done',
			[
				'label' => $this->_x( 'Tamara Authorise Success', 'Order status' ),
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
				'show_in_admin_status_list' => true,
				'label_count' => $this->_n_noop(
					'Tamara Authorise Success <span class="count">(%s)</span>',
					'Tamara Authorise Success <span class="count">(%s)</span>'
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

		register_post_status(
			'wc-tamara-o-canceled',
			[
				'label' => $this->_x( 'Tamara Order Cancelled', 'Order status' ),
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
				'show_in_admin_status_list' => true,
				'label_count' => $this->_n_noop(
					'Tamara Order Cancelled <span class="count">(%s)</span>',
					'Tamara Order Cancelled <span class="count">(%s)</span>'
				),
			]
		);

		register_post_status(
			'wc-tamara-p-capture',
			[
				'label' => $this->_x( 'Tamara Payment Capture', 'Order status' ),
				'public' => true,
				'exclude_from_search' => false,
				'show_in_admin_all_list' => true,
				'show_in_admin_status_list' => true,
				'label_count' => $this->_n_noop(
					'Tamara Payment Capture <span class="count">(%s)</span>',
					'Tamara Payment Capture <span class="count">(%s)</span>'
				),
			]
		);
	}
}
