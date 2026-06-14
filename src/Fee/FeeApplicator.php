<?php

declare(strict_types=1);

namespace Surcharge\Fee;

use Surcharge\Contract\HasHooks;

defined('ABSPATH') || exit;

/**
 * Applies configured fees to the cart via the WooCommerce fees API. Each fee is
 * gated by its conditions (minimum cart total, payment method, shipping country)
 * before being added, so the totals shown in cart/checkout and stored on the
 * order reflect exactly the rules the merchant set.
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

        $cartTotal = $this->cartBase($cart);
        $gateway   = $this->chosenPaymentMethod();
        $country   = $this->shippingCountry();

        $usedLabels = [];
        foreach ($this->repository->active() as $index => $fee) {
            if ('' === trim($fee->label)) {
                continue;
            }
            $applies = $this->passesConditions($fee, $cartTotal, $gateway, $country);

            /**
             * Filter whether a fee applies to the current cart. PRO add-ons hook
             * here to layer on extra conditions (e.g. item quantity, user role).
             *
             * @param bool     $applies Whether the built-in conditions passed.
             * @param Fee      $fee     The fee being evaluated.
             * @param \WC_Cart $cart    The cart being calculated.
             */
            $applies = (bool) apply_filters('surcharge/fee_applies', $applies, $fee, $cart);

            if (! $applies) {
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

    private function passesConditions(Fee $fee, float $cartTotal, string $gateway, string $country): bool
    {
        if ($fee->minCartTotal > 0 && $cartTotal < $fee->minCartTotal) {
            return false;
        }
        if ('' !== $fee->paymentMethod && $fee->paymentMethod !== $gateway) {
            return false;
        }
        if (! empty($fee->countries) && ('' === $country || ! in_array($country, $fee->countries, true))) {
            return false;
        }

        return true;
    }

    /**
     * The base used both for the minimum-total condition and percentage fees:
     * cart contents subtotal (excluding existing fees and shipping).
     */
    private function cartBase(\WC_Cart $cart): float
    {
        return (float) $cart->get_subtotal() + (float) $cart->get_subtotal_tax();
    }

    private function chosenPaymentMethod(): string
    {
        if (function_exists('WC') && WC()->session) {
            $chosen = WC()->session->get('chosen_payment_method');
            if (is_string($chosen)) {
                return $chosen;
            }
        }

        return '';
    }

    private function shippingCountry(): string
    {
        if (function_exists('WC') && WC()->customer) {
            $country = WC()->customer->get_shipping_country();
            if (is_string($country) && '' !== $country) {
                return strtoupper($country);
            }
            $billing = WC()->customer->get_billing_country();
            if (is_string($billing)) {
                return strtoupper($billing);
            }
        }

        return '';
    }
}
