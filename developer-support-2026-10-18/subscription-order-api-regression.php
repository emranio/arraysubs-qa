<?php
/** D6/D7/D8 real WP/WooCommerce regression. All fixtures are removed in finally. */
use ArraySubs\Developer\Subscriptions;
use ArraySubs\Developer\Orders;
use ArraySubs\Developer\SubscriptionSnapshot;
use ArraySubs\Features\RecurringBilling\Services\GracePolicy;
use ArraySubs\Features\RecurringBilling\Services\RenewalScheduler;
use ArraySubs\Supports\ActionScheduler;

global $so_results,$so_subs,$so_orders,$so_products,$so_users;
$so_results=[];$so_subs=[];$so_orders=[];$so_products=[];$so_users=[];
function so_check(string $label,bool $ok,$detail=null):void{
    global $so_results;
    $so_results[]=['name'=>$label,'passed'=>$ok]+($ok?[]:['detail'=>$detail]);
    echo ($ok?'PASS: ':'FAIL: ').$label.($ok?'':' '.wp_json_encode($detail)).PHP_EOL;
}
function so_error($result,?string $code=null):bool{return is_wp_error($result)&&($code===null||$code===$result->get_error_code());}
function so_user(string $suffix):int{
    global $so_users;
    $id=wp_insert_user(['user_login'=>'subscription-order-'.$suffix,'user_email'=>'subscription-order-'.$suffix.'@example.test','user_pass'=>wp_generate_password(30),'role'=>'customer']);
    if(is_wp_error($id)){throw new RuntimeException($id->get_error_message());}$so_users[]=$id;return $id;
}
function so_product(string $name,bool $subscription=true):int{
    global $so_products;
    $product=new WC_Product_Simple();$product->set_name($name);$product->set_status('publish');$product->set_regular_price('10');$product->set_virtual(true);$id=$product->save();
    $so_products[]=$id;if($subscription){update_post_meta($id,'_is_subscription','yes');update_post_meta($id,'_subscription_period','month');update_post_meta($id,'_subscription_interval',1);}return $id;
}
function so_sub(int $customer_id,int $product_id,array $changes=[]):int{
    global $so_subs;
    $status=$changes['status']??'arraysubs-active';unset($changes['status']);
    $meta=['_customer_id'=>$customer_id,'_customer_email'=>$customer_id?get_userdata($customer_id)->user_email:'','_product_id'=>$product_id,'_variation_id'=>0,'_quantity'=>1,'_currency'=>get_woocommerce_currency(),'_subscription_price'=>'10','_recurring_amount'=>'10','_signup_fee'=>0,'_billing_period'=>'month','_billing_interval'=>1,'_subscription_length'=>0,'_trial_length'=>0,'_trial_period'=>'day','_start_date'=>gmdate('Y-m-d H:i:s',time()-5*DAY_IN_SECONDS),'_next_payment_date'=>gmdate('Y-m-d H:i:s',time()+10*DAY_IN_SECONDS),'_completed_payments'=>0,'_payment_method'=>'bacs','_payment_method_title'=>'Bank transfer','_invoice_email'=>'','_different_renewal_price_after'=>1];
    $id=wp_insert_post(['post_type'=>'arraysubs_data','post_status'=>$status,'post_title'=>'Subscription/order developer regression','meta_input'=>array_merge($meta,$changes)],true);
    if(is_wp_error($id)){throw new RuntimeException($id->get_error_message());}$so_subs[]=$id;return $id;
}
function so_order(int $customer_id,array $meta=[],string $status='pending',?string $email=null):WC_Order{
    global $so_orders;
    $order=wc_create_order(['customer_id'=>$customer_id]);if(is_wp_error($order)){throw new RuntimeException($order->get_error_message());}
    $so_orders[]=$order->get_id();$order->set_billing_email($email??($customer_id?get_userdata($customer_id)->user_email:''));$order->set_currency(get_woocommerce_currency());$order->set_status($status);
    foreach($meta as $key=>$value){$order->update_meta_data($key,$value);}$order->save();return $order;
}
function so_foreign_lock(string $hook,int $id,callable $operation){
    $method=new ReflectionMethod(ActionScheduler::class,'getLockKey');$method->setAccessible(true);$key='_transient_'.$method->invoke(null,$hook,[$id]);
    if(!add_option($key,(time()+120).'|regression-other-worker','','no')){throw new RuntimeException('Cannot reserve isolated test lock.');}
    try{return $operation();}finally{delete_option($key);}
}
function so_ids($result):array{return is_array($result)&&isset($result['items'])?$result['items']:[];}

