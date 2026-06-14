<?php

declare(strict_types=1);

namespace Surcharge\Fee;

defined('ABSPATH') || exit;

/**
 * Immutable value object describing a single configurable checkout fee.
 * Constructed from sanitised settings by FeeRepository, so every property here
 * is already trustworthy.
 */
final class Fee
{
    public const TYPE_FIXED   = 'fixed';
    public const TYPE_PERCENT = 'percent';

    /**
     * @param string $label   Human-readable fee label shown to customers.
     * @param string $type    One of TYPE_FIXED or TYPE_PERCENT.
     * @param float  $amount  Fixed amount or percentage (0-100), per $type.
     * @param bool   $taxable Whether WooCommerce should tax this fee.
     * @param bool   $enabled Whether this fee is active.
     */
    public function __construct(
        public readonly string $label,
        public readonly string $type,
        public readonly float $amount,
        public readonly bool $taxable,
        public readonly bool $enabled,
    ) {
    }

    public function isPercent(): bool
    {
        return self::TYPE_PERCENT === $this->type;
    }

    /**
     * Resolve the monetary amount of this fee against a cart subtotal.
     *
     * @param float $cartTotal The base the percentage is applied to.
     * @return float The fee amount (always >= 0).
     */
    public function resolveAmount(float $cartTotal): float
    {
        $value = $this->isPercent()
            ? $cartTotal * ($this->amount / 100)
            : $this->amount;

        return max(0.0, $value);
    }
}
