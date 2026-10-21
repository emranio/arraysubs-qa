<?php
/**
 * Review r2 R2-1 check (fixture only): hold a renewal order's charge lock the way an
 * automatic Stripe renewal attempt does (RENEWAL_CHARGE_LOCK_TTL), for N seconds,
 * so a Pay-page submit in that window shows the "being processed or confirmed" message.
 *
 * Usage: wp eval-file <abs>/qa/fix-65-69/scripts/r2-hold-auto-lock.php <order_id> <seconds> --allow-root
 */
use ArraySubs\Features\AutomaticPayments\Gateways\Stripe\StripeDelegate;
use ArraySubs\Features\AutomaticPayments\Services\ChargeAttemptGuard;

$order   = wc_get_order((int) ($args[0] ?? 0));
$seconds = max(5, min(180, (int) ($args[1] ?? 90)));
if (! $order) { WP_CLI::error('order not found'); }
$ttl = (new ReflectionClassConstant(StripeDelegate::class, 'RENEWAL_CHARGE_LOCK_TTL'))->getValue();
$ok  = ChargeAttemptGuard::acquireChargeLock($order, (int) $ttl);
echo 'automatic charge lock held=', var_export($ok, true), ' ttl=', $ttl, ' at ', gmdate('H:i:s'), "\n";
if (! $ok) { exit(1); }
sleep($seconds);
ChargeAttemptGuard::releaseChargeLock($order);
echo 'released at ', gmdate('H:i:s'), "\n";
