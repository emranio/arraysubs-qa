<?php
// Run with WP-CLI eval-file on the local QA site, with --allow-root.
$cases = [
6387 => ['_subscription_period'=>'month','_subscription_interval'=>'3','_subscription_length'=>'0','_trial_length'=>'7','_arraysubs_subscription_mode'=>'fixed','_arraysubs_fixed_end_date_type'=>'recurring_annual','_arraysubs_fixed_end_date'=>'02-29','_arraysubs_enrollment_window_start'=>'2026-09-10','_arraysubs_enrollment_window_end'=>'2026-12-31','_arraysubs_fixed_end_renewal'=>'renew','_arraysubs_initial_shipping_override'=>'5.50','_arraysubs_renewal_shipping_override'=>'2.25'],
6390 => ['_subscription_period'=>'week','_subscription_interval'=>'1','_subscription_length'=>'24','_arraysubs_subscription_mode'=>'full_flexible','_arraysubs_flexible_periods'=>['week','year'],'_arraysubs_shipping_type'=>'one-time','_arraysubs_initial_shipping_override'=>'6.75','_arraysubs_renewal_shipping_override'=>'','_arraysubs_flex_sync_enabled'=>'yes'],
6392 => ['_subscription_interval'=>'2','_subscription_length'=>'6','_signup_fee'=>'4.50','_arraysubs_subscription_mode'=>'flexible_length','_renewal_price'=>'25.00','_renewal_price_after'=>'2','_arraysubs_initial_shipping_override'=>'','_arraysubs_renewal_shipping_override'=>'','_arraysubs_flex_sync_enabled'=>''],
6394 => ['_subscription_length'=>'0','_arraysubs_fixed_end_date_enabled'=>'yes','_arraysubs_fixed_end_date_type'=>'absolute','_arraysubs_fixed_end_date'=>'2027-12-31','_arraysubs_fixed_end_renewal'=>'expire','_arraysubs_initial_shipping_override'=>'0.00','_arraysubs_renewal_shipping_override'=>'0.00'],
6396 => ['_subscription_period'=>'lifetime','_subscription_interval'=>'1','_subscription_length'=>'0','_trial_length'=>'0','_signup_fee'=>'5.00','_enable_renewal_price'=>'','_arraysubs_fixed_end_date_enabled'=>'','_arraysubs_flex_sync_enabled'=>''],
6398 => ['_subscription_period'=>'month','_subscription_interval'=>'1','_subscription_length'=>'0','_arraysubs_subscription_mode'=>'','_arraysubs_shipping_type'=>'','_arraysubs_fixed_end_date_enabled'=>''],
];
$failures=0;$checks=0;
foreach($cases as $id=>$meta){
$product=wc_get_product($id);
if(!$product || !str_starts_with($product->get_name(),'QPO 2026-09-15') || !$product->is_type('simple')) {echo "FAIL fixture $id\n";$failures++;continue;}
$virtual=in_array($id,[6392,6396],true);$checks++;
if($virtual!==$product->is_virtual()){echo "FAIL virtual $id\n";$failures++;}
foreach($meta as $key=>$expected){$checks++;$actual=get_post_meta($id,$key,true);if($expected!==$actual){echo "FAIL $id $key expected ".wp_json_encode($expected).' actual '.wp_json_encode($actual)."\n";$failures++;}}
echo "Verified $id: ".$product->get_name()."\n";
}
echo "$checks metadata/virtual assertions; $failures failures.\n";
echo 'Pro restored: '.(is_plugin_active('arraysubspro/arraysubspro.php')?'yes':'NO')."\n";
if($failures)exit(1);
