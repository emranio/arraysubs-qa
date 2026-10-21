<?php
/**
 * Authoritative developer snapshot of one fixture subscription.
 *
 * Usage: wp eval-file <abs>/scripts/dev-snapshot.php <subscription_id> [label] --allow-root
 * Writes snapshots/sub-<id>-<label>.json and prints it. Contains:
 *  - \ArraySubs\Developer\Subscriptions::fromId($id)->data()
 *  - related orders (status, total, txn/charge, Stripe intent, paid date)
 *  - pending/recent Action Scheduler actions whose args reference the subscription
 *  - Stripe-safe payment meta (IDs only; no secrets exist in these keys)
 */

$sub_id = (int) ($args[0] ?? 0);
$label = preg_replace('/[^a-z0-9_-]/i', '', (string) ($args[1] ?? gmdate('His')));
if ($sub_id <= 0) {
    WP_CLI::error('Usage: dev-snapshot.php <subscription_id> [label]');
}
$qa = WP_CONTENT_DIR . '/plugins/qa/stripe-dev-support-2026-10-18';
$scope = json_decode((string) file_get_contents($qa . '/capture/fixture-scope.json'), true);
if (! in_array($sub_id, array_map('intval', (array) ($scope['subscription_ids'] ?? [])), true)) {
    WP_CLI::error('Subscription is not a recorded fixture.');
}

$api = \ArraySubs\Developer\Subscriptions::fromId($sub_id);
$data = $api->data();
if (is_wp_error($data)) {
    WP_CLI::error($data->get_error_message());
}

$orders = [];
$order_ids = wc_get_orders([
    'limit' => -1,
    'return' => 'ids',
    'meta_query' => [['key' => '_subscription_ids', 'value' => (string) $sub_id, 'compare' => 'LIKE']],
]);
$ref_ids = array_merge(
    (array) ($data['orders']['items'] ?? []),
    array_values(array_filter((array) ($data['order_references'] ?? [])))
);
foreach (array_unique(array_map('intval', array_merge($order_ids, (array) get_post_meta($sub_id, '_order_ids', true), $ref_ids))) as $oid) {
    $o = wc_get_order((int) $oid);
    if (! $o) {
        continue;
    }
    $orders[] = [
        'id' => $o->get_id(),
        'status' => $o->get_status(),
        'total' => $o->get_total(),
        'currency' => $o->get_currency(),
        'created' => $o->get_date_created() ? gmdate('c', $o->get_date_created()->getTimestamp()) : null,
        'paid' => $o->get_date_paid() ? gmdate('c', $o->get_date_paid()->getTimestamp()) : null,
        'payment_method' => $o->get_payment_method(),
        'transaction_id' => $o->get_transaction_id(),
        'stripe_intent_id' => $o->get_meta('_stripe_intent_id'),
        'stripe_source_id' => $o->get_meta('_stripe_source_id'),
        'stripe_customer_id' => $o->get_meta('_stripe_customer_id'),
        'type' => $o->get_meta('_arraysubs_order_type') ?: $o->get_meta('_arraysubs_computed_type'),
    ];
}

global $wpdb;
$actions = $wpdb->get_results($wpdb->prepare(
    "SELECT action_id, hook, status, scheduled_date_gmt, last_attempt_gmt, args
       FROM {$wpdb->prefix}actionscheduler_actions
      WHERE (args = %s OR args LIKE %s OR args LIKE %s)
      ORDER BY action_id",
    '[' . $sub_id . ']',
    '[' . $sub_id . ',%',
    '%"' . $sub_id . '"%'
), ARRAY_A);

$meta = [];
foreach (get_post_meta($sub_id) as $k => $v) {
    if (preg_match('/secret|key$|token/i', $k)) {
        continue;
    }
    $meta[$k] = count($v) === 1 ? maybe_unserialize($v[0]) : array_map('maybe_unserialize', $v);
}

// Order keys (e.g. inside _arraysubs_payment_action_url) must never land in
// QA evidence: redact every wc_order_ key and key= query value recursively.
$redact = static function ($value) use (&$redact) {
    if (is_array($value)) {
        return array_map($redact, $value);
    }
    if (! is_string($value)) {
        return $value;
    }
    $value = preg_replace('/wc_order_[A-Za-z0-9]+/', 'wc_order_<redacted>', $value);
    return preg_replace('/([?&](?:amp;)?key=)[^&#"\s]+/', '$1<redacted>', $value);
};
unset($meta['_order_key']);
$meta = $redact($meta);
$data = $redact($data);

$out = [
    'taken_at_utc' => gmdate('c'),
    'subscription_id' => $sub_id,
    'developer_data' => $data,
    'orders' => $orders,
    'actions' => $actions,
    'meta' => $meta,
];
$file = $qa . "/snapshots/sub-{$sub_id}-{$label}.json";
file_put_contents($file, wp_json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
WP_CLI::log(wp_json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