wp_set_current_user(1);
$so_pro_active=class_exists('ArraySubsPro',false);
try{
    $run=substr(wp_generate_uuid4(),0,8);$customer=so_user($run.'-a');$other=so_user($run.'-b');$product=so_product('Subscription API fixture '.$run);$plain_product=so_product('Non-subscription API fixture '.$run,false);
    $main=so_sub($customer,$product);$api=Subscriptions::fromId($main);
    so_check('Explicit subscription factories return canonical WP_Post resources',$api instanceof Subscriptions&&Subscriptions::fromSubscription(get_post($main))->object()->ID===$main&&so_error(Subscriptions::fromId(0)));
    $previous_global_post=$GLOBALS['post']??null;$GLOBALS['post']=get_post($main);
    so_check('Zero/negative IDs never resolve an ambient global subscription post',!arraysubs_get_subscription(0)&&!arraysubs_get_subscription(-$main)&&so_error(Subscriptions::fromId(0))&&so_error(Subscriptions::fromId(-$main)));
    $GLOBALS['post']=$previous_global_post;
    $before=get_post_meta($main);$queries=[];
    $trace=static function($sql)use(&$queries){$queries[]=$sql;return $sql;};add_filter('query',$trace);
    $identity=$api->data(['identity']);remove_filter('query',$trace);
    so_check('Identity projection is lazy and performs no metadata writes',array_keys($identity)===['identity']&&get_post_meta($main)===$before&&!array_filter($queries,fn($sql)=>stripos($sql,'arraysubs_credit')!==false||stripos($sql,'shop_order')!==false),$queries);
    $all=$api->data();
    so_check('Full snapshot exposes every documented field group without raw metadata',is_array($all)&&array_keys($all)===SubscriptionSnapshot::GROUPS&&!isset($all['_payment_method'])&&get_post_meta($main)===$before,$all);
    so_check('Unknown projection fields fail even on empty collections',so_error(Subscriptions::query(['include'=>[],'output'=>'data','fields'=>['unknown']]))&&so_error(Orders::query(['customer_id'=>999999,'output'=>'data','fields'=>['unknown']])));
    so_check('Invalid query selectors and non-string order fail predictably',so_error(Subscriptions::query(['customer_id'=>-1]))&&so_error(Subscriptions::query(['order'=>[]]))&&so_error(Orders::query(['order'=>[]]))&&so_error(Subscriptions::query(['per_page'=>101])));
    so_check('Explicit empty statuses and includes return empty pages',so_ids(Subscriptions::query(['statuses'=>[]]))===[]&&so_ids(Orders::query(['statuses'=>[]]))===[]&&so_ids(Subscriptions::query(['include'=>[]]))===[]);

    // Reverse lookup must not inherit the older helper's first-50 cap.
    $source=so_order($customer);$reverse=[];
    for($index=0;$index<61;++$index){$reverse[]=so_sub($customer,$product,['_parent_order_id'=>$source->get_id(),'status'=>$index===60?'arraysubs-cancelled':'arraysubs-active']);}
    $first=Subscriptions::fromOrderId($source->get_id(),['output'=>'ids','per_page'=>40]);$second=Subscriptions::fromOrderId($source->get_id(),['output'=>'ids','per_page'=>40,'page'=>2]);
    so_check('Reverse order lookup pages all 61 subscriptions with stable ordering',array_merge(so_ids($first),so_ids($second))===$reverse&&!empty($first['has_more'])&&empty($second['has_more']),[$first,$second]);
    so_check('Customer/order selectors are ANDed and include terminal history',so_ids(Subscriptions::query(['customer_id'=>$other,'order_id'=>$source->get_id(),'output'=>'ids']))===[]&&in_array(end($reverse),so_ids($second),true));
    $order_side=so_order($customer,['_arraysubs_subscription_id'=>$main]);
    $history=Orders::query(['subscription_id'=>$main,'output'=>'ids']);
    so_check('Order-side-only plan-switch link is visible through Woo CRUD history',in_array($order_side->get_id(),so_ids($history),true),$history);
    $history_only=so_order($customer);update_post_meta($main,'_order_ids',wp_json_encode([$history_only->get_id()]));
    so_check('Subscription-side-only history link is visible',in_array($history_only->get_id(),so_ids($api->orders(['output'=>'ids'])),true));
    $pending_switch_order=so_order($customer,['_arraysubs_subscription_id'=>$main]);update_post_meta($main,'_arraysubs_pending_plan_switch_order_id',$pending_switch_order->get_id());
    so_check('Pending immediate switch settlement is included separately from deferred switch',in_array($pending_switch_order->get_id(),so_ids($api->orders(['output'=>'ids'])),true));delete_post_meta($main,'_arraysubs_pending_plan_switch_order_id');
    $refs=so_sub($customer,$product,['_parent_order_id'=>$source->get_id(),'_original_order_id'=>$history_only->get_id(),'_arraysubs_pending_plan_switch_order_id'=>$pending_switch_order->get_id()]);
    $refs_invoice=so_order($customer,['_is_renewal_order'=>'yes','_subscription_id'=>$refs]);update_post_meta($refs,'_pending_renewal_order_id',$refs_invoice->get_id());
    $references=Subscriptions::fromId($refs)->data(['order_references'])['order_references'];
    so_check('Exact order reference groups verify initial, original and both pending links',$references===['initial_order_id'=>$source->get_id(),'original_order_id'=>$history_only->get_id(),'pending_renewal_order_id'=>$refs_invoice->get_id(),'pending_plan_switch_order_id'=>$pending_switch_order->get_id()],$references);
    $pending_switch_order->update_meta_data('_arraysubs_order_type','plan_switch');$pending_switch_order->save();
    so_check('Woo order identities distinguish renewal and immediate plan-switch orders',Orders::fromId($refs_invoice->get_id())->data(['identity'])['identity']['is_renewal']&&Orders::fromId($pending_switch_order->get_id())->data(['identity'])['identity']['is_plan_switch']);

    $checkout_switch=so_order($customer,['_arraysubs_order_type'=>'plan_switch_checkout']);
    so_check('Plan-switch checkout orders retain their explicit switch identity',Orders::fromId($checkout_switch->get_id())->data(['identity'])['identity']['is_plan_switch']);

    $foreign=so_order($other,['_arraysubs_subscription_id'=>$main], 'pending',get_userdata($customer)->user_email);
    update_post_meta($main,'_order_ids',wp_json_encode([$history_only->get_id(),$foreign->get_id()]));
    so_check('Forged cross-customer order links cannot bridge equal email',!in_array($foreign->get_id(),so_ids($api->orders(['output'=>'ids'])),true)&&so_ids(Subscriptions::fromOrderId($foreign->get_id(),['output'=>'ids']))===[]);
    $guest_email='guest-'.$run.'@example.test';$guest=so_sub(0,$product,['_customer_email'=>$guest_email]);$guest_order=so_order(0,['_subscription_id'=>$guest],'pending',strtoupper($guest_email));
    so_check('Guest order relationships use normalized explicit email identity',in_array($guest_order->get_id(),so_ids(Subscriptions::fromId($guest)->orders(['output'=>'ids'])),true));
    $unidentified=so_sub(0,$product);so_check('Unidentified guest history is rejected',so_error(Subscriptions::fromId($unidentified)->orders()));

    $parent=so_sub($customer,$product);$box=so_sub($customer,$product,['_arraysubs_box_parent_subscription'=>$parent]);$bundle=so_sub($customer,$product,['_arraysubs_bundle_parent_subscription'=>$parent]);
    update_post_meta($parent,'_arraysubs_box_child_subscriptions',wp_json_encode([$box]));update_post_meta($parent,'_arraysubs_bundle_child_subscriptions',wp_json_encode([$bundle]));
    $parent_order=so_order($customer,['_subscription_ids'=>[$parent]]);update_post_meta($parent,'_parent_order_id',$parent_order->get_id());
    so_check('Box and bundle family kinds resolve the verified billing owner',Subscriptions::fromChildId($box)->family()['billing_owner_id']===$parent&&Subscriptions::fromChildId($bundle)->family()['kind']==='bundle');
    so_check('Canonical child history inherits the billing owner order',in_array($parent_order->get_id(),so_ids(Subscriptions::fromId($box)->orders(['output'=>'ids'])),true)&&in_array($parent_order->get_id(),so_ids(Subscriptions::fromId($bundle)->orders(['output'=>'ids'])),true));
    $children=so_ids(Subscriptions::fromId($parent)->children(['output'=>'ids']));so_check('Parent children collection contains both entitlement kinds',in_array($box,$children,true)&&in_array($bundle,$children,true));
    $bad_child=so_sub($other,$product,['_arraysubs_box_parent_subscription'=>$parent,'_customer_email'=>get_userdata($customer)->user_email]);
    so_check('Cross-customer and stale family links are reported as orphaned',Subscriptions::fromId($bad_child)->family()['orphaned']===true&&Subscriptions::fromId($bad_child)->family()['billing_owner_id']===null);
    $child_api=Subscriptions::fromId($box);so_check('Child independent billing, date and status mutations fail',so_error($child_api->update(['quantity'=>2]))&&so_error($child_api->setNextPaymentDate(time()+20*DAY_IN_SECONDS))&&so_error($child_api->suspend()));

    update_post_meta($box,'_waiting_cancellation','yes');
    $undo_child=$child_api->undoCancellation();
    so_check('Child cancellation undo cannot create an independent billing lifecycle',so_error($undo_child)&&arraysubs_is_waiting_cancellation($box),$undo_child);
    delete_post_meta($box,'_waiting_cancellation');

    $edit=so_sub($customer,$product);$edit_api=Subscriptions::fromId($edit);$old=get_post_meta($edit);
    $invalid=$edit_api->update(['quantity'=>3,'recurring_amount'=>-1]);so_check('Whole-change validation prevents partial billing edits',so_error($invalid)&&get_post_meta($edit)===$old,$invalid);
    $invalid=$edit_api->update(['invoice_email'=>'changed@example.test','status'=>'invented-status']);so_check('Invalid lifecycle selection cannot partially change contact data',so_error($invalid)&&get_post_meta($edit)===$old,$invalid);
    so_check('Unknown, null, currency and raw-meta edits fail',so_error($edit_api->update(['_store_credit'=>10]))&&so_error($edit_api->update(['quantity'=>null]))&&so_error($edit_api->update(['currency'=>'EUR'])));
    $stale=$edit_api->update(['quantity'=>2],['quantity'=>99]);so_check('Expected-current-state guards return a conflict without writes',so_error($stale,'subscription_conflict')&&get_post_meta($edit)===$old,$stale);
    $valid=$edit_api->update(['quantity'=>2,'recurring_amount'=>'12.50','billing_address'=>['city'=>'Dhaka'],'invoice_email'=>'invoice@example.test'],['quantity'=>1]);
    so_check('Valid coherent update keeps agreed pricing separate from catalog',!is_wp_error($valid)&&(int)get_post_meta($edit,'_quantity',true)===2&&(float)get_post_meta($edit,'_recurring_amount',true)===12.5&&wc_get_product($product)->get_price()==='10',$valid);
    $failed_write=static function($check,$id,$key)use($edit){return $id===$edit&&$key==='_recurring_amount'?false:$check;};
    add_filter('update_post_metadata',$failed_write,10,3);$failed=$edit_api->update(['quantity'=>4,'recurring_amount'=>15]);remove_filter('update_post_metadata',$failed_write,10);
    so_check('Persistence failure returns error and rolls back all requested fields',so_error($failed)&&(int)get_post_meta($edit,'_quantity',true)===2&&(float)get_post_meta($edit,'_recurring_amount',true)===12.5,$failed);
    $pending=so_sub($customer,$product,['_pending_switch'=>['target_product_id'=>$product,'switch_type'=>'crossgrade']]);$pending_api=Subscriptions::fromId($pending);
    so_check('Pending deferred switch blocks billing and status edits',so_error($pending_api->update(['quantity'=>2]))&&so_error($pending_api->suspend())&&get_post_status($pending)==='arraysubs-active');
    $unpaid=so_order($customer,['_is_renewal_order'=>'yes','_subscription_id'=>$edit]);update_post_meta($edit,'_pending_renewal_order_id',$unpaid->get_id());
    so_check('Outstanding invoice freezes billing terms and renewal-date edits',so_error($edit_api->update(['quantity'=>3]))&&so_error($edit_api->setNextPaymentDate(time()+30*DAY_IN_SECONDS)));delete_post_meta($edit,'_pending_renewal_order_id');$unpaid->set_status('cancelled');$unpaid->save();
    $remote=so_sub($customer,$product);$remote_api=Subscriptions::fromId($remote);
    $ownership=static function($owns,$slug,$id)use($remote){return $id===$remote?true:$owns;};add_filter('arraysubs_gateway_owns_billing_clock',$ownership,100,3);
    $remote_status=$remote_api->suspend();
    $remote_price=$remote_api->update(['recurring_amount'=>99]);$remote_date=$remote_api->setNextPaymentDate(time()+30*DAY_IN_SECONDS);remove_filter('arraysubs_gateway_owns_billing_clock',$ownership,100);
    so_check('Provider-owned billing terms and unsupported date propagation fail closed',so_error($remote_price)&&so_error($remote_date)&&(float)get_post_meta($remote,'_recurring_amount',true)===10.0,[$remote_price,$remote_date]);

    so_check('Provider-owned raw status changes remain blocked',so_error($remote_status)&&get_post_status($remote)==='arraysubs-active',$remote_status);
    $date=so_sub($customer,$product);$date_api=Subscriptions::fromId($date);$old_timestamp=GracePolicy::timestamp(get_post_meta($date,'_next_payment_date',true));$target=time()+22*DAY_IN_SECONDS;
    $events=0;$date_event=static function($subscription)use($date,&$events){if($subscription instanceof WP_Post&&$subscription->ID===$date){++$events;}};add_action('arraysubs_next_payment_date_updated',$date_event,100);
    $changed=$date_api->setNextPaymentDate($target,$old_timestamp);$charge=ActionScheduler::getNextScheduledTime(ActionScheduler::HOOK_PROCESS_RENEWAL,[$date],ActionScheduler::GROUP_RENEWALS);$invoice=ActionScheduler::getNextScheduledTime(ActionScheduler::HOOK_GENERATE_RENEWAL_INVOICE,[$date],ActionScheduler::GROUP_BILLING);
    so_check('UTC renewal-date update aligns invoice and payment scheduler hooks',!is_wp_error($changed)&&GracePolicy::timestamp(get_post_meta($date,'_next_payment_date',true))===$target&&$charge===$target+arraysubs_get_renewal_spread_offset($date)&&is_int($invoice)&&$invoice<=$charge&&$events===1,[$changed,$charge,$invoice]);
    $date_api->setNextPaymentDate($target,$target);so_check('Identical date update has no duplicate event and stale expected date conflicts',$events===1&&so_error($date_api->setNextPaymentDate($target+DAY_IN_SECONDS,$old_timestamp)));remove_action('arraysubs_next_payment_date_updated',$date_event,100);
    RenewalScheduler::unschedule($date);
    $same_date_repair=$date_api->setNextPaymentDate($target,$target);
    so_check('Same-date replay repairs missing payment and invoice actions',!is_wp_error($same_date_repair)&&ActionScheduler::isScheduled(ActionScheduler::HOOK_PROCESS_RENEWAL,[$date],ActionScheduler::GROUP_RENEWALS)&&ActionScheduler::isScheduled(ActionScheduler::HOOK_GENERATE_RENEWAL_INVOICE,[$date],ActionScheduler::GROUP_BILLING),$same_date_repair);
    $locked=so_foreign_lock(ActionScheduler::HOOK_SUBSCRIPTION_MUTATION,$date,fn()=>$date_api->setNextPaymentDate($target+DAY_IN_SECONDS));so_check('Subscription lifecycle respects an independent worker lock',so_error($locked,'subscription_conflict'));
    so_check('Past dates and active-subscription trial edits are rejected',so_error($date_api->setNextPaymentDate(time()-1))&&so_error($date_api->setTrialEnd(time()+DAY_IN_SECONDS))&&so_error($date_api->endTrial()));

    $trial=so_sub($customer,$product,['status'=>'arraysubs-trial','_trial_length'=>10,'_trial_period'=>'day']);$trial_api=Subscriptions::fromId($trial);$trial_target=time()+14*DAY_IN_SECONDS;$trial_events=0;
    $trial_event=static function($subscription)use($trial,&$trial_events){if($subscription instanceof WP_Post&&$subscription->ID===$trial){++$trial_events;}};add_action('arraysubs_trial_terms_updated',$trial_event,100);
    $trial_change=$trial_api->setTrialEnd($trial_target);so_check('Running trial end update exposes canonical UTC end and emits one event',!is_wp_error($trial_change)&&$trial_api->data(['trial'])['trial']['ends_at']===$trial_target&&$trial_events===1,$trial_change);remove_action('arraysubs_trial_terms_updated',$trial_event,100);
    $provider_trial=so_sub($customer,$product,['status'=>'arraysubs-trial','_trial_length'=>10]);$owner_trial=static function($owns,$slug,$id)use($provider_trial){return $id===$provider_trial?true:$owns;};add_filter('arraysubs_gateway_owns_billing_clock',$owner_trial,100,3);$end_remote=Subscriptions::fromId($provider_trial)->endTrial();remove_filter('arraysubs_gateway_owns_billing_clock',$owner_trial,100);
    so_check('Provider-owned trial cannot be converted locally',so_error($end_remote)&&get_post_status($provider_trial)==='arraysubs-trial',$end_remote);
    so_check('Running trial duration edits require the explicit end-date method',so_error($trial_api->update(['trial_length'=>20]))&&so_error($trial_api->update(['trial_period'=>'month'])));
    $ended=$trial_api->endTrial();so_check('Local trial conversion uses the domain flow and rebuilds billing jobs',!is_wp_error($ended)&&get_post_status($trial)==='arraysubs-active'&&(int)get_post_meta($trial,'_trial_length',true)===0&&RenewalScheduler::isScheduled($trial),$ended);

    $activate_trial=so_sub($customer,$product,['status'=>'arraysubs-trial','_trial_length'=>7]);$activate_result=Subscriptions::fromId($activate_trial)->activate();
    so_check('Generic trial activation uses the trial converter and rebuilds both billing jobs',!is_wp_error($activate_result)&&get_post_status($activate_trial)==='arraysubs-active'&&(int)get_post_meta($activate_trial,'_trial_length',true)===0&&ActionScheduler::isScheduled(ActionScheduler::HOOK_PROCESS_RENEWAL,[$activate_trial],ActionScheduler::GROUP_RENEWALS)&&ActionScheduler::isScheduled(ActionScheduler::HOOK_GENERATE_RENEWAL_INVOICE,[$activate_trial],ActionScheduler::GROUP_BILLING),$activate_result);

    $grace=so_sub($customer,$product);$grace_api=Subscriptions::fromId($grace);$grace_events=0;$grace_event=static function($subscription)use($grace,&$grace_events){if($subscription instanceof WP_Post&&$subscription->ID===$grace){++$grace_events;}};add_action('arraysubs_grace_terms_updated',$grace_event,100);
    $grace_result=$grace_api->setGracePolicy(['before_on_hold'=>2,'before_cancel'=>4]);$policy=GracePolicy::settings($grace);
    so_check('Per-subscription grace overrides are explicit and emit one event',!is_wp_error($grace_result)&&$policy['before_on_hold']===2&&$policy['before_cancel']===4&&$policy['before_on_hold_source']==='subscription'&&$grace_events===1,$grace_result);
    $stale_grace=$grace_api->setGracePolicy(['before_cancel'=>8],['before_cancel'=>3]);so_check('Grace bounds and expected policy are validated before writes',so_error($grace_api->setGracePolicy(['before_on_hold'=>-1]))&&so_error($grace_api->setGracePolicy(['before_cancel'=>3651]))&&so_error($stale_grace)&&GracePolicy::settings($grace)['before_cancel']===4);
    $fail_grace=static function($check,$id,$key)use($grace){return $id===$grace&&$key==='_grace_days_before_cancel'?false:$check;};
    add_filter('update_post_metadata',$fail_grace,10,3);$failed_grace=$grace_api->setGracePolicy(['before_on_hold'=>8,'before_cancel'=>9]);remove_filter('update_post_metadata',$fail_grace,10);
    so_check('A partial grace persistence failure restores both prior overrides',so_error($failed_grace)&&GracePolicy::settings($grace)['before_on_hold']===2&&GracePolicy::settings($grace)['before_cancel']===4,$failed_grace);
    $grace_api->setGracePolicy(['before_on_hold'=>null,'before_cancel'=>null]);so_check('Null grace overrides restore the site policy without stale markers',GracePolicy::settings($grace)['before_on_hold_source']==='site'&&!metadata_exists('post',$grace,'_grace_days_before_cancel'));remove_action('arraysubs_grace_terms_updated',$grace_event,100);
    $due=time()-7*DAY_IN_SECONDS;$grace_invoice=so_order($customer,['_is_renewal_order'=>'yes','_subscription_id'=>$grace]);update_post_meta($grace,'_next_payment_date',gmdate('Y-m-d H:i:s',$due));update_post_meta($grace,'_pending_renewal_order_id',$grace_invoice->get_id());update_post_meta($grace,'_grace_days_before_on_hold',1);update_post_meta($grace,'_grace_days_before_cancel',2);
    $raw=get_post_meta($grace);$recovery=$grace_api->data(['recovery'])['recovery'];so_check('Grace snapshot computes due state from an actual unpaid renewal and remains pure',$recovery['unpaid_renewal_order_id']===$grace_invoice->get_id()&&$recovery['can_enter_on_hold']&&$raw===get_post_meta($grace),$recovery);
    $settlement=static function($waiting,$id)use($grace){return $id===$grace?true:$waiting;};add_filter('arraysubs_is_awaiting_gateway_settlement',$settlement,100,2);$settling=$grace_api->data(['recovery'])['recovery'];remove_filter('arraysubs_is_awaiting_gateway_settlement',$settlement,100);
    so_check('Gateway settlement blocks computed overdue transitions',$settling['awaiting_gateway_settlement']&&!$settling['can_enter_on_hold']&&!$settling['can_cancel_overdue']);
    wp_update_post(['ID'=>$grace,'post_status'=>'arraysubs-on-hold']);delete_post_meta($grace,'_on_hold_date');$hold=$grace_api->data(['recovery'])['recovery'];so_check('Immediate on-hold paths without an on-hold timestamp remain readable',$hold['on_hold_at']===null&&$hold['can_cancel_overdue'],$hold);
    delete_post_meta($grace,'_pending_renewal_order_id');$foreign_renewal=so_order($other,['_is_renewal_order'=>'yes','_subscription_id'=>$grace]);$found_unpaid=GracePolicy::unpaidOrder($grace);
    so_check('A newer forged renewal cannot hide the older owned unpaid invoice',$found_unpaid instanceof WC_Order&&$found_unpaid->get_id()===$grace_invoice->get_id(),$found_unpaid?$found_unpaid->get_id():null);

    $switch_source=so_sub($customer,$product);$switch_target=so_product('Pending plan target '.$run);$pending_api_view=Subscriptions::fromId($switch_source);
    $stored_pending=arraysubs_set_pending_switch($switch_source,['target_product_id'=>$switch_target,'switch_type'=>'crossgrade','quantity'=>1,'new_price'=>12,'switch_fee'=>2,'new_billing_period'=>'month','new_billing_interval'=>1]);
    $pending_snapshot=arraysubs_get_pending_switch($switch_source);$switch_invoice=so_order($customer,['_is_renewal_order'=>'yes','_subscription_id'=>$switch_source,'_arraysubs_pending_switch_id'=>$pending_snapshot['pending_switch_id']??'']);update_post_meta($switch_source,'_pending_renewal_order_id',$switch_invoice->get_id());
    $pending_view=$pending_api_view->data(['plan','pending_switch']);
    so_check('Pending-plan projection retains current plan and omits signed private source data',$stored_pending&&$pending_view['plan']['product_id']===$product&&$pending_view['pending_switch']['target_product_id']===$switch_target&&!isset($pending_view['pending_switch']['raw'])&&!isset($pending_view['pending_switch']['signature'])&&$pending_view['pending_switch']['frozen_by_order_id']===$switch_invoice->get_id(),$pending_view);
    $switch_invoice->update_meta_data('_arraysubs_pending_switch_id','another-pending-switch');$switch_invoice->save();$unrelated_freeze=$pending_api_view->data(['pending_switch']);
    so_check('Unrelated pending-switch invoice metadata cannot freeze the current signed target',$unrelated_freeze['pending_switch']['frozen_by_order_id']===null,$unrelated_freeze);
    // Establish paid status before attaching subscription links, leaving a real paid/unprocessed recovery fixture.
    $paid_switch=so_order($customer,[],'processing');$paid_switch->update_meta_data('_is_renewal_order','yes');$paid_switch->update_meta_data('_subscription_id',$switch_source);$paid_switch->update_meta_data('_arraysubs_pending_switch_id',$pending_snapshot['pending_switch_id']);$paid_switch->update_meta_data('_pending_switch_payload',$pending_snapshot);$paid_switch->save();update_post_meta($switch_source,'_pending_renewal_order_id',$paid_switch->get_id());
    $paid_pending=$pending_api_view->data(['pending_switch'])['pending_switch'];
    so_check('Paid unprocessed current renewal retains its pending-switch finalization freeze',$paid_switch->is_paid()&&$paid_switch->get_meta('_subscription_payment_processed',true)!=='yes'&&$paid_pending['awaiting_paid_finalization']===true&&$paid_pending['frozen_by_order_id']===$paid_switch->get_id(),$paid_pending);
    $paid_switch->update_meta_data('_arraysubs_pending_switch_id','unrelated-paid-switch');$paid_switch->save();$wrong_paid=$pending_api_view->data(['pending_switch'])['pending_switch'];
    so_check('Mismatched paid invoice cannot claim pending-switch finalization or freeze',$wrong_paid['awaiting_paid_finalization']===false&&$wrong_paid['frozen_by_order_id']===null,$wrong_paid);
    $foreign_paid=so_order($other,[],'processing');$foreign_paid->update_meta_data('_is_renewal_order','yes');$foreign_paid->update_meta_data('_subscription_id',$switch_source);$foreign_paid->update_meta_data('_arraysubs_pending_switch_id',$pending_snapshot['pending_switch_id']);$foreign_paid->update_meta_data('_pending_switch_payload',$pending_snapshot);$foreign_paid->save();update_post_meta($switch_source,'_pending_renewal_order_id',$foreign_paid->get_id());$foreign_paid_view=$pending_api_view->data(['pending_switch'])['pending_switch'];
    so_check('Foreign paid invoice cannot claim pending-switch finalization or freeze',$foreign_paid_view['awaiting_paid_finalization']===false&&$foreign_paid_view['frozen_by_order_id']===null,$foreign_paid_view);
    $paid_switch->update_meta_data('_arraysubs_pending_switch_id',$pending_snapshot['pending_switch_id']);$paid_switch->update_meta_data('_subscription_payment_processed','yes');$paid_switch->save();update_post_meta($switch_source,'_pending_renewal_order_id',$paid_switch->get_id());$processed_paid=$pending_api_view->data(['pending_switch'])['pending_switch'];
    so_check('Processed paid renewal no longer claims pending-switch finalization freeze',$processed_paid['awaiting_paid_finalization']===false&&$processed_paid['frozen_by_order_id']===null,$processed_paid);


    $cancel=so_sub($customer,$product);$cancel_api=Subscriptions::fromId($cancel);$scheduled=$cancel_api->cancel('end_of_period','Regression scheduling');so_check('Scheduled cancellation is distinct from cancellation and can be undone',!is_wp_error($scheduled)&&get_post_status($cancel)==='arraysubs-active'&&arraysubs_is_waiting_cancellation($cancel)&&!is_wp_error($cancel_api->undoCancellation())&&!arraysubs_is_waiting_cancellation($cancel),$scheduled);
    RenewalScheduler::schedule($cancel,time()+DAY_IN_SECONDS);$cancelled=$cancel_api->cancel('immediate','Regression cancellation');so_check('Immediate cancellation clears both renewal scheduler hooks',!is_wp_error($cancelled)&&get_post_status($cancel)==='arraysubs-cancelled'&&!ActionScheduler::isScheduled(ActionScheduler::HOOK_PROCESS_RENEWAL,[$cancel],ActionScheduler::GROUP_RENEWALS)&&!ActionScheduler::isScheduled(ActionScheduler::HOOK_GENERATE_RENEWAL_INVOICE,[$cancel],ActionScheduler::GROUP_BILLING),$cancelled);
    so_check('Starting a trial by a raw active status assignment is unsupported',so_error($date_api->changeStatus('arraysubs-trial'))&&get_post_status($date)==='arraysubs-active');
    $status=so_sub($customer,$product);$status_api=Subscriptions::fromId($status);RenewalScheduler::schedule($status,time()+DAY_IN_SECONDS);$suspended=$status_api->suspend();$resumed=$status_api->resume();$expired=$status_api->expire();so_check('Suspend/resume/expire run real lifecycle transitions with no terminal renewal jobs',!is_wp_error($suspended)&&!is_wp_error($resumed)&&!is_wp_error($expired)&&get_post_status($status)==='arraysubs-expired'&&!RenewalScheduler::isScheduled($status),[$suspended,$resumed,$expired]);

    $included_ids=[$source->get_id(),$order_side->get_id()];sort($included_ids);
    $included=Orders::query(['customer_id'=>$customer,'include'=>$included_ids,'output'=>'ids','per_page'=>100]);
    so_check('Order include filtering is bounded, deterministic and ANDed with customer identity',so_ids($included)===$included_ids&&so_ids(Orders::query(['customer_id'=>$other,'include'=>$included_ids,'output'=>'ids']))===[],$included);
    so_check('Empty include never selects all orders and invalid projections still fail',so_ids(Orders::query(['include'=>[],'output'=>'ids']))===[]&&so_error(Orders::query(['include'=>[],'output'=>'data','fields'=>['unknown']]))&&so_error(Orders::query(['include'=>[-1]]))&&so_error(Orders::query(['include'=>range(1,101)])));

    $edit_order=so_order($customer);$order_api=Orders::fromId($edit_order->get_id());$order_before=$order_api->data();$contact=$order_api->update(['billing'=>['city'=>'Dhaka','email'=>'qa-order@example.test'],'customer_note'=>'Regression contact update']);
    so_check('Order CRUD edits allow contact details while retaining financial facts',!is_wp_error($contact)&&$order_api->data(['billing'])['billing']['city']==='Dhaka'&&$order_api->data(['amounts'])['amounts']===$order_before['amounts'],$contact);
    so_check('Order financial/status mutation and stale contact updates are rejected',so_error($order_api->update(['total'=>1]))&&so_error($order_api->update(['status'=>'completed']))&&so_error($order_api->update(['customer_note'=>'bad'],['customer_note'=>'stale'])));
    $lock_update=so_foreign_lock(ActionScheduler::HOOK_DEVELOPER_ORDER_MUTATION,$edit_order->get_id(),fn()=>$order_api->update(['customer_note'=>'blocked']));so_check('Order contact mutation respects an independent worker lock',so_error($lock_update,'conflict'));
    $credit_order=so_order($customer,['_arraysubs_credit_plan'=>['key'=>'qa-recovery']]);so_check('Order deletion retains pending credit recovery evidence',so_error(Orders::fromId($credit_order->get_id())->delete())&&wc_get_order($credit_order->get_id()) instanceof WC_Order);
    $applied_order=so_order($customer,['_arraysubs_credit_applied'=>2,'_arraysubs_credit_from_customer'=>2]);
    $applied_delete=Orders::fromId($applied_order->get_id())->delete();
    so_check('Stored applied credit remains financial evidence without a split-plan marker',so_error($applied_delete)&&wc_get_order($applied_order->get_id()) instanceof WC_Order,['order_id'=>$applied_order->get_id(),'result'=>$applied_delete]);
    $signed_settlement=so_order($customer,['_arraysubs_switch_contract'=>['version'=>5,'contract_key'=>'regression-retained-contract']]);$credit_only_settlement=so_order($customer,['_arraysubs_switch_settlement_mode'=>'credit_only']);
    so_check('Signed settlement or credit-only settlement evidence is retained without a paid date',!$signed_settlement->get_date_paid()&&!$credit_only_settlement->get_date_paid()&&so_error(Orders::fromId($signed_settlement->get_id())->delete())&&so_error(Orders::fromId($credit_only_settlement->get_id())->delete())&&wc_get_order($signed_settlement->get_id()) instanceof WC_Order&&wc_get_order($credit_only_settlement->get_id()) instanceof WC_Order);
    $transaction_order=so_order($customer);$transaction_order->set_transaction_id('qa-failed-payment-proof');$transaction_order->save();
    $transaction_delete=Orders::fromId($transaction_order->get_id())->delete();
    so_check('A gateway transaction reference prevents generic order deletion',so_error($transaction_delete)&&wc_get_order($transaction_order->get_id()) instanceof WC_Order,['order_id'=>$transaction_order->get_id(),'result'=>$transaction_delete]);
    $dated=so_order($customer);$dated->set_date_paid(time());$dated->save();so_check('Failed/pending orders with a paid date remain retained',so_error(Orders::fromId($dated->get_id())->delete())&&wc_get_order($dated->get_id()) instanceof WC_Order);
    so_check('Referenced subscription orders remain retained',so_error(Orders::fromId($source->get_id())->delete()));
    $disposable=so_order($customer);$delete_lock=so_foreign_lock(ActionScheduler::HOOK_GATEWAY_ORDER_PAYMENT,$disposable->get_id(),fn()=>Orders::fromId($disposable->get_id())->delete());so_check('Order deletion respects an independent gateway payment lock',so_error($delete_lock,'conflict')&&wc_get_order($disposable->get_id()) instanceof WC_Order);
    $deleted=Orders::fromId($disposable->get_id())->delete();so_check('Only an unreferenced unpaid order can be permanently deleted',is_array($deleted)&&!empty($deleted['deleted'])&&!wc_get_order($disposable->get_id()),$deleted);
    $family_deleted=Subscriptions::fromId($parent)->delete();so_check('Public permanent subscription delete cascades child entitlements but retains order/customer',!is_wp_error($family_deleted)&&!get_post($parent)&&!get_post($box)&&($so_pro_active?!get_post($bundle):(get_post($bundle) instanceof WP_Post&&Subscriptions::fromId($bundle)->family()['orphaned']))&&wc_get_order($parent_order->get_id()) instanceof WC_Order&&get_userdata($customer) instanceof WP_User,$family_deleted);
    so_check('Deletion reports child effects while explicitly retaining orders and customer',is_array($family_deleted)&&$family_deleted['direct_children_deleted']===($so_pro_active?2:1)&&$family_deleted['direct_children_retained']===($so_pro_active?0:1)&&$family_deleted['orders_deleted']===false&&$family_deleted['customer_deleted']===false,$family_deleted);
    echo 'Pro active: '.(int)$so_pro_active.PHP_EOL;
    echo 'HPOS enabled: '.(int)\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled().PHP_EOL;
}catch(Throwable $error){so_check('Regression script completes without an exception',false,['message'=>$error->getMessage(),'file'=>$error->getFile(),'line'=>$error->getLine()]);}
finally{
    remove_filter('arraysubs_gateway_owns_billing_clock',$ownership??'__return_false',100);remove_filter('arraysubs_gateway_owns_billing_clock',$owner_trial??'__return_false',100);remove_filter('arraysubs_is_awaiting_gateway_settlement',$settlement??'__return_false',100);
    foreach(array_reverse($so_subs) as $id){if(get_post($id)){ActionScheduler::unscheduleAllForSubscription($id);arraysubs_delete_subscription($id,true);}}
    foreach(array_reverse($so_orders) as $id){$order=wc_get_order($id);if($order){foreach($order->get_refunds() as $refund){$refund->delete(true);}$order->delete(true);}}
    foreach(array_reverse($so_products) as $id){$product=wc_get_product($id);if($product){$product->delete(true);}}
    require_once ABSPATH.'wp-admin/includes/user.php';foreach($so_users as $id){wp_delete_user($id);}
}
$failed=array_values(array_filter($so_results,fn($result)=>!$result['passed']));
echo wp_json_encode(['checks'=>count($so_results),'passed'=>count($so_results)-count($failed),'failed'=>$failed,'fixtures_cleaned'=>!array_filter($so_subs,fn($id)=>(bool)get_post($id))&&!array_filter($so_orders,fn($id)=>(bool)wc_get_order($id))&&!array_filter($so_products,fn($id)=>(bool)get_post($id))&&!array_filter($so_users,fn($id)=>(bool)get_userdata($id))],JSON_PRETTY_PRINT).PHP_EOL;
if($failed){WP_CLI::error(count($failed).' subscription/order API regression checks failed.');}
