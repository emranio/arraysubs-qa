<?php
/** Args: customer ID and unique worker index. */
add_filter('woocommerce_email_enabled_arraysubs_credit_added', '__return_false');
$api = \ArraySubs\Developer\StoreCredit::fromCustomerId((int) $args[0]);
for ($attempt = 0; $attempt < 50; ++$attempt) {
    $result = $api->adjust(1, ['operation_id'=>'parallel-' . $args[1], 'reason'=>'Parallel credit regression', 'currency'=>get_woocommerce_currency()]);
    if (!is_wp_error($result)) { echo $result['transaction']['id']; return; }
    if ('credit_busy' !== $result->get_error_code()) { throw new RuntimeException(wp_json_encode($result)); }
    usleep(30000);
}
throw new RuntimeException('Credit contention did not clear.');
