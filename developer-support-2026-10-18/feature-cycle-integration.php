<?php

use ArraySubs\Features\RecurringBilling\Services\OrderCreation;
use ArraySubs\Features\RecurringBilling\Services\PaymentProcessor;
use ArraySubs\Features\SubscriptionAdmin\REST\ManualController;
use ArraySubs\Supports\ActionScheduler;
use ArraySubs\Supports\Config;

$posts = [];
$orders = [];
$checks = 0;
$original_user = get_current_user_id();
$settings = Config::get('settings');
$user_id = wp_insert_user(['user_login' => 'feature-cycle-' . wp_generate_password(8, false), 'user_email' => 'feature-cycle-' . wp_generate_password(8, false) . '@example.test', 'user_pass' => wp_generate_password(30), 'role' => 'customer']);
$assert = static function ($condition, $message) use (&$checks) {
    ++$checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$make_product = static function (int $price) use (&$posts) {
    $product = new WC_Product_Simple();
    $product->set_name('Feature cycle integration ' . wp_generate_password(5, false));
    $product->set_regular_price((string) $price);
    $product->set_status('publish');
    $id = $product->save();
    $posts[] = $id;
    foreach (['_is_subscription' => 'yes', '_subscription_period' => 'month', '_subscription_interval' => 1] as $key => $value) {
        update_post_meta($id, $key, $value);
    }
    update_post_meta($id, '_arraysubs_features', wp_json_encode(arraysubs_sanitize_product_features([
        ['name' => 'Seats', 'value' => '20', 'type' => 'number', 'enabled' => true, 'reset_period' => 'billing_cycle'],
    ])));
    return $id;
};
$make_subscription = static function (int $product_id, int $price = 10) use (&$posts, $user_id) {
    $id = wp_insert_post(['post_type' => 'arraysubs_data', 'post_status' => 'arraysubs-active', 'post_title' => 'Feature cycle integration', 'meta_input' => [
        '_customer_id' => $user_id, '_customer_email' => get_userdata($user_id)->user_email,
        '_product_id' => $product_id, '_variation_id' => 0, '_quantity' => 1,
        '_billing_period' => 'month', '_billing_interval' => 1, '_subscription_length' => 0,
        '_subscription_price' => (string) $price, '_recurring_amount' => (string) $price,
        '_currency' => get_woocommerce_currency(), '_completed_payments' => 1,
        '_payment_method' => 'manual', '_requires_manual_renewal' => 'yes',
        '_start_date' => gmdate('Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS),
        '_last_payment_date' => gmdate('Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS),
        '_next_payment_date' => gmdate('Y-m-d H:i:s', time() + 14 * DAY_IN_SECONDS),
    ]]);
    $posts[] = $id;
    arraysubs_increment_feature_usage($id, 'Seats', 3);
    return $id;
};
$invoice = static function ($subscription_id) use (&$orders, $assert) {
    $order = (new OrderCreation())->createRenewalOrder($subscription_id);
    $assert($order instanceof WC_Order, 'Actual core renewal order is created.');
    $orders[] = $order->get_id();
    update_post_meta($subscription_id, '_pending_renewal_order_id', $order->get_id());
    return $order;
};
$marker = static function ($subscription_id) { return (int) get_post_meta($subscription_id, '_current_cycle_order_id', true); };

try {
    $admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
    wp_set_current_user((int) $admins[0]);
    $paid_product = $make_product(10);
    $zero_product = $make_product(0);
    $paid = $make_subscription($paid_product);
    $box_child = $make_subscription($paid_product);
    update_post_meta($box_child, '_arraysubs_box_parent_subscription', $paid);
    update_post_meta($paid, '_arraysubs_box_child_subscriptions', wp_json_encode([$box_child]));
    $bundle_child = class_exists('ArraySubsPro\\Features\\SubscriptionBundle\\Services\\ChildSubscriptions') ? $make_subscription($paid_product) : 0;
    if ($bundle_child) {
        update_post_meta($bundle_child, '_arraysubs_bundle_parent_subscription', $paid);
        update_post_meta($paid, '_arraysubs_bundle_child_subscriptions', wp_json_encode([$bundle_child]));
    }
    $order = $invoice($paid);
    $assert($marker($paid) === 0 && arraysubs_get_feature_usage($paid, 'Seats')['used'] === 3.0, 'An unpaid invoice does not reset a cycle.');
    $scheduled = $order->get_meta('_renewal_scheduled_date', true);
    $order->payment_complete('local-cycle-regression');
    $assert($marker($paid) === $order->get_id(), 'Paid renewal reaches the real OrderIntegration marker write.');
    $assert(arraysubs_get_feature_usage($paid, 'Seats')['used'] === 0.0, 'Paid renewal resets logical usage.');
    $assert($marker($box_child) === $order->get_id(), 'Core box listener mirrors paid marker.');
    if ($bundle_child) {
        $assert($marker($bundle_child) === $order->get_id(), 'Pro bundle listener mirrors paid marker.');
    }
    $assert(get_post_meta($paid, '_next_payment_date', true) === arraysubs_calculate_next_payment_date($scheduled, 1, 'month'), 'Paid renewal preserves its original schedule anchor.');
    $assert(ActionScheduler::getNextScheduledTime(ActionScheduler::HOOK_PROCESS_RENEWAL, [$paid], ActionScheduler::GROUP_RENEWALS) > time(), 'Paid renewal keeps payment scheduling.');
    $assert(ActionScheduler::getNextScheduledTime(ActionScheduler::HOOK_GENERATE_RENEWAL_INVOICE, [$paid], ActionScheduler::GROUP_BILLING) > time(), 'Paid renewal keeps invoice scheduling.');
    arraysubs_increment_feature_usage($paid, 'Seats', 2);
    do_action('arraysubs_order_paid', $order->get_id(), wc_get_order($order->get_id()));
    $assert(arraysubs_get_feature_usage($paid, 'Seats')['used'] === 2.0, 'Repeated paid fan-out does not change the cycle or usage.');

    $zero = $make_subscription($zero_product, 0);
    $zero_order = $invoice($zero);
    $zero_result = (new PaymentProcessor())->processPayment($zero, $zero_order);
    $assert(!empty($zero_result['success']) && $marker($zero) === $zero_order->get_id(), 'Zero-total payment uses the real payment processor and starts a cycle.');
    $assert((int) get_post_meta($zero, '_completed_payments', true) === 1 && arraysubs_get_feature_usage($zero, 'Seats')['used'] === 0.0, 'Zero-total cycle resets without incrementing completed payment count.');

    if (class_exists('ArraySubsPro\\Features\\EarlyRenew\\Services\\EarlyRenewManager')) {
        $enable_early = static function ($values) { return arraysubs_merge_settings_recursive((array) $values, ['customer_actions' => ['allow_early_renew' => true]]); };
        add_filter('option_arraysubs_settings', $enable_early);
        $early = $make_subscription($paid_product);
        $early_due = get_post_meta($early, '_next_payment_date', true);
        $result = (new ArraySubsPro\Features\EarlyRenew\Services\EarlyRenewManager())->process($early, $user_id);
        $assert(!is_wp_error($result), 'Actual Pro early-renew service creates the invoice: ' . (is_wp_error($result) ? $result->get_error_message() : ''));
        $early_order_id = (int) get_post_meta($early, '_pending_renewal_order_id', true);
        $orders[] = $early_order_id;
        $early_order = wc_get_order($early_order_id);
        $assert($early_order && $early_order->get_meta('_arraysubs_early_renewal', true) === 'yes' && $marker($early) === 0, 'Early invoice is flagged and has not reset before payment.');
        $early_order->payment_complete('local-early-cycle-regression');
        $assert($marker($early) === $early_order_id && arraysubs_get_feature_usage($early, 'Seats')['used'] === 0.0, 'Paid Pro early renewal resets cycle.');
        $assert(get_post_meta($early, '_next_payment_date', true) === arraysubs_calculate_next_payment_date($early_due, 1, 'month'), 'Early payment retains paid-through schedule.');
    }

    $target = $make_product(20);
    $switched = $make_subscription($paid_product);
    update_post_meta($paid_product, '_arraysubs_upgrade_products', [$target]);
    $assert(arraysubs_set_pending_switch($switched, ['target_product_id' => $target, 'quantity' => 1, 'switch_type' => 'upgrade', 'new_price' => 20, 'new_billing_period' => 'month', 'new_billing_interval' => 1]), 'Real signed deferred switch is stored.');
    $switch_order = $invoice($switched);
    $assert($marker($switched) === 0 && arraysubs_get_feature_usage($switched, 'Seats')['used'] === 3.0, 'Deferred switch invoice keeps current usage before payment.');
    $switch_order->payment_complete('local-pending-cycle-regression');
    $assert((int) get_post_meta($switched, '_product_id', true) === $target && null === arraysubs_get_pending_switch($switched), 'Paid deferred switch applies target and clears pending state.');
    $assert($marker($switched) === $switch_order->get_id() && arraysubs_get_feature_usage($switched, 'Seats')['used'] === 0.0, 'Paid deferred switch starts exactly its paid order cycle.');

    foreach (['failed', 'on-hold'] as $status) {
        $failed = $make_subscription($paid_product);
        $failed_order = $invoice($failed);
        $failed_order->update_status($status);
        $assert($marker($failed) === 0 && arraysubs_get_feature_usage($failed, 'Seats')['used'] === 3.0, 'Renewal order ' . $status . ' does not reset usage.');
    }
    $proration = $make_subscription($paid_product);
    $proration_order = wc_create_order(['customer_id' => $user_id]);
    $orders[] = $proration_order->get_id();
    $proration_order->update_meta_data('_arraysubs_order_type', 'plan_switch');
    $proration_order->update_meta_data('_subscription_id', $proration);
    $proration_order->save();
    $proration_order->payment_complete('local-proration-cycle-regression');
    $assert($marker($proration) === 0 && arraysubs_get_feature_usage($proration, 'Seats')['used'] === 3.0, 'A proration order is excluded from generic paid-cycle fan-out.');
    update_post_meta($proration, '_next_payment_date', gmdate('Y-m-d H:i:s', time() + 60 * DAY_IN_SECONDS));
    wp_update_post(['ID' => $proration, 'post_status' => 'arraysubs-paused']);
    $assert($marker($proration) === 0 && arraysubs_get_feature_usage($proration, 'Seats')['used'] === 3.0, 'Pause and moved billing date do not create a paid cycle.');

    $manual = $make_subscription($paid_product);
    $manual_box_child = $make_subscription($paid_product);
    update_post_meta($manual_box_child, '_arraysubs_box_parent_subscription', $manual);
    update_post_meta($manual, '_arraysubs_box_child_subscriptions', wp_json_encode([$manual_box_child]));
    $manual_bundle_child = $bundle_child ? $make_subscription($paid_product) : 0;
    if ($manual_bundle_child) {
        update_post_meta($manual_bundle_child, '_arraysubs_bundle_parent_subscription', $manual);
        update_post_meta($manual, '_arraysubs_bundle_child_subscriptions', wp_json_encode([$manual_bundle_child]));
    }
    $request = new WP_REST_Request('POST');
    foreach (['subscription_id' => $manual, 'amount' => 10, 'payment_method' => 'manual', 'note' => 'Local cycle regression'] as $key => $value) {
        $request->set_param($key, $value);
    }
    $response = (new ManualController())->recordPayment($request);
    $manual_order_id = $marker($manual);
    if ($manual_order_id) {
        $orders[] = $manual_order_id;
    }
    $assert($response->get_status() === 200 && $manual_order_id > 0 && arraysubs_get_feature_usage($manual, 'Seats')['used'] === 0.0, 'Actual admin manual-payment controller starts a paid cycle.');
    $assert($marker($manual_box_child) === $manual_order_id, 'Core box child mirrors the manual-payment cycle.');
    if ($manual_bundle_child) {
        $assert($marker($manual_bundle_child) === $manual_order_id, 'Pro bundle child mirrors the manual-payment cycle.');
    }
    $assert(!ActionScheduler::isLocked(ActionScheduler::HOOK_SUBSCRIPTION_MUTATION, [$manual]), 'Manual payment releases the lifecycle lock.');
    WP_CLI::success($checks . ' real renewal/payment cycle assertions passed.');
} finally {
    if (isset($enable_early)) {
        remove_filter('option_arraysubs_settings', $enable_early);
    }
    wp_set_current_user($original_user);
    foreach (array_unique($orders) as $order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $order->delete(true);
        }
    }
    foreach (array_reverse($posts) as $id) {
        ActionScheduler::unscheduleAllForSubscription($id);
        wp_delete_post($id, true);
    }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_delete_user($user_id);
}
