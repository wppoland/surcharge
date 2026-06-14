<?php
/**
 * Constants needed by PHPStan to analyse the plugin without bootstrapping WordPress.
 *
 * @package Surcharge
 */

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
    if (! defined('SURCHARGE_DIR')) {
        define('SURCHARGE_DIR', '/tmp/surcharge/');
    }
    if (! defined('SURCHARGE_URL')) {
        define('SURCHARGE_URL', 'https://example.test/wp-content/plugins/surcharge/');
    }
}

namespace Surcharge {
    if (! defined('Surcharge\\VERSION')) {
        define('Surcharge\\VERSION', '0.1.0');
    }
    if (! defined('Surcharge\\PLUGIN_FILE')) {
        define('Surcharge\\PLUGIN_FILE', '/tmp/surcharge/surcharge.php');
    }
}
