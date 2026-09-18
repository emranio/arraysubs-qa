<?php
/** Temporary local request-scoped portal source probe; never installed remotely. */
defined('ABSPATH') || exit;
if (($_SERVER['HTTP_HOST'] ?? '') !== 'localhost:10003') {
    return;
}
$as_portal_probe = $_GET['as_portal_probe'] ?? '';
if (!in_array($as_portal_probe, ['2.0.0', '2.0.4', 'orders-hidden', 'orders-restored'], true)) {
    return;
}
add_action('plugins_loaded', static function () use ($as_portal_probe) {
    if (!in_array(get_current_user_id(), [1, 525], true)) {
        return;
    }
    $version = $as_portal_probe === '2.0.0' ? '2.0.0' : '2.0.4';
    require_once '/tmp/arraysubs-portal-investigation/' . $version . '/arraysubs/src/Features/CustomerPortal/Services/MyAccountHooks.php';
    if (in_array($as_portal_probe, ['orders-hidden', 'orders-restored'], true)) {
        add_filter('woocommerce_account_menu_items', static function ($items) {
            unset($items['orders']);
            return $items;
        }, 5);
    }
    if ($as_portal_probe === 'orders-restored') {
        add_filter('woocommerce_account_menu_items', static function ($items) {
            $result = [];
            foreach ($items as $key => $label) {
                $result[$key] = $label;
                if ($key === 'dashboard') {
                    $result['orders'] = __('Orders', 'woocommerce');
                }
            }
            return $result;
        }, 20);
    }
}, 1);
