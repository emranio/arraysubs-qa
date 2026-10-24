<?php
wp_set_current_user(1);
$all_ok=true;
foreach (['7d','30d','90d','month'] as $key) {
 $range=\ArraySubs\Features\MainAdmin\Support\Overview\Range::resolve($key);
 $cards=\ArraySubsPro\Features\OverviewWidgets\Support\AnalyticsCards::build(['range'=>$range]);
 $by_id=array_column($cards,null,'id');
 $errors=array_filter($cards, static fn($card)=>isset($card['error']));
 $args=[
  'after'=>get_date_from_gmt($range['start']), 'before'=>get_date_from_gmt($range['end']),
  'interval'=>'day', 'per_page'=>1, 'force_cache_refresh'=>true,
 ];
 $orders_request=new WP_REST_Request('GET','/wc-analytics/reports/orders/stats');
 foreach($args as $k=>$v) $orders_request->set_param($k,$v);
 $response=rest_do_request($orders_request);
 $body=$response->get_data();
 $total=wc_get_orders([
   'type'=>'shop_order',
   'status'=>array_values(array_intersect(array_keys(wc_get_order_statuses()),get_post_stati(['show_in_admin_all_list'=>true],'names'))),
   'date_created'=>strtotime($range['start'].' UTC').'...'.strtotime($range['end'].' UTC'),
   'limit'=>1,'paginate'=>true,'return'=>'ids',
 ]);
 $native=(int)str_replace(',','',$by_id['order-list']['metrics'][0]['value']??'-1');
 $analytics=(int)str_replace(',','',$by_id['orders-analytics']['metrics'][0]['value']??'-1');
 $match=isset($body['totals']['orders_count']) && (int)$body['totals']['orders_count']===$analytics;
 $ok=!$errors && $native===(int)$total->total && $match;
 $all_ok=$all_ok && $ok;
 echo wp_json_encode(['range'=>$key,'cards'=>count($cards),'source_errors'=>count($errors),'native_card'=>$native,'wc_records'=>$total->total,'analytics_card'=>$analytics,'native_rest_analytics'=>$body['totals']['orders_count']??$body,'rest_status'=>$response->get_status(),'pass'=>$ok]),PHP_EOL;
}
echo 'ALL_PASS=',($all_ok?'yes':'no'),PHP_EOL;
