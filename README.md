Installation
============

To install the plugin, follow the below instructions.
1. Copy the contents of the plugin (x-cart.zip) to their respective folders except for install_hosted.sql
2. Run the command  found inside install_hosted in your db.
3. After executing the sql commands, the payment method should be displayed in the Payment Methods list.
4. Go to your x-cart backend, Settings-> Payment Methods and find Credit Card - Checkout.com. Click on Configure button to configure the module.


Webhook
============
Url: example.com/payment/includes/checkoutapipayment_webhook.php


Redirection
============
Success Url:example.com/payment/includes/checkoutapipayment_callback.php
Fail Url: example.com/payment/includes/checkoutapipayment_fail.php