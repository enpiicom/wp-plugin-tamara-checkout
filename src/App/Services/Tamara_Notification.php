<?php

declare(strict_types=1);

namespace Tamara_Checkout\App\Services;

use Enpii_Base\Foundation\Shared\Traits\Static_Instance_Trait;
use Tamara_Checkout\Deps\Tamara\Notification\NotificationService;

class Tamara_Notification {
	use Static_Instance_Trait;

	protected $notification_key;

	protected $notification_service;

	public static function init_wp_app_instance($notification_key, $working_mode = 'live') {
		if (empty(static::$instance)) {
			$this_instance = new static();
			$this_instance->notification_key = $notification_key;
			$this_instance->notification_service = $this_instance->build_notification_service($notification_key);

			static::init_instance($this_instance);
		}
	}

	protected function reinit_notification_service($notification_key): void {
		$notification_service = $this->build_notification_service($notification_key);
		static::$instance->notification_service = $notification_service;
	}

	protected function build_notification_service($notification_key): NotificationService {
		return NotificationService::create($notification_key);
	}
}
