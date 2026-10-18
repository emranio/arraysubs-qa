<?php
/** One-time teardown of this run's exact, disposable fixture identities. */
if (!defined('WP_CLI') || !WP_CLI) { throw new RuntimeException('WP-CLI only.'); }
require_once ABSPATH . 'wp-admin/includes/user.php';
$users = [248=>'sc-77048ee5',249=>'ps-da7ae32d',250=>'ps-5b8fe183',251=>'sc-6a050d5d',252=>'ps-55c3eeb4',260=>'sc-0ead3a3a',261=>'ps-85a456af',263=>'ca-3b7f6764',264=>'ce-3b7f6764',265=>'cc-34b71b5c',270=>'cr-91de2e41',275=>'ca-4a050167',276=>'ce-4a050167',277=>'cr-39430e6c',279=>'ca-d9db9cf9',280=>'ce-d9db9cf9',283=>'ca-9cb10d24',284=>'ce-9cb10d24'];
foreach ($users as $id=>$login) {
    $user=get_user_by('ID',$id);
    if ($user && $user->user_login!==$login) { throw new RuntimeException('Fixture identity mismatch: '.$id); }
}
$removed=[];$guards=[];
// The product's append-only guards stay enabled. Only this CLI teardown
// temporarily detaches those exact callbacks to remove the named QA ledger rows.
foreach (['pre_delete_post','pre_trash_post'] as $hook) {
    foreach (($GLOBALS['wp_filter'][$hook]->callbacks ?? []) as $priority=>$callbacks) {
        foreach ($callbacks as $callback) {
            $function=$callback['function'];
            if (is_array($function)&&$function[0] instanceof \ArraySubsPro\Features\StoreCredit\Services\CreditLedger&&$function[1]==='protectLedgerEntry') {
                $guards[]=[$hook,$function,$priority,$callback['accepted_args']];
                remove_filter($hook,$function,$priority);
            }
        }
    }
}
try {
    foreach ($users as $customer_id=>$login) {
        $rows=get_posts(['post_type'=>'arraysubs_credit','post_status'=>['publish','draft','pending','private','future','trash'],'posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_customer_id','meta_value'=>$customer_id]);
        foreach($rows as $id){if(!wp_delete_post($id,true)){throw new RuntimeException('Cannot delete QA row '.$id);} $removed['ledger'][]=$id;}
        $orders=wc_get_orders(['customer_id'=>$customer_id,'limit'=>-1]);
        foreach($orders as $order){foreach($order->get_refunds() as $refund){$removed['refunds'][]=$refund->get_id();$refund->delete(true);}$removed['orders'][]=$order->get_id();$order->delete(true);}
        $subscriptions=get_posts(['post_type'=>'arraysubs_data','post_status'=>array_keys(arraysubs_get_subscription_statuses()),'posts_per_page'=>-1,'fields'=>'ids','meta_key'=>'_customer_id','meta_value'=>$customer_id]);
        foreach($subscriptions as $id){arraysubs_delete_subscription($id,true);$removed['subscriptions'][]=$id;}
        if(get_user_by('ID',$customer_id)){wp_delete_user($customer_id);$removed['customers'][]=$customer_id;}
    }
    foreach([12382,12384,12393,12395,12423,12425,12532,12534] as $id){
        $product=wc_get_product($id);
        if($product){if(strpos($product->get_name(),'Developer support plan ')!==0){throw new RuntimeException('Fixture product mismatch');}$product->delete(true);$removed['products'][]=$id;}
    }
} finally {
    foreach($guards as [$hook,$callback,$priority,$accepted]){add_filter($hook,$callback,$priority,$accepted);}
}
echo wp_json_encode($removed,JSON_PRETTY_PRINT).PHP_EOL;
