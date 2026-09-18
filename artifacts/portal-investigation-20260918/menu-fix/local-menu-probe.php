<?php
/** Request-only local reproduction of menu filters; no saved options changed. */
defined('ABSPATH') || exit;
if (($_SERVER['HTTP_HOST'] ?? '') !== 'localhost:10003') {
    return;
}
add_action('arraysubs_after_boot', static function () {
    if (get_current_user_id() !== 525) {
        return;
    }
    $case = sanitize_key($_GET['menu_fix_case'] ?? '');
    if (in_array($case, ['no-orders', 'orders-restored', 'no-anchors', 'pro-hidden'], true)) {
        add_filter('woocommerce_account_menu_items', static function ($items) use ($case) {
            unset($items['orders']);
            if ($case === 'no-anchors') {
                unset($items['customer-logout']);
            }
            return $items;
        }, 5);
    }
    if ($case === 'orders-restored') {
        add_filter('woocommerce_account_menu_items', static function ($items) {
            return array_slice($items, 0, 1, true) + ['orders' => 'Orders'] + array_slice($items, 1, null, true);
        }, 20);
    }
    if ($case === 'pro-hidden') {
        $settings = arraysubs_get_settings();
        $settings['profile_builder']['my_account_menu_items_enabled'] = true;
        add_filter('pre_option_arraysubs_settings', static fn() => $settings);
        add_filter('pre_option_arraysubs_myaccount_menu_config', static fn() => [
            ['id' => 'subscriptions', 'type' => 'default', 'enabled' => false],
        ]);
        require_once WP_PLUGIN_DIR . '/arraysubspro/src/functions/myaccount-editor-helpers.php';
        require_once WP_PLUGIN_DIR . '/arraysubspro/src/Features/MyAccountEditor/Services/Hooks.php';
        new \ArraySubsPro\Features\MyAccountEditor\Services\Hooks();
    }
}, 20);
