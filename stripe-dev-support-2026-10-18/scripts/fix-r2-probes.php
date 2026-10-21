<?php
/**
 * Read-only probes for the review-round-2 patches (Stripe fixes #56-#64).
 *
 * Usage: wp eval-file <abs>/scripts/fix-r2-probes.php --allow-root
 * Nothing is written: overrides use in-process filters only. Stripe is called
 * only for a GET of a PaymentMethod id that does not exist.
 */

use ArraySubs\Features\AutomaticPayments\Gateways\Stripe\StripeDelegate;

$delegate = new StripeDelegate(false);
$call = static function ($object, string $method, array $args = []) {
    $ref = new ReflectionMethod($object, $method);
    $ref->setAccessible(true);
    return $ref->invokeArgs($object, $args);
};
$out = [];

// RS-F1: Woo Stripe returns null on a 401.
$r = $call($delegate, 'normalizeStripeResponse', [null]);
$out['rs_f1_null_response'] = is_wp_error($r) ? $r->get_error_code() : 'NOT_ERROR:' . wp_json_encode($r);
$r = $call($delegate, 'normalizeStripeResponse', [(object) ['id' => 'pm_x', 'object' => 'payment_method']]);
$out['rs_f1_object_response'] = is_array($r) ? ($r['id'] ?? '') : 'ERROR';

// RS-F2: a PaymentMethod Stripe does not have.
$customer = (string) get_post_meta(69202, '_gateway_customer_id', true);
$r = $call($delegate, 'verifyStripePaymentMethodIsReusable', [$customer, 'pm_1ArraySubsProbeMissing000']);
$code = is_wp_error($r) ? $r->get_error_code() : 'NOT_ERROR';
$out['rs_f2_missing_pm'] = $code . ' -> ' . arraysubs_classify_payment_failure_reason($code, '') . (arraysubs_is_hard_payment_decline(arraysubs_classify_payment_failure_reason($code, '')) ? ' (hard)' : ' (soft)');
$raw = $call($delegate, 'retrieveAutomaticRenewalPaymentMethod', ['pm_1ArraySubsProbeMissing000', $customer]);
$raw_data = is_wp_error($raw) ? $raw->get_error_data() : null;
$out['rs_f2_stripe_error_code'] = is_array($raw_data) ? (string) ($raw_data['code'] ?? '') : (is_wp_error($raw) ? $raw->get_error_code() : 'NOT_ERROR');
$out['rs_f1_empty_response_category'] = arraysubs_classify_payment_failure_reason('renewal_payment_method_verification_failed', '');

// MB-F2: unstamped bindings are not "newer than" any payment.
$bridge = new ArraySubs\Features\AutomaticPayments\Services\StripeOrderContextBridge();
foreach ([69202, 68473] as $sid) {
    $order_id = (int) get_post_meta($sid, '_pending_renewal_order_id', true) ?: 68473;
    $order = wc_get_order($order_id);
    if (! $order) { continue; }
    $stamp = (string) get_post_meta($sid, '_payment_method_updated_at', true);
    $out['mb_f2_newer_' . $sid] = ['stamp' => $stamp ?: '(none)', 'order' => $order_id, 'newer' => $call($bridge, 'bindingIsNewerThanPayment', [$sid, $order])];
}
$fake_unstamped = static function ($value, $id, $key) {
    return (69202 === (int) $id && '_payment_method_updated_at' === $key) ? [''] : $value;
};
add_filter('get_post_metadata', $fake_unstamped, 10, 3);
$out['mb_f2_newer_69202_unstamped'] = $call($bridge, 'bindingIsNewerThanPayment', [69202, wc_get_order(69222)]);
remove_filter('get_post_metadata', $fake_unstamped, 10);

