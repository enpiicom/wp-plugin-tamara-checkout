<?php

declare(strict_types=1);

namespace Enpii_Base\App\Providers;

use Enpii_Base\App\Support\App_Const;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\View\ViewServiceProvider;

class View_Service_Provider extends ViewServiceProvider {
	public function register() {
		$this->fetch_config();

		parent::register();
	}

	public function boot() {
		/** @var \Illuminate\View\Factory $view */
		$view = wp_app_view();
		$view->addExtension( 'php', 'blade' );
	}

	protected function fetch_config(): void {
		wp_app_config(
			[
				'view' => apply_filters(
					App_Const::FILTER_WP_APP_VIEW_CONFIG,
					[
						'paths' => $this->generate_view_storage_paths(),
						'compiled' => $this->generate_view_compiled_path(),
					]
				),
			]
		);
	}

	/**
	 * The Paths to store the views files
	 *
	 * @return array
	 */
	protected function generate_view_storage_paths(): array {
		// We want to use the child theme and the template as the main views paths
		// then the fallback is the Enpii Base plugin views
		return get_stylesheet_directory() === get_template_directory() ?
			[
				get_stylesheet_directory(),
			]
			: [
				get_stylesheet_directory(),
				get_template_directory(),
			];
	}

	/**
	 * The view complided path to store compiled files
	 *
	 * @return string
	 * @throws BindingResolutionException
	 */
	protected function generate_view_compiled_path(): string {
		return (string) realpath( wp_app_storage_path( 'framework/views' ) );
	}
}
