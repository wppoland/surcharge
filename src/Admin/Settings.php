<?php

declare(strict_types=1);

namespace Surcharge\Admin;

use Surcharge\Contract\HasHooks;
use Surcharge\Fee\Fee;
use Surcharge\Fee\FeeRepository;

defined('ABSPATH') || exit;

/**
 * Admin settings page registered as a WooCommerce submenu ("Surcharge").
 *
 * Renders the master toggle plus a repeatable fees table (label, type, amount,
 * taxable, and conditions: minimum cart total, payment method, shipping country).
 * All output is escaped; all input is sanitised on save through FeeRepository's
 * canonical shape. The save capability is aligned to manage_woocommerce so shop
 * managers (not just admins) can save.
 */
final class Settings implements HasHooks
{
    private const PAGE = 'surcharge-settings';

    public function __construct(private readonly FeeRepository $repository)
    {
    }

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(string $hook): void
    {
        if (! str_contains($hook, self::PAGE)) {
            return;
        }

        wp_enqueue_style(
            'surcharge-admin',
            SURCHARGE_URL . 'assets/css/admin.css',
            [],
            \Surcharge\VERSION,
        );

        wp_enqueue_script(
            'surcharge-admin',
            SURCHARGE_URL . 'assets/js/admin.js',
            [],
            \Surcharge\VERSION,
            ['in_footer' => true, 'strategy' => 'defer'],
        );
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Surcharge', 'surcharge'),
            __('Surcharge', 'surcharge'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::PAGE,
            FeeRepository::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
            ],
        );

