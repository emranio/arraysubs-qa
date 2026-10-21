<?php
/**
 * Run one fixture renewal attempt (the same action the queue runs) and print every Stripe
 * API call it makes, plus new subscription notes. Fixture use only.
 *
 * Usage: wp eval-file <abs>/qa/fix-65-69/scripts/t65-trace-run.php <subscription_id>
 */
$sid = (int) ($args[0] ?? 0);
if ($sid <= 0) { WP_CLI::error('subscription id required'); }
$calls = [];
add_action('http_api_debug', static function ($response, $context, $class, $parsed_args, $url) use (&$calls) {
    if (false === strpos((string) $url, 'api.stripe.com')) { return; }
    $code = is_wp_error($response) ? 'WP_Error ' . $response->get_error_message() : wp_remote_retrieve_response_code($response);
    $body = is_wp_error($response) ? '' : json_decode(wp_remote_retrieve_body($response), true);
    $calls[] = sprintf('%s %s -> %s %s', $parsed_args['method'] ?? '?', preg_replace('#^https://api.stripe.com/v1/#', '', strtok((string) $url, '?')), $code,
        is_array($body) ? (isset($body['error']) ? 'error=' . ($body['error']['code'] ?? '') . ' ' . ($body['error']['message'] ?? '') : 'status=' . ($body['status'] ?? ($body['object'] ?? ''))) : '');
}, 10, 5);
$before = (int) get_posts(['post_type' => 'arraysubs_sub_note', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_subscription_id', 'meta_value' => $sid, 'orderby' => 'ID', 'order' => 'DESC'])[0];
do_action(\ArraySubs\Supports\ActionScheduler::HOOK_PROCESS_RENEWAL, $sid);
echo "== Stripe calls\n" . implode("\n", $calls) . "\n== new notes\n";
foreach (get_posts(['post_type' => 'arraysubs_sub_note', 'posts_per_page' => 20, 'meta_key' => '_subscription_id', 'meta_value' => $sid, 'orderby' => 'ID', 'order' => 'ASC', 'post__not_in' => [], 'date_query' => []]) as $n) {
    if ($n->ID > $before) { echo $n->post_date_gmt, ' ', wp_strip_all_tags($n->post_content), "\n"; }
}
