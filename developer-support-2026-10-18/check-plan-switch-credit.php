<?php
/** Real WordPress/WooCommerce state checks; retains QA products/orders/ledger. */
use ArraySubs\Features\PlanSwitching\Services\PlanManager;
use ArraySubs\Features\PlanSwitching\Services\ProrationCalculator;
use ArraySubsPro\Features\StoreCredit\Services\CreditManager;
use ArraySubsPro\Features\StoreCredit\Services\CreditLedger;

function ps_check(string $label, bool $ok, $detail = null): void {
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label . ' ' . wp_json_encode($detail)); }
    echo 'PASS: ' . $label . PHP_EOL;
}
wp_set_current_user(1);
$settings = arraysubs_get_settings();
$settings['modules']['store_credit'] = true;
$settings['store_credit']['auto_apply_to_renewals'] = true;
$settings['store_credit']['expiration_days'] = 30;
$settings['plan_switching'] = array_merge($settings['plan_switching'], ['enabled'=>true,'allow_customer_switch'=>true,'allow_upgrades'=>true,'allow_downgrades'=>true,'allow_crossgrades'=>true,'proration_type'=>'prorate_immediately']);
$settings['proration']['switch_fees'] = ['upgrade'=>0,'downgrade'=>0,'crossgrade'=>0];
add_filter('pre_option_arraysubs_settings', function () use (&$settings) { return $settings; });
$run = wp_generate_uuid4();
$user_id = wp_insert_user(['user_login'=>'ps-'.substr($run,0,8),'user_email'=>'ps-'.substr($run,0,8).'@example.test','user_pass'=>wp_generate_password(),'role'=>'customer']);
$products = [];
foreach ([100,20] as $price) {
    $product = new WC_Product_Simple();
    $product->set_name('Developer support plan ' . $price . ' ' . substr($run,0,8));
    $product->set_status('publish'); $product->set_regular_price($price); $product->set_virtual(true);
    $id = $product->save();
    update_post_meta($id, '_is_subscription', 'yes');
    update_post_meta($id, '_subscription_period', 'month');
    update_post_meta($id, '_subscription_interval', 1);
    $products[] = $id;
}
[$old_id,$new_id] = $products;
update_post_meta($old_id,'_arraysubs_downgrade_products',[$new_id]);
$start = time() - 15 * DAY_IN_SECONDS; $end = time() + 15 * DAY_IN_SECONDS;
$subscription_id = arraysubs_create_subscription(['customer_id'=>$user_id,'product_id'=>$old_id,'status'=>'arraysubs-active','subscription_price'=>100,'recurring_amount'=>100,'start_date'=>gmdate('Y-m-d H:i:s',$start),'next_payment_date'=>gmdate('Y-m-d H:i:s',$end),'payment_method'=>'bacs']);
ps_check('Subscription fixture created', is_int($subscription_id), $subscription_id);
$order = wc_create_order(['customer_id'=>$user_id]);
$order->set_currency(get_woocommerce_currency());
$order->set_billing_email(get_userdata($user_id)->user_email);
$item = new WC_Order_Item_Product(); $item->set_product(wc_get_product($old_id)); $item->set_quantity(1); $item->set_subtotal(100); $item->set_total(100); $item->add_meta_data('_subscription_id',$subscription_id,true); $order->add_item($item);
$order->update_meta_data('_subscription_ids',[$subscription_id]);
$order->calculate_totals(false); $order->set_date_paid($start); $order->set_status('completed'); $order->save();
update_post_meta($subscription_id,'_parent_order_id',$order->get_id());
update_post_meta($subscription_id,'_order_ids',wp_json_encode([$order->get_id()]));
$quote = ProrationCalculator::calculate($subscription_id,$new_id,'prorate_immediately',1);
ps_check('Live Store Credit offers immediate excess', !empty($quote['success']) && $quote['proration_type']==='prorate_immediately' && $quote['refund_amount']>0, $quote);
$signed_quote = PlanManager::createProrationQuote($quote);
$settings['modules']['store_credit']=false;
$deferred = ProrationCalculator::calculate($subscription_id,$new_id,'prorate_immediately',1);
ps_check('Disabled module defers excess to renewal with no credit', !empty($deferred['success']) && $deferred['proration_type']==='apply_at_renewal' && $deferred['credit_policy']==='deferred_without_store_credit' && $deferred['refund_amount']==0 && $deferred['credit_amount']==0, $deferred);
ps_check('Module change invalidates confirmed quote', is_wp_error(PlanManager::validateProrationQuote($signed_quote,$deferred)));
$settings['modules']['store_credit']=true; $settings['store_credit']['auto_apply_to_renewals']=false;
$off = ProrationCalculator::calculate($subscription_id,$new_id,'prorate_immediately',1);
ps_check('Auto-apply off also defers excess', $off['proration_type']==='apply_at_renewal' && $off['credit_policy']==='deferred_without_store_credit');
$settings['store_credit']['auto_apply_to_renewals']=true;
$quote = ProrationCalculator::calculate($subscription_id,$new_id,'prorate_immediately',1);
$proration_order_id = PlanManager::createProrationOrder($subscription_id,$quote,1,'bacs');
ps_check('Signed credit-only settlement created', is_int($proration_order_id), $proration_order_id);
$proration_order = wc_get_order($proration_order_id);
$proof = PlanManager::getAuthenticatedProrationOrderProof($proration_order);
ps_check('Version 5 contract has destination and no frozen balance', !is_wp_error($proof) && $proof['contract']['version']===5 && $proof['contract']['credit_destination']==='subscription' && !array_key_exists('store_credit',$proof['contract']['old_plan']), $proof);
$settings['modules']['store_credit']=false;
$failed = PlanManager::finalizeProrationOrder($proration_order);
ps_check('Unavailable ledger blocks finalization before plan changes', is_wp_error($failed) && $failed->get_error_code()==='plan_switch_credit_unavailable' && (int)get_post_meta($subscription_id,'_product_id',true)===$old_id, $failed);
$settings['modules']['store_credit']=true;
CreditManager::addSubscriptionCredit($subscription_id, 3, CreditManager::SOURCE_ADMIN);
$notifications=0;
add_action('arraysubs_subscription_credit_added',function($id)use($subscription_id,&$notifications){if($id===$subscription_id){++$notifications;}},99);
$result = PlanManager::finalizeProrationOrder($proration_order);
ps_check('Settlement finalizes after ledger returns', true===$result, $result);
$balance = CreditManager::getSubscriptionCredit($subscription_id);
ps_check('Mid-switch balance change preserved and excess added', abs($balance - (3 + $quote['refund_amount'])) < .00001, $balance);
$rows = get_posts(['post_type'=>CreditLedger::CPT_CREDIT_LOG,'post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_contract_key','meta_value'=>$proof['fingerprint']]);
ps_check('One published ledger entry matches signed contract', count($rows)===1 && (float)get_post_meta($rows[0],'_balance_before',true)===3.0 && (float)get_post_meta($rows[0],'_balance_after',true)===$balance, $rows);
ps_check('Creation-based expiry set', (string)get_post_meta($rows[0],'_expires_at',true)===gmdate('Y-m-d H:i:s',strtotime(get_post_field('post_date_gmt',$rows[0]).' UTC')+30*DAY_IN_SECONDS));
ps_check('Replayed finalization changes no balance or email dispatch', true===PlanManager::finalizeProrationOrder(wc_get_order($proration_order_id)) && CreditManager::getSubscriptionCredit($subscription_id)===$balance && $notifications===1);
echo wp_json_encode(['customer_id'=>$user_id,'subscription_id'=>$subscription_id,'source_order_id'=>$order->get_id(),'settlement_order_id'=>$proration_order_id,'products'=>$products,'ledger_id'=>$rows[0]],JSON_PRETTY_PRINT).PHP_EOL;
