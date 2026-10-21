<?php
/**
 * Read-only state of a renewal order and the Stripe PaymentIntents it references.
 *
 * Usage: wp eval-file <abs>/qa/fix-65-69/scripts/t65-state.php <order_id> <subscription_id> [pi_id ...] --allow-root
 * Prints no order keys or metadata values other than order_id/subscription_id.
 */

use ArraySubs\Supports\ActionScheduler;

$order_id = (int) ($args[0] ?? 0);
$sid      = (int) ($args[1] ?? 0);
$order    = wc_get_order($order_id);
if (! $order) {
    WP_CLI::error('Order not found.');
}
$order->read_meta_data(true);
$out = ['utc' => gmdate('Y-m-d H:i:s'), 'order' => $order_id, 'status' => $order->get_status(), 'paid' => $order->is_paid(), 'transaction_id' => $order->get_transaction_id()];
foreach (['_stripe_intent_id', '_arraysubs_pending_transaction_id', '_arraysubs_charge_attempt_transaction_id', '_arraysubs_charge_attempt_seq', '_arraysubs_customer_attempt_seen', '_arraysubs_customer_pay_started_at', '_arraysubs_payment_action_intent_id', '_arraysubs_pending_deadline', '_gateway_payment_method_id'] as $key) {
    $out['order_meta'][$key] = (string) $order->get_meta($key, true);
}
clean_post_cache($sid);
foreach (['_gateway_payment_method_id', '_last_payment_failure', '_last_payment_failure_category', '_payment_retry_attempts', '_pending_renewal_order_id', '_next_payment_date'] as $key) {
    $out['subscription_meta'][$key] = (string) get_post_meta($sid, $key, true);
}
$out['subscription_status'] = get_post_status($sid);
$next = ActionScheduler::getNextPendingAction(ActionScheduler::HOOK_PROCESS_RENEWAL, [$sid], ActionScheduler::GROUP_RENEWALS);
$out['next_renewal_action'] = $next ? ['id' => $next['id'], 'at' => gmdate('Y-m-d H:i:s', $next['timestamp'])] : null;
$ids = array_values(array_unique(array_filter(array_merge([(string) $order->get_meta('_stripe_intent_id', true), (string) $order->get_meta('_arraysubs_charge_attempt_transaction_id', true)], array_slice($args, 2)), static fn($id) => str_starts_with($id, 'pi_'))));
foreach ($ids as $id) {
    $pi = WC_Stripe_API::retrieve('payment_intents/' . $id);
    $out['intents'][$id] = is_object($pi) && isset($pi->id) ? [
        'status' => $pi->status, 'created' => gmdate('H:i:s', (int) $pi->created), 'amount' => $pi->amount,
        'received' => $pi->amount_received, 'capturable' => $pi->amount_capturable,
        'payment_method' => is_string($pi->payment_method ?? null) ? $pi->payment_method : null,
        'meta_order_id' => $pi->metadata->order_id ?? null, 'meta_subscription_id' => $pi->metadata->subscription_id ?? null,
        'cancellation_reason' => $pi->cancellation_reason ?? null,
        'last_error' => isset($pi->last_payment_error) ? trim(($pi->last_payment_error->code ?? '') . ' ' . ($pi->last_payment_error->decline_code ?? '')) : null,
    ] : 'unreadable';
}
echo wp_json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
