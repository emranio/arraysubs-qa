<?php
/** Refund failure/rollback and paid purchase replay against WooCommerce. */
use ArraySubsPro\Features\StoreCredit\Services\CreditManager;
use ArraySubsPro\Features\StoreCredit\Services\CreditProductRuntime;
use ArraySubsPro\Features\StoreCredit\Services\CreditLedger;
function cr_check(string $label,bool $ok,$detail=null):void{if(!$ok){throw new RuntimeException('FAIL: '.$label.' '.wp_json_encode($detail));}echo 'PASS: '.$label.PHP_EOL;}
wp_set_current_user(1);
$key=substr(wp_generate_uuid4(),0,8);
$user_id=wp_insert_user(['user_login'=>'cr-'.$key,'user_email'=>'cr-'.$key.'@example.test','user_pass'=>wp_generate_password(),'role'=>'customer']);
$order=wc_create_order(['customer_id'=>$user_id]);$fee=new WC_Order_Item_Fee();$fee->set_name('QA refundable purchase');$fee->set_amount(30);$fee->set_total(30);$order->add_item($fee);$order->calculate_totals();$order->set_date_paid(time());$order->set_status('completed');$order->save();
$force_refund_error=static function(){throw new Exception('Injected refund persistence failure');};
$block_rollback=static function($check,$id,$meta_key,$value)use($user_id){return $id===$user_id&&$meta_key===CreditManager::USER_CREDIT_META&&(float)$value===0.0?false:$check;};
add_action('woocommerce_create_refund',$force_refund_error,20);
add_filter('update_user_metadata',$block_rollback,10,4);
$result=CreditManager::processRefundAsCredit($order->get_id(),10,'QA rollback regression');
remove_action('woocommerce_create_refund',$force_refund_error,20);remove_filter('update_user_metadata',$block_rollback,10);
cr_check('Failed refund rollback keeps a recoverable pending operation',!$result['success']&&CreditManager::getCustomerCredit($user_id)===10.0&&!empty(wc_get_order($order->get_id())->get_meta('_arraysubs_credit_refund_pending')['rollback']),$result);
$recover=CreditManager::processRefundAsCredit($order->get_id(),10,'QA rollback regression');
cr_check('Retry completes rollback before another refund can grant credit',!$recover['success']&&CreditManager::getCustomerCredit($user_id)===0.0&&!wc_get_order($order->get_id())->get_meta('_arraysubs_credit_refund_pending'),$recover);
$success=CreditManager::processRefundAsCredit($order->get_id(),10,'QA successful refund');
cr_check('Refund and ledger grant share persisted proof',!empty($success['success'])&&CreditManager::getCustomerCredit($user_id)===10.0&&wc_get_order($success['refund_id'])->get_meta('_arraysubs_refund_as_credit')==='yes'&&absint(wc_get_order($success['refund_id'])->get_meta('_arraysubs_credit_log_id'))===$success['log_id'],$success);
cr_check('Refund reduces remaining refundable capacity exactly once',CreditManager::getRefundableAmountForOrder(wc_get_order($order->get_id()))===20.0);

// A paid credit purchase can recover from a failed credit write and a missing flag.
$purchase=wc_create_order(['customer_id'=>$user_id]);$item=new WC_Order_Item_Product();$item->set_name('QA credit purchase');$item->set_quantity(1);$item->set_subtotal(6);$item->set_total(6);$item->add_meta_data('_arraysubs_is_credit_product','yes');$item->add_meta_data('_arraysubs_credit_amount',6);$purchase->add_item($item);$purchase->calculate_totals();$purchase->save();
$block_credit=static function($check,$id,$meta_key)use($user_id){return $id===$user_id&&$meta_key===CreditManager::USER_CREDIT_META?false:$check;};
add_filter('update_user_metadata',$block_credit,10,3);
$purchase->set_date_paid(time());$purchase->set_status('completed');$purchase->save();
remove_filter('update_user_metadata',$block_credit,10);
cr_check('Unfulfilled paid purchase is not marked processed',wc_get_order($purchase->get_id())->get_meta(CreditProductRuntime::ORDER_PROCESSED_META)!=='yes'&&CreditManager::getCustomerCredit($user_id)===10.0);
$runtime=new CreditProductRuntime();$runtime->processCreditPurchase($purchase->get_id());
cr_check('Paid purchase retry grants once',CreditManager::getCustomerCredit($user_id)===16.0&&wc_get_order($purchase->get_id())->get_meta(CreditProductRuntime::ORDER_PROCESSED_META)==='yes');
$runtime->processCreditPurchase($purchase->get_id());
$purchase=wc_get_order($purchase->get_id());$purchase->delete_meta_data(CreditProductRuntime::ORDER_PROCESSED_META);$purchase->save();$runtime->processCreditPurchase($purchase->get_id());
cr_check('Durable purchase key survives missing processed flag',CreditManager::getCustomerCredit($user_id)===16.0);
echo wp_json_encode(['customer_id'=>$user_id,'refund_order_id'=>$order->get_id(),'credit_purchase_order_id'=>$purchase->get_id()],JSON_PRETTY_PRINT).PHP_EOL;
