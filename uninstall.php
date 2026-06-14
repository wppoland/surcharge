<?php

/**
 * Surcharge uninstall routine.
 *
 * Removes plugin options when the user deletes the plugin from the WordPress
 * admin. The plugin stores no custom tables.
 *
 * @package Surcharge
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('surcharge_settings');
delete_option('surcharge_db_version');
