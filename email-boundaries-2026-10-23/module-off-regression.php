<?php
/** Real boot-time module gating inventory/render test; no fixtures or mail. */
defined('ABSPATH') || exit;
$checks = [];
$warnings = [];
$failure = null;
$original_settings = get_option('arraysubs_settings', []);
$original_plugins = get_option('active_plugins', []);
$mail_count = 0;
$block_mail = static function () use (&$mail_count) { ++$mail_count; return false; };
$check = static function (bool $ok, string $label) use (&$checks): void {
    $checks[] = ['check' => $label, 'passed' => $ok];
    if (!$ok) { throw new RuntimeException($label); }
};
add_filter('pre_wp_mail', $block_mail, PHP_INT_MAX);
set_error_handler(static function ($level, $message, $file, $line) use (&$warnings) {
    if (str_contains($file, '/arraysubs')) { $warnings[] = compact('level', 'message', 'file', 'line'); return true; }
    return false;
}, E_WARNING | E_NOTICE);
try {
    $check(is_plugin_active('arraysubspro/arraysubspro.php'), 'Pro remains active while only this request disables modules');
    $check(!arraysubs_is_module_enabled('store_credit') && !arraysubs_is_module_enabled('cancellation_flow'), 'Request-local module filter is applied before provider boot');
    $check(arraysubs_has_dormant_module('Features\\StoreCredit\\') && arraysubs_has_dormant_module('Features\\CancellationFlow\\'), 'Both premium providers boot dormant');
    $emails = WC()->mailer()->emails;
    $core = array_filter($emails, static fn($email) => $email instanceof \ArraySubs\Features\Emails\Abstracts\BaseSubscriptionEmail);
    $credit = array_filter($emails, static fn($email) => str_starts_with(get_class($email), 'ArraySubsPro\\Features\\StoreCredit\\Emails\\'));
    $check(count($core) === 25, 'All 25 core emails register with premium modules disabled');
    $check($credit === [], 'All four Pro credit email classes stay unregistered');
    $actions = apply_filters('woocommerce_email_actions', []);
    foreach (['arraysubs_customer_credit_added', 'arraysubs_subscription_credit_added', 'arraysubs_credits_applied_to_order', 'arraysubs_credit_expiring_soon', 'arraysubs_credit_expired'] as $action) {
        $check(!in_array($action, $actions, true), $action . ' is absent from transactional email registration');
        $check(!has_action($action . '_notification'), $action . ' notification has no email callback');
    }
    $check(isset($emails['arraysubs_retention_discount_accepted']) && !arraysubs_is_retention_offers_enabled(), 'Core retention email remains registered while premium offers are unavailable');
    $routes = rest_get_server()->get_routes();
    $check(!isset($routes['/arraysubs/v1/cancellation/form-config']) && !isset($routes['/arraysubs/v1/subscriptions/(?P<id>\d+)/retention-offers']), 'Premium cancellation configuration and offer endpoints are absent');
    foreach ($core as $email) {
        foreach (['html', 'plain'] as $format) {
            $method = 'get_content_' . $format;
            $check(strlen($email->$method()) > 100, $email->id . ' ' . $format . ' renders with modules disabled');
        }
    }
    $check($warnings === [], 'Module-off email render has no PHP warning or notice');
    $check($mail_count === 0, 'Inventory and preview rendering trigger no mail');
    $check(did_action('arraysubs_modules_saved') === 0, 'No module-save lifecycle action is simulated');
} catch (Throwable $error) {
    $failure = get_class($error) . ': ' . $error->getMessage();
} finally {
    restore_error_handler();
    remove_filter('pre_wp_mail', $block_mail, PHP_INT_MAX);
}
$settings_unchanged = get_option('arraysubs_settings', []) === $original_settings;
$plugins_unchanged = get_option('active_plugins', []) === $original_plugins;
echo wp_json_encode(['checks' => $checks, 'failure' => $failure, 'warnings' => $warnings, 'mail_intercepted_count' => $mail_count, 'external_mail_sent' => 0, 'fixtures' => [], 'saved_settings_unchanged' => $settings_unchanged, 'active_plugins_unchanged' => $plugins_unchanged], JSON_PRETTY_PRINT) . PHP_EOL;
if ($failure || !$settings_unchanged || !$plugins_unchanged) { WP_CLI::halt(1); }
