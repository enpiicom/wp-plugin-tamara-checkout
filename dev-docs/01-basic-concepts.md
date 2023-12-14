## Basic Concepts
- Tamara Checkout plugin is a WP_Plugin instance of Enpii Base. It plays as a Service Provider.
- All services we need for Tamara will be initialized when register the plugin and bind to the wp_app() as singletons. We have:
  - Tamara Client service for interacting with Tamara API
  - Tamara Notification service for decode messages sent from Tamara
  - Tamara Widget service for embedding Tamara promotion widgets
  - Tamara WC Payment Gateway service (inherited from WC_Payment_Gateway) to handle the payment
