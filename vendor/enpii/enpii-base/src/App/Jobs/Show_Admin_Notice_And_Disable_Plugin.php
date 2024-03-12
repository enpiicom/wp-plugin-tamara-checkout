<?php

declare(strict_types=1);

namespace Enpii_Base\App\Jobs;

use Enpii_Base\Foundation\Support\Executable_Trait;
use Enpii_Base\Foundation\WP\WP_Plugin_Interface;
use Illuminate\Support\Facades\Session;

class Show_Admin_Notice_And_Disable_Plugin {
	use Executable_Trait;

	/**
	 * @var \Enpii_Base\Foundation\WP\WP_Plugin
	 */
	protected $plugin;
	protected $extra_messages;

	public function __construct( WP_Plugin_Interface $plugin, array $extra_messages = [] ) {
		$this->plugin = $plugin;
		$this->extra_messages = $extra_messages;
	}

	/**
	 * @throws \Exception
	 */
	public function handle() {
		foreach ( $this->extra_messages as $message ) {
			Session::push( 'caution', $message );
		}

		Session::push(
			'caution',
			sprintf(
				// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment, WordPress.WP.I18n.NonSingularStringLiteralDomain
				__( 'Plugin <strong>%s</strong> is disabled.', $this->plugin->get_text_domain() ),
				$this->plugin->get_name() . ' ' . $this->plugin->get_version()
			)
		);

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( $this->plugin->get_plugin_basename() );
	}
}
