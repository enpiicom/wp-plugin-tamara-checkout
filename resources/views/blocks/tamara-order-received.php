<?php

use Tamara_Checkout\App\WP\Tamara_Checkout_WP_Plugin;

echo Tamara_Checkout_WP_Plugin::wp_app_instance()->_t('Thank you for choosing Tamara! We will inform you once the merchant ships your order.')
?>
<div class="tamara-view-and-pay-button">
    <div class="tamara-view-and-pay-button__text">
        <a href="{{ $view_and_pay_url }}"
		   class="tamara-view-and-pay-button__link"
           target="_blank"><?php echo Tamara_Checkout_WP_Plugin::wp_app_instance()->_t('Go to Tamara and pay') ?>
		</a>
    </div>
</div>
