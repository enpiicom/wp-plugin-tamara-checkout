<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\DTOs;

use Enpii_Base\Foundation\Shared\Traits\Config_Trait;

/**
 * Value Object for Tamara_WC_Payment_Gateway object setting
 * 	to be able to use auto-complete faster
 * @package Tamara_Checkout\App\DTOs
 * @property string $enabled yes|no
 * @property string $environment live_mode|sandbox_mode
 * @property string $live_api_url
 * @property string $excluded_products
 */
class Tamara_WC_Payment_Gateway_Settings_VO {
	use Config_Trait;

	protected $enabled;
	protected $environment;
	protected $live_api_url;
	protected $excluded_products;

	public function __construct(array $config)
	{
		$this->bind_config($config);
	}
}
