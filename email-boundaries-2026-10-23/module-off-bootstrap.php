<?php
/** WP-CLI request-only module state; no option writes or modules_saved action. */
if (!defined('WP_CLI') || !WP_CLI) { exit; }
WP_CLI::add_hook('after_wp_config_load', static function (): void {
    require_once ABSPATH . 'wp-includes/plugin.php';
    add_filter('arraysubs_is_module_enabled', static function ($enabled, $module) {
        return in_array($module, ['store_credit', 'cancellation_flow'], true) ? false : $enabled;
    }, PHP_INT_MAX, 2);
});
