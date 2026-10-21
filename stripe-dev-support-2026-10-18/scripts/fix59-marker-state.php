<?php
$q = wc_get_orders(['type'=>'shop_order','status'=>['pending','failed','on-hold'],'limit'=>200,'return'=>'ids','orderby'=>'ID','order'=>'ASC','meta_query'=>[['key'=>'_is_renewal_order','value'=>'yes'],['key'=>'_arraysubs_charge_attempt_gateway','value'=>'stripe']]]);
echo "unpaid stripe-attempted renewals: ".count($q)." -> ".implode(',', $q)."\n";
echo "sweep page option: ".get_option('arraysubs_stripe_requires_action_sweep_page', '(none)')."\n";
foreach ([68473,68550,68553,68614,68694,68822,68903,68494,68846] as $oid) { $o = wc_get_order($oid); if (!$o) continue; $sub = (int)$o->get_meta('_subscription_id');
 printf("order %d %s intent=%s action_intent=%s req_at=%s sub=%d sub_action=%s\n", $oid, $o->get_status(), substr((string)$o->get_meta('_stripe_intent_id'),0,14), substr((string)$o->get_meta('_arraysubs_payment_action_intent_id'),0,14), $o->get_meta('_arraysubs_payment_action_required_at') ?: '-', $sub, substr((string)get_post_meta($sub,'_arraysubs_payment_action_intent_id',true),0,14) ?: '-'); }
$next = as_next_scheduled_action('arraysubs_sweep_stripe_renewal_verifications'); echo "next sweep: ".(is_int($next)?gmdate('H:i:s',$next):var_export($next,true))."\n";
