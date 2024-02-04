<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Entities;

use Enpii_Base\Foundation\Shared\Traits\Config_Trait;
use Enpii_Base\Foundation\Shared\Traits\Getter_Trait;

/**
 * The Entity of WC_Order data
 * @package Tamara_Checkout\App\Entities
 * @property int $id
 * @property string $status
 * @property string $payment_method
 * @property string $amount
 * @property string $currency
 * @property string $created_at
 * @property string $tamara_order_id
 */
class WC_Order_Entity {
	use Getter_Trait;
	use Config_Trait;

	protected $id;
	protected $status;
	protected $created_at;
	protected $updated_at;
	protected $amount;
	protected $currency;
	protected $payment_method;
	protected $tamara_order_id;

	public function __construct(array $config)
	{
		$this->bind_config($config);
	}

	public function get_id(): int {
		return (int) $this->id;
	}
}
