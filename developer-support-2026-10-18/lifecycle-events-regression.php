<?php
defined('ABSPATH') || exit;
/** Native lifecycle source operations plus adapter payload/identity checks. */

use ArraySubs\Features\Integrations\Support\SubscriptionEvent;
use ArraySubs\Features\RecurringBilling\Services\RenewalProcessor;
use ArraySubs\Supports\ActionScheduler;

$checks = 0;
$assert = static function ($condition, string $message) use (&$checks): void {
    ++$checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$ids = [];
$user_id = 0;
$order = null;
$orders = [];
$events = [];
$subscription_id = 0;
$listen = static function (string $event, array $payload) use (&$events, &$subscription_id): void {
    if (($payload['subscription_id'] ?? 0) === $subscription_id) {
        $events[] = ['event' => $event, 'payload' => $payload];
    }
};
$has = static function (string $event) use (&$events): bool { return in_array($event, array_column($events, 'event'), true); };
$payload = static function (string $event) use (&$events): array {
    foreach (array_reverse($events) as $item) {
        if ($item['event'] === $event) {
            return $item['payload'];
        }
    }
    return [];
};
add_action('arraysubs_lifecycle_event', $listen, 100, 2);
try {
    $user_id = wp_insert_user(['user_login' => 'lifecycle-api-' . wp_generate_password(9, false), 'user_pass' => wp_generate_password(30), 'role' => 'customer']);
    $product = new WC_Product_Simple();
    $product->set_name('Lifecycle event fixture');
    $product->set_regular_price('10');
    $product_id = $product->save();
    update_post_meta($product_id, '_is_subscription', 'yes');
    update_post_meta($product_id, '_subscription_period', 'month');
    update_post_meta($product_id, '_subscription_interval', 1);
    $ids[] = $product_id;
    $subscription_id = wp_insert_post(['post_type' => 'arraysubs_data', 'post_status' => 'arraysubs-active', 'post_title' => 'Lifecycle event fixture', 'meta_input' => ['_customer_id' => $user_id, '_product_id' => $product_id, '_billing_period' => 'month', '_billing_interval' => 1, '_recurring_amount' => '10', '_quantity' => 1, '_currency' => 'USD', '_next_payment_date' => gmdate('Y-m-d H:i:s', time() + MONTH_IN_SECONDS), '_start_date' => gmdate('Y-m-d H:i:s', time() - MONTH_IN_SECONDS)]]);
    $ids[] = $subscription_id;
    SubscriptionEvent::resetClaims();
    $assert(arraysubs_schedule_cancellation($subscription_id, 'Regression check', 'system'), 'Native cancellation scheduling succeeds');
    $assert($has('cancellation_scheduled'), 'Scheduling source reaches normalized catalog');
    $assert(arraysubs_undo_scheduled_cancellation($subscription_id), 'Native cancellation undo succeeds');
    $assert($has('cancellation_undone'), 'Undo source reaches normalized catalog');
    wp_update_post(['ID' => $subscription_id, 'post_status' => 'arraysubs-on-hold']);
    $assert($has('status_changed') && $has('on_hold'), 'Every real status transition emits status detail and on-hold');
    wp_update_post(['ID' => $subscription_id, 'post_status' => 'arraysubs-trial']);
    wp_update_post(['ID' => $subscription_id, 'post_status' => 'arraysubs-active']);
    $assert($has('trial_ended'), 'Trial exit emits its own event');

    $order = wc_create_order(['customer_id' => $user_id]);
    $orders[] = $order;
    $order->add_product($product, 1);
    $order->save();
    update_post_meta($subscription_id, '_pending_renewal_order_id', $order->get_id());
    update_post_meta($subscription_id, '_payment_retry_attempts', 0);
    $schedule = new ReflectionMethod(RenewalProcessor::class, 'scheduleNextRetry');
    $schedule->setAccessible(true);
    $schedule->invoke(new RenewalProcessor(), $subscription_id);
    $assert($has('retry_scheduled'), 'Actual retry scheduling emits a native event');
    $assert($payload('retry_scheduled')['identity_scope'] === 'operation', 'Scheduled action has durable identity');
    $assert($payload('retry_scheduled')['action_id'] > 0, 'Retry event names the scheduled action');
    update_post_meta($subscription_id, '_payment_retry_attempts', 100);
    $schedule->invoke(new RenewalProcessor(), $subscription_id);
    $assert($has('retry_exhausted'), 'Actual exhausted retry branch emits a source event');

    // Adapter contract checks do not submit a gateway charge.
    $subscription = arraysubs_get_subscription($subscription_id);
    do_action('arraysubs_renewal_payment_attempt', $subscription, $order, 1);
    do_action('arraysubs_renewal_payment_attempt_result', $subscription, $order, 1, 'failed');
    $assert($has('retry_attempted') && $has('retry_failed'), 'Retry attempt/result adapters map correctly');
    $before = count($events);
    do_action('arraysubs_renewal_payment_attempt_result', $subscription, $order, 1, 'failed');
    $assert(count($events) === $before, 'Duplicate same-operation notification is suppressed in one request');
    $first_id = $payload('retry_failed')['event_id'];
    SubscriptionEvent::resetClaims();
    do_action('arraysubs_renewal_payment_attempt_result', $subscription, $order, 1, 'failed');
    $assert($payload('retry_failed')['event_id'] === $first_id, 'Replay retains durable order/attempt event identity');
    do_action('arraysubs_renewal_payment_attempt_result', $subscription, $order, 2, 'failed');
    $assert($payload('retry_failed')['event_id'] !== $first_id, 'Different attempt has distinct durable identity');
    $pending = ['target_product_id' => $product_id, 'switch_type' => 'crossgrade', 'signature' => 'private-signed-data'];
    do_action('arraysubs_pending_switch_set', $subscription_id, $pending, null);
    do_action('arraysubs_pending_switch_set', $subscription_id, $pending + ['requested_at' => 'later'], $pending);
    do_action('arraysubs_pending_switch_cleared', $subscription_id, $pending, 'cancellation');
    do_action('arraysubs_pending_switch_applied', $subscription_id, $pending, $order->get_id());
    $assert($has('pending_switch_set') && $has('pending_switch_replaced') && $has('pending_switch_cancelled') && $has('pending_switch_applied'), 'Pending switch cases are distinct');
    $assert(!str_contains(wp_json_encode($events), 'private-signed-data'), 'Signed pending payload does not leak into scalar bus');
    do_action('arraysubs_trial_terms_updated', $subscription, ['length' => 1], ['length' => 2]);
    do_action('arraysubs_grace_terms_updated', $subscription, ['before_cancel' => 3], ['before_cancel' => 5]);
    $assert($has('trial_changed') && $has('grace_changed'), 'Trial/grace change adapters carry documented state');
    foreach ($events as $item) {
        $event_payload = $item['payload'];
        $assert(!array_filter($event_payload, static fn($value) => !is_scalar($value) && $value !== null), 'Bus stays scalar-only');
        if (in_array($item['event'], ['status_changed', 'on_hold', 'trial_ended'], true)) {
            $assert($event_payload['effect'] === 'none', 'Detail events do not repeat canonical entitlement effects');
        }
        $assert(!empty($event_payload['event_id']) && !empty($event_payload['event_time']) && !empty($event_payload['source_hook']), 'Every event includes identity/time/source');
    }
    $assert(!arraysubs_cancel_subscription_immediately($subscription_id, 'Regression check', 'system'), 'Cancellation still refuses an ambiguous renewal order ownership');
    delete_post_meta($subscription_id, '_pending_renewal_order_id');
    $renewal = (new ArraySubs\Features\RecurringBilling\Services\OrderCreation())->createRenewalOrder($subscription_id);
    $assert($renewal instanceof WC_Order, 'Actual renewal invoice is built for recovery');
    $orders[] = $renewal;
    update_post_meta($subscription_id, '_pending_renewal_order_id', $renewal->get_id());
    update_post_meta($subscription_id, '_last_payment_failure', gmdate('Y-m-d H:i:s'));
    wp_update_post(['ID' => $subscription_id, 'post_status' => 'arraysubs-on-hold']);
    SubscriptionEvent::resetClaims();
    $renewal->payment_complete('local-lifecycle-recovery');
    $assert(get_post_status($subscription_id) === 'arraysubs-active', 'Real paid renewal restores active status');
    $assert($has('grace_recovered'), 'Real paid renewal emits captured recovery after failure metadata was cleared');
    $assert($payload('grace_recovered')['order_id'] === $renewal->get_id(), 'Recovery event identifies its actual paid order');
    $assert($payload('grace_recovered')['status'] === 'arraysubs-active' && $payload('grace_recovered')['identity_scope'] === 'operation', 'Recovery snapshot is final and durably identified');
    $recovery_count = count(array_filter($events, static fn($event) => $event['event'] === 'grace_recovered'));
    SubscriptionEvent::resetClaims();
    do_action('arraysubs_order_paid', $renewal->get_id(), wc_get_order($renewal->get_id()));
    $assert(count(array_filter($events, static fn($event) => $event['event'] === 'grace_recovered')) === $recovery_count, 'Replayed paid-order fanout does not emit another recovery');
    $assert(arraysubs_cancel_subscription_immediately($subscription_id, 'Regression check', 'system'), 'Native immediate cancellation succeeds');
    $assert($has('cancelled'), 'Native cancellation completion reaches catalog');
    WP_CLI::success($checks . ' lifecycle event assertions passed; gateway attempt mapping tested without a charge.');
} finally {
    remove_action('arraysubs_lifecycle_event', $listen, 100);
    foreach ($orders as $order) {
        $order->delete(true);
    }
    foreach (array_reverse($ids) as $id) {
        wp_delete_post($id, true);
    }
    if ($user_id && !is_wp_error($user_id)) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($user_id);
    }
}
