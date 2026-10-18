<?php
/** WP-CLI eval-file regression checks. Retains append-only QA ledger fixtures. */
use ArraySubs\Features\PlanSwitching\Services\PlanManager;
use ArraySubs\Features\PlanSwitching\Services\ProrationCalculator;
use ArraySubsPro\Features\StoreCredit\Services\CreditManager;
use ArraySubsPro\Features\StoreCredit\Services\CreditLedger;
use ArraySubsPro\Features\StoreCredit\Services\TransactionLogger;

function sc_check(string $label, bool $ok): void {
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    echo 'PASS: ' . $label . PHP_EOL;
}
function sc_grant(int $subscription_id, string $key, float $amount = 7.25) {
    return CreditManager::grantSubscriptionCreditOnce($subscription_id, $amount, CreditManager::SOURCE_PROMOTIONAL, null, 'Developer support regression', get_woocommerce_currency(), hash('sha256', $key));
}
function sc_rows(string $key): array {
    return get_posts(['post_type' => CreditLedger::CPT_CREDIT_LOG, 'post_status' => ['draft','publish'], 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_contract_key', 'meta_value' => hash('sha256', $key)]);
}

wp_set_current_user(1);
$run = wp_generate_uuid4();
$user_id = wp_insert_user(['user_login' => 'sc-' . substr($run, 0, 8), 'user_email' => 'sc-' . substr($run, 0, 8) . '@example.test', 'user_pass' => wp_generate_password(), 'role' => 'customer']);
sc_check('QA customer created', !is_wp_error($user_id));
$subscription_id = wp_insert_post(['post_type'=>'arraysubs_data', 'post_status'=>'arraysubs-active', 'post_title'=>'Store credit regression ' . $run, 'meta_input'=>['_customer_id'=>$user_id]]);
$notifications = 0;
add_action('arraysubs_subscription_credit_added', function ($id) use ($subscription_id, &$notifications) { if ($id === $subscription_id) { ++$notifications; } }, 99);
$key = $run . '-once';
$first = sc_grant($subscription_id, $key);
sc_check('Grant publishes one durable row', is_int($first) && get_post_status($first) === 'publish');
sc_check('Grant credits balance', CreditManager::getSubscriptionCredit($subscription_id) === 7.25);
sc_check('Replay reuses row without balance or notification change', sc_grant($subscription_id, $key) === $first && count(sc_rows($key)) === 1 && CreditManager::getSubscriptionCredit($subscription_id) === 7.25 && $notifications === 1);
CreditManager::addSubscriptionCredit($subscription_id, 2, CreditManager::SOURCE_ADMIN);
sc_check('Replay after unrelated credit keeps current balance', sc_grant($subscription_id, $key) === $first && CreditManager::getSubscriptionCredit($subscription_id) === 9.25);
sc_check('Direct ledger deletion refused', false === wp_delete_post($first, true) && get_post($first) instanceof WP_Post);
sc_check('Ledger trash refused', false === wp_trash_post($first) && get_post_status($first) === 'publish');
sc_check('Ledger excluded from user-deletion cascade', false === get_post_type_object(CreditLedger::CPT_CREDIT_LOG)->delete_with_user);
$response = rest_do_request(new WP_REST_Request('DELETE', '/arraysubs/v1/credits/log/' . $first));
sc_check('Delete REST route absent', 404 === $response->get_status() && 'rest_no_route' === $response->get_data()['code']);
sc_check('Server formats credit amount', strpos(TransactionLogger::formatTransaction(get_post($first))['amount_formatted'], '7.25') !== false);

// Interrupt after reservation, before the balance write; retry uses the same draft.
$fail_write = function ($check, $id, $meta_key) use ($subscription_id) { return $id === $subscription_id && $meta_key === CreditManager::SUBSCRIPTION_CREDIT_META ? false : $check; };
add_filter('update_post_metadata', $fail_write, 10, 3);
$draft_key = $run . '-draft-before';
$failed = sc_grant($subscription_id, $draft_key);
remove_filter('update_post_metadata', $fail_write, 10);
$draft_rows = sc_rows($draft_key);
sc_check('Failed balance write leaves unpublished recovery row', is_wp_error($failed) && count($draft_rows) === 1 && get_post_status($draft_rows[0]) === 'draft' && CreditManager::getSubscriptionCredit($subscription_id) === 9.25);
sc_check('Draft before balance resumes', sc_grant($subscription_id, $draft_key) === $draft_rows[0] && CreditManager::getSubscriptionCredit($subscription_id) === 16.5);

// Interrupt after the balance write, before publication.
$fail_publish = function ($data, $postarr) { if (($data['post_type'] ?? '') === CreditLedger::CPT_CREDIT_LOG && ($data['post_status'] ?? '') === 'publish' && !empty($postarr['ID'])) { $data['post_status'] = 'draft'; } return $data; };
add_filter('wp_insert_post_data', $fail_publish, 10, 2);
$after_key = $run . '-draft-after';
$failed = sc_grant($subscription_id, $after_key);
remove_filter('wp_insert_post_data', $fail_publish, 10);
$after_rows = sc_rows($after_key);
sc_check('Publication failure preserves applied balance in draft', is_wp_error($failed) && count($after_rows) === 1 && get_post_status($after_rows[0]) === 'draft' && CreditManager::getSubscriptionCredit($subscription_id) === 23.75);
sc_check('Draft after balance publishes without adding again', sc_grant($subscription_id, $after_key) === $after_rows[0] && CreditManager::getSubscriptionCredit($subscription_id) === 23.75);

add_filter('update_post_metadata', $fail_write, 10, 3);
$conflict_key = $run . '-conflict';
sc_grant($subscription_id, $conflict_key);
remove_filter('update_post_metadata', $fail_write, 10);
CreditManager::addSubscriptionCredit($subscription_id, 1, CreditManager::SOURCE_ADMIN);
sc_check('Ambiguous interrupted grant fails closed', is_wp_error(sc_grant($subscription_id, $conflict_key)) && CreditManager::getSubscriptionCredit($subscription_id) === 24.75);

// Force a fresh ordinary ledger row to fail verification. Only that new row may be removed.
$rows_before = get_posts(['post_type'=>CreditLedger::CPT_CREDIT_LOG, 'post_status'=>'publish', 'posts_per_page'=>-1, 'fields'=>'ids', 'meta_key'=>'_subscription_id', 'meta_value'=>$subscription_id]);
$corrupt_log = function ($check, $id, $meta_key) { return get_post_type($id) === CreditLedger::CPT_CREDIT_LOG && $meta_key === '_amount' ? false : $check; };
add_filter('update_post_metadata', $corrupt_log, 10, 3);
$failed = CreditManager::addSubscriptionCredit($subscription_id, 3, CreditManager::SOURCE_ADMIN);
remove_filter('update_post_metadata', $corrupt_log, 10);
$rows_after = get_posts(['post_type'=>CreditLedger::CPT_CREDIT_LOG, 'post_status'=>'publish', 'posts_per_page'=>-1, 'fields'=>'ids', 'meta_key'=>'_subscription_id', 'meta_value'=>$subscription_id]);
sc_check('Failed inserted row rollback remains scoped and restores balance', false === $failed && count($rows_before) === count($rows_after) && CreditManager::getSubscriptionCredit($subscription_id) === 24.75 && get_post($first) instanceof WP_Post);
$sum = 0.0;
foreach ($rows_after as $id) { $amount = (float) get_post_meta($id, '_amount', true); $sum += get_post_meta($id, '_type', true) === 'credit' ? $amount : -$amount; }
sc_check('Published ledger reconciles with subscription bucket', $sum === CreditManager::getSubscriptionCredit($subscription_id));
echo wp_json_encode(['run'=>$run,'customer_id'=>$user_id,'subscription_id'=>$subscription_id,'grant_id'=>$first], JSON_PRETTY_PRINT) . PHP_EOL;
