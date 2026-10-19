<?php
/**
 * Phase 1 shared fixtures for the staging Stripe QA run.
 *
 * Usage: wp eval-file <abs>/scripts/setup-phase1-fixtures.php <abs-password-file-outside-qa> --allow-root
 *
 * Creates (only if absent, matched by exact email/slug/title):
 *  - customers sqa-p1-classic / sqa-p1-block / sqa-p1-initfail @example.com
 *    through wc_create_new_customer() with US billing details. Random
 *    passwords go to the password file argument (mode 0600, outside the
 *    publicly served QA folder) and are never printed.
 *  - "SQA Daily Stripe Plan" ($7.31 / day, no trial/fee, unlimited length)
 *    through the shipped Quick Product REST route as the administrator.
 *  - "SQA Classic Checkout" page containing [woocommerce_checkout].
 *  - "SQA Members Daily Content" post restricted (per-post config) to an
 *    active subscription of the daily product.
 * Prints the resulting IDs as JSON.
 */

$pw_file = (string) ($args[0] ?? '');
if ('' === $pw_file || false !== strpos($pw_file, '/plugins/qa/')) {
    WP_CLI::error('Pass a password file path outside the QA folder.');
}

$out = ['customers' => [], 'product_id' => 0, 'classic_checkout_page_id' => 0, 'protected_post_id' => 0];
$passwords = [];

$customers = [
    ['login' => 'sqa-p1-classic', 'first' => 'Sqa', 'last' => 'Classic', 'role_in_run' => 'S01 classic success, S02 renewal'],
    ['login' => 'sqa-p1-block', 'first' => 'Sqa', 'last' => 'Block', 'role_in_run' => 'S01 block success, S02 renewal'],
    ['login' => 'sqa-p1-initfail', 'first' => 'Sqa', 'last' => 'Initfail', 'role_in_run' => 'S01 initial payment failure'],
];
foreach ($customers as $c) {
    $email = $c['login'] . '@example.com';
    $existing = get_user_by('email', $email);
    if ($existing) {
        $uid = (int) $existing->ID;
        $created = false;
    } else {
        $password = wp_generate_password(20, false, false);
        $uid = wc_create_new_customer($email, $c['login'], $password, ['first_name' => $c['first'], 'last_name' => $c['last']]);
        if (is_wp_error($uid)) {
            WP_CLI::error('Customer create failed for ' . $email . ': ' . $uid->get_error_message());
        }
        $passwords[$c['login']] = $password;
        $created = true;
    }
    $customer = new WC_Customer($uid);
    $customer->set_first_name($c['first']);
    $customer->set_last_name($c['last']);
    $customer->set_billing_first_name($c['first']);
    $customer->set_billing_last_name($c['last']);
    $customer->set_billing_address_1('100 QA Test Street');
    $customer->set_billing_city('San Francisco');
    $customer->set_billing_state('CA');
    $customer->set_billing_postcode('94105');
    $customer->set_billing_country('US');
    $customer->set_billing_phone('4155550100');
    $customer->set_billing_email($email);
    $customer->save();
    $out['customers'][] = ['id' => $uid, 'login' => $c['login'], 'email' => $email, 'created' => $created, 'use' => $c['role_in_run']];
}

if ($passwords) {
    $existing_pw = file_exists($pw_file) ? (json_decode((string) file_get_contents($pw_file), true) ?: []) : [];
    file_put_contents($pw_file, wp_json_encode(array_merge($existing_pw, $passwords), JSON_PRETTY_PRINT));
    chmod($pw_file, 0600);
}

// Product through the shipped Quick Product route.
$found = get_posts(['post_type' => 'product', 'post_status' => 'any', 'title' => 'SQA Daily Stripe Plan', 'fields' => 'ids', 'posts_per_page' => 1]);
if ($found) {
    $out['product_id'] = (int) $found[0];
    $out['product_created'] = false;
} else {
    wp_set_current_user(1);
    $request = new WP_REST_Request('POST', '/arraysubs/v1/quick-products');
    $request->set_header('Content-Type', 'application/json');
    $request->set_body(wp_json_encode([
        'request_id' => 'sqa-p1-daily-20261018',
        'type' => 'simple',
        'title' => 'SQA Daily Stripe Plan',
        'description' => 'Staging Stripe QA fixture (2026-10-18). Daily plan priced 7.31 USD so each renewal is easy to verify.',
        'virtual' => true,
        'regular_price' => '7.31',
        'settings' => [
            '_subscription_period' => 'day',
            '_subscription_interval' => 1,
            '_subscription_length' => 0,
            '_trial_length' => 0,
            '_trial_period' => 'day',
            '_signup_fee' => '',
        ],
    ]));
    $response = rest_do_request($request);
    $data = $response->get_data();
    if ($response->is_error() || $response->get_status() >= 300) {
        WP_CLI::error('Quick product failed: ' . wp_json_encode($data));
    }
    $out['product_rest_status'] = $response->get_status();
    $out['product_rest_response_keys'] = is_array($data) ? array_keys($data) : null;
    $found = get_posts(['post_type' => 'product', 'post_status' => 'any', 'title' => 'SQA Daily Stripe Plan', 'fields' => 'ids', 'posts_per_page' => 1]);
    $out['product_id'] = (int) ($found[0] ?? 0);
    $out['product_created'] = true;
    wp_set_current_user(0);
}
$product = wc_get_product($out['product_id']);
$out['product'] = $product ? [
    'status' => $product->get_status(),
    'price' => $product->get_price(),
    'virtual' => $product->get_virtual(),
    'meta' => array_intersect_key(get_post_meta($out['product_id']), array_flip(['_is_subscription', '_subscription_period', '_subscription_interval', '_subscription_length', '_trial_length', '_signup_fee'])),
    'permalink' => get_permalink($out['product_id']),
] : null;

// Classic checkout page.
$page = get_page_by_path('sqa-classic-checkout');
if ($page) {
    $out['classic_checkout_page_id'] = (int) $page->ID;
} else {
    $out['classic_checkout_page_id'] = (int) wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => 'SQA Classic Checkout',
        'post_name' => 'sqa-classic-checkout',
        'post_content' => '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
        'post_author' => 1,
    ]);
}
$out['classic_checkout_url'] = get_permalink($out['classic_checkout_page_id']);

// Protected post (per-post restriction: active subscription to the daily product).
$post = get_page_by_path('sqa-members-daily-content', OBJECT, 'post');
if ($post) {
    $out['protected_post_id'] = (int) $post->ID;
} else {
    $out['protected_post_id'] = (int) wp_insert_post([
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_title' => 'SQA Members Daily Content',
        'post_name' => 'sqa-members-daily-content',
        'post_content' => '<!-- wp:paragraph --><p>SQA-ENTITLED-CONTENT-7731: visible only to active SQA Daily Stripe Plan subscribers.</p><!-- /wp:paragraph -->',
        'post_author' => 1,
    ]);
}
$config = \ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig::save($out['protected_post_id'], [
    'enabled' => true,
    'mode' => 'restrict',
    'products' => [$out['product_id']],
    'condition' => 'and',
    'deniedAction' => 'message',
    'message' => 'SQA-DENIED-7731: an active SQA Daily Stripe Plan subscription is required.',
    'loginRequired' => false,
    'showToAdmins' => true,
]);
$out['protected_post_config'] = $config;
$out['protected_post_url'] = get_permalink($out['protected_post_id']);
$out['passwords_written_for'] = array_keys($passwords);

echo wp_json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
