<?php
wp_set_current_user(1);
use ArraySubsPro\Features\OverviewWidgets\Support\AnalyticsCards;
$future = ['range'=>['start'=>'2100-01-01 00:00:00','end'=>'2100-01-07 23:59:59']];
$cards = array_column(AnalyticsCards::build($future),null,'id');
$empty = !isset($cards['orders-analytics']['error'],$cards['revenue']['error'],$cards['variations']['error'],$cards['order-list']['error'])
    && $cards['orders-analytics']['metrics'][0]['value']==='0'
    && $cards['variations']['metrics'][0]['value']==='0'
    && $cards['order-list']['metrics'][0]['value']==='0';
// A request-local simulated failed WC source must never display its numeric defaults as real data.
add_filter('woocommerce_analytics_customers_stats_select_query', static function ($result) {
    global $wpdb;
    $wpdb->last_error='QA simulated source read failure';
    return (object)['customers_count'=>0,'avg_orders_count'=>0,'avg_total_spend'=>0,'avg_avg_order_value'=>0];
}, PHP_INT_MAX);
$failed = array_column(AnalyticsCards::build($future),null,'id');
$error = !empty($failed['customers']['error']) && $failed['customers']['metrics']===[];
echo wp_json_encode(['empty_sales_source_pass'=>$empty,'failed_source_shows_error_without_zero_metrics'=>$error,'other_source_stays_available'=>empty($failed['order-list']['error'])],JSON_PRETTY_PRINT);
