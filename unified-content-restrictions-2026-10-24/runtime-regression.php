<?php
/**
 * Supplemental runtime coverage for the real-browser QA recorded in this folder.
 * Run through WP-CLI eval-file with --allow-root. Optional first argument: user ID.
 * Uses read-only metadata/option filters; does not save settings or content.
 */
defined('ABSPATH') || exit;

use ArraySubs\Features\MembersAccess\Services\ConditionEvaluator;
use ArraySubs\Features\MembersAccess\Services\ContentGating;
use ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig;
use ArraySubs\Features\MembersAccess\Services\PostRestrictionGate;
use ArraySubs\Features\GutenbergIntegration\Services\Hooks as GutenbergHooks;
use ArraySubs\Features\ElementorIntegration\Services\Hooks as ElementorHooks;

if (!method_exists(PostRestrictionConfig::class, 'message')) {
    WP_CLI::error('The unified restriction backend must be installed before this script runs.');
}

$checks = [];
$assert = static function ($actual, $expected, string $label) use (&$checks): void {
    $checks[] = ['passed' => $actual === $expected, 'check' => $label, 'actual' => $actual, 'expected' => $expected];
};
$original_user = get_current_user_id();
$fixtures = is_file(__DIR__ . '/fixtures.json') ? json_decode(file_get_contents(__DIR__ . '/fixtures.json'), true) : [];
$admin_ids = get_users(['capability' => 'manage_options', 'number' => 1, 'fields' => 'ID']);
$admin_id = (int) ($admin_ids[0] ?? 1);
$customer_id = absint($args[0] ?? $fixtures['customer'] ?? 224);
$member_id = absint($fixtures['member'] ?? 224);
if (!$customer_id || !get_userdata($customer_id) || user_can($customer_id, 'manage_options')) {
    $customer_ids = get_users(['role' => 'customer', 'number' => 1, 'fields' => 'ID']);
    $customer_id = (int) ($customer_ids[0] ?? 0);
}
if (!$customer_id || !get_userdata($admin_id)) {
    WP_CLI::error('Runtime coverage requires an existing administrator and non-admin customer.');
}
$customer_role = get_userdata($customer_id)->roles[0];
$never_role = 'qa_unified_restriction_missing_role';
$marker = 'QA_UNIFIED_PRIVATE_CONTENT';
$default_message = '<p>QA shared <strong>default</strong> <a href="https://example.test/join">Join</a></p>';
$custom_message = '<p>QA <em>custom</em> <a href="https://example.test/custom">Custom link</a></p>';
$settings_before = get_option('arraysubs_settings', []);
$test_user_ids = array_unique([$admin_id, $customer_id, $member_id]);
$metadata_before = array_map('get_user_meta', $test_user_ids);

$option_filter = static function ($settings) use (&$default_message) {
    $settings = is_array($settings) ? $settings : [];
    $settings['members_access']['default_message'] = $default_message;
    return $settings;
};
// Prevent the zero-spend boundary check from warming WooCommerce user metadata.
$spend_filter = static fn() => '0';
// Nonexistent-feature cases need no historical holdings and must not save a cache.
$empty_history = static fn() => [];
add_filter('option_arraysubs_settings', $option_filter);
add_filter('woocommerce_customer_get_total_spent', $spend_filter);
foreach ($test_user_ids as $id) {
    add_filter('pre_transient_arraysubs_purchase_history_items_' . $id, $empty_history);
}

$elementor = (new ReflectionClass(ElementorHooks::class))->newInstanceWithoutConstructor();
$elementor_apply = new ReflectionMethod(ElementorHooks::class, 'applyRestriction');
$elementor_apply->setAccessible(true);
$members_hooks = (new ReflectionClass(\ArraySubs\Features\MembersAccess\Services\Hooks::class))->newInstanceWithoutConstructor();