// MB-F1 root guard: an invoice whose payment is settling.
$processor = new ArraySubs\Features\RecurringBilling\Services\RenewalProcessor();
$out['mb_f1_settling_69202_now'] = $call($processor, 'hasSettlingRenewalPayment', [69202]);
$fake_status = static fn($status) => 'on-hold';
$fake_txn = static fn($txn) => 'ch_probe';
add_filter('woocommerce_order_get_status', $fake_status);
add_filter('woocommerce_order_get_transaction_id', $fake_txn);
$out['mb_f1_settling_69202_onhold_txn'] = $call($processor, 'hasSettlingRenewalPayment', [69202]);
remove_filter('woocommerce_order_get_status', $fake_status);
remove_filter('woocommerce_order_get_transaction_id', $fake_txn);

// RS-F3: the hold waits for a retry at the edge of the window.
$retry_at = arraysubs_get_payment_retry_next_attempt_at(69202);
$out['rs_f3_retry_at'] = $retry_at ? gmdate('Y-m-d H:i:s', $retry_at) : null;
$base = ArraySubs\Features\RecurringBilling\Services\GracePolicy::thresholds(69202);
$out['rs_f3_now'] = ['due' => gmdate('Y-m-d H:i:s', (int) $base['due_at']), 'hold_eligible_at' => gmdate('Y-m-d H:i:s', (int) $base['hold_eligible_at'])];
if ($retry_at) {
    $days = (int) $base['policy']['before_on_hold'];
    $fake_due = gmdate('Y-m-d H:i:s', $retry_at - $days * DAY_IN_SECONDS + 10 * MINUTE_IN_SECONDS);
    $fake_date = static function ($value, $id, $key) use ($fake_due) {
        return (69202 === (int) $id && '_next_payment_date' === $key) ? [$fake_due] : $value;
    };
    add_filter('get_post_metadata', $fake_date, 10, 3);
    $edge = ArraySubs\Features\RecurringBilling\Services\GracePolicy::thresholds(69202);
    remove_filter('get_post_metadata', $fake_date, 10);
    $out['rs_f3_retry_at_window_edge'] = ['fake_due' => $fake_due, 'window_end' => gmdate('Y-m-d H:i:s', strtotime($fake_due . ' UTC') + $days * DAY_IN_SECONDS), 'hold_eligible_at' => gmdate('Y-m-d H:i:s', (int) $edge['hold_eligible_at'])];
    $far_due = gmdate('Y-m-d H:i:s', $retry_at - $days * DAY_IN_SECONDS - 12 * HOUR_IN_SECONDS);
    $fake_far = static function ($value, $id, $key) use ($far_due) {
        return (69202 === (int) $id && '_next_payment_date' === $key) ? [$far_due] : $value;
    };
    add_filter('get_post_metadata', $fake_far, 10, 3);
    $far = ArraySubs\Features\RecurringBilling\Services\GracePolicy::thresholds(69202);
    remove_filter('get_post_metadata', $fake_far, 10);
    $out['rs_f3_retry_after_window'] = ['fake_due' => $far_due, 'hold_eligible_at' => gmdate('Y-m-d H:i:s', (int) $far['hold_eligible_at'])];
    $out['rs_f3_offset_seconds'] = arraysubs_get_renewal_spread_offset(69202);
    $late_due = gmdate('Y-m-d H:i:s', $retry_at - $days * DAY_IN_SECONDS - 30 * MINUTE_IN_SECONDS);
    $fake_late = static function ($value, $id, $key) use ($late_due) {
        return (69202 === (int) $id && '_next_payment_date' === $key) ? [$late_due] : $value;
    };
    add_filter('get_post_metadata', $fake_late, 10, 3);
    $late = ArraySubs\Features\RecurringBilling\Services\GracePolicy::thresholds(69202);
    remove_filter('get_post_metadata', $fake_late, 10);
    $out['rs_f3_retry_30min_after_window_end'] = ['fake_due' => $late_due, 'window_end' => gmdate('Y-m-d H:i:s', strtotime($late_due . ' UTC') + $days * DAY_IN_SECONDS), 'hold_eligible_at' => gmdate('Y-m-d H:i:s', (int) $late['hold_eligible_at'])];
}

echo wp_json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
