<?php
/** Alternate renewal prices use the shared billing guards. Run with WP-CLI. */
use ArraySubs\Developer\Subscriptions;
use ArraySubs\Supports\ActionScheduler;

$results = [];
$subscriptions = [];
$orders = [];
$product_id = 0;
$customer_id = 0;
$fixtures = [];
$events = 0;
$owned = [];
$settling = [];
$external = [];
$check = static function (string $label, bool $passed, $detail = null) use (&$results): void {
    $results[] = ['name' => $label, 'passed' => $passed] + ($passed ? [] : ['detail' => $detail]);
    echo ($passed ? 'PASS: ' : 'FAIL: ') . $label . ($passed ? '' : ' ' . wp_json_encode($detail)) . PHP_EOL;
};
$ownership = static function ($value, $slug, $id) use (&$owned) { return isset($owned[$id]) ? true : $value; };
$settlement = static function ($value, $id) use (&$settling) { return isset($settling[$id]) ? true : $value; };
$externally_billed = static function ($value, $id) use (&$external) { return isset($external[$id]) ? true : $value; };
$observe = static function ($subscription) use (&$events, &$subscriptions): void {
    if ($subscription instanceof WP_Post && in_array($subscription->ID, $subscriptions, true)) {
        ++$events;
    }
};
$create_subscription = static function (array $extra = []) use (&$subscriptions, &$product_id, &$customer_id): int {
    $id = wp_insert_post([
        'post_type' => 'arraysubs_data',
        'post_status' => 'arraysubs-active',
        'post_title' => 'Alternate renewal-price guard regression',
        'meta_input' => array_merge([
            '_customer_id' => $customer_id, '_customer_email' => get_userdata($customer_id)->user_email,
            '_product_id' => $product_id, '_variation_id' => 0, '_quantity' => 1,
            '_currency' => get_woocommerce_currency(), '_subscription_price' => '10', '_recurring_amount' => '10',
            '_billing_period' => 'month', '_billing_interval' => 1, '_subscription_length' => 0,
            '_start_date' => gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS),
            '_next_payment_date' => gmdate('Y-m-d H:i:s', time() + 10 * DAY_IN_SECONDS),
            '_payment_method' => 'bacs', '_trial_length' => 0, '_trial_period' => 'day',
            '_invoice_email' => '', '_enable_different_renewal_price' => 'no',
            '_different_renewal_price' => 0, '_different_renewal_price_after' => 1,
        ], $extra),
    ], true);
    if (is_wp_error($id)) {
        throw new RuntimeException($id->get_error_message());
    }
    $subscriptions[] = $id;
    return $id;
};

