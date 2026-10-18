<?php
defined('ABSPATH') || exit;
/** Real editor/helper term changes reach the scalar lifecycle bus without contact data. */

use ArraySubs\Features\Integrations\Support\SubscriptionEvent;
use ArraySubs\Features\SubscriptionAdmin\Services\SubscriptionMutation;

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    ++$checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$ids = [];
$user_id = 0;
$subscription_id = 0;
$events = [];
$listen = static function (string $event, array $payload) use (&$events, &$subscription_id): void {
    if ($event === 'updated' && ($payload['subscription_id'] ?? 0) === $subscription_id) {
        $events[] = $payload;
    }
};
add_action('arraysubs_lifecycle_event', $listen, 100, 2);
try {
    $user_id = wp_insert_user(['user_login' => 'updated-event-' . wp_generate_password(9, false), 'role' => 'customer', 'user_pass' => wp_generate_password(30)]);
    $products = [];
    foreach ([10, 20] as $price) {
        $product = new WC_Product_Simple();
        $product->set_name('Updated event fixture');
        $product->set_regular_price((string) $price);
        $product->set_status('publish');
        $id = $product->save();
        $products[] = $id;
        $ids[] = $id;
        foreach (['_is_subscription' => 'yes', '_subscription_period' => 'month', '_subscription_interval' => 1] as $key => $value) {
            update_post_meta($id, $key, $value);
        }
    }
    $subscription_id = wp_insert_post(['post_type' => 'arraysubs_data', 'post_status' => 'arraysubs-active', 'post_title' => 'Updated event fixture', 'meta_input' => [
        '_customer_id' => $user_id, '_product_id' => $products[0], '_variation_id' => 0,
        '_billing_period' => 'month', '_billing_interval' => 1, '_recurring_amount' => '10', '_quantity' => 1,
        '_currency' => get_woocommerce_currency(), '_next_payment_date' => gmdate('Y-m-d H:i:s', time() + MONTH_IN_SECONDS),
        '_start_date' => gmdate('Y-m-d H:i:s', time() - MONTH_IN_SECONDS), '_payment_method' => 'manual',
        '_requires_manual_renewal' => 'yes', '_invoice_email' => 'old-invoice-private@example.invalid',
    ]]);
    $ids[] = $subscription_id;
    SubscriptionEvent::resetClaims();
    $mutation = new SubscriptionMutation();
    $result = $mutation->update($subscription_id, ['product_id' => $products[1], 'recurring_amount' => 20, 'invoice_email' => 'new-invoice-private@example.invalid', 'payment_method_title' => 'PRIVATE_GATEWAY_TITLE', 'billing_address' => ['address_1' => 'PRIVATE_ADDRESS']]);
    $assert(!is_wp_error($result), 'Real product/price editor mutation succeeds: ' . (is_wp_error($result) ? $result->get_error_message() : ''));
    $assert(count($events) === 1, 'Editor product/price change emits exactly one normalized updated event');
    $event = $events[0];
    $assert($event['source_hook'] === 'arraysubs_subscription_terms_updated', 'Editor emits only after its completed native terms hook');
    $assert($event['previous_state_available'] === true, 'Editor supplies a previous snapshot');
    $assert($event['previous_product_id'] === $products[0] && $event['current_product_id'] === $products[1], 'Old and new parent product IDs are preserved');
    $assert($event['previous_recurring_amount'] === 10.0 && $event['current_recurring_amount'] === 20.0, 'Old and new price values are preserved');
    $assert($event['status'] === 'arraysubs-active' && $event['previous_status'] === 'arraysubs-active', 'Active-to-active changes remain visible with known status');
    $assert($event['identity_scope'] === 'request', 'Generic edits do not claim a durable operation identity');
    $assert($event['changed_fields'] === 'product_id,recurring_amount', 'Changed field list is allowlisted and deterministic');
    $encoded = wp_json_encode($event);
    $assert(!str_contains($encoded, 'invoice-private') && !str_contains($encoded, 'PRIVATE_') && !str_contains($encoded, 'billing_address') && !str_contains($encoded, 'payment_method_title'), 'Editor event does not export invoice/contact/gateway title fields');
    $assert(!array_filter($event, static fn($value) => !is_scalar($value) && $value !== null), 'Updated event stays scalar-only');

    $result = $mutation->update($subscription_id, ['invoice_email' => 'another-private@example.invalid']);
    $assert(!is_wp_error($result) && count($events) === 1, 'Contact-only mutation emits no access terms update');
    $result = $mutation->update($subscription_id, ['recurring_amount' => 30]);
    $assert(!is_wp_error($result) && count($events) === 2, 'A second different mutation in one request is not swallowed');
    $assert($events[1]['previous_recurring_amount'] === 20.0 && $events[1]['current_recurring_amount'] === 30.0, 'Second event reflects its own before/after state');

    $result = arraysubs_update_subscription($subscription_id, ['_quantity' => 3, 'recurring_amount' => 35, '_invoice_email' => 'helper-private@example.invalid']);
    $assert($result === true && count($events) === 3, 'Low-level helper changes reach the same normalized bus');
    $helper = $events[2];
    $assert($helper['source_hook'] === 'arraysubs_data_updated' && $helper['previous_state_available'] === false, 'Low-level source explicitly lacks previous state');
    $assert($helper['current_quantity'] === 3 && $helper['current_recurring_amount'] === 35.0, 'Low-level payload re-reads authoritative stored terms');
    $assert($helper['previous_quantity'] === null && $helper['previous_recurring_amount'] === null, 'Missing previous values are null');
    $assert(!str_contains(wp_json_encode($helper), 'helper-private') && !str_contains($helper['changed_fields'], 'invoice_email'), 'Low-level event excludes unrelated contact metadata');
    do_action('arraysubs_data_updated', $subscription_id, ['_quantity' => 3, 'recurring_amount' => 35, '_invoice_email' => 'helper-private@example.invalid']);
    $assert(count($events) === 3, 'Duplicate low-level notification is suppressed in one request');
    $assert(arraysubs_update_subscription($subscription_id, ['_invoice_email' => 'private-only@example.invalid']) === true && count($events) === 3, 'Low-level contact-only update emits no access event');
    $assert(arraysubs_update_subscription($subscription_id, ['quantity' => 4]) === true && count($events) === 4, 'Later distinct low-level term changes remain visible');
    $assert($events[3]['current_quantity'] === 4, 'Later low-level event contains the current term');
    $result = $mutation->update($subscription_id, ['status' => 'arraysubs-on-hold']);
    $assert(!is_wp_error($result) && count($events) === 5, 'A real editor status change emits its completed terms snapshot');
    $status_event = $events[4];
    $assert($status_event['previous_status'] === 'arraysubs-active' && $status_event['current_status'] === 'arraysubs-on-hold', 'Status update preserves exact previous/current status');
    $assert($status_event['effect'] === 'none', 'Generic update does not repeat the canonical revoke effect');
    $assert(get_post_meta($subscription_id, '_on_hold_date', true) !== '', 'Completed terms event follows on-hold metadata persistence');
    WP_CLI::success($checks . ' updated lifecycle event assertions passed.');
} finally {
    remove_action('arraysubs_lifecycle_event', $listen, 100);
    foreach (array_reverse($ids) as $id) {
        wp_delete_post($id, true);
    }
    if ($user_id && !is_wp_error($user_id)) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($user_id);
    }
}
