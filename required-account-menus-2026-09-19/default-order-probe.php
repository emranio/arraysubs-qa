<?php
/** Request-only local fixtures for the default subscription position. */
defined('ABSPATH') || exit;
if (($_SERVER['HTTP_HOST'] ?? '') !== 'localhost:10003') {
    return;
}
$case = $_SERVER['HTTP_X_ARRAYSUBS_DEFAULT_ORDER'] ?? '';
if (!in_array($case, ['default', 'no-dashboard', 'custom'], true)) {
    return;
}
add_filter('option_arraysubs_myaccount_menu_config', static function ($items) use ($case) {
    if ($case !== 'custom') {
        return [];
    }
    $subscriptions = [];
    $others = [];
    foreach ($items as $item) {
        if (($item['id'] ?? '') === 'subscriptions') {
            $subscriptions[] = $item;
        } else {
            $others[] = $item;
        }
    }
    return array_merge($others, $subscriptions);
});
if ($case === 'no-dashboard') {
    add_filter('woocommerce_account_menu_items', static function ($items) {
        unset($items['dashboard']);
        return ['qa-support' => 'Support'] + $items;
    }, 1000);
}
