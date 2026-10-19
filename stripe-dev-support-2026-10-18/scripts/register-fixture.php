<?php
/**
 * Record a fixture ID in the QA registry (fixtures.json) and, for customers and
 * subscriptions, in the lifecycle-capture scope (capture/fixture-scope.json).
 *
 * Usage: wp eval-file <abs>/scripts/register-fixture.php <kind> <id> [label words...] --allow-root
 *   kind: customer | subscription | order | product | page | post | action | stripe_object | note
 *   For stripe_object/note the "id" is a free-form reference (no secrets!).
 * The registry is append-only: entries are never removed here; cleanup phases
 * mark them with a cleanup result instead.
 */

$qa = WP_CONTENT_DIR . '/plugins/qa/stripe-dev-support-2026-10-18';
$kind = (string) ($args[0] ?? '');
$id = (string) ($args[1] ?? '');
$label = trim(implode(' ', array_slice($args, 2)));
$kinds = ['customer', 'subscription', 'order', 'product', 'page', 'post', 'action', 'stripe_object', 'note'];
if (! in_array($kind, $kinds, true) || '' === $id) {
    WP_CLI::error('Usage: register-fixture.php <' . implode('|', $kinds) . '> <id> [label]');
}
if (preg_match('/(sk|rk)_(test|live)_|whsec_/', $id . ' ' . $label)) {
    WP_CLI::error('Refusing to store a secret-looking value.');
}

$registry_file = $qa . '/fixtures.json';
$fh = fopen($registry_file, 'c+');
flock($fh, LOCK_EX);
$raw = stream_get_contents($fh);
$registry = json_decode((string) $raw, true);
if (! is_array($registry)) {
    $registry = ['run' => 'stripe-dev-support-2026-10-18', 'entries' => []];
}
$registry['entries'][] = [
    'kind' => $kind,
    'id' => ctype_digit($id) ? (int) $id : $id,
    'label' => $label,
    'recorded_at_utc' => gmdate('c'),
    'phase' => getenv('SQA_PHASE') ?: 'unspecified',
];
ftruncate($fh, 0);
rewind($fh);
fwrite($fh, wp_json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
flock($fh, LOCK_UN);
fclose($fh);

if (in_array($kind, ['customer', 'subscription'], true) && ctype_digit($id)) {
    $scope_file = $qa . '/capture/fixture-scope.json';
    $sfh = fopen($scope_file, 'c+');
    flock($sfh, LOCK_EX);
    $scope = json_decode((string) stream_get_contents($sfh), true);
    if (! is_array($scope)) {
        $scope = ['subscription_ids' => [], 'customer_ids' => [], 'customer_emails' => []];
    }
    if ('subscription' === $kind) {
        $scope['subscription_ids'][] = (int) $id;
    } else {
        $scope['customer_ids'][] = (int) $id;
        $user = get_userdata((int) $id);
        if ($user) {
            $scope['customer_emails'][] = strtolower($user->user_email);
        }
    }
    foreach (['subscription_ids', 'customer_ids', 'customer_emails'] as $k) {
        $scope[$k] = array_values(array_unique($scope[$k]));
    }
    ftruncate($sfh, 0);
    rewind($sfh);
    fwrite($sfh, wp_json_encode($scope, JSON_PRETTY_PRINT) . "\n");
    flock($sfh, LOCK_UN);
    fclose($sfh);
}
WP_CLI::success("Registered {$kind} {$id}" . ('' !== $label ? " ({$label})" : ''));