$elementor_settings = static function (array $config): array {
    $map = [
        'status' => 'status', 'products' => 'products', 'variations' => 'variations',
        'purchased' => 'purchased', 'roles' => 'roles', 'lifetimeSpent' => 'lifetime_spent',
        'feature' => 'feature', 'featureMatch' => 'feature_match', 'featureValue' => 'feature_value',
        'featureOp' => 'feature_op', 'aggregation' => 'aggregation', 'condition' => 'condition',
        'message' => 'message', 'accessVersion' => 'access_version',
    ];
    $settings = [
        'arraysubs_enable' => !empty($config['enabled']) ? 'yes' : '',
        'arraysubs_login_required' => !empty($config['loginRequired']) ? 'yes' : '',
        'arraysubs_show_to_admins' => !empty($config['showToAdmins']) ? 'yes' : '',
        'arraysubs_custom_message_enabled' => !empty($config['useDefaultMessage']) ? '' : 'yes',
    ];
    foreach ($map as $from => $to) {
        $settings['arraysubs_' . $to] = $config[$from];
    }
    return $settings;
};
$shortcode_atts = static function (array $config): array {
    $feature = array_values(array_filter(PostRestrictionConfig::buildConditions($config)['rules'], static fn(array $rule): bool => $rule['type'] === 'feature_value'));
    $feature = $feature[0] ?? [];
    return [
        'status' => implode(',', $config['status']),
        'products' => implode(',', $config['products']),
        'variations' => implode(',', $config['variations']),
        'purchased' => implode(',', $config['purchased']),
        'roles' => implode(',', $config['roles']),
        'lifetime_spent' => $config['lifetimeSpent'],
        'feature' => $config['feature'],
        'feature_min' => (string) ($feature['value'] ?? ''),
        'feature_op' => $feature['operator'] ?? '>=',
        'aggregation' => $feature['aggregation'] ?? 'sum',
        'condition' => $config['condition'],
        'login_required' => !empty($config['loginRequired']) ? 'true' : 'false',
        'show_to_admins' => !empty($config['showToAdmins']) ? 'true' : 'false',
        'message' => PostRestrictionConfig::message($config),
        'prerendered' => 'true',
        'access_version' => '2',
    ];
};
$renderers = [
    'Gutenberg' => static fn(array $config): string => GutenbergHooks::gate($config, $marker),
    'Elementor' => static fn(array $config): string => $elementor_apply->invoke($elementor, $elementor_settings($config), $marker),
    'Shortcode' => static fn(array $config): string => $members_hooks->restrictContentShortcode($shortcode_atts($config), $marker),
];

