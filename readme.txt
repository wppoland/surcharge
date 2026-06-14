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

Add fixed or percentage fees to the WooCommerce cart and checkout.

== Description ==

Surcharge lets you add one or more fees to the WooCommerce cart and checkout. Each fee is either a fixed amount or a percentage of the cart subtotal.

Fees are added through the official WooCommerce fees API, so they show up in the cart totals, on the checkout page, and on the resulting order — exactly like shipping or tax.

= Features =

* Add unlimited fees, each a fixed amount or a percentage of the cart subtotal.
* Mark a fee as taxable so WooCommerce applies your standard tax rules to it.
* Master switch to pause every fee without deleting your configuration.
* A clean, accessible settings screen under WooCommerce → Surcharge.
* Self-contained: no external services, no account, no tracking.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/surcharge`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to WooCommerce → Surcharge and add your first fee.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. Surcharge extends the WooCommerce cart and checkout and does nothing without it.

= How is a percentage fee calculated? =

It is a percentage of the cart contents subtotal (before existing fees and shipping).

= Can I add more than one fee? =

Yes. Add as many fee rows as you need; each one is applied independently.

== Screenshots ==

1. The Surcharge settings screen with a fee configured.

== Changelog ==

= 0.1.0 =
* Initial release: fixed and percentage checkout fees, taxable option, and a master switch.
