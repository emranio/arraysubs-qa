<?php
// Usage: wp eval-file fix-sub-state.php <subscription_id> [order_id ...] --allow-root
// Prints a compact, secret-free state of a subscription, its bound Stripe card and given orders.
$sid = (int) ($args[0] ?? 0);
$out = ['subscription' => $sid, 'status' => get_post_status($sid)];
foreach (['_gateway_customer_id','_gateway_payment_method_id','_payment_method_brand','_payment_method_last4','_gateway_status','_last_payment_failure','_last_payment_failure_category','_payment_retry_attempts','_payment_retry_next_attempt_at','_pending_renewal_order_id','_next_payment_date','_arraysubs_payment_action_intent_id','_arraysubs_stripe_portal_requested_at'] as $k) { $out['meta'][$k] = get_post_meta($sid, $k, true); }
$out['derived_next_retry_at'] = arraysubs_get_payment_retry_next_attempt_at($sid);
$pm = (string) $out['meta']['_gateway_payment_method_id'];
if ($pm !== '' && class_exists('WC_Stripe_API')) { try { $o = WC_Stripe_API::retrieve('payment_methods/' . $pm); $out['bound_pm'] = ['id' => $o->id ?? null, 'customer' => is_object($o->customer ?? null) ? $o->customer->id : ($o->customer ?? null), 'type' => $o->type ?? null, 'last4' => $o->card->last4 ?? null]; } catch (Throwable $e) { $out['bound_pm'] = 'error'; } }
$uid = (int) get_post_meta($sid, '_customer_id', true);
$out['wc_tokens'] = array_map(fn($t) => ['id' => $t->get_id(), 'pm' => $t->get_token(), 'last4' => method_exists($t, 'get_last4') ? $t->get_last4() : '', 'default' => $t->is_default()], array_values(WC_Payment_Tokens::get_tokens(['user_id' => $uid, 'gateway_id' => 'stripe'])));
foreach (array_slice($args, 1) as $oid) { $o = wc_get_order((int) $oid); if (! $o) continue; $out['orders'][$oid] = ['status' => $o->get_status(), 'paid' => $o->is_paid(), 'intent' => $o->get_meta('_stripe_intent_id'), 'source' => $o->get_meta('_stripe_source_id'), 'action_intent' => $o->get_meta('_arraysubs_payment_action_intent_id'), 'transaction' => $o->get_transaction_id(), 'billing_email' => $o->get_billing_email()]; }
$out['next_actions'] = array_map(fn($a) => $a, ArraySubs\Supports\ActionScheduler::getScheduledForSubscription($sid));
echo wp_json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
