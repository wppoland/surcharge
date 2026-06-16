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
 * taxable). All output is escaped; all input is sanitised on save through
 * FeeRepository's canonical shape. The save capability is aligned to
 * manage_woocommerce so shop managers (not just admins) can save.
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

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $option  = FeeRepository::OPTION;
        $enabled = $this->repository->isEnabled();
        $fees    = $this->repository->all();
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
                    <h2><?php esc_html_e('Add checkout fees in two steps', 'surcharge'); ?></h2>
                    <p><?php esc_html_e('1. Add a fee row below. 2. Choose a fixed amount or a percentage of the cart. Fees appear in the cart, at checkout and on the order.', 'surcharge'); ?></p>
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
                                    <?php esc_html_e('Enable fees', 'surcharge'); ?>
                                </th>
                                <td>
                                    <label for="surcharge_enabled">
                                        <input type="checkbox" id="surcharge_enabled" name="<?php echo esc_attr($option); ?>[enabled]" value="1" <?php checked($enabled, true); ?> />
                                        <?php esc_html_e('Apply the fees below at cart and checkout.', 'surcharge'); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('Master switch. When off, no fees are added to any cart, regardless of the rows below.', 'surcharge'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="surcharge-admin__card">
                    <div class="surcharge-admin__card-head">
                        <h2><?php esc_html_e('Fees', 'surcharge'); ?></h2>
                        <p class="surcharge-admin__card-hint"><?php esc_html_e('Each row is one fee. A fee with no label is skipped.', 'surcharge'); ?></p>
                    </div>

                    <div class="surcharge-fees" id="surcharge-fees"
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

        $label      = $fee?->label ?? '';
        $type       = $fee?->type ?? Fee::TYPE_FIXED;
        $amount     = null !== $fee ? (string) $fee->amount : '';
        $taxable    = $fee?->taxable ?? false;
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
                </p>

                <?php
                // Presentation-only till readout. Mirrors the Type select for a
                // quick "what prints on the receipt" glance. The <select> stays
                // the source of truth; with JS off this field is hidden via CSS.
                $isPercent = (Fee::TYPE_PERCENT === $type);
                ?>
                <p class="surcharge-fee__field surcharge-fee__field--readout" aria-hidden="true">
                    <span class="surcharge-fee__readout" data-fixed-glyph="&#43;&#36;" data-percent-glyph="&#43;&#37;">
                        <span class="surcharge-fee__readout-glyph"><?php echo $isPercent ? '&#43;&#37;' : '&#43;&#36;'; ?></span>
                        <span class="surcharge-fee__readout-text"><?php esc_html_e('Receipt line', 'surcharge'); ?></span>
                    </span>
                </p>

                <p class="surcharge-fee__field surcharge-fee__field--check">
                    <label for="<?php echo esc_attr($uid . '-taxable'); ?>">
                        <input type="checkbox" id="<?php echo esc_attr($uid . '-taxable'); ?>" name="<?php echo esc_attr($base . '[taxable]'); ?>" value="1" <?php checked($taxable, true); ?> />
                        <?php esc_html_e('Taxable', 'surcharge'); ?>
                    </label>
                </p>
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

                $fees[] = $this->repository->normalizeFee([
                    'label'   => $label,
                    'type'    => sanitize_key((string) ($row['type'] ?? Fee::TYPE_FIXED)),
                    'amount'  => isset($row['amount']) ? (float) wc_format_decimal((string) $row['amount']) : 0,
                    'taxable' => ! empty($row['taxable']),
                    'enabled' => ! empty($row['enabled']),
                ]);
            }
        }

        return [
            'enabled' => ! empty($raw['enabled']),
            'fees'    => array_values($fees),
        ];
    }
}
