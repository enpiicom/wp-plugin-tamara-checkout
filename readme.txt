=== Tamara Checkout ===
Contributors: tamarasolution, npbtrac, kimloile
Tags: tamara, tamara-checkout, tamara-payment, e-commerce, store, sales, sell, woo, shop, cart, checkout, payments, woocommerce
Requires at least: 5.0
Tested up to: 6.4.1
Requires PHP: 7.3+
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

With Tamara Buy Now Pay Later, you can split your payments – totally interest-free. Accepts payments from Mada, Apple Pay, or Credit Cards.

== Description ==

[Tamara](https://tamara.co) is the best way to pay for your online shopping. advantage icon. Buy now and pay later. No immediate payment needed.

Tamara is a solution for brick and mortar and e-commerce players in the GCC. We are a regional startup with a bold vision and a global experienced team.

We provide a Buy Now Pay Later solution in Saudi Arabia and the UAE. Our solution solves local challenges and provides merchants and their customers with significant value.

The plugin integrates with the merchant's checkout. It tackles cash on delivery challenges and increases order frequency, average order size and conversion. It also provides consumers with an exceptional checkout experience.

### FOR MERCHANTS
#### Your customers want to buy now and pay later!
Customers enjoy a flexible payment solution to buy the product they love. No hidden fees. No risk.

#### Why tamara?
Unleash your store’s potential by enabling ​Pay Later ​with tamara!

* Increase sales and average order value.
* Tackle cash on delivery.
* Reduce fraud and non-payment risks.

#### Benefits to Merchants

* More sales: Higher average order value, conversion, repeat purchases and less card transaction declined.
* Access to quality customers: tamara directs new customers to merchants through its network.
* No collections: tamara collects from the customers, not merchants.
* No fraud and credit risk: tamara assumes 100% of payment risk on behalf of the merchant.
* No COD: In case merchants offer it, we'll take this headache away from them and provide a better alternative.
* Logistics: Freedom to select the top couriers (e.g. DHL) as there is no restriction on specific couriers who collect cash.

### FOR SHOPPERS
#### Pay later with tamara
Buy online and pay later after your goods are delivered. No hidden fees.

#### What is tamara?
tamara is the best way to pay for your online shopping.

* Buy now and pay later
* No hidden fees
* Smooth process

#### Benefits to Customers

* Convenient and smooth checkout experience: No friction of having customers to pay upfront.
* In-store experience for e-commerce: Customers can try the product at home without having to pay upfront.
* Payment flexibility and better money management: Pay later in 30 days or in installments.
* Trust: customers can purchase the products they love with peace of mind. No credit/debit card details needed upfront, no chasing logistics companies for delivery.

== Installation ==

= Minimum Requirements =

* Wordpress 5.5 or greater is recommended
* Woocommerce 5.0 or greater is recommended

### Install and Enable/Disable the plugin
* The Tamara Checkout plugin is now available for WordPress. After installing it, simply click on **Activate** the plugin.
* Please note that the plugin can only be activated when Woocommerce is active.
* After the plugin is activated, there is an option to Enable/Disable the plugin in **Woocommerce => Settings => Payments => Tamara Gateway Settings**

* You can find out more how to install and use the plugin with our user guides documentation.

== Frequently Asked Questions ==

= Where can I find Tamara Checkout documentation and user guides? =

For more information about the documentation, user guides and the FAQs, please visit: [our website](https://tamara.co/en/faq.html)

== Changelog ==

= 2.0.0 - 2024-05-15 =

Enhancements:

* Refactor the codebase to improve the performance
* Make the plugin compatible with PHP 7.3+, 8.0+ (8.1 8.2 8.3)

Bugfixes:

* Fix duplicated payment options issues
* Fix the Widget value on cart

= 1.9.6 - 2023-11-18 =

Bugfixes:

* Fix public key related bugs

= 1.9.4 - 2023-07-10 =

Enhancements:

* Add customer risk assessment data to Tamara checkout session.

= 1.9.2 - 2023-02-22 =

Bugfixes:

* Fix errors while adjusting payment types on checkout.
* Add method to fetch payment types from supported countries only.

= 1.9.1 - 2023-02-21 =

Enhancements:

* Remove api url as readonly field and update default value.
* Update Tamara payment methods offset on checkout.

= 1.9.0 - 2023-01-07 =

Enhancements:

* Fetch payment options avalibility and show on checkout.
* Remove manual select payment options in admin screens.
* Update widgets and enhance admin settings screen.

= 1.8.10 - 2022-11-4 =

Bugfixes:

* Remove old dependencies packages in vendor directory.

= 1.8.9 - 2022-11-3 =

Bugfixes:

* Fix package dependencies namespace conflicts.

= 1.8.8 - 2022-10-12 =

Enhancements:

* Revert detect Single Checkout option.
* Add Pay In 2 payment method.

= 1.8.7 - 2022-09-13 =

Enhancements:

* Add Single Checkout admin option field to enable/disable it manually.
* Add Popup widet on cart page option.

= 1.8.6 - 2022-08-25 =

Enhancements:

* Update Pay Now title and description on checkout.
* Enhance Tamara help text in Admin settings.

== Changelog ==

= 1.8.5 - 2022-08-12 =

Enhancements:

* Add Pay Now payment method.

= 1.8.4 - 2022-07-29 =

Bugfixes:

* Fix PDP widget not showing when script is cached.

= 1.8.3 - 2022-07-29 =

Enhancements:

* Update new scripts url to checkout widget.

= 1.8.2 - 2022-07-20 =

Enhancements:

* Remove unused scripts.

= 1.8.1 - 2022-07-16 =

Enhancements:

* Update PHP Minimum Requirements to 7.1+.
* Remove unused scripts.

= 1.8.0 - 2022-07-12 =

Enhancements:

* Add Tamara Single Checkout Feature which redirect customer to Tamara page directly instead of selecting Tamara payment options at checkout step.
* Enhance creating Tamara order via RestAPI.

= 1.7.7 - 2022-07-03 =

Enhancements:

* Add Public key field to Widgets.

Bugfixes:

* Fix error on Default Billing Country field on init.

= 1.7.6 - 2022-06-10 =

Enhancements:

* Use default title and description for payment types on checkout.
* Update Discount name when creating Tamara order for checkout session.
* Add new option field "Public Key" used for Popup widget.

= 1.7.5 - 2022-05-11 =

Enhancements:

* Update available amount to display popup widget on single product page.

= 1.7.4 - 2022-04-23 =

Enhancements:

* Update new Tamara logo on payment success page.
* Enhance Tamara setting option fields in Admin.

= 1.7.3 - 2022-04-02 =

Enhancements:

* Update new Tamara logo and PDP badge.
* Add BHD currency options.

= 1.7.2 - 2022-03-08 =

Enhancements:

* Only display the PDP widget and Tamara payment options on supported currency.

= 1.7 - 2021-12-30 =

Bugfixes:

* Add Credit Precheck feature option on checkout.
* Update default Arabic title for Pay In Xs on checkout.

= 1.6.18 - 2021-12-25 =

Bugfixes:

* Fix conflicts with WooCommerce multiple currencies.
* Update PDP Widget price when product's price is out of all limits.
* Turn all instalment plans settings off when it's unavailable.

= 1.6.17 - 2021-12-16 =

Enhancements:

* Keep cart items for orders with cancelled/failed payments from Tamara.
* Update PDP Widget price according to selected variation product price.
* Enhance Default Billing Country and Allowed Shipping Country field options in settings.

= 1.6.16 - 2021-12-02 =

Enhancements:

* Adjust priority payment methods displayed on popup widget.
* Adjust description country text on checkout box.

= 1.6.15 - 2021-11-09 =

Enhancements:

* Add Disable Tamara popup widget option on single product page.

= 1.6.14 - 2021-10-20 =

Enhancements:

* Raise admin error if allowed shipping country codes setting is empty.
* Add help text for allowed shipping country codes setting.

Bugfixes:

* Fix conflicts when making a refund for orders paid via other payments.

= 1.6.13 - 2021-10-14 =

Enhancements:

* Add always show Tamara popup widget on single product page option.

= 1.6.12 - 2021-10-07 =

Enhancements:

* Add payment type options for Kuwait and KWD currency.

= 1.6.11 - 2021-09-27 =

Bugfixes:

* Fix Tamara icon's height issue on checkout.

= 1.6.10 - 2021-09-22 =

Enhancements:

* Add enable Pay Later popup widget option on single product page.
* For re-capture event, update capture id to order meta data if it's already captured.
* Update status to successful if a re-capture event happens.
* Update new checkout icon.

= 1.6.9 - 2021-09-17 =

Enhancements:

* Fix refund event issue when auto capture is enabled.
* Update helptext description for refund event.

= 1.6.8 - 2021-09-11 =

Enhancements:

* Add excluded products and product categories options.
* Update error message when duplicated capture payment occurs.

= 1.6.7 - 2021-09-08 =

Enhancements:

* Move all inline scripts to js files.

= 1.6.6 - 2021-09-06 =

Bugfixes:

* Fix duplicate instalment plan widget on updated checkout.

= 1.6.5 - 2021-09-05 =

Bugfixes:

* Fix Pay In X titles error on completed order.
* Add more payment method ids on trigger Tamara events.
* Update Tamara success url to default WooCommerce order received page.

= 1.6.4 - 2021-08-24 =

Enhancements:

* Update help texts description.
* Update default titles for Pay In X.

Bugfixes:

* Hide Pay Later options if none is set from Tamara remote.
* Update new limits to options after being pulled from Tamara remote.
* Fix raise admin error if credential information is not correct or empty.

= 1.6.3 - 2021-08-19 =

Bugfixes:

* Fix description text styles on checkout.
* Fix styles dir attribtute for rtl.
* Update button color in Admin settings.
* Fix redirect url text translation on Tamara success page.

= 1.6.2 - 2021-08-18 =

Bugfixes:

* Fix redirect url on Tamara success page.

= 1.6.1 - 2021-08-17 =

Enhancements:

* Show help texts description by default.
* Shorten Tamara custom debug log displayed in Admin Settings.
* Add download link for Tamara custom debug log in Admin Settings.

Bugfixes:

* Fix Tamara widget call errors all checkout and product page.
* Remove Pay Later settings if none is set from Tamara remote.

= 1.6.0 - 2021-08-10 =

Enhancements:

* Remove Pay By Instalments and add Tamara Pay In X (from 3 to 12 instalments).
* Add Tamara redirect url to meta tag on payment success page.
* Update payment types descriptions on checkout.
* Add help texts description within settings fields.
* Add Pay In X Options fields in settings.
* Update Pay In X settings for order created via RESTAPI.
* Re-organize all settings fields.
* Update Tamara product widgets and checkout widgets for Pay In X.

Bugfixes:

* Fix pulling payment types min/max error on first settings saved.
* Fix payment methods order on checkout.

= 1.5.5 - 2021-07-15 =

Enhancements:

* Add Tamara success content shortcode [tamara_success_html].
* Add After Tamara success action hook `after_tamara_success`.
* Add Tamara authorise order shortcode [tamara_authorise_order].
* Add Tamara after payment cancelled filter hook `after_tamara_cancel`.
* Add Tamara after payment failed filter hook `after_tamara_failure`.

Bugfixes:

* Update default Authorised Done order status.

= 1.5.1 - 2021-06-15 =

Enhancements:

* Update Pay in 3 description on checkout.
* Add shortcode attributes for [tamara_shop_popup] widget.
* Add shortcode to print out Tamara success content.
* Add custom page option for Tamara success content.
* Update default status for Tamara Authorised success.

Bugfixes:

* Fix WC default country location on checkout.
* Fix Tamara success page layout.

= 1.5.0 - 2021-04-19 =

Enhancements:

* Split payment types into 2 different methods on checkout screen.
* Add before add to cart form position to display popup.
* Add custom title fields for payment types on both languages EN & AR.
* Add default titles for payment types on both languages EN & AR.
* Update descriptions on checkout screen for payment types.
* Update translation files for new strings.

= 1.4.31 - 2021-04-07 =

Bugfixes:

* Fix response for order created failed through Rest API.
* Update payment type param format for request body.

= 1.4.30 - 2021-04-07 =

Bugfixes:

* Fix limit amounts for each country payment types on checkout.


= 1.4.29 - 2021-03-18 =

Enhancements:

* Add Tamara Cancel Id to order meta data.
* Disable Iframe checkout on mobile.
* Add Tamara checkout data to order meta data created through RESTAPI.
 * Create WC Order Endpoint `wp-json/wc/v3/orders/`:
  - "meta_data": [
            {
                "key": "tamara_checkout_session_id",
                "value": "18f6efe9-6cac-48a2-bc79-28b3317a8b96"
            },
            {
                "key": "tamara_checkout_url",
                "value": "https://checkout-sandbox.tamara.co/checkout/18f6efe9-6cac-48a2-bc79-28b3317a8b96?locale=en_US&orderId=9dde35e1-259f-423b-9149-c259e393ea35"
            }
        ]
 * Get Order details Endpoint `wp-json/wc/v3/orders/{order-id}`:
  - "meta_data": [
            {
                "key": "tamara_checkout_session_id",
                "value": "8944639c-e762-4939-a230-7ceca309c561"
            },
            {
                "key": "tamara_checkout_url",
                "value": "https://checkout-sandbox.tamara.co/checkout/8944639c-e762-4939-a230-7ceca309c561?locale=en_US&orderId=8f893a5f-ca3a-4691-a9cf-036758234d08"
            }
        ]

= 1.4.26 - 2021-03-02 =

Bugfixes:

* Add limit amount for Pay By Later popup widget to display on frontend.
* Add force pull from remote param for get payment types through Tamara API.

Enhancements:

* Update translation files for new error codes.

= 1.4.25 - 2021-02-25 =

Enhancements:

* Edit instalment plans amount to display on frontend.
* Add default country code to verify Tamara API.

= 1.4.22 - 2021-01-30 =

Bugfixes:

* Get correct product price to display on instalment popup.
* Fix Tamara widget call repeat on checkout.
* Add redirect url on order pay page.

= 1.4.6 - 2020-12-30 =

Enhancements:

* Improve Tamara popups to have better view.
* Use custom scripts to call Tamara widgets.
* Remove Capture ID in condition when creating a Refund request.

= 1.4.4 - 2020-12-09 =

Enhancements:

* Improve Tamara popups to have better view.
* Edit get Woocommerce checkout/cart methods.
* Update translation for Arabic language.

= 1.4.1 - 2020-12-03 =

Enhancements:

* Improve Tamara Checkout fields to have better view.
* Edit options for Pay By Later and Pay By Instalments on checkout.

= 1.4.0 - 2020-11-30 =

Bugfixes:

* Fix errors and not loading services when unused.
* Move some methods to Tamara Checkout class.

Enhancements:

* Add Tamara Pay By Instalments service.
* Add Update status and add order note wrapper.
* Add options for Pay By Later and Pay By Instalments.

= 1.3.4 - 2020-11-12 =

Bugfixes:

* Fix errors on duplicate Jquery modal script.
* Enhance get payment types method.
* Fix payment icon layout displays on checkout.

Enhancements:

* Add allowed shipping country codes option.
* Hide Tamara Payment method on checkout if the total order is under/over limit.
* Polish Tamara Success page returned.

= 1.3.3 - 2020-11-12 =

Bugfixes:

* Fix errors on check Woocommerce existence.

Enhancements:

* Add allow message log option.

= 1.2.8 - 2020-11-03 =

Bugfixes:

* Fix errors display on Iframe checkout.
* Add some error codes for error displaying on checkout.
* Fix errors when webhook id value displays on Admin settings page.

Enhancements:

* Not run the webhook events on authorised orders.

= 1.2.3 - 2020-09-25 =

Bugfixes:

* Fix errors display on Iframe checkout.
* Edit the parse request to fix conflicts with other plugins.

= 1.2.2 - 2020-09-20 =

Enhancements:

* Add Iframe for Tamara Checkout.

= 1.2.1 - 2020-09-19 =

Enhancements:

* Add specific endpoints for merchant urls.

= 1.2.0 - 2020-09-18 =

Enhancements:

* Add Custom Tamara status for Capturing Payment.

= 1.1.9 - 2020-09-13 =

Bugfixes:

* Fix automatic disabling issue.
* Repair to have better readme file.

= 1.1.8 - 2020-09-06 =

Bugfixes:

* Fixes errors displayed on Checkout page.

Enhancements:

* Add webhook to handle Order declined and Order expired from Tamara.
* Update the Arabic translation.

= 1.0 - 2020-08-28 =

Release version
