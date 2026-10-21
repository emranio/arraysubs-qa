<?php
/**
 * Read-only: one renewal order's Stripe attempt state and its PaymentIntents.
 * Usage: wp eval-file <abs>/scripts/fix-r2-order-pi-state.php <order_id> [extra_pi...] --allow-root
 */
use ArraySubs\Features\AutomaticPayments\Services\ChargeAttemptGuard;

$order = wc_get_order((int) ($args[0] ?? 0));
if (! $order) { WP_CLI::error('order not found'); }
$sub = (int) $order->get_meta('_subscription_id', true);
$pis = array_values(array_unique(array_filter(array_merge(
    [(string) $order->get_meta('_stripe_intent_id', true), ChargeAttemptGuard::attemptResult($order)],
    array_slice($args, 1)
))));
$state = [
    'order' => $order->get_id(), 'status' => $order->get_status(), 'paid' => $order->is_paid(),
    'transaction' => substr((string) $order->get_transaction_id(), 0, 14),
    'stripe_intent_id' => (string) $order->get_meta('_stripe_intent_id', true),
    'attempt_result' => ChargeAttemptGuard::attemptResult($order),
    'attempt_snapshot_pm' => (string) $order->get_meta('_gateway_payment_method_id', true),
    'source_pm' => (string) $order->get_meta('_stripe_source_id', true),
    'action_intent' => (string) $order->get_meta('_arraysubs_payment_action_intent_id', true),
    'action_required_at' => (string) $order->get_meta('_arraysubs_payment_action_required_at', true),
    'outbox' => (string) $order->get_meta('_arraysubs_stripe_requires_action_notice_status', true),
    'sub' => $sub, 'sub_status' => get_post_status($sub),
    'sub_pm' => (string) get_post_meta($sub, '_gateway_payment_method_id', true),
    'sub_last4' => (string) get_post_meta($sub, '_payment_method_last4', true),
    'sub_failure' => (string) get_post_meta($sub, '_last_payment_failure', true),
    'sub_failure_category' => (string) get_post_meta($sub, '_last_payment_failure_category', true),
    'sub_retry_attempts' => (string) get_post_meta($sub, '_payment_retry_attempts', true),
    'derived_next_retry' => ($n = arraysubs_get_payment_retry_next_attempt_at($sub)) ? gmdate('Y-m-d H:i:s', $n) : null,
    'next_payment_date' => (string) get_post_meta($sub, '_next_payment_date', true),
    'pis' => [],
];
foreach ($pis as $pi_id) {
    $pi = WC_Stripe_API::retrieve('payment_intents/' . rawurlencode($pi_id));
    $state['pis'][$pi_id] = is_object($pi) && empty($pi->error) ? [
        'status' => $pi->status, 'pm' => is_object($pi->payment_method ?? null) ? $pi->payment_method->id : ($pi->payment_method ?? ''),
        'received' => $pi->amount_received, 'capturable' => $pi->amount_capturable,
        'cancellation_reason' => $pi->cancellation_reason ?? null,
        'last_error' => isset($pi->last_payment_error) && $pi->last_payment_error ? trim(($pi->last_payment_error->code ?? '') . ' ' . ($pi->last_payment_error->decline_code ?? '')) : null,
        'setup_future_usage' => $pi->setup_future_usage ?? null,
    ] : 'unreadable';
}
echo wp_json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
