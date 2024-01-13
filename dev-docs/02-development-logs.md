## Register the plugin
- We need to use `manipulate_hooks` methos to register all needed hooks or de-register hooks.
- We want to put all hooks that works for this plugin inside the `manipulate_hooks` method of the `Tamara_Chekout_WP_Plugin`
- We use the action `init` (with order = -100) for checking the requirement for the Tamara Checkout plugin to works. The reason for the hook `init` is to make sure all plugins, themes are loaded and all Service Providers are registered and booted.

## Working with the Admin
- Register the main payment gateway class `Tamara_WC_Payment_Gateway` as the new payment gateway.
- Use the `init_form_field` to have the Admin form for inputting settings
- Use the `process_admin_options` method of `WC_Payment_Gateway` to save the settings via the action `woocommerce_update_options_payment_gateways_<gateway_id>`. We use Laravel validation to validate the settings, especially with API Token to ensure the tokens are input correctly.
- Use the VO (Value Object) as a way to interaction with settings that are save in the database (as the option). All settings that we need can be accessed via `Tamara_WC_Payment_Gateway_Settings_VO` class.
- When saving the settings, we need to validate the API Token and register Tamara webhook as well. We use a queued job for this action. The queued job will be executed and save the Tamara Webhook Id to the settings.
- We use a single class `Tamara_WC_Payment_Gateway` to work with the Admin Settings.
- We need to make this class working with the Block Theme as well - **Todo**

## Working with Tamara API
- We create a `Tamara_Client` class to play as a wrapper to cover all missing cases for Tamara PHP SDK. (we try to catch all the errors, exceptions when requesting to Tamara API).
- We want to use async Jobs (via database queue) to Register Webhook, Cancel, Capture, Refund payments using Tamara API for not blocking any actions. Authorise job may need to perform synchronously.

## Working with the Frontend
- When Tamara Checkout enabled, the plugin will add a Tamara promo widget on Product Single Page (PDP) screen if the product is qualidied to be paid by Tamara. The position is based on the location hook in the Admin Settings.
- A promo Widget would be added to the Cart page as well.
- On checkout page, we use differents Payment Gateway class based on the response when the plugin requests to Tamara API to look for any available Payment Options (Tamara calls them Payment Types) for the customer that is checking out.
- Available Payment Options would be shown at the checkout and the customer can proceed to the Tamara checkout page.
- After the successful checkout on Tamara website, the customer would be redirected to the Success page:
  - At this step, Tamara sends the webhook request to the website to notify that the order has been `approved` and the website needs to `authorise` the order using the API and return 200 to Tamara.
  - On the other hand, to ensure that the order would be authorised on time when showing the success message to the customer, the success page would actively `authorise` the Tamara order as well (as a fallback) then showing the success content to the customer.
  - At this stage the order on Tamara is `authorised` and the WooCommerce order should be `processing` (or the defined status in the Admin Settings)
- When the wc_order is updated to Canceled, Completed or Refunded there would be an corresponding actions to update the Tamara order to Canceled, Captured and Refunded as well.

