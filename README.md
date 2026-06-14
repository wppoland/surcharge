# Surcharge - Checkout Fees for WooCommerce

Add one or more fees (fixed amount or percentage of the cart) to the WooCommerce
cart and checkout, each optionally conditional on minimum cart total, payment
method and/or shipping country. Fees are added through the official WooCommerce
fees API, so they appear in the cart totals, on the checkout page and on the
order.

This is a free, self-contained, wp.org-ready plugin. No external services, no
account, no tracking, no runtime Composer dependencies.

## Features

- Unlimited fees: fixed amount or percentage of the cart subtotal.
- Per-fee taxable flag (WooCommerce applies your standard tax rules).
- Optional, combinable conditions per fee:
  - Minimum cart total.
  - A specific payment method.
  - One or more shipping countries (two-letter codes).
- Master switch to pause all fees without deleting the configuration.
- Accessible, dark-mode-aware settings screen under **WooCommerce → Surcharge**.

## How it works

`Surcharge\Fee\FeeApplicator` hooks `woocommerce_cart_calculate_fees`, loads the
configured fees from `Surcharge\Fee\FeeRepository`, evaluates each fee's
conditions against the current cart (subtotal, chosen gateway, shipping country)
and adds the ones that qualify via `WC_Cart::add_fee()`.

Settings are stored in a single `surcharge_settings` option. The admin screen
(`Surcharge\Admin\Settings`) renders a repeatable fees table and sanitises every
row through the repository's canonical shape on save.

## Architecture

- `surcharge.php` — bootstrap: PHP/WooCommerce guards, HPOS + cart-blocks compat,
  boot on `init` priority 0, fires `surcharge/booted` for PRO companions.
- `src/Plugin.php` + `src/Container.php` — tiny DI container and boot sequence.
- `src/Fee/` — `Fee` value object, `FeeRepository`, `FeeApplicator`.
- `src/Admin/Settings.php` — the WooCommerce submenu settings page.
- `config/` — `services.php`, `hooks.php`, `defaults.php`.

## Development

```bash
composer install
composer cs        # PHPCS (WordPress security + i18n rules)
composer analyse   # PHPStan level 6
```

The plugin ships no runtime dependencies; `/vendor` is dev-only and excluded from
the wp.org build (see `.distignore`). A bundled PSR-4 fallback autoloader in
`autoload.php` is used in distribution.

## License

GPL-2.0-or-later.
