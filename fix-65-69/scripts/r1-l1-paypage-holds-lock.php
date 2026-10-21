<?php
/**
 * Review r1 L1 check (fixture only): act as a customer's Stripe Pay-page request for a renewal:
 * write the Pay-page stamp and hold the order's charge lock for N seconds, like
 * holdPayPageChargeLock() does for the rest of a real Pay-page request.
 *
 * Usage: wp eval-file <abs>/qa/fix-65-69/scripts/r1-l1-paypage-holds-lock.php <order_id> <seconds> --allow-root
 */
use ArraySubs\Features\AutomaticPayments\Gateways\Stripe\StripeDelegate;
use ArraySubs\Features\AutomaticPayments\Services\ChargeAttemptGuard;

$order   = wc_get_order((int) ($args[0] ?? 0));
$seconds = max(5, min(120, (int) ($args[1] ?? 60)));
if (! $order) { WP_CLI::error('order not found'); }
$delegate = new StripeDelegate(false);
$hold = new ReflectionMethod($delegate, 'holdPayPageChargeLock');
$hold->setAccessible(true);
$ok = $hold->invoke($delegate, $order, 'stripe');
echo 'pay-page lock held=', var_export($ok, true), ' stamp=', $order->get_meta('_arraysubs_customer_pay_started_at'), ' at ', gmdate('H:i:s'), "\n";
sleep($seconds);
ChargeAttemptGuard::releaseChargeLock($order);
echo 'released at ', gmdate('H:i:s'), "\n";
