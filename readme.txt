=== Surcharge - Checkout Fees for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, checkout, fees, surcharge, payment fee
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add fixed or percentage checkout fees, optionally conditional on cart total, payment method or shipping country.

== Description ==

Surcharge lets you add one or more fees to the WooCommerce cart and checkout. Each fee is either a fixed amount or a percentage of the cart, and can be made conditional so it only applies when it should.

Fees are added through the official WooCommerce fees API, so they show up in the cart totals, on the checkout page, and on the resulting order — exactly like shipping or tax.

= Features =

* Add unlimited fees, each a fixed amount or a percentage of the cart subtotal.
* Mark a fee as taxable so WooCommerce applies your standard tax rules to it.
* Conditions per fee, all optional and combinable:
  * Minimum cart total before the fee applies.
  * A specific payment method (e.g. only charge a fee for Cash on Delivery).
  * One or more shipping countries.
* A clean, accessible settings screen under WooCommerce → Surcharge.
* Master switch to pause every fee without deleting your configuration.
* Self-contained: no external services, no account, no tracking.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/surcharge`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to WooCommerce → Surcharge and add your first fee.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. Surcharge extends the WooCommerce cart and checkout and does nothing without it.

= Can I charge a fee only for a specific payment method? =

Yes. Each fee has an optional "Payment method" condition. Pick a gateway and the fee only applies when the customer selects it at checkout.

= How is a percentage fee calculated? =

It is a percentage of the cart contents subtotal (before existing fees and shipping).

= Can a fee be limited to certain countries? =

Yes. Enter comma-separated two-letter country codes (e.g. US, CA, GB) in the fee's "Shipping countries" condition.

== Screenshots ==

1. The Surcharge settings screen with a conditional fee configured.

== Changelog ==

= 0.1.0 =
* Initial release: fixed and percentage checkout fees with cart-total, payment-method and country conditions.
