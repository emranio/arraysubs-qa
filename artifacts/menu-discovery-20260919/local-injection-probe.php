<?php
/** Request-only LOCAL injection fixture. Never install on a customer site. */
defined('ABSPATH') || exit;
if (($_SERVER['HTTP_HOST'] ?? '') !== 'localhost:10003') {
    return;
}
$case = $_SERVER['HTTP_X_ARRAYSUBS_DISCOVERY_CASE'] ?? '';
if (!in_array($case, ['missing-saved', 'removed', 'removed-saved', 'renamed'], true)) {
    return;
}

// Make the two extra Pro menu injectors observable without changing settings.
add_filter('option_arraysubs_settings', static function ($settings) {
    $settings['feature_manager']['enabled'] = true;
    $settings['feature_manager']['show_in_my_account'] = true;
    $settings['store_credit']['enabled'] = true;
    return $settings;
});

add_filter('option_arraysubs_myaccount_menu_config', static function ($items) use ($case) {
    if (in_array($case, ['missing-saved', 'removed'], true)) {
        return array_values(array_filter($items, static fn ($item) => ($item['id'] ?? '') !== 'subscriptions'));
    }
    if ($case === 'renamed') {
        foreach ($items as &$item) {
            if (($item['id'] ?? '') === 'subscriptions') {
                $item['label'] = 'Support';
            }
        }
        unset($item);
    }
    return $items;
});

if (in_array($case, ['removed', 'removed-saved'], true)) {
    // Simulate an unrelated account-menu filter after ArraySubs injects items.
    add_filter('woocommerce_account_menu_items', static function ($items) {
        unset($items['subscriptions']);
        return $items;
    }, 20);
}
