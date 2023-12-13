## Register the plugin
- We need to use `manipulate_hooks` methos to register all needed hooks or de-register hooks.
- We want to put all hooks that works for this plugin inside the `manipulate_hooks` method of the `Tamara_Chekout_WP_Plugin`
- We use the action `init` (with order = -100) for checking the requirement for the Tamara Checkout plugin to works. The reason for the hook `init` is to make sure all plugins, themes are loaded and all Service Providers are registered and booted.

## Working with the Admin
- Register the main payment gateway class `Tamara_WC_Payment_Gateway` as the new payment gateway.
- Use the `init_form_field` to have the Admin form for inputting settings
- Use the `process_admin_options` method of `WC_Payment_Gateway` to save the settings via the action `woocommerce_update_options_payment_gateways_<gateway_id>`. We use Laravel validation to validate the settings, especially with API Token to ensure the tokens are input correctly.
- Use the VO (Value Object) as a way to interaction with settings that are save in the database (as the option). All settings that we need can be accessed via `Tamara_WC_Payment_Gateway_Settings_VO` class.
- When saving the settings, we need to register Tamara webhook as well. We use a queued job for this action.