try {
    $assert(PostRestrictionConfig::defaults()['useDefaultMessage'], true, 'Shared default message is the initial choice');
    $assert(PostRestrictionConfig::defaults()['loginRequired'], false, 'Empty configuration starts with optional login');
    $assert(PostRestrictionConfig::defaults()['showToAdmins'], true, 'Administrator bypass defaults on');

    $cases = [
        ['Public empty gate', [], true, true],
        ['Login only', ['loginRequired' => true], false, true],
        ['Unmatched role', ['roles' => [$never_role]], false, false],
        ['Matching role', ['roles' => [$customer_role]], false, true],
        ['Login never overrides unmatched role AND', ['loginRequired' => true, 'roles' => [$never_role], 'condition' => 'and'], false, false],
        ['Login never overrides unmatched role OR', ['loginRequired' => true, 'roles' => [$never_role], 'condition' => 'or'], false, false],
        ['Role OR failing status', ['roles' => [$customer_role], 'status' => ['qa-missing-status'], 'condition' => 'or'], false, true],
        ['Role AND failing status', ['roles' => [$customer_role], 'status' => ['qa-missing-status'], 'condition' => 'and'], false, false],
        ['Two failing clauses OR', ['roles' => [$never_role], 'status' => ['qa-missing-status'], 'condition' => 'or'], false, false],
        ['Unmatched product', ['products' => [2147483000]], false, false],
        ['Unmatched variation', ['variations' => [2147483000]], false, false],
        ['Unpurchased product', ['purchased' => [2147483000]], false, false],
        ['Unmatched feature', ['feature' => 'QA missing feature'], false, false],
        ['Zero spend is an applied rule', ['lifetimeSpent' => '0'], false, true],
        ['Positive spend boundary', ['lifetimeSpent' => '0.01'], false, false],
        ['Feature child fields without feature are ignored', ['featureValue' => '20', 'featureMatch' => 'min_total'], true, true],
    ];
    foreach ($cases as [$label, $input, $guest_expected, $customer_expected]) {
        $config = PostRestrictionConfig::sanitize(array_merge(['enabled' => true, 'showToAdmins' => false], $input));
        foreach ([0 => $guest_expected, $customer_id => $customer_expected] as $user_id => $expected) {
            wp_set_current_user($user_id);
            ConditionEvaluator::clearCache();
            $assert(PostRestrictionGate::evaluate($user_id, $config), $expected, $label . ': per-post user ' . $user_id);
            foreach ($renderers as $name => $render) {
                $assert(strpos($render($config), $marker) !== false, $expected, $label . ': ' . $name . ' user ' . $user_id);
            }
        }
    }

    // Exercise a real existing subscriber and its plan; feature fixtures affect reads only.
    $subscriptions = arraysubs_get_customer_subscriptions($member_id, ['arraysubs-active', 'arraysubs-trial']);
    $subscription_id = (int) ($subscriptions[0] ?? 0);
    if (!$subscription_id) {
        throw new RuntimeException('Member fixture needs an existing active/trial subscription.');
    }
    $product_id = absint(get_post_meta($subscription_id, '_product_id', true));
    $variation_id = absint(get_post_meta($subscription_id, '_variation_id', true));
    $feature_product_id = arraysubs_get_subscription_effective_product_id($subscription_id);
    $feature_meta_filter = static function ($value, $object_id, $key) use ($feature_product_id) {
        if ((int) $object_id !== $feature_product_id || $key !== '_arraysubs_features') {
            return $value;
        }
        return [wp_json_encode([
            ['name' => 'QA Seats', 'type' => 'number', 'value' => '5', 'enabled' => true],
            ['name' => 'QA Flag', 'type' => 'toggle', 'value' => 'Yes', 'enabled' => true],
        ])];
    };
    add_filter('get_post_metadata', $feature_meta_filter, 10, 3);
    try {
        $member_cases = [
            ['Real active status', ['status' => [preg_replace('/^arraysubs-/', '', get_post_status($subscription_id))]], true],
            ['Real subscribed product', ['products' => [$product_id]], true],
            ['Real subscription AND impossible role', ['products' => [$product_id], 'roles' => [$never_role], 'condition' => 'and'], false],
            ['Real subscription OR impossible role', ['products' => [$product_id], 'roles' => [$never_role], 'condition' => 'or'], true],
            ['Feature presence', ['feature' => 'QA Flag', 'featureMatch' => 'has'], true],
            ['Feature total equals boundary', ['feature' => 'QA Seats', 'featureMatch' => 'min_total', 'featureValue' => '5'], true],
            ['Feature total below boundary', ['feature' => 'QA Seats', 'featureMatch' => 'min_total', 'featureValue' => '6'], false],
            ['Feature single equals boundary', ['feature' => 'QA Seats', 'featureMatch' => 'min_single', 'featureValue' => '5'], true],
            ['Feature single below boundary', ['feature' => 'QA Seats', 'featureMatch' => 'min_single', 'featureValue' => '6'], false],
            ['Feature custom equal', ['feature' => 'QA Seats', 'featureMatch' => 'custom', 'featureOp' => '=', 'featureValue' => '5', 'aggregation' => 'max'], true],
            ['Feature custom greater', ['feature' => 'QA Seats', 'featureMatch' => 'custom', 'featureOp' => '>', 'featureValue' => '5', 'aggregation' => 'sum'], false],
            ['Feature missing AND real product', ['feature' => 'QA missing feature', 'products' => [$product_id], 'condition' => 'and'], false],
            ['Feature missing OR real product', ['feature' => 'QA missing feature', 'products' => [$product_id], 'condition' => 'or'], true],
        ];
        if ($variation_id) {
            $member_cases[] = ['Real subscribed variation', ['variations' => [$variation_id]], true];
        }
        wp_set_current_user($member_id);
        foreach ($member_cases as [$label, $input, $expected]) {
            $config = PostRestrictionConfig::sanitize(array_merge(['enabled' => true, 'showToAdmins' => false, 'loginRequired' => true], $input));
            ConditionEvaluator::clearCache();
            $assert(PostRestrictionGate::evaluate($member_id, $config), $expected, $label . ': per-post');
            foreach ($renderers as $name => $render) {
                $assert(strpos($render($config), $marker) !== false, $expected, $label . ': ' . $name);
            }
        }
    } finally {
        remove_filter('get_post_metadata', $feature_meta_filter, 10);
        ConditionEvaluator::clearCache();
    }

    foreach (['and', 'or'] as $logic) {
        foreach ([false, true] as $admin_bypass) {
            $config = PostRestrictionConfig::sanitize([
                'enabled' => true, 'roles' => [$never_role], 'loginRequired' => true,
                'condition' => $logic, 'showToAdmins' => $admin_bypass,
            ]);
            wp_set_current_user($admin_id);
            $assert(PostRestrictionGate::evaluate($admin_id, $config), $admin_bypass, 'Admin bypass ' . (int) $admin_bypass . ': per-post ' . $logic);
            foreach ($renderers as $name => $render) {
                $assert(strpos($render($config), $marker) !== false, $admin_bypass, 'Admin bypass ' . (int) $admin_bypass . ': ' . $name . ' ' . $logic);
            }
        }
    }

    // The UI locks login whenever a real condition is configured; the server must agree.
    foreach ([
        ['status' => ['active']], ['products' => [10]], ['variations' => [10]],
        ['purchased' => [10]], ['roles' => [$customer_role]], ['lifetimeSpent' => '0'],
        ['feature' => 'Seats'],
    ] as $index => $input) {
        $config = PostRestrictionConfig::sanitize(array_merge(['enabled' => true, 'loginRequired' => false], $input));
        $assert($config['loginRequired'], true, 'Condition ' . $index . ' forces login on the server');
        $tree = PostRestrictionConfig::buildConditions($config);
        $assert(count($tree['rules']), 1, 'Condition ' . $index . ' produces exactly one match clause');
        $assert(in_array('user_login_status', array_column($tree['rules'], 'type'), true), false, 'Condition ' . $index . ' keeps login outside match logic');
    }
    $cleared = PostRestrictionConfig::sanitize(['enabled' => true, 'loginRequired' => false]);
    $assert($cleared['loginRequired'], false, 'Cleared conditions permit login to be switched off');
    $assert(PostRestrictionConfig::isActive($cleared), false, 'Empty enabled per-post configuration is inactive');
    $assert(PostRestrictionConfig::isActive(PostRestrictionConfig::sanitize(['enabled' => false, 'roles' => [$never_role]])), false, 'Disabled gate remains inactive with saved conditions');

    wp_set_current_user($customer_id);
    $disabled = PostRestrictionConfig::sanitize(['enabled' => false, 'roles' => [$never_role], 'showToAdmins' => false]);
    foreach (['Gutenberg', 'Elementor'] as $name) {
        $assert(strpos($renderers[$name]($disabled), $marker) !== false, true, $name . ' disabled gate preserves content');
    }

    // Match normalization must remain fail-closed; login cannot rescue an invalid AND group.
    foreach (['AND', ' and ', '', 'xor', null, [], 1] as $logic) {
        $config = PostRestrictionConfig::sanitize([
            'enabled' => true, 'roles' => [$customer_role], 'status' => ['qa-missing-status'],
            'condition' => $logic, 'loginRequired' => true, 'showToAdmins' => false,
        ]);
        $assert($config['condition'], 'and', 'Unknown/all condition logic normalizes: ' . wp_json_encode($logic));
        $assert(PostRestrictionGate::evaluate($customer_id, $config), false, 'Unknown/all condition logic enforces AND: ' . wp_json_encode($logic));
    }
    foreach (['OR', ' Or ', 'or'] as $logic) {
        $config = PostRestrictionConfig::sanitize([
            'enabled' => true, 'roles' => [$customer_role], 'status' => ['qa-missing-status'],
            'condition' => $logic, 'showToAdmins' => false,
        ]);
        $assert($config['condition'], 'or', 'OR condition logic normalizes: ' . $logic);
        foreach ($renderers as $name => $render) {
            $assert(strpos($render($config), $marker) !== false, true, $name . ' normalized OR accepts matching role: ' . $logic);
        }
    }
    $evaluator = new ConditionEvaluator();
    foreach ([['type' => 'qa_unknown_condition'], 'not-an-array'] as $rule) {
        $assert($evaluator->evaluate($customer_id, ['logic' => 'or', 'rules' => [$rule]]), false, 'Unknown/malformed evaluator rule denies: ' . wp_json_encode($rule));
    }
    $assert($evaluator->evaluate($customer_id, ['logic' => 'AND', 'rules' => [[
        'type' => 'group', 'logic' => 'OR', 'rules' => [
            ['type' => 'user_role', 'roles' => [$customer_role]],
            ['type' => 'user_role', 'roles' => [$never_role]],
        ],
    ]]]), true, 'Nested groups normalize match logic independently');

    wp_set_current_user(0);
    $blank_messages = ['', '  ', '<p><br></p>', '<p>&nbsp; </p>', "\xc2\xa0", '<!-- empty -->'];
    $message_cases = [[true, $custom_message, $default_message], [false, $custom_message, $custom_message]];
    foreach ($blank_messages as $blank) {
        $message_cases[] = [false, $blank, $default_message];
    }
    foreach ($message_cases as $index => [$use_default, $message, $expected]) {
        $config = PostRestrictionConfig::sanitize([
            'enabled' => true, 'loginRequired' => true, 'showToAdmins' => false,
            'useDefaultMessage' => $use_default, 'message' => $message,
        ]);
        $assert($config['message'], $message, 'Message ' . $index . ' retains saved custom text');
        $assert(PostRestrictionConfig::message($config), $expected, 'Message ' . $index . ' resolves default/custom');
        foreach ($renderers as $name => $render) {
            $output = $render($config);
            $assert(strpos($output, $expected) !== false, true, 'Message ' . $index . ': ' . $name . ' shows selected message');
            $assert(strpos($output, $marker) !== false, false, 'Message ' . $index . ': ' . $name . ' withholds private content');
        }
    }
    $config = PostRestrictionConfig::sanitize(['useDefaultMessage' => true, 'message' => $custom_message]);
    $default_message = '<p>Changed QA shared message</p>';
    $assert(PostRestrictionConfig::message($config), $default_message, 'Default follows later global edits while custom text is retained');
    $default_message = '<p>&nbsp;<br></p>';
    $assert(PostRestrictionConfig::message($config), __('This content is restricted. Please subscribe to access.', 'arraysubs'), 'Empty global rich HTML uses built-in fallback');
    $default_message = '<p>QA shared <strong>default</strong> <a href="https://example.test/join">Join</a></p>';
    $unsafe = '<p onclick="bad()"><strong>Safe</strong> <em>HTML</em><a href="javascript:bad()">Link</a></p><iframe src="x"></iframe>';
    $safe_config = PostRestrictionConfig::sanitize(['useDefaultMessage' => false, 'message' => $unsafe]);
    $safe = PostRestrictionConfig::message($safe_config);
    $assert((bool) preg_match('/onclick=|javascript:|<iframe/i', $safe), false, 'Custom message sanitizes events, unsafe links and iframes');
    $assert(strpos($safe, '<strong>Safe</strong>') !== false && strpos($safe, '<em>HTML</em>') !== false, true, 'Custom message preserves safe formatting');
    $assert(PostRestrictionConfig::message(PostRestrictionConfig::sanitize(['useDefaultMessage' => false, 'message' => '0'])), '0', 'Zero is meaningful custom message text');

    // Whole-page, purchase and comment message adapters must honor the same switch.
    $post_id = 2147483000;
    $override_config = [];
    $post_meta_filter = static function ($value, $object_id, $key) use ($post_id, &$override_config) {
        return (int) $object_id === $post_id && $key === PostRestrictionConfig::META_CONFIG
            ? [wp_json_encode($override_config)] : $value;
    };
    add_filter('get_post_metadata', $post_meta_filter, 10, 3);
    try {
        $shop = (new ReflectionClass(\ArraySubs\Features\MembersAccess\Services\EcommerceRestriction::class))->newInstanceWithoutConstructor();
        $shop_method = new ReflectionMethod(get_class($shop), 'getPostRestrictionRule');
        $shop_method->setAccessible(true);
        foreach ([true, false] as $use_default) {
            $override_config = PostRestrictionConfig::sanitize([
                'enabled' => true, 'loginRequired' => true, 'showToAdmins' => false,
                'useDefaultMessage' => $use_default, 'message' => $custom_message,
            ]);
            $expected = $use_default ? $default_message : $custom_message;
            $assert(strpos((new ContentGating())->getRestrictedMessage($post_id), $expected) !== false, true, 'Post content message adapter default=' . (int) $use_default);
            $shop_rule = $shop_method->invoke($shop, $post_id);
            $assert(arraysubs_resolve_rule_restricted_message($shop_rule), $expected, 'Purchase message adapter default=' . (int) $use_default);
            $comments = (new ReflectionClass(\ArraySubs\Features\MembersAccess\Services\CommentRestriction::class))->newInstanceWithoutConstructor();
            $comment_rule = $comments->getRestrictionForPost($post_id, 0);
            $assert(arraysubs_resolve_rule_restricted_message($comment_rule), $expected, 'Comment message adapter default=' . (int) $use_default);
        }
        $override_config = $disabled;
        $assert(PostRestrictionGate::decide($customer_id, $post_id), null, 'Disabled per-post gate yields to surrounding access rules');
    } finally {
        remove_filter('get_post_metadata', $post_meta_filter, 10);
    }
} finally {
    wp_set_current_user($original_user);
    remove_filter('option_arraysubs_settings', $option_filter);
    remove_filter('woocommerce_customer_get_total_spent', $spend_filter);
    foreach ($test_user_ids as $id) {
        remove_filter('pre_transient_arraysubs_purchase_history_items_' . $id, $empty_history);
    }
    ConditionEvaluator::clearCache();
}

$assert(get_option('arraysubs_settings', []) === $settings_before, true, 'Runtime assertions leave saved settings unchanged');
$assert(array_map('get_user_meta', $test_user_ids) === $metadata_before, true, 'Runtime assertions leave user metadata unchanged');
$passed = count(array_filter($checks, static fn(array $check): bool => $check['passed']));
$result = [
    'passed' => $passed, 'total' => count($checks),
    'admin_id' => $admin_id, 'customer_id' => $customer_id,
    'pro_loaded' => class_exists('ArraySubsPro', false), 'checks' => $checks,
];
echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if ($passed !== count($checks)) {
    WP_CLI::error('Unified content restriction runtime regression failed.');
}
