<?php

/** Read-only verification of the documented subscription/order snippets. */
use ArraySubs\Developer\Orders;
use ArraySubs\Developer\Subscriptions;

$fixture_path = WP_PLUGIN_DIR . '/qa/developer-support-2026-10-18/browser-fixtures.json';
$fixtures = is_file($fixture_path) ? json_decode(file_get_contents($fixture_path), true) : [];
$subscription_id = (int) ($args[0] ?? ($fixtures['items'][0]['subscription_id'] ?? 0));
$resource = Subscriptions::fromId($subscription_id);
if (is_wp_error($resource)) {
    WP_CLI::error('Pass an existing test subscription ID. The shared browser fixture may have been cleaned up.');
}
$checks = 0;
$assert = static function ($condition, $message) use (&$checks) {
    ++$checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$assert($resource->object() instanceof WP_Post, 'Canonical subscription remains WP_Post.');
$meta_before = get_post_meta($subscription_id);
$data = $resource->data(['identity', 'plan', 'schedule', 'family', 'order_references', 'orders', 'modules']);
$assert(is_array($data) && array_keys($data) === ['identity', 'plan', 'schedule', 'family', 'order_references', 'orders', 'modules'], 'Named lazy groups return the documented projection.');
$assert($data['identity']['id'] === $subscription_id && $data['plan']['effective_product_id'] > 0, 'Identity and effective plan are populated.');
$assert(is_array($data['family']['children']['items']) && array_key_exists('next_page', $data['family']['children']), 'Nested children report their own continuation.');
$assert(is_array($data['orders']['items']) && array_key_exists('has_more', $data['orders']), 'Nested order history is explicitly paginated.');
$assert(array_key_exists('provider_owns_billing_clock', $data['schedule']) && !array_key_exists('externally_billed', $data['schedule']), 'Provider clock ownership has an explicit canonical name.');
$assert(array_keys($data['order_references']) === ['initial_order_id', 'original_order_id', 'pending_renewal_order_id', 'pending_plan_switch_order_id'], 'Order-role pointers are projected explicitly.');
$assert(get_post_meta($subscription_id) === $meta_before, 'Reading all selected groups does not change subscription metadata.');
$history = $resource->orders(['page' => 1, 'per_page' => 2, 'output' => 'ids']);
$assert(!is_wp_error($history) && $history['per_page'] === 2 && count($history['items']) <= 2, 'Related orders support a bounded ID page.');
$sample_order_id = $history['items'][0] ?? 0;
if (!$sample_order_id) {
    $sample = wc_get_orders(['type' => 'shop_order', 'limit' => 1, 'orderby' => 'ID', 'order' => 'DESC']);
    $sample_order_id = $sample ? $sample[0]->get_id() : 0;
}
if ($sample_order_id) {
    $order = Orders::fromId($sample_order_id);
    $assert(!is_wp_error($order) && $order->object() instanceof WC_Order, 'An order remains WC_Order.');
    $projection = $order->data(['identity', 'amounts']);
    $assert(is_array($projection) && array_keys($projection) === ['identity', 'amounts'], 'Order projection excludes unrelated address data.');
    $assert(isset($projection['identity']['is_renewal']) && isset($projection['identity']['is_plan_switch']), 'Order-role flags are present as booleans.');
}
$customer_id = (int) $data['identity']['customer_id'];
if ($customer_id) {
    $page = Subscriptions::fromUserId($customer_id, ['per_page' => 1, 'output' => 'object']);
    $assert(!is_wp_error($page) && count($page['items']) <= 1 && (!$page['items'] || $page['items'][0] instanceof WP_Post), 'Named customer lookup returns a paged canonical collection.');
}
$invalid = $resource->data(['arbitrary_meta']);
$assert(is_wp_error($invalid) && $invalid->get_error_code() === 'invalid_field', 'Unknown projection fails.');
$invalid = Subscriptions::query(['per_page' => 101]);
$assert(is_wp_error($invalid) && $invalid->get_error_code() === 'invalid_query', 'Unbounded page size is rejected.');
WP_CLI::success($checks . ' read-only subscription/order documentation assertions passed.');
