@extends('enpii-base::layouts/simple')

@section('content')
	<div class="container">
		<h1><?php echo 'Enpii Base WP App Index page'; ?></h1>
		{{
			wp_app_html()->div( esc_html( $message ) );
		}}
		<div class="message-content"><p>Introducing a groundbreaking WordPress plugin that leverages the power of Laravel for seamless development and enhanced performance: <strong>Enpii Base</strong>.</p><p><strong>Enpii Base</strong> is a revolutionary WordPress plugin designed to bridge the worlds of WordPress and Laravel, providing developers with the robustness of Laravel's MVC architecture within the familiar environment of WordPress. By integrating Laravel's modern development practices, <strong>Enpii Base</strong> empowers developers to create complex, scalable, and maintainable WordPress applications with ease.</p><p><strong>Key Features:</strong></p><ol><li><p><strong>Laravel Development Approach</strong>: Harness the elegance and efficiency of Laravel's development paradigms directly within WordPress. Utilize Laravel's MVC structure, routing system, eloquent ORM, and powerful templating engine for WordPress development.</p></li><li><p><strong>Blade Templating</strong>: Enjoy the simplicity and power of Laravel's Blade templating engine within WordPress themes. Create reusable, clean, and expressive templates for your WordPress projects.</p></li><li><p><strong>Eloquent ORM Integration</strong>: Access and manage WordPress database tables using Laravel's eloquent ORM. Streamline database operations with Laravel's query builder and eloquent models.</p></li><li><p><strong>Routing and Controllers</strong>: Define custom routes and controllers for WordPress, enabling structured and organized request handling. Implement RESTful APIs and custom endpoints effortlessly.</p></li><li><p><strong>Composer Support</strong>: Easily manage dependencies using Composer, allowing integration of third-party packages and libraries into your WordPress projects.</p></li><li><p><strong>Enhanced Security</strong>: Leverage Laravel's built-in security features to fortify your WordPress applications against common vulnerabilities.</p></li><li><p><strong>Seamless Integration</strong>: Incorporate Laravel components into existing or new WordPress projects without disrupting the core functionality of WordPress.</p></li></ol><p><strong>Why Choose Enpii Base?</strong></p><ul><li><p><strong>Efficiency</strong>: Develop complex WordPress applications faster and with more control using Laravel's modern development practices.</p></li><li><p><strong>Scalability</strong>: Build scalable WordPress projects with the robustness of Laravel's architecture, making future enhancements and maintenance straightforward.</p></li><li><p><strong>Developer Experience</strong>: Empower developers with the tools and workflows they love from Laravel, reducing the learning curve and accelerating productivity.</p></li></ul><p><strong>Get Started with Enpii Base</strong></p><p>Step into the future of WordPress development with <strong>Enpii Base</strong>. Whether you're an experienced Laravel developer looking to extend your skills to WordPress or a WordPress developer seeking enhanced capabilities, <strong>Enpii Base</strong> opens up a world of possibilities.</p><p>Begin your journey today and unlock the potential of WordPress with Laravel, more information can be found here <a href="https://enpii.com/wp-plugin-enpii-base" target="_blank">https://enpii.com/wp-plugin-enpii-base</a>.</p></div>
	</div>
@endsection
