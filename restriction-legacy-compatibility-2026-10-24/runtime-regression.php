<?php
/**
 * Actual-WordPress regression matrix for saved restriction compatibility.
 * Supplemental to this cycle's browser checks. Run with WP-CLI eval-file and
 * --allow-root. Only disposable content/users are written; settings are read.
 */
defined('ABSPATH') || exit;

use ArraySubs\Features\MembersAccess\Services\ConditionEvaluator;
use ArraySubs\Features\MembersAccess\Services\ContentGating;
use ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig as Store;
use ArraySubs\Features\MembersAccess\Services\PostRestrictionGate as Gate;
use ArraySubs\Features\MembersAccess\Services\RestrictionCompatibility as Compatibility;
use ArraySubs\Features\GutenbergIntegration\Services\Hooks as GutenbergHooks;
use ArraySubs\Features\ElementorIntegration\Services\Hooks as ElementorHooks;

$checks = [];
$assert = static function ($actual, $expected, string $label) use (&$checks): void {
    $checks[] = ['passed' => $actual === $expected, 'check' => $label, 'actual' => $actual, 'expected' => $expected];
};
$original_user = get_current_user_id();
$settings_before = get_option('arraysubs_settings');
$plugins_before = get_option('active_plugins');
$post_ids = [];
$customer_id = 0;
$member_id = 224;
$admin_id = 1;
$marker = 'QA_LEGACY_RUNTIME_PRIVATE_CONTENT';
$custom = '<p>QA old <strong>custom message</strong> <a href="https://example.test/legacy">Join</a></p>';
$default = arraysubs_resolve_restricted_message('');
$never_role = 'qa_legacy_runtime_missing_role';
$shortcode_name = 'qa_restriction_legacy_fallback';
$fatal = null;

// Do not let spend/history evaluation warm metadata or transients on real users.
$spend_filter = static fn() => '0';
$empty_history = static fn() => [];
add_filter('woocommerce_customer_get_total_spent', $spend_filter);
add_shortcode($shortcode_name, static fn() => 'QA_EXPANDED_LEGACY_FALLBACK');

$elementor = (new ReflectionClass(ElementorHooks::class))->newInstanceWithoutConstructor();
$elementor_apply = new ReflectionMethod(ElementorHooks::class, 'applyRestriction');
$elementor_apply->setAccessible(true);
$shortcode_handler = $GLOBALS['shortcode_tags']['arraysubs_restrict'];

$elementor_settings = static function (array $raw): array {
    $map = [
        'accessVersion' => 'access_version', 'mode' => 'mode', 'visShow' => 'vis_show',
        'visFallback' => 'vis_fallback', 'status' => 'status', 'roles' => 'roles',
        'products' => 'products', 'variations' => 'variations', 'purchased' => 'purchased',
        'lifetimeSpent' => 'lifetime_spent', 'condition' => 'condition', 'feature' => 'feature',
        'featureMatch' => 'feature_match', 'featureValue' => 'feature_value',
        'featureOp' => 'feature_op', 'aggregation' => 'aggregation', 'message' => 'message',
    ];
    $out = ['arraysubs_enable' => !empty($raw['enabled']) ? 'yes' : ''];
    foreach ($map as $from => $to) {
        if (array_key_exists($from, $raw)) {
            $out['arraysubs_' . $to] = $from === 'accessVersion' ? (string) $raw[$from] : $raw[$from];
        }
    }
    foreach (['loginRequired' => 'login_required', 'showToAdmins' => 'show_to_admins', 'useDefaultMessage' => 'use_default_message'] as $from => $to) {
        if (array_key_exists($from, $raw)) {
            $out['arraysubs_' . $to] = $raw[$from] ? 'yes' : '';
        }
    }
    return $out;
};
$render_block = static function (array $raw) use ($marker): string {
    return do_blocks('<!-- wp:arraysubs/content-restricted ' . wp_json_encode($raw) . ' -->'
        . '<div class="wp-block-arraysubs-content-restricted"><!-- wp:paragraph --><p>' . $marker
        . '</p><!-- /wp:paragraph --></div><!-- /wp:arraysubs/content-restricted -->');
};
$render_elementor = static function (array $native) use ($marker): string {
    $element = \Elementor\Plugin::$instance->elements_manager->create_element_instance([
        'id' => substr(md5(wp_generate_uuid4()), 0, 7),
        'elType' => 'container',
        'settings' => $native,
        'elements' => [[
            'id' => substr(md5(wp_generate_uuid4()), 0, 7),
            'elType' => 'widget',
            'widgetType' => 'heading',
            'settings' => ['title' => $marker],
            'elements' => [],
        ]],
    ]);
    if (!$element) {
        throw new RuntimeException('Elementor could not create the runtime container.');
    }
    $level = ob_get_level();
    ob_start();
    try {
        $element->print_element();
        return (string) ob_get_clean();
    } finally {
        while (ob_get_level() > $level) {
            ob_end_clean();
        }
    }
};
$contains = static fn(string $html): bool => strpos($html, $marker) !== false;
$snapshot = static fn(int $id): string => wp_json_encode(get_post_meta($id));

