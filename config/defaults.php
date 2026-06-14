<?php
/**
 * Default settings, merged under the option key `surcharge_settings`.
 *
 * @package Surcharge
 *
 * @return array<string, mixed>
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return [
    'enabled' => true,
    // A list of fee definitions. Each fee is an associative array; see
    // Surcharge\Fee\FeeRepository::normalizeFee() for the canonical shape.
    'fees'    => [],
];
