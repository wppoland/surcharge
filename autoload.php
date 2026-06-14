<?php
/**
 * Autoloading: prefer Composer's optimized classmap when present. Fall back to a
 * minimal PSR-4 autoloader so the plugin still boots if vendor/ is somehow absent.
 *
 * @package Surcharge
 */

declare(strict_types=1);

namespace Surcharge;

defined('ABSPATH') || exit;

$surcharge_composer = __DIR__ . '/vendor/autoload.php';
if (is_readable($surcharge_composer)) {
    require_once $surcharge_composer;
    return;
}

spl_autoload_register(static function (string $class): void {
    $prefix  = 'Surcharge\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative = substr($class, $len);
    $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (is_readable($file)) {
        require_once $file;
    }
});
