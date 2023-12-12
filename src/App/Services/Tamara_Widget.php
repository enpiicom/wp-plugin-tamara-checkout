<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;

class Tamara_Widget {
	use Static_Instance_Trait;

	protected $working_mode;
	protected $public_key;

	public static function init_wp_app_instance($public_key, $working_mode) {
		if (empty(static::$instance)) {
			$this_instance = new static();
			$this_instance->working_mode = $working_mode;
			$this_instance->public_key = $public_key;

			static::init_instance($this_instance);
		}
	}
}
