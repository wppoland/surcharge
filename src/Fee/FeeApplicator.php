<?php

declare(strict_types=1);

namespace Surcharge\Fee;

use Surcharge\Contract\HasHooks;

defined('ABSPATH') || exit;

/**
 * Applies configured fees to the cart via the WooCommerce fees API, so the
 * totals shown in cart/checkout and stored on the order reflect exactly the
 * fees the merchant set up.
 */
final class FeeApplicator implements HasHooks
{
    public function __construct(private readonly FeeRepository $repository)
    {
    }

    public function registerHooks(): void
    {
        add_action('woocommerce_cart_calculate_fees', [$this, 'applyFees']);
    }

    /**
     * @param \WC_Cart $cart The cart being calculated.
     */
    public function applyFees($cart): void
    {
        // Never run in admin (except AJAX) and bail on any unexpected state so
        // a misconfiguration can never fatal a checkout.
        if (is_admin() && ! wp_doing_ajax()) {
            return;
        }
        if (! $this->repository->isEnabled()) {
            return;
        }
        if (! $cart instanceof \WC_Cart) {
            return;
        }

        /**
         * Whether Surcharge fees should apply to the current request.
         *
         * FREE passes true once the master switch is on. Add-ons (e.g.
         * Surcharge Pro role restrictions) may return false to skip all fees
         * for the current customer/context.
         *
         * @param bool $applies Whether the FREE conditions passed.
         */
        if (! apply_filters('surcharge/fee_applies', true)) {
            return;
        }

        $cartTotal = $this->cartBase($cart);

        $usedLabels = [];
        foreach ($this->repository->active() as $index => $fee) {
            if ('' === trim($fee->label)) {
                continue;
            }

            $amount = $fee->resolveAmount($cartTotal);
            if ($amount <= 0) {
                continue;
            }

            // WooCommerce keys fees by name; guarantee uniqueness so two fees
            // sharing a label do not silently overwrite one another.
            $label = $fee->label;
            if (isset($usedLabels[$label])) {
                $label .= ' #' . ($index + 1);
            }
            $usedLabels[$fee->label] = true;

            $cart->add_fee($label, $amount, $fee->taxable);
        }
    }

    /**
     * The base used for percentage fees: cart contents subtotal (excluding
     * existing fees and shipping).
     */
    private function cartBase(\WC_Cart $cart): float
    {
        return (float) $cart->get_subtotal() + (float) $cart->get_subtotal_tax();
    }
}
