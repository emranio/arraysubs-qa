<?php
/** Run with the local site's WP-CLI eval-file command. */
defined('ABSPATH') || exit;

$checks = [];
$check = static function ($condition, $name) use (&$checks) {
    $checks[] = ['passed' => (bool) $condition, 'check' => $name];
};
$global = '<p>QA shared <strong>default</strong> <a href="https://example.test/join">Join</a></p>';
// Read the real option once, then vary the runtime default without saving settings.
$override = static function ($value) use (&$global) {
    $value['members_access']['default_message'] = $global;
    return $value;
};
add_filter('option_arraysubs_settings', $override);

foreach ([
    ['custom_message_enabled' => false, 'message' => '<p>Ignored</p>'],
    ['message' => '<p>No switch</p>'],
    ['custom_message_enabled' => true, 'message' => ''],
    ['custom_message_enabled' => true, 'message' => '<p><br></p>'],
    ['custom_message_enabled' => true, 'message' => '<p>&nbsp; </p>'],
    ['custom_message_enabled' => 'false', 'message' => '<p>Ignored string false</p>'],
] as $i => $rule) {
    $check(arraysubs_resolve_rule_restricted_message($rule) === $global, 'Shared default for disabled/blank case ' . $i);
}
$custom = '<p>QA <strong>custom</strong> <a href="https://example.test/custom">Custom link</a></p>';
$rule = ['custom_message_enabled' => true, 'message' => $custom];
$check(arraysubs_resolve_rule_restricted_message($rule) === $custom, 'Enabled rich HTML and absolute link preserved');
$check(strpos(arraysubs_resolve_rule_restricted_message(['custom_message_enabled' => true, 'message' => '<p onclick="bad()">Safe<script>bad()</script></p>']), '<script') === false, 'Custom output removes unsafe script markup');
$global = '<p>Changed shared message</p>';
$check(arraysubs_resolve_rule_restricted_message(['custom_message_enabled' => false, 'message' => $custom]) === $global, 'Disabled custom follows later global edits');
$global = '<p>&nbsp;</p>';
$check(strpos(arraysubs_resolve_rule_restricted_message([]), 'This content is restricted.') !== false, 'Empty shared message uses built-in fallback');
$global = '<p>QA shared <strong>default</strong> <a href="https://example.test/join">Join</a></p>';

$product = new WC_Product_Simple();
$product->set_name('QA product <em>literal</em>');
$limit = new \ArraySubs\Features\MembersAccess\Services\PurchaseLimit();
$check($limit->formatLimitMessage(['custom_message_enabled' => false, 'message' => 'Ignored'], $product, 3) === $global, 'Purchase limits use shared default');
$check(strpos($limit->formatLimitMessage(['custom_message_enabled' => true, 'message' => 'Limit {max}: {product}'], $product, 3), 'Limit 3: QA product') !== false, 'Purchase limit placeholders rendered');
$check(strpos($limit->formatLimitMessage(['custom_message_enabled' => true, 'message' => '{product}'], $product, 3), '<em>') === false, 'Purchase limit product name escaped');

$controller = new \ArraySubs\Features\MembersAccess\REST\RestrictionController(false);
foreach (['url_rules', 'cpt_rules', 'ecommerce_rules', 'comment_rules', 'purchase_limit_rules'] as $key) {
    foreach ([true, false, 'false', '0', '1'] as $flag) {
        $sanitized = $controller->sanitizeSettings([$key => [['id' => 'qa', 'enabled' => true, 'message' => $custom, 'custom_message_enabled' => $flag]]]);
        $saved = $sanitized[$key][0];
        $check(is_bool($saved['custom_message_enabled']) && $saved['custom_message_enabled'] === rest_sanitize_boolean($flag), $key . ': switch sanitized as boolean ' . json_encode($flag));
        $check($saved['message'] === $custom, $key . ': switch retains HTML message ' . json_encode($flag));
    }
}

wp_set_current_user(0);
$response = rest_do_request(new WP_REST_Request('GET', '/arraysubs/v1/members-access/settings'));
$check(in_array($response->get_status(), [401, 403], true), 'Anonymous settings access denied');
$request = new WP_REST_Request('POST', '/arraysubs/v1/members-access/settings');
$request->set_header('content-type', 'application/json');
$request->set_body(wp_json_encode(['default_message' => 'Unauthorized']));
$check(in_array(rest_do_request($request)->get_status(), [401, 403], true), 'Anonymous settings write denied');

// Per-post editors are deferred: their existing custom messages must still work.
// Override reads only, without changing product/post metadata in the database.
$post_config = array_merge(\ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig::defaults(), [
    'enabled' => true, 'message' => $custom, 'useDefaultMessage' => false, 'loginRequired' => true,
]);
$post_meta = static function ($value, $object_id, $meta_key) use ($post_config) {
    if (in_array($object_id, [6702, 9239], true) && $meta_key === '_arraysubs_restrict_config') {
        return [wp_json_encode($post_config)];
    }
    return $value;
};
add_filter('get_post_metadata', $post_meta, 10, 3);
$shop = new \ArraySubs\Features\MembersAccess\Services\EcommerceRestriction();
$shop_rule = $shop->getMatchingRule(6702);
$check(($shop_rule['source'] ?? '') === 'post_config', 'Per-product settings still take precedence');
$check(arraysubs_resolve_rule_restricted_message($shop_rule) === $custom, 'Per-product adapter retains its custom message');
$comments = new \ArraySubs\Features\MembersAccess\Services\CommentRestriction();
$comment_rule = $comments->getRestrictionForPost(9239, 0);
$check(is_array($comment_rule) && arraysubs_resolve_rule_restricted_message($comment_rule) === $custom, 'Per-post comment adapter retains its custom message');
$gating = new \ArraySubs\Features\MembersAccess\Services\ContentGating();
$check(strpos($gating->getRestrictedMessage(6702), 'QA <strong>custom</strong>') !== false, 'Per-post content message unchanged');
remove_filter('get_post_metadata', $post_meta, 10);
remove_filter('option_arraysubs_settings', $override);

$result = ['passed' => count(array_filter($checks, static fn($row) => $row['passed'])), 'total' => count($checks), 'checks' => $checks];
echo wp_json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
if ($result['passed'] !== $result['total']) {
    WP_CLI::error('Member Access message regression failed.');
}
