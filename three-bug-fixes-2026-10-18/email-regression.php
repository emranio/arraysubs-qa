<?php
/** Run with WP-CLI eval-file against an isolated development database. */
defined('ABSPATH') || exit;

use ArraySubs\Features\Emails\Services\ExpiringSoonScheduler as Notices;
use ArraySubs\Supports\ActionScheduler as Jobs;

$ids = [];
$checks = [];
$messages = [];
$mail_succeeds = true;
$days = 7;
$enabled = true;
$now = time();
$settings_filter = static function ($settings) use (&$days, &$enabled) {
    $settings['emails']['expiring_soon'] = ['enabled' => $enabled, 'days_before' => $days];
    return $settings;
};
$mail_filter = static function ($result, $mail) use (&$messages, &$mail_succeeds) {
    if (strpos($mail['subject'], 'ending soon') !== false) {
        $messages[] = $mail;
    }
    return $mail_succeeds;
};
add_filter('option_arraysubs_settings', $settings_filter);
add_filter('pre_wp_mail', $mail_filter, 10, 2);
$email = WC()->mailer()->emails['arraysubs_expiring_soon'];
$original_enabled = $email->enabled;
$email->enabled = 'yes';
$check = static function ($condition, string $label) use (&$checks) {
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks[] = $label;
};
$create = static function (array $meta = [], string $status = 'arraysubs-active') use (&$ids, $now) {
    $id = wp_insert_post([
        'post_type' => 'arraysubs_data',
        'post_status' => $status,
        'post_title' => 'QA expiry notice regression 2026-10-18',
        'meta_input' => array_merge([
            '_customer_id' => 1, '_customer_email' => 'qa-bugfix20261018@example.test',
            '_invoice_email' => 'qa-bugfix20261018@example.test', '_product_id' => 6710,
            '_billing_period' => 'day', '_billing_interval' => 2,
            '_subscription_length' => 3, '_completed_payments' => 1,
            '_next_payment_date' => gmdate('Y-m-d H:i:s', $now + 10 * DAY_IN_SECONDS),
            '_start_date' => gmdate('Y-m-d H:i:s', $now), '_payment_method' => 'bacs',
            '_recurring_amount' => 7,
        ], $meta),
    ], true);
    if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
    $ids[] = $id;
    return $id;
};
$pending = static fn(int $id) => as_get_scheduled_actions([
    'hook' => Jobs::HOOK_SEND_EXPIRING_SOON, 'args' => [$id],
    'group' => Jobs::GROUP_EMAILS, 'status' => 'pending', 'per_page' => 20,
], 'ids');
$send = static fn(int $id) => do_action(Jobs::HOOK_SEND_EXPIRING_SOON, $id);
$sent = static fn(int $id) => (string) get_post_meta($id, '_arraysubs_expiring_soon_sent_for', true);
$failure = null;
try {
    $long = $create();
    $target = gmdate('Y-m-d H:i:s', $now + 12 * DAY_IN_SECONDS);
    $check(Notices::getTargetDate($long) === $target, 'Finite target matches last payment boundary');
    $before = get_post_meta($long);
    Notices::reconcile($long);
    $actions = $pending($long);
    $check(count($actions) === 1, 'One reminder is queued');
    $check(Jobs::getNextScheduledTime(Jobs::HOOK_SEND_EXPIRING_SOON, [$long], Jobs::GROUP_EMAILS) === $now + 5 * DAY_IN_SECONDS, 'Reminder uses configured lead time');
    Notices::reconcile($long);
    $check($pending($long) === $actions, 'Unchanged reconciliation preserves action identity');
    $after = get_post_meta($long);
    unset($before['_expiring_soon_action_id'], $after['_expiring_soon_action_id']);
    $check($before === $after, 'Reconciliation leaves billing and lifecycle metadata unchanged');
    update_post_meta($long, '_next_payment_date', gmdate('Y-m-d H:i:s', $now + 20 * DAY_IN_SECONDS));
    $send($long);
    $check($sent($long) === '' && count($messages) === 0, 'An early stale job does not send');
    $check(Jobs::getNextScheduledTime(Jobs::HOOK_SEND_EXPIRING_SOON, [$long], Jobs::GROUP_EMAILS) === $now + 15 * DAY_IN_SECONDS, 'Stale job is requeued for revised target');

    $short = $create(['_next_payment_date' => gmdate('Y-m-d H:i:s', $now + 2 * DAY_IN_SECONDS)]);
    Notices::reconcile($short);
    $due = Jobs::getNextScheduledTime(Jobs::HOOK_SEND_EXPIRING_SOON, [$short], Jobs::GROUP_EMAILS);
    $check($due >= $now && $due <= time() + 2, 'Signup inside lead window queues promptly');
    $send($short);
    $send($short);
    $check(count($messages) === 1 && $sent($short) !== '', 'Repeated executions deliver once per target');
    $check(strpos($messages[0]['message'], arraysubs_format_date_local(Notices::getTargetDate($short))) !== false, 'Email body displays actual finite end');
    $check(strpos(wp_strip_all_tags($messages[0]['message']), 'end in 4 days') !== false, 'Inside-window notice shows actual remaining days');

    $failed = $create(['_next_payment_date' => gmdate('Y-m-d H:i:s', $now + 2 * DAY_IN_SECONDS)]);
    $mail_succeeds = false;
    $send($failed);
    $check($sent($failed) === '', 'Failed mail does not consume sent guard');
    $mail_succeeds = true;
    $send($failed);
    $check($sent($failed) !== '', 'Failed transport can retry successfully');
    $disabled = $create(['_next_payment_date' => gmdate('Y-m-d H:i:s', $now + 2 * DAY_IN_SECONDS)]);
    $email->enabled = 'no';
    $send($disabled);
    $check($sent($disabled) === '', 'Woo-disabled email does not consume sent guard');
    $email->enabled = 'yes';
    $send($disabled);
    $check($sent($disabled) !== '', 'Woo reenabled email can send same target');

    $enabled = false;
    Notices::reconcile($long);
    $check($pending($long) === [], 'Plugin-disabled notices cancel queued job');
    $enabled = true;
    $days = 5;
    Notices::reconcile($long);
    $check(Jobs::getNextScheduledTime(Jobs::HOOK_SEND_EXPIRING_SOON, [$long], Jobs::GROUP_EMAILS) === $now + 17 * DAY_IN_SECONDS, 'Changed lead time moves queued reminder');
    $days = 7;

    foreach (['arraysubs-pending', 'arraysubs-paused', 'arraysubs-on-hold', 'arraysubs-cancelled', 'arraysubs-expired'] as $status) {
        $id = $create([], $status);
        Jobs::scheduleSingle(Jobs::HOOK_SEND_EXPIRING_SOON, $now + DAY_IN_SECONDS, [$id], Jobs::GROUP_EMAILS);
        Notices::reconcile($id);
        $check(Notices::getTargetDate($id) === '' && $pending($id) === [], 'Inactive status clears notice: ' . $status);
    }
    foreach ([
        'unlimited' => ['_subscription_length' => 0],
        'lifetime' => ['_billing_period' => 'lifetime'],
        'completed' => ['_completed_payments' => 3],
        'pending cancellation' => ['_waiting_cancellation' => 'yes'],
        'box child' => ['_arraysubs_box_parent_subscription' => 123],
        'bundle child' => ['_arraysubs_bundle_parent_subscription' => 123],
        'invalid date' => ['_next_payment_date' => '0000-00-00 00:00:00'],
    ] as $name => $meta) {
        $id = $create($meta);
        $check(Notices::getTargetDate($id) === '', 'Ineligible target excluded: ' . $name);
    }
    $fixed_date = gmdate('Y-m-d H:i:s', $now + 4 * DAY_IN_SECONDS);
    $fixed = $create(['_subscription_length' => 0, '_next_payment_date' => '', '_arraysubs_fixed_end_date_enabled' => 'yes', '_end_date' => $fixed_date]);
    $check(Notices::getTargetDate($fixed) === $fixed_date, 'Fixed-date membership works without renewal date');
    update_post_meta($fixed, '_arraysubs_fixed_end_date_type', 'recurring_annual');
    update_post_meta($fixed, '_arraysubs_fixed_end_renewal', 'renew');
    $check(Notices::getTargetDate($fixed) === '', 'Annual auto-renew membership is not described as expiring');
    $trial = $create(['_completed_payments' => 0, '_next_payment_date' => gmdate('Y-m-d H:i:s', $now + DAY_IN_SECONDS)], 'arraysubs-trial');
    $check(Notices::getTargetDate($trial) === gmdate('Y-m-d H:i:s', $now + 5 * DAY_IN_SECONDS), 'Free trial includes all remaining paid cycles');
    $month = $create(['_next_payment_date' => '2027-01-31 12:00:00', '_billing_period' => 'month', '_billing_interval' => 1, '_subscription_length' => 4]);
    $check(Notices::getTargetDate($month) === '2027-04-03 12:00:00', 'Month-end projection matches sequential renewals');
    $timezone = static fn() => 'America/New_York';
    add_filter('pre_option_timezone_string', $timezone);
    $sync = $create(['_next_payment_date' => '2027-03-07 13:00:00', '_billing_period' => 'week', '_billing_interval' => 1, '_renewal_sync_enabled' => 'yes']);
    $check(Notices::getTargetDate($sync) === '2027-03-14 12:00:00', 'Synced boundary preserves local wall time across DST');
    remove_filter('pre_option_timezone_string', $timezone);

    Jobs::unscheduleAll(Jobs::HOOK_SEND_EXPIRING_SOON, [$long], Jobs::GROUP_EMAILS);
    $scheduler = null;
    foreach ($GLOBALS['wp_filter']['shutdown']->callbacks[PHP_INT_MAX] ?? [] as $callback) {
        if (is_array($callback['function']) && $callback['function'][0] instanceof Notices) {
            $scheduler = $callback['function'][0]; break;
        }
    }
    $check($scheduler instanceof Notices, 'Scheduler is loaded by the existing feature provider');
    $scheduler->reconcileBatch(min($ids) - 1);
    $check(count($pending($long)) === 1, 'Bounded recovery repairs an existing subscription with no notice');
    Jobs::unscheduleAllForSubscription($long);
    $check($pending($long) === [], 'Subscription teardown includes expiry notice');
} catch (Throwable $error) {
    $failure = $error;
} finally {
    $email->enabled = $original_enabled;
    remove_filter('pre_wp_mail', $mail_filter, 10);
    remove_filter('option_arraysubs_settings', $settings_filter);
    foreach ($ids as $id) {
        Jobs::unscheduleAllForSubscription($id);
        wp_delete_post($id, true);
    }
}
echo wp_json_encode(['passed' => count($checks), 'checks' => $checks, 'fixtures_deleted' => $ids, 'failure' => $failure ? $failure->getMessage() : null], JSON_PRETTY_PRINT) . "\n";
if ($failure) { throw $failure; }
