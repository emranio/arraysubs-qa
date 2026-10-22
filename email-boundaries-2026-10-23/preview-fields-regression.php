<?php
defined('ABSPATH') || exit;
$checks = [];
$settings = get_option('arraysubs_settings');
$plugins = get_option('active_plugins');
$block = static fn() => false;
add_filter('pre_wp_mail', $block, PHP_INT_MAX);
$check = static function ($pass, $name) use (&$checks) { $checks[] = ['check' => $name, 'passed' => (bool) $pass]; };
$id = 0;
try {
    $mailer = WC()->mailer();
    $reminder = $mailer->emails['arraysubs_renewal_reminder'];
    $card = $mailer->emails['arraysubs_card_expiring'];
    $reminder_html = $reminder->get_content_html();
    $reminder_plain = $reminder->get_content_plain();
    $card_html = $card->get_content_html();
    $card_plain = $card->get_content_plain();
    $check(str_contains($reminder_plain, 'Next Payment Date:') && !str_contains($reminder_plain, "\n: \n"), 'Reminder preview has a date label and value');
    $check(str_contains($reminder_plain, arraysubs_format_timestamp_local(time() + 3 * DAY_IN_SECONDS, get_option('date_format'))), 'Reminder sample date matches its three-day notice');
    $check(str_contains($reminder_html, 'Next Payment Date'), 'HTML reminder preview includes the shared date label');
    $check(str_contains($card_html, '4242') && str_contains($card_plain, '4242'), 'HTML/plain card previews contain sample card digits');
    $check(!str_contains($card_plain, 'expiring on soon'), 'Card preview uses a sample month/year');
    $id = wp_insert_post(['post_type' => 'arraysubs_data', 'post_status' => 'draft', 'post_title' => 'QA91 final card field', 'meta_input' => ['_customer_email' => 'qa91-card@example.invalid', '_payment_method_last4' => '9876', '_payment_method_expiry_month' => 12, '_payment_method_expiry_year' => 2030]]);
    $card->trigger($id);
    $check(str_contains($card->get_content_plain(), '9876') && str_contains($card->get_content_plain(), '12/2030'), 'Real subscription card metadata replaces all preview samples');
    delete_post_meta($id, '_payment_method_last4');
    $card->trigger($id);
    $check(str_contains($card->get_content_plain(), 'N/A') && !str_contains($card->get_content_plain(), '4242'), 'Missing real card digits remain explicit without sample leakage');
    file_put_contents(__DIR__ . '/preview-fields-card.html', $card_html);
    file_put_contents(__DIR__ . '/preview-fields-reminder.html', $reminder_html);
} finally {
    if ($id) { wp_delete_post($id, true); }
    remove_filter('pre_wp_mail', $block, PHP_INT_MAX);
}
echo wp_json_encode(['checks' => $checks, 'deleted_subscription' => $id, 'fixture_deleted' => !get_post($id), 'settings_unchanged' => get_option('arraysubs_settings') === $settings, 'plugins_unchanged' => get_option('active_plugins') === $plugins], JSON_PRETTY_PRINT) . PHP_EOL;
if (array_filter($checks, static fn($check) => !$check['passed'])) { WP_CLI::halt(1); }
