<?php
/** Empty credit-history projection validation, with exact fixture cleanup. */
use ArraySubs\Developer\StoreCredit;

$customer_id = wp_insert_user([
    'user_login' => 'credit-fields-' . substr(wp_generate_uuid4(), 0, 8),
    'user_email' => 'credit-fields-' . wp_generate_uuid4() . '@example.test',
    'user_pass' => wp_generate_password(30),
    'role' => 'customer',
]);
if (is_wp_error($customer_id)) { throw new RuntimeException($customer_id->get_error_message()); }
try {
    $credit = StoreCredit::fromCustomerId($customer_id);
    $cases = [
        'Unknown field fails on an empty ledger' => is_wp_error($credit->history(['fields' => ['unknown']])),
        'Non-string field fails on an empty ledger' => is_wp_error($credit->history(['fields' => [null]])),
        'Valid field projection keeps the empty collection envelope' => ($credit->history(['fields' => ['id', 'amount']])['items'] ?? null) === [],
    ];
    foreach ($cases as $label => $passed) {
        echo ($passed ? 'PASS: ' : 'FAIL: ') . $label . PHP_EOL;
        if (!$passed) { throw new RuntimeException($label); }
    }
} finally {
    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_delete_user($customer_id);
}
echo 'Fixtures cleaned: ' . (int) !get_userdata($customer_id) . PHP_EOL;
