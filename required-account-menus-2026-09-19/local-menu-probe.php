<?php
/** Local, request-only conflict fixtures for required account navigation. */
defined('ABSPATH') || exit;
if (($_SERVER['HTTP_HOST'] ?? '') !== 'localhost:10003') {
    return;
}
$case = $_SERVER['HTTP_X_ARRAYSUBS_REQUIRED_CASE'] ?? '';
if (!in_array($case, ['removed', 'hidden', 'missing', 'blank-orders', 'features', 'early-removal'], true)) {
    return;
}
if (in_array($case, ['removed', 'missing', 'early-removal'], true)) {
    $remove = static function ($items) {
        unset($items['orders'], $items['subscriptions']);
        return $items;
    };
    add_filter('woocommerce_account_menu_items', $remove, $case === 'early-removal' ? 11 : 20);
    add_filter('woocommerce_account_menu_items', $remove, 1000);
    add_action('wp_loaded', static function () use ($remove) {
        add_filter('woocommerce_account_menu_items', $remove, PHP_INT_MAX);
    }, 1);
}
if (in_array($case, ['hidden', 'missing'], true)) {
    add_filter('option_arraysubs_myaccount_menu_config', static function ($items) use ($case) {
        if ($case === 'missing') {
            return array_values(array_filter($items, static fn ($item) => !in_array($item['id'] ?? '', ['orders', 'subscriptions'], true)));
        }
        foreach ($items as &$item) {
            if (in_array($item['id'] ?? '', ['orders', 'subscriptions'], true)) {
                $item['enabled'] = false;
                $item['label'] = 'Hidden old label';
            }
        }
        unset($item);
        return $items;
    });
}
if ($case === 'blank-orders') {
    add_filter('option_woocommerce_myaccount_orders_endpoint', static fn () => '');
}
if (in_array($case, ['features', 'early-removal'], true)) {
    add_filter('option_arraysubs_settings', static function ($settings) {
        $settings['feature_manager']['enabled'] = true;
        $settings['feature_manager']['show_in_my_account'] = true;
        return $settings;
    });
}
add_action('template_redirect', static function () {
    if (is_account_page()) {
        header('X-ArraySubs-QA-Endpoint: ' . wp_json_encode([
            'subscriptions' => is_wc_endpoint_url('subscriptions'),
            'view-subscription' => is_wc_endpoint_url('view-subscription'),
            'features' => is_wc_endpoint_url('features'),
            'store-credit' => is_wc_endpoint_url('store-credit'),
        ]));
    }
}, 999);
