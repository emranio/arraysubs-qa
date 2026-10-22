<?php
/** Real WP/WooCommerce checks; only own disposable data, never a gateway. */
defined('ABSPATH') || exit;

use ArraySubs\Features\Modules\Services\ProductAvailability;
use ArraySubs\Features\Modules\Support\Registry;

$checks = [];
$fixtures = ['customer' => 0, 'products' => [], 'orders' => []];
$failure = null;
$original_user = get_current_user_id();
$original_cart = WC()->cart;
$original_settings = get_option('arraysubs_settings', []);
$original_plugins = get_option('active_plugins', []);
$enabled = true;
$effective_settings = is_array($original_settings) ? $original_settings : [];
$effective_settings['store_credit']['enable_purchase'] = true;
$settings_filter = static function () use ($effective_settings) { return $effective_settings; };
$freeze_settings = static function ($value, $old) { return $old; };
$module_filter = static function ($current, $module) use (&$enabled) {
    return in_array($module, ['store_credit', 'subscription_bundle'], true) ? $enabled : $current;
};
$suppress_mail = static function () { return true; };
$observer_hits = 0;
$observe_dispatch = static function ($dispatch, $request) use (&$observer_hits, &$fixtures) {
    if ($dispatch !== null || $request->get_method() !== 'POST'
        || !preg_match('#^/wc/store(?:/v[0-9]+)?/checkout/(\d+)$#', $request->get_route(), $matches)
        || !in_array((int) $matches[1], $fixtures['orders'], true)) {
        return $dispatch;
    }
    ++$observer_hits;
    // Observe a successful guard pass without ever invoking order/payment callbacks.
    return new WP_REST_Response(['guard_passed' => true], 200);
};
$check = static function (bool $passed, string $label) use (&$checks): void {
    $checks[] = ['check' => $label, 'passed' => $passed];
    if (!$passed) { throw new RuntimeException($label); }
};
$post_order = static function (WC_Order $order, bool $bad_key = false): WP_REST_Response {
    $request = new WP_REST_Request('POST', '/wc/store/v1/checkout/' . $order->get_id());
    $request->set_header('Nonce', wp_create_nonce('wc_store_api'));
    $request->set_body_params([
        'key' => $bad_key ? 'incorrect' : $order->get_order_key(),
        'billing_email' => $order->get_billing_email(),
        'billing_address' => ['first_name' => 'Must never be saved'],
        'payment_method' => 'bacs',
    ]);
    return rest_do_request($request);
};
$order_snapshot = static function (WC_Order $order): string {
    return wp_json_encode(wc_get_order($order->get_id())->get_data());
};
$probe = [];
$mode = !empty(Registry::all()['store_credit']['available']) ? 'pro' : 'free';
$boot_modules = [
    'store_credit' => arraysubs_has_module('Features\\StoreCredit\\'),
    'subscription_bundle' => arraysubs_has_module('Features\\SubscriptionBundle\\'),
];

