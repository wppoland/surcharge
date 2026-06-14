<?php
/**
 * Service wiring. Returns a closure that registers every service in the
 * container. Keep services thin; fee logic lives in the Fee namespace.
 *
 * @package Surcharge
 */

declare(strict_types=1);

use Surcharge\Admin\Settings;
use Surcharge\Container;
use Surcharge\Fee\FeeApplicator;
use Surcharge\Fee\FeeRepository;
use Surcharge\Migrator;

defined('ABSPATH') || exit;

return static function (Container $c): void {
    $c->singleton(Migrator::class, static fn (): Migrator => new Migrator());

    $c->singleton(FeeRepository::class, static fn (): FeeRepository => new FeeRepository());

    $c->singleton(FeeApplicator::class, static fn (Container $c): FeeApplicator => new FeeApplicator(
        $c->get(FeeRepository::class),
    ));

    $c->singleton(Settings::class, static fn (Container $c): Settings => new Settings(
        $c->get(FeeRepository::class),
    ));
};