$original_actor = get_current_user_id();
wp_set_current_user(1);
add_filter('arraysubs_gateway_owns_billing_clock', $ownership, 100, 3);
add_filter('arraysubs_is_awaiting_gateway_settlement', $settlement, 100, 2);
add_filter('arraysubs_subscription_externally_billed', $externally_billed, 100, 2);
add_action('arraysubs_subscription_terms_updated', $observe, 100);
try {
    $run = substr(wp_generate_uuid4(), 0, 8);
    $customer_id = wp_insert_user([
        'user_login' => 'alternate-price-' . $run, 'user_email' => 'alternate-price-' . $run . '@example.test',
        'user_pass' => wp_generate_password(30), 'role' => 'customer',
    ]);
    if (is_wp_error($customer_id)) {
        throw new RuntimeException($customer_id->get_error_message());
    }
    $product = new WC_Product_Simple();
    $product->set_name('Alternate renewal-price fixture ' . $run);
    $product->set_status('publish');
    $product->set_regular_price('10');
    $product->set_virtual(true);
    $product_id = $product->save();
    update_post_meta($product_id, '_is_subscription', 'yes');
    update_post_meta($product_id, '_subscription_period', 'month');
    update_post_meta($product_id, '_subscription_interval', 1);
    $parent = $create_subscription();
    $contexts = [
        'provider-owned' => 'provider_rejection',
        'box child' => 'parent_billed_subscription',
        'bundle child' => 'parent_billed_subscription',
        'externally billed' => 'parent_billed_subscription',
        'gateway settlement' => 'gateway_settlement_pending',
        'pending plan switch' => 'pending_switch_conflict',
        'frozen renewal invoice' => 'frozen_renewal_invoice',
    ];
    $changes = ['different_renewal_price' => 99, 'different_renewal_price_after' => 2, 'enable_different_renewal_price' => true];
    foreach ($contexts as $context => $expected_error) {
        $id = $create_subscription();
        $fixtures[$context] = $id;
        if ($context === 'provider-owned') {
            $owned[$id] = true;
        } elseif ($context === 'box child' || $context === 'bundle child') {
            update_post_meta($id, $context === 'box child' ? '_arraysubs_box_parent_subscription' : '_arraysubs_bundle_parent_subscription', $parent);
        } elseif ($context === 'externally billed') {
            $external[$id] = true;
        } elseif ($context === 'gateway settlement') {
            $settling[$id] = true;
        } elseif ($context === 'pending plan switch') {
            update_post_meta($id, '_pending_switch', ['target_product_id' => $product_id, 'switch_type' => 'crossgrade']);
        } else {
            $order = wc_create_order(['customer_id' => $customer_id]);
            if (is_wp_error($order)) {
                throw new RuntimeException($order->get_error_message());
            }
            $orders[] = $order->get_id();
            $order->set_billing_email(get_userdata($customer_id)->user_email);
            $order->set_currency(get_woocommerce_currency());
            $order->update_meta_data('_is_renewal_order', 'yes');
            $order->update_meta_data('_subscription_id', $id);
            $order->save();
            update_post_meta($id, '_pending_renewal_order_id', $order->get_id());
        }
        $api = Subscriptions::fromId($id);
        foreach ($changes as $field => $value) {
            $before = get_post_meta($id);
            $event_count = $events;
            $result = $api->update([$field => $value, 'invoice_email' => 'changed@example.test']);
            $check($context . ' rejects ' . $field . ' without partial writes or events',
                is_wp_error($result) && $result->get_error_code() === $expected_error && $before === get_post_meta($id) && $event_count === $events,
                ['subscription_id' => $id, 'expected' => $expected_error, 'actual' => is_wp_error($result) ? $result->get_error_code() : 'success']);
        }
        $same = $api->update(['different_renewal_price' => get_post_meta($id, '_different_renewal_price', true)]);
        $check($context . ' accepts an unchanged renewal price', !is_wp_error($same));
    }
    $control = $create_subscription();
    $control_result = Subscriptions::fromId($control)->update($changes + ['invoice_email' => 'valid@example.test']);
    $check('Ordinary local subscriptions can update alternate renewal prices together', !is_wp_error($control_result)
        && (float) get_post_meta($control, '_different_renewal_price', true) === 99.0
        && (int) get_post_meta($control, '_different_renewal_price_after', true) === 2
        && get_post_meta($control, '_enable_different_renewal_price', true) === 'yes'
        && get_post_meta($control, '_invoice_email', true) === 'valid@example.test');
    $check('Fixtures use the real persisted subscription and WooCommerce order stores', count($subscriptions) === 9
        && count($orders) === 1 && wc_get_order($orders[0]) instanceof WC_Order);
} finally {
    remove_filter('arraysubs_gateway_owns_billing_clock', $ownership, 100);
    remove_filter('arraysubs_is_awaiting_gateway_settlement', $settlement, 100);
    remove_filter('arraysubs_subscription_externally_billed', $externally_billed, 100);
    remove_action('arraysubs_subscription_terms_updated', $observe, 100);
    foreach (array_reverse($subscriptions) as $id) {
        ActionScheduler::unscheduleAllForSubscription($id);
        wp_delete_post($id, true);
    }
    foreach ($orders as $id) {
        $order = wc_get_order($id);
        if ($order) {
            $order->delete(true);
        }
    }
    if ($product_id) {
        wc_get_product($product_id)->delete(true);
    }
    if (is_int($customer_id) && $customer_id > 0) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($customer_id);
    }
    wp_set_current_user($original_actor);
}
$cleaned = !array_filter($subscriptions, 'get_post') && !array_filter($orders, 'wc_get_order')
    && !get_post($product_id) && !get_userdata($customer_id);
$check('Every temporary subscription, order, product and customer was removed', $cleaned);
$failed = array_values(array_filter($results, static fn($result) => !$result['passed']));
echo wp_json_encode(['checks' => count($results), 'passed' => count($results) - count($failed), 'failed' => $failed,
    'fixtures' => ['subscriptions' => $fixtures, 'orders' => $orders, 'customer_id' => $customer_id,
        'login' => 'alternate-price-' . $run, 'email' => 'alternate-price-' . $run . '@example.test', 'role' => 'customer'],
    'fixtures_cleaned' => $cleaned], JSON_PRETTY_PRINT) . PHP_EOL;
if ($failed) {
    WP_CLI::error(count($failed) . ' alternate renewal-price regression checks failed.');
}
