<?php
// Runs the new abandoned-attempt retirement for paid renewal 68614 (sub 68599), whose
// earlier renewal PI was left requires_action by an abandoned on-session 3DS.
$d = \ArraySubs\Features\AutomaticPayments\Services\GatewayResolver::resolveBySlug('stripe');
$m = new ReflectionMethod($d, 'retireAbandonedRenewalAttemptIntent'); $m->setAccessible(true);
$o = wc_get_order(68614);
$m->invoke($d, 68599, $o, (string) $o->get_meta('_stripe_intent_id'));
$pi = WC_Stripe_API::retrieve('payment_intents/pi_3USDAtJG5OzSNVs21p7WVc87');
echo 'old PI status=', $pi->status, ' reason=', $pi->cancellation_reason ?? '-', ' received=', $pi->amount_received, "\n";
