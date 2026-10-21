<?php
if (get_posts(['post_type'=>'product','post_status'=>'any','title'=>'SQA Daily Trial Plan','fields'=>'ids'])) { WP_CLI::error('exists'); }
wp_set_current_user(1);
$r = new WP_REST_Request('POST', '/arraysubs/v1/quick-products');
$r->set_header('Content-Type', 'application/json');
$r->set_body(wp_json_encode(['request_id'=>'sqa-p2-trial-20261019','type'=>'simple','title'=>'SQA Daily Trial Plan','description'=>'Staging Stripe QA fixture (2026-10-19). 1-day free trial, then 7.31 USD per day.','virtual'=>true,'regular_price'=>'7.31','settings'=>['_subscription_period'=>'day','_subscription_interval'=>1,'_subscription_length'=>0,'_trial_length'=>1,'_trial_period'=>'day','_signup_fee'=>'']]));
$res = rest_do_request($r);
echo $res->get_status(), "\n";
$id = get_posts(['post_type'=>'product','post_status'=>'any','title'=>'SQA Daily Trial Plan','fields'=>'ids'])[0] ?? 0;
echo "id=$id ", wp_json_encode(array_map(fn($v)=>$v[0], array_intersect_key(get_post_meta($id), array_flip(['_is_subscription','_subscription_period','_trial_length','_trial_period','_price'])))), "\n";
