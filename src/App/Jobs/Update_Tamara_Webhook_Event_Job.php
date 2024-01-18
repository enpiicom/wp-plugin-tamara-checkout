<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Jobs;

use Enpii_Base\Foundation\Shared\Base_Job;
use Enpii_Base\Foundation\Shared\Traits\Config_Trait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tamara_Checkout\App\Exceptions\Tamara_Exception;
use Tamara_Checkout\App\Support\Traits\Tamara_Checkout_Trait;
use Tamara_Checkout\App\Support\Traits\Tamara_Trans_Trait;
use Tamara_Checkout\App\WP\Data\Tamara_WC_Order;

class Update_Tamara_Webhook_Event_Job extends Base_Job implements ShouldQueue {
	use Dispatchable;
	use InteractsWithQueue;
	use Queueable;
	use SerializesModels;
	use Config_Trait;
	use Tamara_Trans_Trait;
	use Tamara_Checkout_Trait;

	protected $wc_order_id;
	protected $tamara_order_id;
	protected $event_type;

	/**
	 * @var Tamara_WC_Order
	 */
	protected $tamara_wc_order;

	/**
	 * @throws Tamara_Exception
	 * @throws \Exception
	 */
	public function __construct( array $config ) {
		$this->bind_config( $config );

		parent::__construct();
	}

	/**
	 * We want to retry this job if it is not a succesful one
	 *  after this amount of seconds
	 * @return int
	 */
	public function backoff() {
		return 700;
	}

	/**
	 * Set tag for filtering
	 * @return string[]
	 */
	public function tags() {
		return [ 'site_id_' . $this->site_id, 'tamara:api', 'tamara_order:' . str_replace( '-', '_', sanitize_title( $this->event_type ) ) ];
	}

	/**
	 * We want to add notes for the order whenever receiving a webhook event from Tamara
	 */
	public function handle() {
		$this->before_handle();

		$this->tamara_wc_order = $this->build_tamara_wc_order( $this->wc_order_id );
		$this->tamara_wc_order->add_tamara_order_note( sprintf( $this->_t( 'Tamara webhook event `%s` for this order has just happened. Tamara Order Id `%s`' ), $this->event_type, $this->tamara_order_id ) );

		if ( ! empty( $new_order_status ) ) {
			$this->tamara_wc_order->get_wc_order()->update_status( $new_order_status );
		}
	}
}
