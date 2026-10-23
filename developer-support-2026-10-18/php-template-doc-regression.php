<?php
defined('ABSPATH') || exit;
/** Executes the guide's PHP snippets without persisting fixture/configuration changes. */

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    ++$checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$path = dirname(__DIR__, 2) . '/documentations/architecture/developer-php-templates.md';
$source = file_get_contents($path);
preg_match_all('/```php\n(.*?)\n```/s', $source, $matches);
$snippets = array_map(static fn($snippet) => preg_replace('/^<\?php\s*/', '', $snippet), $matches[1]);
$assert(count($snippets) === 3, 'Guide contains exactly the three documented PHP approaches');
$subscription_id = 12479;
$user_id = 256;
$subscription = arraysubs_get_subscription($subscription_id);
$assert($subscription instanceof WP_Post && (int) get_post_meta($subscription_id, '_customer_id', true) === $user_id, 'Read-only fixture identity exists');
$product_id = (int) get_post_meta($subscription_id, '_product_id', true);
$before = ['subscription' => get_post_meta($subscription_id), 'product' => get_post_meta($product_id), 'user' => get_user_meta($user_id), 'actor' => get_current_user_id()];
$settings = arraysubs_get_settings();
$settings_filter = static function () use (&$settings) { return $settings; };
$old_post = $GLOBALS['post'] ?? null;
$old_shortcodes = $GLOBALS['shortcode_tags'];
$partial_calls = 0;
$observe_partial = static function () use (&$partial_calls): void { ++$partial_calls; };
$run = static function (string $snippet): string {
    ob_start();
    try {
        eval($snippet);
        return ob_get_contents();
    } finally {
        ob_end_clean();
    }
};
add_filter('pre_option_arraysubs_settings', $settings_filter);
add_action('get_template_part_template-parts/premium-resource', $observe_partial);
try {
    $settings['members_access']['enabled'] = true;
    $assert(arraysubs_is_members_access_enabled(), 'Runtime enabled setting is available');
    $assert(shortcode_exists('arraysubs_restrict'), 'Shortcode has initialized at init');
    $expected = arraysubs_user_meets_conditions(get_current_user_id(), ['rules' => [['type' => 'subscription_status', 'statuses' => ['active', 'trial']]]]);
    $output = $run($snippets[0]);
    $assert(str_contains($output, 'Premium resource content.') === $expected, 'Executed shortcode PHP snippet renders only its allowed branch');
    $custom = str_replace("'product_ids' => [123]", "'product_ids' => [" . $product_id . ']',$snippets[1]);
    $expected_custom = is_user_logged_in() && arraysubs_user_meets_conditions(get_current_user_id(), ['rules' => [['type' => 'subscription_status', 'statuses' => ['active', 'trial']], ['type' => 'has_active_subscription', 'product_ids' => [$product_id]]]]);
    $output = $run($custom);
    $assert(str_contains($output, 'Premium resource content.') === $expected_custom, 'Executed custom-condition PHP snippet agrees with the explicit rules');
    if (get_current_user_id() === $user_id) {
        $assert($expected && $expected_custom, 'Fixture owner passes the documented subscription and product clauses');
    }

    $assert(!locate_template('template-parts/premium-resource.php'), 'Sample partial is absent, so the read-only test cannot execute an unrelated theme generator');
    $GLOBALS['post'] = get_post($product_id);
    $expected_post = arraysubs_current_user_can_access($product_id);
    $output = $run($snippets[2]);
    $assert($partial_calls === ($expected_post ? 1 : 0), 'Executed saved-post example calls the custom partial only for an allowed post');
    $assert($expected_post || str_contains($output, 'arraysubs-restricted-content'), 'Denied saved-post example prints the existing formatted message');
    $GLOBALS['post'] = null;
    $count = $partial_calls;
    $assert($run($snippets[2]) === '' && $partial_calls === $count, 'Saved-post example withholds output for a missing current post');

    remove_shortcode('arraysubs_restrict');
    $assert($run($snippets[0]) === '', 'Shortcode example fails closed when registration is absent');
    $GLOBALS['shortcode_tags'] = $old_shortcodes;
    $settings['members_access']['enabled'] = false;
    foreach ($snippets as $snippet) {
        $assert($run($snippet) === '', 'Each PHP approach fails closed while Members Access is disabled');
    }
    $assert(function_exists('arraysubs_user_meets_conditions'), 'Helper existence alone does not indicate enabled protection');
    $settings['members_access']['enabled'] = true;

    $unrestricted = do_shortcode('[arraysubs_restrict]DOC_PUBLIC_MARKER[/arraysubs_restrict]');
    $assert(str_contains($unrestricted, 'DOC_PUBLIC_MARKER'), 'No-clause shortcode intentionally returns its content');
    $admin_default = do_shortcode('[arraysubs_restrict roles="doc-role-does-not-exist"]DOC_ADMIN_MARKER[/arraysubs_restrict]');
    $assert(str_contains($admin_default, 'DOC_ADMIN_MARKER') === current_user_can('manage_options'), 'Default administrator bypass agrees with manage_options');
    $login_prerequisite = do_shortcode('[arraysubs_restrict access_version="2" login_required="true" status="cancelled" roles="doc-role-does-not-exist" show_to_admins="false"]DOC_LOGIN_MARKER[/arraysubs_restrict]');
    $assert(!str_contains($login_prerequisite, 'DOC_LOGIN_MARKER'), 'login_required=true preserves the other clauses; an unmatched role still denies access');
    $assert(arraysubs_user_meets_conditions(0, ['rules' => []]), 'Empty custom rules grant even to a guest');
    $assert(!arraysubs_user_meets_conditions($user_id, ['rules' => [['type' => 'doc-unknown-rule']]]), 'Unknown rule types fail');
    $assert(!arraysubs_user_meets_conditions($user_id, ['rules' => [['type' => 'has_active_subscription', 'product_ids' => [(string) $product_id]]]]), 'Direct product IDs must be integer values');
    $assert(arraysubs_resolve_restricted_message('{site_name}') === '{site_name}', 'Plain resolver keeps merge placeholders literal');
    $denial = do_shortcode('[arraysubs_restrict roles="doc-role-does-not-exist" show_to_admins="false" message="{site_name}"]DOC_DENIED_MARKER[/arraysubs_restrict]');
    $assert(str_contains($denial, '{site_name}') && !str_contains($denial, 'DOC_DENIED_MARKER'), 'Shortcode denial preserves placeholders and withholds enclosed content');
    $settings['members_access']['default_message'] = '{site_name} {login_link} {pricing_link}';
    $formatted = arraysubs_get_restricted_message($product_id);
    $assert(str_contains($formatted, 'arraysubs-restricted-post') && !str_contains($formatted, '{site_name}') && !str_contains($formatted, '{login_link}'), 'Saved-post message replaces merge tags and already includes wrappers');

    foreach ($snippets as $snippet) {
        $program = '$snippet=' . var_export($snippet, true) . ';ob_start();eval($snippet);$output=ob_get_clean();exit($output === "" ? 0 : 1);';
        $process = proc_open([PHP_BINARY, '-r', $program], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $assert(proc_close($process) === 0 && $stdout === '' && $stderr === '', 'Exact PHP snippet fails closed in a clean PHP process without WordPress/ArraySubs functions');
    }
    $assert(get_post_meta($subscription_id) === $before['subscription'], 'Guide checks preserve fixture subscription metadata');
    $assert(get_post_meta($product_id) === $before['product'], 'Guide checks preserve fixture product metadata');
    $assert(get_user_meta($user_id) === $before['user'], 'Guide checks preserve fixture user metadata');
    $assert(get_current_user_id() === $before['actor'], 'Guide snippets never switch the logged-in actor');
    WP_CLI::success($checks . ' PHP-template documentation assertions passed for actor ' . get_current_user_id() . '; fixtures unchanged.');
} finally {
    remove_filter('pre_option_arraysubs_settings', $settings_filter);
    remove_action('get_template_part_template-parts/premium-resource', $observe_partial);
    $GLOBALS['post'] = $old_post;
    $GLOBALS['shortcode_tags'] = $old_shortcodes;
}