try {
    if (!get_userdata($member_id) || !user_can($admin_id, 'manage_options')) {
        throw new RuntimeException('Existing member 224 and administrator 1 are required.');
    }
    $customer_result = wp_insert_user([
        'user_login' => 'qa_legacy_runtime_' . wp_generate_password(10, false),
        'user_pass' => wp_generate_password(30, true),
        'user_email' => 'qa-legacy-runtime-' . wp_generate_password(10, false) . '@example.test',
        'role' => 'customer',
    ]);
    if (is_wp_error($customer_result)) {
        throw new RuntimeException($customer_result->get_error_message());
    }
    $customer_id = (int) $customer_result;
    $users = ['guest' => 0, 'customer' => $customer_id, 'member' => $member_id, 'admin' => $admin_id];
    foreach ($users as $id) {
        add_filter('pre_transient_arraysubs_purchase_history_items_' . $id, $empty_history);
    }
    $page_id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'QA legacy runtime disposable', 'post_content' => $marker], true);
    if (is_wp_error($page_id)) {
        throw new RuntimeException($page_id->get_error_message());
    }
    $page_id = (int) $page_id;
    $post_ids[] = $page_id;
    $customer_role = get_userdata($customer_id)->roles[0];
    $has_customer_role = static fn(int $id): bool => $id > 0 && in_array($customer_role, get_userdata($id)->roles, true);
    $new_base = ['enabled' => true, 'accessVersion' => 2, 'showToAdmins' => false];
    $old_base = ['enabled' => true, 'mode' => 'restrict', 'showToAdmins' => false, 'message' => $custom];
    $cases = [
        ['old empty restriction stays public', $old_base, static fn(): bool => true],
        ['old login-only ignores stale OR clauses', array_merge($old_base, ['loginRequired' => true, 'roles' => [$never_role], 'products' => [2147483000], 'condition' => 'or']), static fn(int $id): bool => $id > 0],
        ['old ordinary OR conditions', array_merge($old_base, ['loginRequired' => false, 'roles' => [$customer_role], 'products' => [2147483000], 'condition' => 'OR']), $has_customer_role],
        ['old ordinary ALL conditions', array_merge($old_base, ['loginRequired' => false, 'roles' => [$customer_role], 'products' => [2147483000], 'condition' => 'and']), static fn(): bool => false],
        ['old admin bypass on', array_merge($old_base, ['roles' => [$never_role], 'showToAdmins' => true]), static fn(int $id): bool => $id === $admin_id],
        ['old admin bypass off', array_merge($old_base, ['roles' => [$never_role]]), static fn(): bool => false],
        ['old logged-in visibility', array_merge($old_base, ['mode' => 'visibility', 'visShow' => 'logged_in', 'visFallback' => 'QA visibility fallback']), static fn(int $id): bool => $id > 0],
        ['old guest visibility', array_merge($old_base, ['mode' => 'visibility', 'visShow' => 'logged_out', 'visFallback' => 'QA visibility fallback']), static fn(int $id): bool => $id === 0],
        ['old guest visibility plus admin bypass', array_merge($old_base, ['mode' => 'visibility', 'visShow' => 'logged_out', 'showToAdmins' => true]), static fn(int $id): bool => $id === 0 || $id === $admin_id],
        ['old login-only overrides guest visibility', array_merge($old_base, ['mode' => 'visibility', 'visShow' => 'logged_out', 'loginRequired' => true]), static fn(int $id): bool => $id > 0],
        ['new ANY does not count login', array_merge($new_base, ['loginRequired' => true, 'roles' => [$never_role], 'products' => [2147483000], 'condition' => 'or']), static fn(): bool => false],
        ['new ANY can match one actual condition', array_merge($new_base, ['loginRequired' => true, 'roles' => [$customer_role], 'products' => [2147483000], 'condition' => 'or']), $has_customer_role],
        ['new ALL requires both conditions', array_merge($new_base, ['roles' => [$customer_role], 'products' => [2147483000], 'condition' => 'and']), static fn(): bool => false],
        ['new login alone', array_merge($new_base, ['loginRequired' => true]), static fn(int $id): bool => $id > 0],
        ['new disabled restriction', array_merge($new_base, ['enabled' => false, 'roles' => [$never_role]]), static fn(): bool => true],
        ['transitional explicit message flag uses new rules', ['enabled' => true, 'showToAdmins' => false, 'useDefaultMessage' => false, 'message' => $custom, 'loginRequired' => true, 'roles' => [$never_role], 'condition' => 'or'], static fn(): bool => false],
    ];

    foreach ($cases as [$label, $raw, $expected]) {
        foreach ([Store::META_CONFIG, Store::META_ENABLED, Store::META_CONDITIONS, Store::META_MESSAGE] as $key) {
            delete_post_meta($page_id, $key);
        }
        update_post_meta($page_id, Store::META_CONFIG, wp_slash(wp_json_encode($raw)));
        $before = $snapshot($page_id);
        $config = Store::get($page_id);
        $assert(Store::sanitize($config), $config, "$label: normalization is idempotent");
        $assert(Store::fromStored(wp_json_encode($config)), $config, "$label: normalized JSON round-trip");
        $assert($config['accessVersion'], isset($raw['mode']) ? 1 : 2, "$label: correct access version");
        foreach ($users as $role => $id) {
            wp_set_current_user($id);
            ConditionEvaluator::clearCache();
            $want = $expected($id);
            $assert(Gate::decide($id, $page_id) ?? true, $want, "$label/$role: persisted post decision");
            $assert($contains(GutenbergHooks::gate($raw, $marker)), $want, "$label/$role: Gutenberg gate raw attributes");
            $assert($contains($render_block($raw)), $want, "$label/$role: Gutenberg actual raw serialized block");
            $assert($contains($elementor_apply->invoke($elementor, $elementor_settings($raw), $marker)), $want, "$label/$role: Elementor raw settings adapter");
        }
        $assert($snapshot($page_id), $before, "$label: all reads leave stored configuration untouched");
        Store::save($page_id, $config);
        $saved = Store::get($page_id);
        $assert($saved['accessVersion'], $config['accessVersion'], "$label: unrelated save keeps version");
        $assert(Store::buildConditions($saved), Store::buildConditions($config), "$label: unrelated save keeps exact effective conditions");
        $assert($saved['message'], $config['message'], "$label: unrelated save keeps custom text");
    }

    // The old format had no custom-message switch. Explicit choices always win.
    wp_set_current_user(0);
    $message_cases = [
        ['original implicit custom', ['mode' => 'restrict', 'message' => $custom], $custom],
        ['original explicit default', ['mode' => 'restrict', 'message' => $custom, 'useDefaultMessage' => true], $default],
        ['original explicit custom', ['mode' => 'restrict', 'message' => $custom, 'useDefaultMessage' => false], $custom],
        ['new custom disabled', ['accessVersion' => 2, 'message' => $custom, 'useDefaultMessage' => true], $default],
        ['new custom enabled', ['accessVersion' => 2, 'message' => $custom, 'useDefaultMessage' => false], $custom],
        ['transitional custom enabled', ['message' => $custom, 'useDefaultMessage' => false], $custom],
        ['new empty custom falls back', ['accessVersion' => 2, 'message' => '<p>&nbsp;<br></p>', 'useDefaultMessage' => false], $default],
        ['old empty custom falls back', ['mode' => 'restrict', 'message' => '<p>&nbsp;<br></p>'], $default],
        ['custom zero is text', ['mode' => 'restrict', 'message' => '0'], '0'],
    ];
    foreach ($message_cases as [$label, $choice, $want]) {
        $raw = array_merge(['enabled' => true, 'loginRequired' => true, 'showToAdmins' => false], $choice);
        update_post_meta($page_id, Store::META_CONFIG, wp_slash(wp_json_encode($raw)));
        $before = $snapshot($page_id);
        $config = Store::get($page_id);
        $assert(Store::message($config), $want, "$label: post message resolver");
        $assert(strpos((new ContentGating())->getRestrictedMessage($page_id), $want) !== false, true, "$label: actual persisted post content message");
        foreach (['Gutenberg direct' => GutenbergHooks::gate($raw, $marker), 'Gutenberg serialized' => $render_block($raw), 'Elementor' => $elementor_apply->invoke($elementor, $elementor_settings($raw), $marker)] as $surface => $html) {
            $assert(strpos($html, $want) !== false, true, "$label: $surface preserves selected message");
            $assert($contains($html), false, "$label: $surface withholds private content");
        }
        $assert($snapshot($page_id), $before, "$label: message reads never rewrite stored settings");
    }

    foreach ([
        ['positive custom on', ['arraysubs_custom_message_enabled' => 'yes'], $custom],
        ['positive custom off', ['arraysubs_custom_message_enabled' => ''], $default],
        ['transitional inverse default on', ['arraysubs_use_default_message' => 'yes'], $default],
        ['transitional inverse default off', ['arraysubs_use_default_message' => ''], $custom],
        ['positive flag wins inverse flag', ['arraysubs_custom_message_enabled' => '', 'arraysubs_use_default_message' => ''], $default],
    ] as [$label, $flags, $want]) {
        $native = array_merge(['arraysubs_enable' => 'yes', 'arraysubs_mode' => 'restrict', 'arraysubs_login_required' => 'yes', 'arraysubs_show_to_admins' => '', 'arraysubs_message' => $custom], $flags);
        $html = $elementor_apply->invoke($elementor, $native, $marker);
        $assert(strpos($html, $want) !== false, true, "Elementor $label: explicit message choice");
        $assert($contains($html), false, "Elementor $label: restricted content stays hidden");
    }

    // Exercise Element_Base's real defaults/conditional-control filtering, not
    // just the adapter. Old messages are otherwise hidden before afterRender.
    foreach ([
        ['original implicit custom', array_merge($old_base, ['roles' => [$never_role]]), static fn(): bool => false, $custom],
        ['original login override', array_merge($old_base, ['loginRequired' => true, 'roles' => [$never_role]]), static fn(int $id): bool => $id > 0, $custom],
        ['original guest visibility', array_merge($old_base, ['mode' => 'visibility', 'visShow' => 'logged_out', 'visFallback' => 'QA actual visibility fallback']), static fn(int $id): bool => $id === 0, 'QA actual visibility fallback'],
        ['current rules', array_merge($new_base, ['roles' => [$never_role], 'loginRequired' => true, 'message' => $custom, 'useDefaultMessage' => false]), static fn(): bool => false, $custom],
        ['transitional custom rules', ['enabled' => true, 'showToAdmins' => false, 'roles' => [$never_role], 'loginRequired' => true, 'message' => $custom, 'useDefaultMessage' => false], static fn(): bool => false, $custom],
        ['transitional default rules', ['enabled' => true, 'showToAdmins' => false, 'roles' => [$never_role], 'loginRequired' => true, 'message' => $custom, 'useDefaultMessage' => true], static fn(): bool => false, $default],
    ] as [$label, $raw, $expected, $denied]) {
        foreach ($users as $role => $id) {
            wp_set_current_user($id);
            $html = $render_elementor($elementor_settings($raw));
            $assert($contains($html), $expected($id), "Actual Elementor container/$label/$role: protected markup decision");
            if (!$expected($id)) {
                $assert(strpos($html, $denied) !== false, true, "Actual Elementor container/$label/$role: retained denied message");
            }
        }
    }
    foreach ($users as $role => $id) {
        wp_set_current_user($id);
        $native = $elementor_settings(array_merge($new_base, ['roles' => [$never_role], 'loginRequired' => true, 'message' => $custom, 'useDefaultMessage' => false]));
        $native['arraysubs_access_version'] = 2;
        $html = $render_elementor($native);
        $assert($contains($html), false, "Actual Elementor imported numeric version/$role: filtered controls cannot expose private markup");
        $assert(strpos($html, $custom) !== false, true, "Actual Elementor imported numeric version/$role: custom message survives filtering");
    }

    foreach (['', '[' . $shortcode_name . ']', '<p>Plain legacy fallback</p>'] as $fallback) {
        foreach (['logged_in' => 0, 'logged_out' => $customer_id] as $show => $id) {
            wp_set_current_user($id);
            $raw = ['enabled' => true, 'mode' => 'visibility', 'visShow' => $show, 'visFallback' => $fallback, 'showToAdmins' => false];
            $want = do_shortcode($fallback);
            $assert(GutenbergHooks::gate($raw, $marker), $want, "Visibility $show fallback exact output: " . wp_json_encode($fallback));
            $assert($render_block($raw), $want, "Serialized visibility $show fallback exact output: " . wp_json_encode($fallback));
            $assert($elementor_apply->invoke($elementor, $elementor_settings($raw), $marker), $want, "Elementor visibility $show fallback exact output: " . wp_json_encode($fallback));
        }
    }

    // Original direct-meta trees predate the flat configurator: keep their group
    // structure, no administrator override, and no surprise writes on reads.
    delete_post_meta($page_id, Store::META_CONFIG);
    $tree = ['logic' => 'or', 'rules' => [
        ['type' => 'group', 'logic' => 'and', 'rules' => [
            ['type' => 'user_role', 'roles' => [$customer_role]],
            ['type' => 'user_login_status', 'login_status' => 'logged_in'],
        ]],
        ['type' => 'user_login_status', 'login_status' => 'logged_out'],
    ]];
    update_post_meta($page_id, Store::META_ENABLED, '1');
    update_post_meta($page_id, Store::META_CONDITIONS, wp_slash(wp_json_encode($tree)));
    update_post_meta($page_id, Store::META_MESSAGE, wp_slash($custom));
    $before = $snapshot($page_id);
    $meta_config = Store::get($page_id);
    $assert($meta_config['legacy']['surface'], 'meta', 'Direct-meta tree is identified');
    $assert($meta_config['showToAdmins'], false, 'Direct-meta tree gains no administrator bypass');
    $assert(Store::buildConditions($meta_config), $tree, 'Nested direct-meta tree remains exact');
    $assert(Store::message($meta_config), $custom, 'Direct-meta custom message remains active');
    foreach ($users as $role => $id) {
        wp_set_current_user($id);
        $want = $id === 0 || $has_customer_role($id);
        $assert(Gate::decide($id, $page_id), $want, "Direct-meta tree/$role: decision");
        $assert((new ContentGating())->canUserAccessPost($id, $page_id), $want, "Direct-meta tree/$role: full content gate");
    }
    $assert($snapshot($page_id), $before, 'Direct-meta reads never create or rewrite canonical configuration');
    Store::save($page_id, $meta_config);
    $assert(Store::buildConditions(Store::get($page_id)), $tree, 'Direct-meta tree survives its first editor save');
    $assert(Store::get($page_id)['accessVersion'], 1, 'Direct-meta first editor save remains legacy');
    delete_post_meta($page_id, Store::META_CONFIG);
    update_post_meta($page_id, Store::META_CONDITIONS, '[]');
    $assert(Store::isActive(Store::get($page_id)), true, 'Valid empty legacy tree retains per-post override precedence');

    // Deliberate conversion is the only point at which ignored legacy clauses
    // become the current prerequisites and match conditions.
    $raw = array_merge($old_base, ['loginRequired' => true, 'roles' => [$never_role], 'condition' => 'or']);
    $legacy = Store::fromStored($raw);
    $current = Store::sanitize(array_merge($legacy, ['accessVersion' => 2]));
    $assert(isset($current['legacy']), false, 'Explicit version-2 conversion removes legacy descriptor');
    $assert($current['loginRequired'], true, 'Converted conditions still require login');
    $assert(Store::buildConditions($current)['rules'][0]['type'], 'user_role', 'Converted rule evaluates its former ignored role');
    foreach ([$customer_id, $member_id, $admin_id] as $id) {
        $assert(Gate::evaluate($id, $legacy), true, "Legacy login-only allows logged-in user $id");
        $assert(Gate::evaluate($id, $current), false, "Explicit conversion rejects unmatched user $id");
    }
    Store::save($page_id, $current);
    $assert(Store::get($page_id)['accessVersion'], 2, 'Converted post version persists');
    $assert(isset(Store::get($page_id)['legacy']), false, 'Converted post reload stays current');

    foreach ([['array', $raw], ['JSON', wp_json_encode($raw)]] as [$format, $payload]) {
        $cached_save = Store::save($page_id, $payload);
        $assert($cached_save['accessVersion'], 1, "Cached old editor $format save preserves legacy version");
        $assert(Store::buildConditions($cached_save)['rules'], [['type' => 'user_login_status', 'login_status' => 'logged_in']], "Cached old editor $format save preserves login-only override");
        $assert(Store::get($page_id), $cached_save, "Cached old editor $format save survives reload");
    }
    $cached_visibility = Store::save($page_id, ['enabled' => true, 'mode' => 'visibility', 'visShow' => 'logged_out', 'showToAdmins' => false]);
    $assert($cached_visibility['accessVersion'], 1, 'Cached old visibility editor save preserves version');
    $assert(Store::buildConditions($cached_visibility)['rules'][0]['login_status'], 'logged_out', 'Cached old visibility editor save preserves guest-only access');
    $explicit_current = Store::save($page_id, ['enabled' => true, 'accessVersion' => 2, 'mode' => 'visibility', 'visShow' => 'logged_out', 'roles' => [$never_role]]);
    $assert($explicit_current['accessVersion'], 2, 'Explicit conversion wins over old cached source fields');
    $assert(isset($explicit_current['legacy']), false, 'Explicit conversion removes old cached source descriptor');
    $new_unmarked = Store::save($page_id, ['enabled' => true, 'roles' => [$never_role]]);
    $assert($new_unmarked['accessVersion'], 2, 'New unmarked API configuration still saves current semantics');

    // Gutenberg's registered defaults must not mask absence of a legacy flag.
    $raw = ['roles' => [$never_role], 'loginRequired' => true, 'message' => $custom, 'showToAdmins' => false];
    wp_set_current_user($customer_id);
    $assert($contains($render_block($raw)), true, 'Unmarked original block retains login-only semantics despite injected defaults');
    $raw['accessVersion'] = 2;
    $assert($contains($render_block($raw)), false, 'Persisted explicit block version adopts prerequisite semantics');
    $raw['useDefaultMessage'] = false;
    wp_set_current_user(0);
    $assert(strpos($render_block($raw), $custom) !== false, true, 'Explicit current custom block survives registered defaults');
    unset($raw['accessVersion']);
    $assert($contains($render_block($raw)), false, 'Transitional explicit message choice marks new block rules');

    // Shortcode callers retain their old public contract unless they explicitly
    // opt into the new integration contract.
    $atts = ['login_required' => 'true', 'roles' => $never_role, 'condition' => 'or', 'show_to_admins' => 'false', 'message' => $custom, 'prerendered' => 'true'];
    foreach ($users as $role => $id) {
        wp_set_current_user($id);
        $assert($contains((string) call_user_func($shortcode_handler, $atts, $marker, 'arraysubs_restrict')), $id > 0, "Original shortcode/$role retains login-only override");
        $assert($contains((string) call_user_func($shortcode_handler, array_merge($atts, ['access_version' => '2']), $marker, 'arraysubs_restrict')), false, "Version-2 shortcode/$role requires actual rule match");
    }

    $redirect = Store::fromStored(array_merge($old_base, ['deniedAction' => 'redirect', 'redirectType' => 'page', 'redirectPageId' => $page_id]));
    $assert(Gate::redirectUrl($redirect), get_permalink($page_id), 'Old selected-page redirect destination remains intact');
    $assert(Gate::redirectUrl(Store::sanitize($redirect)), Gate::redirectUrl($redirect), 'Unchanged legacy normalization retains redirect');
    $redirect['redirectType'] = 'url';
    $redirect['redirectUrl'] = 'https://example.test/legacy-pricing?from=old';
    $assert(Gate::redirectUrl(Store::sanitize($redirect)), $redirect['redirectUrl'], 'Old explicit URL redirect remains intact');

    $assert(Compatibility::isLegacy(['mode' => 'visibility', 'accessVersion' => 2]), false, 'Explicit new version wins over leftover old fields');
    $assert(Compatibility::isLegacy(['mode' => 'restrict', 'useDefaultMessage' => true]), true, 'Original mode wins over transitional message flag');
    $assert(Compatibility::isLegacy([], 'post'), false, 'An empty new per-post configuration stays current');
} catch (Throwable $error) {
    $fatal = get_class($error) . ': ' . $error->getMessage();
    $assert($fatal, null, 'Runtime matrix completes without an exception');
} finally {
    wp_set_current_user($original_user);
    remove_filter('woocommerce_customer_get_total_spent', $spend_filter);
    foreach (array_unique([0, 1, 224, $customer_id]) as $id) {
        remove_filter('pre_transient_arraysubs_purchase_history_items_' . $id, $empty_history);
    }
    remove_shortcode($shortcode_name);
    ConditionEvaluator::clearCache();
    foreach ($post_ids as $id) {
        wp_delete_post($id, true);
    }
    if ($customer_id > 0) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($customer_id);
    }
}

$assert(get_option('arraysubs_settings') === $settings_before, true, 'Saved shared settings remain unchanged');
$assert(get_option('active_plugins') === $plugins_before, true, 'Active plugin list remains unchanged');
foreach ($post_ids as $id) {
    $assert(get_post($id), null, "Disposable post $id removed");
}
if ($customer_id > 0) {
    $assert(get_userdata($customer_id), false, 'Disposable customer removed');
}
$passed = count(array_filter($checks, static fn(array $check): bool => $check['passed']));
echo wp_json_encode([
    'passed' => $passed, 'total' => count($checks), 'fatal' => $fatal,
    'pro_loaded' => class_exists('ArraySubsPro', false), 'checks' => $checks,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if ($passed !== count($checks)) {
    WP_CLI::error('Legacy restriction runtime regression failed.');
}
