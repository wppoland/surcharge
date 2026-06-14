<?php

declare(strict_types=1);

namespace Surcharge\Fee;

defined('ABSPATH') || exit;

/**
 * Reads, normalises and persists fee definitions stored in the plugin settings
 * option. Acts as the single source of truth for the fee data shape, so both the
 * admin UI and the checkout applicator agree on structure and types.
 */
final class FeeRepository
{
    public const OPTION = 'surcharge_settings';

    /**
     * Whether the plugin's master switch is on.
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->settings()['enabled'] ?? false);
    }

    /**
     * All configured fees as immutable value objects.
     *
     * @return Fee[]
     */
    public function all(): array
    {
        $settings = $this->settings();
        $raw      = $settings['fees'] ?? [];

        if (! is_array($raw)) {
            return [];
        }

        $fees = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $fees[] = $this->hydrate($this->normalizeFee($row));
        }

        return $fees;
    }

    /**
     * Only the fees that are individually enabled.
     *
     * @return Fee[]
     */
    public function active(): array
    {
        return array_values(array_filter($this->all(), static fn (Fee $fee): bool => $fee->enabled));
    }

    /**
     * Settings array merged over packaged defaults.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $stored = get_option(self::OPTION, []);
        if (! is_array($stored)) {
            $stored = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require SURCHARGE_DIR . 'config/defaults.php';

        return array_merge($defaults, $stored);
    }

    /**
     * Coerce a raw settings row into the canonical fee shape with safe types.
     * Used by both load and save paths so the stored data is always consistent.
     *
     * @param array<string, mixed> $row
     * @return array{label:string,type:string,amount:float,taxable:bool,enabled:bool}
     */
    public function normalizeFee(array $row): array
    {
        $type = (string) ($row['type'] ?? Fee::TYPE_FIXED);
        if (! in_array($type, [Fee::TYPE_FIXED, Fee::TYPE_PERCENT], true)) {
            $type = Fee::TYPE_FIXED;
        }

        $amount = (float) ($row['amount'] ?? 0);
        if (Fee::TYPE_PERCENT === $type) {
            $amount = min(100.0, max(0.0, $amount));
        } else {
            $amount = max(0.0, $amount);
        }

        return [
            'label'   => (string) ($row['label'] ?? ''),
            'type'    => $type,
            'amount'  => $amount,
            'taxable' => ! empty($row['taxable']),
            'enabled' => ! empty($row['enabled']),
        ];
    }

    /**
     * @param array{label:string,type:string,amount:float,taxable:bool,enabled:bool} $row
     */
    private function hydrate(array $row): Fee
    {
        return new Fee(
            $row['label'],
            $row['type'],
            $row['amount'],
            $row['taxable'],
            $row['enabled'],
        );
    }
}
