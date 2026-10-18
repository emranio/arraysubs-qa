<?php
/** Developer credit API, expiry and order recovery checks on real WordPress state. */
use ArraySubs\Developer\StoreCredit;
use ArraySubsPro\Features\StoreCredit\Services\CreditManager;
use ArraySubsPro\Features\StoreCredit\Services\CreditLedger;
use ArraySubsPro\Features\StoreCredit\Services\CreditExpiration;

function ca_check(string $label, bool $ok, $detail = null): void {
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label . ' ' . wp_json_encode($detail)); }
    echo 'PASS: ' . $label . PHP_EOL;
}
wp_set_current_user(1);
$settings = arraysubs_get_settings();
$settings['modules']['store_credit'] = true;
$settings['store_credit']['auto_apply_to_renewals'] = true;
$settings['store_credit']['apply_at_checkout'] = true;
$settings['store_credit']['expiration_days'] = 30;
add_filter('pre_option_arraysubs_settings', function()use(&$settings){return $settings;});
$run=wp_generate_uuid4();
$user_id=wp_insert_user(['user_login'=>'ca-'.substr($run,0,8),'user_email'=>'ca-'.substr($run,0,8).'@example.test','user_pass'=>wp_generate_password(),'role'=>'customer']);
$subscription_id=wp_insert_post(['post_type'=>'arraysubs_data','post_status'=>'arraysubs-active','post_title'=>'Credit API regression '.$run,'meta_input'=>['_customer_id'=>$user_id,'_currency'=>get_woocommerce_currency()]]);
$api=StoreCredit::fromCustomerId($user_id); $sub=StoreCredit::fromSubscriptionId($subscription_id);
ca_check('Factories resolve explicit customer and subscription', $api instanceof StoreCredit && $sub instanceof StoreCredit);
ca_check('Invalid customer and subscription fail', is_wp_error(StoreCredit::fromCustomerId(0))&&is_wp_error(StoreCredit::fromSubscriptionId(0)));
$rows_before=count(get_posts(['post_type'=>CreditLedger::CPT_CREDIT_LOG,'post_status'=>['publish','draft'],'posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_customer_id','meta_value'=>$user_id]));
$raw_meta=get_post_meta($subscription_id); $raw_user=get_user_meta($user_id);
$data=$sub->data(); $history=$api->history(['fields'=>['id','amount']]);
ca_check('Reads are pure and expose eligible buckets', !is_wp_error($data)&&$data['available']&&$data['spend_order']===['subscription','customer']&&$raw_meta===get_post_meta($subscription_id)&&$raw_user===get_user_meta($user_id)&&$rows_before===count(get_posts(['post_type'=>CreditLedger::CPT_CREDIT_LOG,'post_status'=>['publish','draft'],'posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_customer_id','meta_value'=>$user_id])));
ca_check('Field selection and invalid fields handled', array_keys($api->data(['customer_balance']))===['customer_balance']&&is_wp_error($api->data(['missing'])));
ca_check('Pagination bounds enforced',is_wp_error($api->history(['per_page'=>101]))&&is_wp_error($api->history(['page'=>0])));
$base=['reason'=>'Credit API regression','currency'=>get_woocommerce_currency()];
$credit=$api->adjust(10,$base+['operation_id'=>$run.'-credit','expected_balance'=>0]);
ca_check('Customer credit has ledger and resulting state',!is_wp_error($credit)&&$credit['state']['customer_balance']===10.0,$credit);
$replay=$api->adjust(10,$base+['operation_id'=>$run.'-credit','expected_balance'=>0]);
ca_check('Credit replay reuses transaction',!is_wp_error($replay)&&$replay['transaction']['id']===$credit['transaction']['id']&&$replay['state']['customer_balance']===10.0,$replay);
$debit=$api->adjust(-3,$base+['operation_id'=>$run.'-debit']);
ca_check('Strict debit writes once',!is_wp_error($debit)&&$debit['state']['customer_balance']===7.0&&!is_wp_error($api->adjust(-3,$base+['operation_id'=>$run.'-debit']))&&CreditManager::getCustomerCredit($user_id)===7.0);
$insufficient=$api->adjust(-8,$base+['operation_id'=>$run.'-insufficient']);
ca_check('Insufficient debit does not clamp',is_wp_error($insufficient)&&$insufficient->get_error_code()==='insufficient_credit'&&CreditManager::getCustomerCredit($user_id)===7.0,$insufficient);
$stale=$api->adjust(1,$base+['operation_id'=>$run.'-stale','expected_balance'=>6]);
ca_check('Stale expected balance fails',is_wp_error($stale)&&$stale->get_error_code()==='credit_balance_changed');
ca_check('Invalid amount precision, currency and required args fail',is_wp_error($api->adjust(1.001,$base+['operation_id'=>$run.'-precision']))&&is_wp_error($api->adjust(1,['reason'=>'wrong currency','currency'=>'XXX','operation_id'=>$run.'-currency']))&&is_wp_error($api->adjust(1,$base)));
$settings['modules']['store_credit']=false;
ca_check('Module-off reads retain stored balance but cannot spend or adjust',$api->data()['customer_balance']===7.0&&$api->data()['available_for_renewal']===0.0&&is_wp_error($api->adjust(1,$base+['operation_id'=>$run.'-off'])));
$settings['modules']['store_credit']=true;
$subscription_credit=$sub->adjust(5,$base+['operation_id'=>$run.'-subscription']);
ca_check('Subscription bucket stays separate',!is_wp_error($subscription_credit)&&$sub->data()['subscription_balance']===5.0&&$sub->data()['customer_balance']===7.0);
$customer_history=$api->history(['fields'=>['id','subscription_id']]); $sub_history=$sub->history(['fields'=>['id','subscription_id']]);
ca_check('History is paginated and bucket scoped',count($customer_history['items'])===2&&count($sub_history['items'])===1&&$sub_history['items'][0]['subscription_id']===$subscription_id,[$customer_history,$sub_history]);