try {
    add_filter('pre_option_arraysubs_settings', $settings_filter);
    add_filter('pre_update_option_arraysubs_settings', $freeze_settings, PHP_INT_MAX, 2);
    add_filter('arraysubs_is_module_enabled', $module_filter, 99, 2);
    add_filter('pre_wp_mail', $suppress_mail);
    add_filter('rest_dispatch_request', $observe_dispatch, 1000, 2);

    $token = strtolower(wp_generate_password(12, false, false));
    $customer_id = wp_insert_user([
        'user_login' => 'product-guard-qa-' . $token,
        'user_email' => 'product-guard-qa-' . $token . '@example.invalid',
        'user_pass' => wp_generate_password(32),
        'role' => 'customer',
    ]);
    $check(!is_wp_error($customer_id), 'Disposable customer created');
    $fixtures['customer'] = (int) $customer_id;
    wp_set_current_user($customer_id);
    wc_load_cart();
    WC()->cart->empty_cart();

    $new_product = static function (string $type = 'simple') use (&$fixtures): WC_Product {
        $product = new WC_Product_Simple();
        $product->set_name('Product guard QA ' . $type);
        $product->set_status('publish');
        $product->set_regular_price('20');
        $product->set_virtual(true);
        $id = $product->save();
        $fixtures['products'][] = $id;
        wp_set_object_terms($id, $type, 'product_type');
        WC_Cache_Helper::invalidate_cache_group('product_' . $id);
        wc_delete_product_transients($id);
        // WooCommerce's instance cache otherwise retains this fixture's initial Simple object.
        if (class_exists(\Automattic\WooCommerce\Internal\Caches\ProductCache::class)) {
            wc_get_container()->get(\Automattic\WooCommerce\Internal\Caches\ProductCache::class)->remove($id);
        }
        return wc_get_product($id);
    };
    $plain = $new_product();
    $credit = $new_product('arraysubs_store_credit');
    $bundle = $new_product('arraysubs_subscription_bundle');
    update_post_meta($credit->get_id(), '_arraysubs_credit_product', 'yes');
    update_post_meta($credit->get_id(), '_arraysubs_credit_amount_type', 'fixed');
    update_post_meta($credit->get_id(), '_arraysubs_credit_amount', 20);
    update_post_meta($bundle->get_id(), '_arraysubs_subscription_bundle', 'yes');
    update_post_meta($bundle->get_id(), '_arraysubs_bundle_config', wp_json_encode([
        'schedule' => ['period' => 'month', 'interval' => 1, 'length' => 0],
        'products' => [['id' => 'qa-child', 'product_id' => $plain->get_id(), 'qty' => 1]],
        'discount' => ['type' => 'none', 'amount' => 0],
    ]));

    $guard = null;
    foreach (($GLOBALS['wp_filter']['before_woocommerce_pay_form']->callbacks ?? []) as $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) && $callback['function'][0] instanceof ProductAvailability) {
                $guard = $callback['function'][0];
            }
        }
    }
    $check($guard instanceof ProductAvailability, 'Core boot registers the product/payment availability service');
    $check($plain->is_purchasable() && ProductAvailability::unavailableModule($plain) === '', 'Ordinary products remain available');
    foreach (['store_credit' => $credit, 'subscription_bundle' => $bundle] as $module => $product) {
        $raw_type = WC_Product_Factory::get_product_type($product->get_id());
        $probe[$module] = ['id' => $product->get_id(), 'class' => get_class($product), 'stored_type' => $raw_type];
        $check($raw_type === ($module === 'store_credit' ? 'arraysubs_store_credit' : 'arraysubs_subscription_bundle'), $module . ' preserves its raw stored product type');
        $expected_unavailable = $mode === 'free' ? $module : '';
        $check(ProductAvailability::unavailableModule($product) === $expected_unavailable, $module . ' follows actual provider availability');
        if ($mode === 'free') {
            $check(get_class($product) === 'WC_Product_Simple' && !$product->is_purchasable(), $module . ' Simple fallback is blocked without Pro');
        } else {
            $check($guard->filterPurchasable(true, $product), $module . ' guard permits the enabled premium module');
            if ($boot_modules[$module] === 1) {
                $check(get_class($product) !== 'WC_Product_Simple' && $product->is_purchasable(), $module . ' live premium product remains genuinely purchasable');
            }
        }

        $enabled = false;
        $check(!$product->is_purchasable() && ProductAvailability::unavailableModule($product) === $module, $module . ' disabled module blocks product purchase');
        $check($product->add_to_cart_text() === __('Read more', 'arraysubs'), $module . ' unavailable catalog button offers product details');
        wc_clear_notices();
        $check(WC()->cart->add_to_cart($product->get_id(), 1) === false && WC()->cart->is_empty(), $module . ' cannot enter a real WooCommerce cart while unavailable');
        $cart_request = new WP_REST_Request('POST', '/wc/store/v1/cart/add-item');
        $cart_request->set_header('Nonce', wp_create_nonce('wc_store_api'));
        $cart_request->set_body_params(['id' => $product->get_id(), 'quantity' => 1]);
        $cart_response = rest_do_request($cart_request);
        $check($cart_response->get_status() >= 400 && WC()->cart->is_empty(), $module . ' direct Store API add-item cannot bypass purchase refusal');

        $order = wc_create_order(['customer_id' => $customer_id, 'status' => 'pending']);
        $fixtures['orders'][] = $order->get_id();
        $order->add_product($product, 1, ['subtotal' => 20, 'total' => 20]);
        $order->set_billing_email(get_userdata($customer_id)->user_email);
        $order->set_payment_method('bacs');
        $order->set_total(20);
        $order->save();
        $before = $order_snapshot($order);
        $check(ProductAvailability::blocksOrder($order), $module . ' unpaid initial order is blocked');
        $check($order->needs_payment(), $module . ' blocked order still requires payment rather than becoming free');
        $actions = $guard->filterOrderActions(['pay' => ['name' => 'Pay'], 'view' => ['name' => 'View']], $order);
        $check(!isset($actions['pay']) && isset($actions['view']), $module . ' order hides only its Pay action');
        try {
            $guard->refusePaymentForm($order);
            $check(false, $module . ' payment form refuses unavailable fulfillment');
        } catch (Exception $expected) {
            $check($expected->getMessage() !== '', $module . ' payment form gives a controlled refusal');
        }
        $response = $post_order($order);
        $check($response->get_status() === 409 && ($response->get_data()['code'] ?? '') === 'arraysubs_product_unavailable', $module . ' real Store API payment is rejected before dispatch');
        $check($order_snapshot($order) === $before && $observer_hits === 0, $module . ' denied payment changes no order data and reaches no payment callback');
        wp_set_current_user(0);
        $response = $post_order($order);
        $check($response->get_status() === 403 && ($response->get_data()['code'] ?? '') === 'woocommerce_rest_invalid_user', $module . ' WooCommerce authorization rejects nonowners before the guard');
        $check($order_snapshot($order) === $before && $observer_hits === 0, $module . ' unauthorized request changes no order and reaches no callback');
        wp_set_current_user($customer_id);

        $order->update_meta_data('_is_renewal_order', 'yes');
        $order->save();
        $check(!ProductAvailability::blocksOrder($order), $module . ' existing renewal is exempt');
        $response = $post_order($order);
        $check($response->get_status() === 200 && ($response->get_data()['guard_passed'] ?? false), $module . ' exempt renewal passes guard without invoking a gateway');
        $observer_hits = 0;
        $order->delete_meta_data('_is_renewal_order');
        foreach (['plan_switch', 'plan_switch_checkout'] as $type) {
            $order->update_meta_data('_arraysubs_order_type', $type);
            $check(!ProductAvailability::blocksOrder($order), $module . ' ' . $type . ' order is exempt');
        }
        $order->delete_meta_data('_arraysubs_order_type');
        $line_item = current($order->get_items());
        $line_item->update_meta_data('_arraysubs_checkout_migration', 'yes');
        $check(!ProductAvailability::blocksOrder($order), $module . ' checkout-migration line is exempt');
        $line_item->delete_meta_data('_arraysubs_checkout_migration');
        $order->set_date_paid(time());
        $check(!ProductAvailability::blocksOrder($order), $module . ' already-paid order is exempt without rerunning fulfillment');
        $enabled = true;
    }
    $stale_product = $new_product();
    wc_clear_notices();
    $check((bool) WC()->cart->add_to_cart($stale_product->get_id(), 1), 'An ordinary disposable product can enter the isolated cart');
    wp_set_object_terms($stale_product->get_id(), 'arraysubs_store_credit', 'product_type');
    WC_Cache_Helper::invalidate_cache_group('product_' . $stale_product->get_id());
    $enabled = false;
    do_action('woocommerce_check_cart_items');
    $check(wc_notice_count('error') > 0, 'A previously cached cart line cannot bypass the raw-type guard after becoming unavailable');
} catch (Throwable $error) {
    $failure = get_class($error) . ': ' . $error->getMessage();
} finally {
    remove_filter('rest_dispatch_request', $observe_dispatch, 1000);
    remove_filter('arraysubs_is_module_enabled', $module_filter, 99);
    remove_filter('pre_option_arraysubs_settings', $settings_filter);
    remove_filter('pre_update_option_arraysubs_settings', $freeze_settings, PHP_INT_MAX);
    if (WC()->cart) { WC()->cart->empty_cart(); }
    WC()->cart = $original_cart;
    foreach ($fixtures['orders'] as $id) {
        $order = wc_get_order($id);
        if ($order) { $order->delete(true); }
    }
    foreach (array_reverse($fixtures['products']) as $id) { wp_delete_post($id, true); }
    if ($fixtures['customer']) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($fixtures['customer']);
    }
    remove_filter('pre_wp_mail', $suppress_mail);
    wp_set_current_user($original_user);
}

$settings_unchanged = get_option('arraysubs_settings', []) === $original_settings;
$plugins_unchanged = get_option('active_plugins', []) === $original_plugins;
echo wp_json_encode([
    'mode' => $mode, 'live_modules_at_boot' => $boot_modules,
    'checks' => $checks, 'failure' => $failure, 'raw_type_probe' => $probe,
    'deleted_fixtures' => $fixtures, 'saved_settings_unchanged' => $settings_unchanged,
    'active_plugins_unchanged' => $plugins_unchanged,
], JSON_PRETTY_PRINT) . PHP_EOL;
if ($failure !== null || !$settings_unchanged || !$plugins_unchanged) { WP_CLI::halt(1); }
