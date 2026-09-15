<?php
use ArraySubs\Features\QuickProductCreation\REST\ProductController;
wp_set_current_user(1);
$c = new ProductController();
$method = new ReflectionMethod($c, 'prepare');
$method->setAccessible(true);
$base = ['type'=>'simple', 'title'=>'QPO validation only', 'regular_price'=>'29', 'virtual'=>false, 'settings'=>[]];
$cases = [
 ['valid fixed', [], false, false],
 ['full flexible requires periods', ['_arraysubs_subscription_mode'=>'full_flexible','_arraysubs_flexible_periods'=>[]], true, false],
 ['invalid mode', ['_arraysubs_subscription_mode'=>'bogus'], true, false],
 ['invalid periods', ['_arraysubs_subscription_mode'=>'full_flexible','_arraysubs_flexible_periods'=>['lifetime']], true, false],
 ['interval range', ['_subscription_interval'=>13], true, false],
 ['length range', ['_subscription_length'=>366], true, false],
 ['fractional length', ['_subscription_length'=>1.5], true, false],
 ['negative shipping', ['_arraysubs_initial_shipping_override'=>-1], true, false],
 ['invalid shipping', ['_arraysubs_shipping_type'=>'bogus'], true, false],
 ['virtual ignores shipping', ['_arraysubs_shipping_type'=>'bogus','_arraysubs_initial_shipping_override'=>-1], false, true],
 ['one-time ignores renewal shipping', ['_arraysubs_shipping_type'=>'one-time','_arraysubs_renewal_shipping_override'=>-1], false, false],
 ['disabled renewal price ignores stale count', ['_renewal_price_after'=>'bad'], false, false],
 ['active renewal price validates count', ['_enable_renewal_price'=>true,'_renewal_price'=>20,'_renewal_price_after'=>0], true, false],
 ['lifetime ignores inactive fields', ['_subscription_period'=>'lifetime','_subscription_interval'=>0,'_subscription_length'=>-1,'_trial_length'=>-1,'_trial_period'=>'bad'], false, false],
 ['full flexible locks interval', ['_arraysubs_subscription_mode'=>'full_flexible','_arraysubs_flexible_periods'=>['week','year'],'_subscription_interval'=>0], false, false],
];
$fpm=['_arraysubs_fixed_end_date_enabled'=>true,'_arraysubs_fixed_end_date_type'=>'absolute'];
$cases[]= ['missing end date',$fpm,true,false];
$cases[]= ['impossible end date',$fpm+['_arraysubs_fixed_end_date'=>'2026-02-30'],true,false];
$cases[]= ['year zero',$fpm+['_arraysubs_fixed_end_date'=>'0000-01-01'],true,false];
$cases[]= ['reversed enrollment',$fpm+['_arraysubs_fixed_end_date'=>'2027-12-31','_arraysubs_enrollment_window_start'=>'2026-10-01','_arraysubs_enrollment_window_end'=>'2026-09-01'],true,false];
$cases[]= ['fixed date overrides length and mode',$fpm+['_arraysubs_fixed_end_date'=>'2027-12-31','_subscription_length'=>-1,'_arraysubs_subscription_mode'=>'flexible_length'],false,false];
$cases[]= ['annual leap day',['_arraysubs_fixed_end_date_enabled'=>true,'_arraysubs_fixed_end_date_type'=>'recurring_annual','_arraysubs_fixed_end_date'=>'02-29'],false,false];
$cases[]= ['annual impossible date',['_arraysubs_fixed_end_date_enabled'=>true,'_arraysubs_fixed_end_date_type'=>'recurring_annual','_arraysubs_fixed_end_date'=>'02-30'],true,false];
$failed=0;
foreach ($cases as [$name,$settings,$expect_error,$virtual]) {
 $input=$base;$input['settings']=$settings;$input['virtual']=$virtual;
 $result=$method->invoke($c,$input);
 $ok=is_wp_error($result)===$expect_error;
 if(!$ok)$failed++;
 echo ($ok?'PASS ':'FAIL ').$name.(is_wp_error($result)?': '.$result->get_error_message():'')."\n";
}
echo count($cases)." cases; $failed failures. No products written.\n";
if($failed)exit(1);
