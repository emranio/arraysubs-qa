<?php
// M2 probe: an authentication intent carrying a card other than the renewal's
// attempt snapshot must not publish verification markers. Order 69222 / sub 69202.
$d = \ArraySubs\Features\AutomaticPayments\Services\GatewayResolver::resolveBySlug('stripe');
$m = new ReflectionMethod($d, 'queueRenewalRequiresActionNotice'); $m->setAccessible(true);
$o = wc_get_order(69222);
$intent = ['id' => (string) $o->get_meta('_stripe_intent_id'), 'object' => 'payment_intent', 'status' => 'requires_action', 'payment_method' => 'pm_probeDifferentCard000', 'metadata' => ['renewal' => 'true', 'order_id' => '69222', 'subscription_id' => '69202']];
$r = $m->invoke($d, 69202, $o, $intent);
$o = wc_get_order(69222); $o->read_meta_data(true);
echo 'result=', var_export($r, true), ' action_intent=', $o->get_meta('_arraysubs_payment_action_intent_id') ?: '-', ' sub_action=', get_post_meta(69202, '_arraysubs_payment_action_intent_id', true) ?: '-', "\n";
