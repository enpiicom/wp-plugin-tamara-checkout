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
use Tamara_Checkout\App\Support\Tamara_Checkout_Helper;
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
	public function handle(): void {
		$this->before_handle();

		$this->tamara_wc_order = $this->build_tamara_wc_order( $this->wc_order_id );
		$this->handle_order_notes( $this->tamara_wc_order );

		$new_order_status = $this->get_new_order_status();
		$this->update_new_order_status( $this->tamara_wc_order, $new_order_status );
	}

	protected function handle_order_notes( Tamara_WC_Order $tamara_wc_order ): void {
		$order_note = sprintf( $this->__( 'Tamara webhook event `%s` for this order has just happened. Tamara Order Id `%s`' ), $this->event_type, $this->tamara_order_id );
		$tamara_wc_order->add_tamara_order_note( $order_note );
	}

	protected function get_new_order_status(): string {
		if ( (string) $this->event_type === Tamara_Checkout_Helper::TAMARA_EVENT_TYPE_ORDER_CANCELED ) {
			return $this->tamara_settings()->order_status_on_tamara_canceled;
		}

		if ( (string) $this->event_type === Tamara_Checkout_Helper::TAMARA_EVENT_TYPE_ORDER_DECLINED ) {
			return $this->tamara_settings()->order_status_on_tamara_failed;
		}

		return '';
	}

	protected function update_new_order_status( Tamara_WC_Order $tamara_wc_order, $new_order_status ): void {
		// We only want to update the new WooCommerce status if the new status is different
		//  than the current one
		if ( ! empty( $new_order_status ) && $new_order_status !== 'wc-' . $tamara_wc_order->get_wc_order()->get_status() ) {
			$tamara_wc_order->get_wc_order()->update_status( $new_order_status );
		}
	}
}
