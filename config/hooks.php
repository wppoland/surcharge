<?php
/**
 * Boot order: services listed here are resolved from the container and have
 * their registerHooks() called during Plugin::boot(). Each must implement
 * Surcharge\Contract\HasHooks.
 *
 * @package Surcharge
 *
 * @return array<class-string>
 */

declare(strict_types=1);

use Surcharge\Admin\Settings;
use Surcharge\Fee\FeeApplicator;

defined('ABSPATH') || exit;

return [
    FeeApplicator::class,
    Settings::class,
];
