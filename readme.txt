=== Plogins Surcharge - Checkout Fees for WooCommerce ===
Contributors: motylanogha
Tags: woocommerce, checkout, fees, surcharge, payment fee
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.2
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add fixed or percentage fees to the WooCommerce cart and checkout.

== Description ==

Surcharge lets you add one or more fees to the WooCommerce cart and checkout. Each fee is either a fixed amount or a percentage of the cart subtotal.

Fees are added through the WooCommerce fees API, so they appear in the cart totals, on the checkout page, and on the saved order, the same way shipping or tax does. The cart and checkout blocks and HPOS are both supported.

The code lives at https://github.com/wppoland/plogins-surcharge if you want to read it, report a bug, or suggest a fee type.

= Documentation and links =

* **Documentation** - https://plogins.com/plogins-surcharge/docs/
* **Plugin page** - https://plogins.com/plogins-surcharge/
* **Source code** - https://github.com/wppoland/plogins-surcharge
* **Bug reports and feature requests** - https://github.com/wppoland/plogins-surcharge/issues


= What it does =

* Add as many fees as you need, each a fixed amount or a percentage of the cart.
* Flag a fee as taxable so WooCommerce runs it through your normal tax rules.
* Turn every fee off at once with a master switch, without losing the rows you set up.
* Enable or disable individual fees, so you can keep a fee configured but inactive.
* Manage it all from one settings screen under WooCommerce → Surcharge.
* No external services, no account, no tracking.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/plogins-surcharge`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to WooCommerce → Surcharge and add your first fee.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. Surcharge extends the WooCommerce cart and checkout and does nothing without it.

= How is a percentage fee calculated? =

It is taken from the cart contents subtotal, including the tax on those items, and before any shipping or other fees are added. Percentages are capped at 100.

= Can I add more than one fee? =

Yes. Add as many fee rows as you need; each one is applied independently.

= Where do fees appear? =

On the checkout order totals table, labelled with the name you set for each fee row.

= Do percentage fees include tax? =

Percentage fees are calculated from the cart contents subtotal including line tax, before shipping and other fees.


= Does this plugin work on WordPress Multisite? =

Yes. This plugin is compatible with WordPress Multisite. Network activate it or activate it on individual sites; each site keeps its own settings and data.

== Screenshots ==

1. On the storefront.
2. Settings in the WordPress admin.
3. On a mobile device.
== External Services ==

Surcharge does not connect to any external service. It calls no remote APIs, loads no third-party scripts, fonts, or trackers, and sends nothing off your site. Its only stylesheet and script are served from the plugin folder and loaded just on the WooCommerce → Surcharge admin screen.

All data stays in your own database: your fee rows and the master switch are kept in the `surcharge_settings` option, and a schema marker in `surcharge_db_version`. Both options are removed when you delete the plugin. The plugin creates no custom tables and sends no email; fees are applied at runtime through WooCommerce's own cart fees API.

== Changelog ==

= 0.1.2 =
* Renamed to Plogins Surcharge for WooCommerce for a more distinctive plugin name.

= 0.1.1 =
* `surcharge/fee_amount` filter so add-ons can override per-fee amounts (e.g. tiered rules in Surcharge Pro).

= 0.1.0 =
* Initial release: fixed and percentage checkout fees, taxable option, and a master switch.