        add_filter(
            'option_page_capability_' . self::PAGE,
            static fn (): string => 'manage_woocommerce',
        );
    }

    /**
     * Accessible inline-help affordance: a "?" button wired to a tooltip via
     * aria-describedby, with a visible fallback span when JS/Popover is absent.
     */
    private function help(string $id, string $text): void
    {
        printf(
            '<button type="button" class="surcharge-help" aria-describedby="%1$s" aria-label="%2$s">?</button>'
                . '<span id="%1$s" role="tooltip" popover class="surcharge-tooltip">%3$s</span>'
                . '<span class="surcharge-help-fallback">%3$s</span>',
            esc_attr($id),
            esc_attr__('More information', 'surcharge'),
            esc_html($text),
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $option   = FeeRepository::OPTION;
        $enabled  = $this->repository->isEnabled();
        $fees     = $this->repository->all();
        $currency = function_exists('get_woocommerce_currency_symbol')
            ? wp_strip_all_tags(get_woocommerce_currency_symbol())
            : '';
        ?>
        <div class="wrap surcharge-admin">
            <h1>
                <?php echo esc_html(get_admin_page_title()); ?>
                <?php if ($enabled) : ?>
                    <span class="surcharge-admin__status surcharge-admin__status--on"><?php esc_html_e('Active', 'surcharge'); ?></span>
                <?php else : ?>
                    <span class="surcharge-admin__status surcharge-admin__status--off"><?php esc_html_e('Disabled', 'surcharge'); ?></span>
                <?php endif; ?>
            </h1>

            <div class="surcharge-admin__intro">
                <span class="surcharge-admin__intro-icon" aria-hidden="true">&#43;</span>
                <div>
                    <h2><?php esc_html_e('Add checkout fees in three steps', 'surcharge'); ?></h2>
                    <p><?php esc_html_e('1. Add a fee row below. 2. Choose a fixed amount or a percentage of the cart. 3. Optionally limit it to a minimum cart total, a payment method or a shipping country. Fees appear in the cart, at checkout and on the order.', 'surcharge'); ?></p>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <div class="surcharge-admin__card">
                    <h2><?php esc_html_e('General', 'surcharge'); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <span class="surcharge-admin__label">
                                        <?php esc_html_e('Enable fees', 'surcharge'); ?>
                                        <?php $this->help('surcharge-help-enabled', __('Master switch. When off, no fees are added to any cart, regardless of the rows below. Turn this off to pause all fees without deleting them.', 'surcharge')); ?>
                                    </span>
                                </th>
                                <td>
                                    <label for="surcharge_enabled">
                                        <input type="checkbox" id="surcharge_enabled" name="<?php echo esc_attr($option); ?>[enabled]" value="1" <?php checked($enabled, true); ?> />
                                        <?php esc_html_e('Apply the fees below at cart and checkout.', 'surcharge'); ?>
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="surcharge-admin__card">
                    <div class="surcharge-admin__card-head">
                        <h2><?php esc_html_e('Fees', 'surcharge'); ?></h2>
                        <p class="surcharge-admin__card-hint"><?php esc_html_e('Each row is one fee. Leave a condition blank to ignore it. A fee with no label is skipped.', 'surcharge'); ?></p>
                    </div>

                    <div class="surcharge-fees" id="surcharge-fees"
                        data-currency="<?php echo esc_attr($currency); ?>"
                        data-add-label="<?php esc_attr_e('Add fee', 'surcharge'); ?>">
                        <?php
                        if (empty($fees)) {
                            $this->renderFeeRow($option, 0, null);
                        } else {
                            foreach ($fees as $i => $fee) {
                                $this->renderFeeRow($option, (int) $i, $fee);
                            }
                        }
                        ?>
                    </div>

                    <p>
                        <button type="button" class="button surcharge-add-fee" id="surcharge-add-fee">
                            <span aria-hidden="true">+</span> <?php esc_html_e('Add fee', 'surcharge'); ?>
                        </button>
                    </p>

                    <?php $this->renderRowTemplate($option); ?>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render a single fee row. When $fee is null, an empty row is produced.
     */
    private function renderFeeRow(string $option, int $index, ?Fee $fee): void
    {
        $base = $option . '[fees][' . $index . ']';
        $uid  = 'surcharge-fee-' . $index;

        $label    = $fee?->label ?? '';
        $type     = $fee?->type ?? Fee::TYPE_FIXED;
        $amount   = null !== $fee ? (string) $fee->amount : '';
        $taxable  = $fee?->taxable ?? false;
        $minTotal = null !== $fee && $fee->minCartTotal > 0 ? (string) $fee->minCartTotal : '';
        $gateway  = $fee?->paymentMethod ?? '';
        $countries = null !== $fee ? implode(', ', $fee->countries) : '';
        $rowEnabled = $fee?->enabled ?? true;
        ?>
        <fieldset class="surcharge-fee" data-index="<?php echo esc_attr((string) $index); ?>">
            <legend class="screen-reader-text"><?php esc_html_e('Fee', 'surcharge'); ?></legend>

            <div class="surcharge-fee__grid">
                <p class="surcharge-fee__field surcharge-fee__field--label">
                    <label for="<?php echo esc_attr($uid . '-label'); ?>"><?php esc_html_e('Label', 'surcharge'); ?></label>
                    <input type="text" id="<?php echo esc_attr($uid . '-label'); ?>" name="<?php echo esc_attr($base . '[label]'); ?>" value="<?php echo esc_attr($label); ?>" class="regular-text" placeholder="<?php esc_attr_e('e.g. Handling fee', 'surcharge'); ?>" />
                </p>

                <p class="surcharge-fee__field">
                    <label for="<?php echo esc_attr($uid . '-type'); ?>"><?php esc_html_e('Type', 'surcharge'); ?></label>
                    <select id="<?php echo esc_attr($uid . '-type'); ?>" name="<?php echo esc_attr($base . '[type]'); ?>" class="surcharge-fee__type">
                        <option value="fixed" <?php selected($type, Fee::TYPE_FIXED); ?>><?php esc_html_e('Fixed amount', 'surcharge'); ?></option>
                        <option value="percent" <?php selected($type, Fee::TYPE_PERCENT); ?>><?php esc_html_e('Percentage of cart', 'surcharge'); ?></option>
                    </select>
                </p>

                <p class="surcharge-fee__field">
                    <label for="<?php echo esc_attr($uid . '-amount'); ?>"><?php esc_html_e('Amount', 'surcharge'); ?></label>
                    <input type="number" step="0.01" min="0" id="<?php echo esc_attr($uid . '-amount'); ?>" name="<?php echo esc_attr($base . '[amount]'); ?>" value="<?php echo esc_attr($amount); ?>" class="small-text surcharge-fee__amount" />
                    <span class="surcharge-fee__amount-suffix" aria-hidden="true"></span>
                </p>

                <p class="surcharge-fee__field surcharge-fee__field--check">
                    <label for="<?php echo esc_attr($uid . '-taxable'); ?>">
                        <input type="checkbox" id="<?php echo esc_attr($uid . '-taxable'); ?>" name="<?php echo esc_attr($base . '[taxable]'); ?>" value="1" <?php checked($taxable, true); ?> />
                        <?php esc_html_e('Taxable', 'surcharge'); ?>
                        <?php $this->help($uid . '-help-taxable', __('When ticked, WooCommerce applies tax to this fee using your standard tax rules.', 'surcharge')); ?>
                    </label>
                </p>
            </div>

            <div class="surcharge-fee__conditions">
                <span class="surcharge-fee__conditions-title">
                    <?php esc_html_e('Conditions', 'surcharge'); ?>
                    <?php $this->help($uid . '-help-cond', __('Optional. The fee only applies when every filled-in condition is met. Leave a field blank to ignore that condition.', 'surcharge')); ?>
                </span>
                <div class="surcharge-fee__grid">
                    <p class="surcharge-fee__field">
                        <label for="<?php echo esc_attr($uid . '-min'); ?>"><?php esc_html_e('Minimum cart total', 'surcharge'); ?></label>
                        <input type="number" step="0.01" min="0" id="<?php echo esc_attr($uid . '-min'); ?>" name="<?php echo esc_attr($base . '[min_cart_total]'); ?>" value="<?php echo esc_attr($minTotal); ?>" class="small-text" placeholder="0.00" />
                    </p>

                    <p class="surcharge-fee__field">
                        <label for="<?php echo esc_attr($uid . '-gateway'); ?>"><?php esc_html_e('Payment method', 'surcharge'); ?></label>
                        <select id="<?php echo esc_attr($uid . '-gateway'); ?>" name="<?php echo esc_attr($base . '[payment_method]'); ?>">
                            <option value=""><?php esc_html_e('Any payment method', 'surcharge'); ?></option>
                            <?php foreach ($this->paymentGateways() as $id => $title) : ?>
                                <option value="<?php echo esc_attr($id); ?>" <?php selected($gateway, $id); ?>><?php echo esc_html($title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>

                    <p class="surcharge-fee__field surcharge-fee__field--countries">
                        <label for="<?php echo esc_attr($uid . '-countries'); ?>">
                            <?php esc_html_e('Shipping countries', 'surcharge'); ?>
                            <?php $this->help($uid . '-help-countries', __('Comma-separated two-letter country codes (e.g. US, CA, GB). The fee applies only when the shipping country is in this list. Leave blank for all countries.', 'surcharge')); ?>
                        </label>
                        <input type="text" id="<?php echo esc_attr($uid . '-countries'); ?>" name="<?php echo esc_attr($base . '[countries]'); ?>" value="<?php echo esc_attr($countries); ?>" class="regular-text" placeholder="US, CA, GB" />
                    </p>
                </div>
            </div>

            <div class="surcharge-fee__footer">
                <label class="surcharge-fee__enabled" for="<?php echo esc_attr($uid . '-enabled'); ?>">
                    <input type="checkbox" id="<?php echo esc_attr($uid . '-enabled'); ?>" name="<?php echo esc_attr($base . '[enabled]'); ?>" value="1" <?php checked($rowEnabled, true); ?> />
                    <?php esc_html_e('Enabled', 'surcharge'); ?>
                </label>
                <button type="button" class="button-link surcharge-fee__remove"><?php esc_html_e('Remove fee', 'surcharge'); ?></button>
            </div>
        </fieldset>
        <?php
    }

    /**
     * A hidden template row JS clones for "Add fee". Uses the __INDEX__ token so
     * cloned rows get unique names without server round-trips.
     */
    private function renderRowTemplate(string $option): void
    {
        echo '<script type="text/html" id="surcharge-fee-template">';
        $this->renderFeeRow($option, 0, null);
        echo '</script>';
    }

    /**
     * Available payment gateway ids => titles, for the condition dropdown.
     *
     * @return array<string, string>
     */
    private function paymentGateways(): array
    {
        $out = [];
        if (function_exists('WC') && WC()->payment_gateways()) {
            foreach (WC()->payment_gateways()->payment_gateways() as $id => $gateway) {
                $title = is_object($gateway) && ! empty($gateway->title)
                    ? wp_strip_all_tags((string) $gateway->title)
                    : (string) $id;
                $out[(string) $id] = $title;
            }
        }

        return $out;
    }

    /**
     * Sanitise submitted settings into the canonical shape before save.
     *
     * @param mixed $raw
     * @return array<string, mixed>
     */
    public function sanitize(mixed $raw): array
    {
        if (! is_array($raw)) {
            $raw = [];
        }

        $fees = [];
        if (isset($raw['fees']) && is_array($raw['fees'])) {
            foreach ($raw['fees'] as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $label = sanitize_text_field((string) ($row['label'] ?? ''));
                // Skip wholly empty rows so the saved list stays clean.
                if ('' === $label && empty($row['amount'])) {
                    continue;
                }

                $countriesRaw = isset($row['countries']) ? sanitize_text_field((string) $row['countries']) : '';
                $countries    = '' === $countriesRaw ? [] : array_map('trim', explode(',', $countriesRaw));

                $normalized = $this->repository->normalizeFee([
                    'label'          => $label,
                    'type'           => sanitize_key((string) ($row['type'] ?? Fee::TYPE_FIXED)),
                    'amount'         => isset($row['amount']) ? (float) wc_format_decimal((string) $row['amount']) : 0,
                    'taxable'        => ! empty($row['taxable']),
                    'min_cart_total' => isset($row['min_cart_total']) ? (float) wc_format_decimal((string) $row['min_cart_total']) : 0,
                    'payment_method' => sanitize_text_field((string) ($row['payment_method'] ?? '')),
                    'countries'      => $countries,
                    'enabled'        => ! empty($row['enabled']),
                ]);

                $fees[] = $normalized;
            }
        }

        return [
            'enabled' => ! empty($raw['enabled']),
            'fees'    => array_values($fees),
        ];
    }
}
