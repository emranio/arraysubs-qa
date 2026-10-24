<?php
wp_set_current_user(1);
use ArraySubs\Features\MainAdmin\Support\Overview\Range;
use ArraySubs\Features\MainAdmin\Support\Overview\Money;
use ArraySubs\Features\MainAdmin\Support\Overview\ReportCards;
use ArraySubsPro\Features\OverviewWidgets\Support\AnalyticsCards;
use ArraySubsPro\Features\OverviewWidgets\Support\OperationsCards;
use ArraySubsPro\Features\StoreCredit\Services\CreditManager;
use ArraySubsPro\Features\StoreCredit\Services\TransactionLogger;

$checks = [];
foreach (['7d', '30d', '90d', 'month'] as $key) {
    $range = Range::resolve($key);
    $cards = array_column(array_merge(AnalyticsCards::build(['range'=>$range]), OperationsCards::build(['range'=>$range])), null, 'id');
    $args = ['after'=>get_date_from_gmt($range['start']), 'before'=>get_date_from_gmt($range['end']), 'interval'=>'day', 'force_cache_refresh'=>true];
    foreach (['orders-analytics'=>['orders','net_revenue','Net sales'], 'revenue'=>['revenue','gross_sales','Gross sales'], 'variations'=>['variations','items_sold','Items sold']] as $id=>[$report,$metric,$label]) {
        $req = new WP_REST_Request('GET','/wc-analytics/reports/'.$report.'/stats');
        $req->set_query_params($args);
        $response=rest_do_request($req);
        $data=$response->get_data();
        $expected = is_array($data) ? ($data['totals'][$metric] ?? null) : null;
        $expected = 'items_sold' === $metric ? number_format_i18n($expected) : Money::format($expected);
        $actual = array_column($cards[$id]['metrics'], 'value', 'label')[$label] ?? null;
        $checks[]=['range'=>$key,'card'=>$id,'metric'=>$label,'actual'=>$actual,'expected'=>$expected,'pass'=>$actual===$expected];
    }
    foreach (['activity-audits'=>'activity-logs', 'scheduled-jobs'=>'scheduled-job-logs'] as $id=>$route) {
        $req = new WP_REST_Request('GET','/arraysubs/v1/audits/'.$route);
        $req->set_query_params(['page'=>1,'per_page'=>3,'date_from'=>get_date_from_gmt($range['start'],'Y-m-d'),'date_to'=>get_date_from_gmt($range['end'],'Y-m-d')]);
        $result=rest_do_request($req)->get_data()['content'];
        $actual=$cards[$id]['metrics'][0]['value'];
        $checks[]=['range'=>$key,'card'=>$id,'actual'=>$actual,'expected'=>number_format_i18n($result['total']),'pass'=>$actual===number_format_i18n($result['total'])];
    }
}
$ops=array_column(OperationsCards::build(['range'=>Range::resolve('30d')]),null,'id');
$sum=0; $count=0;
foreach (get_users(['fields'=>'ID','meta_key'=>CreditManager::USER_CREDIT_META]) as $user_id) {
    $balance=CreditManager::getCustomerCredit((int)$user_id);
    if ($balance>0) { $sum += $balance; $count++; }
}
$metrics=array_column($ops['credit-balances']['metrics'],'value','label');
$checks[]=['card'=>'credit-balances','metric'=>'Customer credit','actual'=>array_values($metrics)[0],'expected'=>Money::format($sum),'pass'=>array_values($metrics)[0]===Money::format($sum) && array_values($metrics)[1]===number_format_i18n($count)];
$history=TransactionLogger::getAllTransactions(1,3);
$checks[]=['card'=>'credit-history','actual'=>$ops['credit-history']['metrics'][0]['value'],'expected'=>number_format_i18n($history['total']),'pass'=>$ops['credit-history']['metrics'][0]['value']===number_format_i18n($history['total'])];
wp_set_current_user(0);
$denied=rest_do_request(new WP_REST_Request('GET','/arraysubs/v1/overview'));
$checks[]=['case'=>'guest-overview-denied','status'=>$denied->get_status(),'pass'=>$denied->get_status()===401];
echo wp_json_encode(['checks'=>$checks,'all_pass'=>!array_filter($checks,static fn($c)=>!$c['pass'])],JSON_PRETTY_PRINT);
