<?php
/**
 * Probes for the review-round-3 patches (Stripe fixes #56-#64).
 *
 * Usage: wp eval-file <abs>/scripts/fix-r3-probes.php --allow-root
 * Overrides use in-process filters. The only write is a temporary
 * `_arraysubs_customer_attempt_seen` meta on fixture order 69222 (paid),
 * removed again before the script ends. Stripe is only read (GETs of ids
 * that do not exist, and of fixture customer 593).
 */

use ArraySubs\Features\AutomaticPayments\Gateways\Stripe\StripeDelegate;
use ArraySubs\Features\RecurringBilling\Services\GracePolicy;
use ArraySubs\Supports\ActionScheduler;

$delegate = new StripeDelegate(false);
$call = static function ($object, string $method, array $args = []) {
    $ref = new ReflectionMethod($object, $method);
    $ref->setAccessible(true);
    return $ref->invokeArgs($object, $args);
};
$fmt = static fn($ts) => is_int($ts) ? gmdate('Y-m-d H:i:s', $ts) : var_export($ts, true);
$out = [];
$sid = 69202;

// L1: pending-only lookups.
$out['l1_next_pending_101610'] = $fmt(ActionScheduler::getNextPendingTime(ActionScheduler::HOOK_PROCESS_RENEWAL, [$sid], ActionScheduler::GROUP_RENEWALS));
$out['l1_queue_attempt_with_pending_action'] = $call($delegate, 'queueRenewalAttempt', [$sid, HOUR_IN_SECONDS]) ? 'QUEUED (unexpected)' : 'not queued (pending action kept)';

// Fake an open failure with 3 retries scheduled, so the pending action counts as a retry.
$fake = [ '_payment_retry_attempts' => '3', '_last_payment_failure' => '2026-10-19 21:00:00' ];
$meta_filter = static function ($value, $id, $key) use ($sid, &$fake) {
    return ($sid === (int) $id && array_key_exists($key, $fake)) ? [$fake[$key]] : $value;
};
add_filter('get_post_metadata', $meta_filter, 10, 3);
$retry_at = arraysubs_get_payment_retry_next_attempt_at($sid);
$out['l1_helper_retry_at'] = $fmt($retry_at);

// L4: the hold waits for a queued retry whose place in the schedule fits the window,
// however late it runs. Default ladder 24,24,24 (72 h) vs 3 days.
$days = (int) GracePolicy::settings($sid)['before_on_hold'];
$fake['_next_payment_date'] = gmdate('Y-m-d H:i:s', $retry_at - $days * DAY_IN_SECONDS - 6 * HOUR_IN_SECONDS);
$t = GracePolicy::thresholds($sid);
$out['l4_default_ladder_retry_6h_late'] = ['due' => $fake['_next_payment_date'], 'window_end' => $fmt($t['due_at'] + $days * DAY_IN_SECONDS), 'hold_eligible_at' => $fmt($t['hold_eligible_at']), 'waits' => $t['hold_eligible_at'] === $retry_at + HOUR_IN_SECONDS];
$long = static function ($config) { $config['intervals'] = [DAY_IN_SECONDS, 2 * DAY_IN_SECONDS, 3 * DAY_IN_SECONDS]; $config['max_attempts'] = 3; return $config; };
add_filter('arraysubs_payment_retry_config', $long, 999);
$t = GracePolicy::thresholds($sid);
remove_filter('arraysubs_payment_retry_config', $long, 999);
$out['l4_ladder_24_48_72_retry3'] = ['hold_eligible_at' => $fmt($t['hold_eligible_at']), 'waits' => $t['hold_eligible_at'] === $retry_at + HOUR_IN_SECONDS];
remove_filter('get_post_metadata', $meta_filter, 10);

// M1 (round 3) / L3 (round 4): resource_missing is hard unless Stripe says the object exists in the other mode.
$customer = (string) get_post_meta($sid, '_gateway_customer_id', true);
$r = $call($delegate, 'verifyStripePaymentMethodIsReusable', [$customer, 'pm_1ArraySubsProbeMissing000']);
$out['m1_missing_pm_real_customer'] = is_wp_error($r) ? $r->get_error_code() : 'NOT_ERROR';
$r = $call($delegate, 'verifyStripePaymentMethodIsReusable', ['cus_ArraySubsProbeMissing0', 'pm_1ArraySubsProbeMissing000']);
$out['m1_missing_pm_unknown_customer'] = is_wp_error($r) ? $r->get_error_code() : 'NOT_ERROR';
$out['m1_similar_object_message'] = $call($delegate, 'isStripeObjectInOtherMode', [['code' => 'resource_missing', 'message' => "No such PaymentMethod: 'pm_x'; a similar object exists in live mode, but a test mode key was used to make this request."]]) ? 'soft (other mode)' : 'hard';
$out['m1_plain_missing_message'] = $call($delegate, 'isStripeObjectInOtherMode', [['code' => 'resource_missing', 'message' => "No such PaymentMethod: 'pm_x'"]]) ? 'soft (other mode)' : 'hard';
$next = ActionScheduler::getNextPendingAction(ActionScheduler::HOOK_PROCESS_RENEWAL, [$sid], ActionScheduler::GROUP_RENEWALS);
$out['r4_next_pending_action'] = $next ? ['id' => $next['id'], 'at' => $fmt($next['timestamp'])] : null;

// M2a: first sighting of a customer attempt is remembered and read back.
$order = wc_get_order(69222);
$before = (string) $order->get_meta('_arraysubs_customer_attempt_seen', true);
$call($delegate, 'rememberCustomerAttempt', [$order, 'pi_probe', 'pm_probe']);
$first = $call($delegate, 'customerAttemptSeenAt', [wc_get_order(69222), 'pi_probe', 'pm_probe']);
sleep(1);
$call($delegate, 'rememberCustomerAttempt', [wc_get_order(69222), 'pi_probe', 'pm_probe']);
$second = $call($delegate, 'customerAttemptSeenAt', [wc_get_order(69222), 'pi_probe', 'pm_probe']);
$other = $call($delegate, 'customerAttemptSeenAt', [wc_get_order(69222), 'pi_probe', 'pm_other']);
$o = wc_get_order(69222);
if ('' === $before) { $o->delete_meta_data('_arraysubs_customer_attempt_seen'); } else { $o->update_meta_data('_arraysubs_customer_attempt_seen', $before); }
$o->save();
$out['m2a_seen'] = ['first' => $first > 0, 'kept_on_repeat' => $first === $second, 'other_card' => $other, 'meta_restored' => (string) wc_get_order(69222)->get_meta('_arraysubs_customer_attempt_seen', true) === $before];

// L3: paid-but-unprocessed current invoice counts as in flight.
$processor = new ArraySubs\Features\RecurringBilling\Services\RenewalProcessor();
$fake_ptr = static fn($value, $id, $key) => (68753 === (int) $id && '_pending_renewal_order_id' === $key) ? ['69364'] : $value;
add_filter('get_post_metadata', $fake_ptr, 10, 3);
$out['l3_paid_processed_pointer'] = $call($processor, 'hasSettlingRenewalPayment', [68753]);
$out['l3_pointer_is_found_order'] = $call($processor, 'hasSettlingRenewalPayment', [68753, wc_get_order(69364)]);
remove_filter('get_post_metadata', $fake_ptr, 10);

echo wp_json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
