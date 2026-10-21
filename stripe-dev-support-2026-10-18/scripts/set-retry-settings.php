<?php
// Usage: wp eval-file set-retry.php <enabled 0|1> <stop_on_hard 0|1> <hours,csv> --allow-root
wp_set_current_user(1);
$r = new WP_REST_Request('POST', '/arraysubs/v1/settings/save');
$r->set_header('Content-Type', 'application/json');
$r->set_body(wp_json_encode(['settings' => ['payment_retry' => [
    'enabled' => (bool) (int) $args[0],
    'stop_on_hard_decline' => (bool) (int) $args[1],
    'interval_hours' => array_map('intval', explode(',', $args[2])),
]]]));
$res = rest_do_request($r);
echo $res->get_status(), ' ', wp_json_encode(arraysubs_get_settings()['payment_retry'] ?? null), "\n";