$order=wc_create_order(['customer_id'=>$user_id]); $fee=new WC_Order_Item_Fee();$fee->set_name('QA purchase');$fee->set_amount(9);$fee->set_total(9);$order->add_item($fee);$order->calculate_totals();$order->save();
$fail_customer=function($check,$id,$key)use($user_id){return $id===$user_id&&$key===CreditManager::USER_CREDIT_META?false:$check;};
add_filter('update_user_metadata',$fail_customer,10,3);
try { CreditManager::applyCreditsToOrder($order,$subscription_id); $failed=false; } catch(Throwable $error){$failed=true;}
remove_filter('update_user_metadata',$fail_customer,10);
ca_check('Failed second-bucket debit grants no discount',$failed&&CreditManager::getSubscriptionCredit($subscription_id)===0.0&&CreditManager::getCustomerCredit($user_id)===7.0&&(float)wc_get_order($order->get_id())->get_meta('_arraysubs_credit_applied')===0.0);
$total=CreditManager::applyCreditsToOrder(wc_get_order($order->get_id()),$subscription_id);
ca_check('Interrupted application resumes subscription-first split once',$total===9.0&&CreditManager::getSubscriptionCredit($subscription_id)===0.0&&CreditManager::getCustomerCredit($user_id)===3.0&&CreditManager::applyCreditsToOrder(wc_get_order($order->get_id()),$subscription_id)===0.0);
add_filter('update_user_metadata',$fail_customer,10,3);
try {CreditManager::reverseCreditsForOrder(wc_get_order($order->get_id()),$subscription_id);$failed=false;}catch(Throwable $error){$failed=true;}
remove_filter('update_user_metadata',$fail_customer,10);
ca_check('Failed restoration does not mark reversal complete',$failed&&CreditManager::getSubscriptionCredit($subscription_id)===5.0&&wc_get_order($order->get_id())->get_meta('_arraysubs_credit_reversed')!=='yes');
$restored=CreditManager::reverseCreditsForOrder(wc_get_order($order->get_id()),$subscription_id);
ca_check('Reversal retries restore both buckets exactly once',$restored===9.0&&CreditManager::getSubscriptionCredit($subscription_id)===5.0&&CreditManager::getCustomerCredit($user_id)===7.0&&CreditManager::reverseCreditsForOrder(wc_get_order($order->get_id()),$subscription_id)===0.0);

$referenced=$api->adjust(1,$base+['operation_id'=>$run.'-reference','reference_id'=>$order->get_id(),'actor_id'=>0]);
ca_check('Adjustment records a validated order reference and explicit system actor',!is_wp_error($referenced)&&$referenced['transaction']['source_id']===$order->get_id()&&$referenced['transaction']['actor_id']===0);
ca_check('Invalid reference, actor, expected precision and unknown options fail',is_wp_error($api->adjust(1,$base+['operation_id'=>$run.'-bad-reference','reference_id'=>999999]))&&is_wp_error($api->adjust(1,$base+['operation_id'=>$run.'-bad-actor','actor_id'=>999999]))&&is_wp_error($api->adjust(1,$base+['operation_id'=>$run.'-bad-expected','expected_balance'=>8.001]))&&is_wp_error($api->adjust(1,$base+['operation_id'=>$run.'-bad-option','expires_at'=>'2099-01-01']))&&is_wp_error($api->history(['fields'=>'id'])));

// Use a separate empty customer to make FIFO and >20 grant assertions exact.
$expiry_user=wp_insert_user(['user_login'=>'ce-'.substr($run,0,8),'user_email'=>'ce-'.substr($run,0,8).'@example.test','user_pass'=>wp_generate_password(),'role'=>'customer']);
$expiry_api=StoreCredit::fromCustomerId($expiry_user);
$first=$expiry_api->adjust(20,$base+['operation_id'=>'first']);$second=$expiry_api->adjust(20,$base+['operation_id'=>'second']);$expiry_api->adjust(-25,$base+['operation_id'=>'consume']);
$expiring=CreditExpiration::getExpiringCredits($expiry_user);
ca_check('Expiring snapshot tracks FIFO remainder',count($expiring)===1&&$expiring[0]['id']===$second['transaction']['id']&&$expiring[0]['amount']===15.0,$expiring);
$new=$expiry_api->adjust(10,$base+['operation_id'=>'newer']);delete_post_meta($new['transaction']['id'],'_expires_at');
update_post_meta($second['transaction']['id'],'_expires_at',gmdate('Y-m-d H:i:s',time()-1));
$expire=new ReflectionMethod(CreditExpiration::class,'expireSingleCredit');$expire->setAccessible(true);$expire->invoke(new CreditExpiration(),get_post($second['transaction']['id']));
ca_check('Expiration deducts only remaining grant and keeps newer credit',CreditManager::getCustomerCredit($expiry_user)===10.0&&(float)get_post_meta($second['transaction']['id'],'_expired_amount',true)===15.0);
for($index=0;$index<25;++$index){$expiry_api->adjust(1,$base+['operation_id'=>'many-'.$index]);}
ca_check('Expiring history has no 20-grant truncation',count(CreditExpiration::getExpiringCredits($expiry_user))===25&&CreditExpiration::getTotalExpiringCredit($expiry_user)===25.0);
echo wp_json_encode(['customer_id'=>$user_id,'subscription_id'=>$subscription_id,'order_id'=>$order->get_id(),'expiry_customer_id'=>$expiry_user],JSON_PRETTY_PRINT).PHP_EOL;
