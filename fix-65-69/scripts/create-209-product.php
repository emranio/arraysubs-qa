<?php
/**
 * Fixture for #69: a virtual yearly subscription at 209.00 (the reporter's price), no trial or fee.
 * Usage: wp eval-file <abs>/qa/fix-65-69/scripts/create-209-product.php --allow-root
 */
$title = 'SQA Yearly 209 Plan';
if ($existing = get_posts(['post_type' => 'product', 'post_status' => 'any', 'title' => $title, 'fields' => 'ids'])) {
    WP_CLI::success('exists id=' . $existing[0]);
    return;
}
wp_set_current_user(1);
$r = new WP_REST_Request('POST', '/arraysubs/v1/quick-products');
$r->set_header('Content-Type', 'application/json');
$r->set_body(wp_json_encode(['request_id' => 'sqa-fix69-209-20261020', 'type' => 'simple', 'title' => $title, 'description' => 'Staging QA fixture for #69 (2026-10-20): 209.00 per year.', 'virtual' => true, 'regular_price' => '209', 'settings' => ['_subscription_period' => 'year', '_subscription_interval' => 1, '_subscription_length' => 0, '_trial_length' => 0, '_trial_period' => 'day', '_signup_fee' => '']]));
$res = rest_do_request($r);
$id  = get_posts(['post_type' => 'product', 'post_status' => 'any', 'title' => $title, 'fields' => 'ids'])[0] ?? 0;
echo $res->get_status(), " id=$id ", wp_json_encode(array_map(fn($v) => $v[0], array_intersect_key(get_post_meta($id), array_flip(['_is_subscription', '_subscription_period', '_price', '_tax_status'])))), "\n";
