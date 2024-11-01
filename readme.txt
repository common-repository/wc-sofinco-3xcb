=== Sofinco 3XCB ===
Contributors: paybox
Donate link: none
Tags: Payment Gateway, Orders, woocommerce, e-commerce, payment, Sofinco
Requires at least: 5.0.0
Tested up to: 6.6
Stable tag: 0.9.9.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 4.0
WC tested up to: 9.2
This plugin is a Sofinco 3x CB payment gateway for WooCommerce 4.x

== Description ==

This module adds a Sofinco Payment Gateway to your Installation of WooCommerce.

Sofinco is a Payment Services Provider in Europe, part of the Verifone Group.

plugin actions in wordpress:

this plugin offers an admin panel from the order section to the settings of Woocommerce.
it adds payment information to the orders details and changes the status of orders (upon reception of an IPN, see below.) and adds payment means on the checkout page.

This plugin takes information from the order and creates a form containing the details of the payment to be made, including parameters configured in the admin panel of the module that identify the mechant.

The plugin checks for availability of the Sofinco platform, through a call to our servers.
It then submits with javascript the form to the first available server.

the customer is then presented with a payment page, hosted on the Sofinco Platform (urls above).

The Sofinco Platform sends an Instant Payment Notification (IPN) to the server when the customer actually made the payment, indicating to the merchant the status of the payment.

the plugin generates a url that can catch the IPN call from Sofinco's server, filtering incoming calls to the Sofinco IP address.

if payment is successfull, then the plugin validates the order though woocommerce.

== Installation ==

1. Upload the entire folder `woocommerce-sofinco` to the `/wp-content/plugins/` directory
or through WordPress's plugin upload/install mecanism.

2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= What is the HMAC key in the admin panel for ? =

The HMAC key is generated on sofinco server through your sofinco back office. it is used to authenticate your calls to Sofinco Server. it is generated on the platform you choose: Production (live) or Pre-Production (test)

= My orders are not validated, even though the payment went through ? =

The oder paid with Sofinco is only validated upon rectpion of a positive Instant Payment Notification (IPN) this IPN is authenticated with the filter on the IP address, if the IP is somewhow changed, the plugin will give a 500 HTTP error.
Avoid Maintenance mode, or allow Sofinco IP to go through (194.2.122.158,195.25.7.166,195.101.99.76). If the WordPress Installation is in maintenance mode, the Sofinco server will not be able to contact it.

= Something is not working for me, how can i get help ? =

Contact [Sofinco WordPress Support](mailto:wordpress-sofinco@verifone.com "WordPress support at sofinco@verifone"), we will be glad to help you out !

== Screenshots ==

1. The administration panel: payment configuration
2. The administration panel: Sofinco Account parameters
3. The Checkout page: Payment method choice (1/ 3 times)
4. The Payment Means choice (hosted at Sofinco)
5. The Payment page
6. Once successfully processed, the Payment transaction details appear in the order details

== Changelog ==
= 0.9.9.5 =
- Fix error if no phone is provided
- Improve generation of PBX_BILLING field
- Improve IPN checks

= 0.9.9.4 =
- Fix HMAC calculation in specific cases
- Update compatibility

= 0.9.9.3 =
- Fix CountryCode value if empty

= 0.9.9.2 =
- Rework module configuration
- Improve logging
- Add 3DS fields
- Fix compatibility with some PHP & WooCommerce versions

= 0.9.9.1 =
Fixed IPN treatment and Logging system

= 0.9.8.8 =
Fixing various bugs: transaction/call mixup, phone formatting and labeling, etc..

= 0.9.8.8 =
Fixing various bugs: transaction/call mixup, low limit, etc..

= 0.9.8.7 =
Fixing XML parameters and adding lower and higher limits for payment mean activation.

= 0.9.8.6 =
First version of Sofinco 3XCB module.

= 0.9.8.5 =
Correction for HTTP2 compatibility and notice 'id of order called directly' on order display.

= 0.9.8.4 =
Correction for PHP7.2 mcrypt removal: openssl used if no mcrypt present.

= 0.9.8.3 =
Correction for potential HTTP 500 error: thx @.

= 0.9.8.2 =
Correction for network urls, order properties calling.

= 0.9.8.1 =
Correction of url called, to work for mobile.

= 0.9.8 =
Correction of minor bugs.

= 0.9.7.1 =
Correction of multisite wordpress bug.

= 0.9.7 =
Correction of a potential fatal error on error logging thx @vasyltech!.

Urls construct
= 0.9.6.9 =
Compatibility for folder-based wordpress mono-site.
Urls construct

= 0.9.6.8 =
Added compatibility for folder-based wordpress multi-site.
Removed IPN IP checking

= 0.9.6.7 =
Changed:
only rely on the $_SERVER data to check for the IP address:
this solves the non reception of the IPN  (error 500)

= 0.9.6.6 =
Second release:
Fixed:
-Missing table now created ok.
-"Syntax error: Unexpected token < " message when checking out,
-Use of deprecated functions to get pages url: now we use endpoints.

Added	:
-Informations about the payment on the order detail page, now actually displayed.
-card numbers appear in the detail
-three time payment IPN properly stored


= 0.9.6.5 =
First stable release



== Upgrade Notice ==

= 1.0 =
This is the first major Release.

