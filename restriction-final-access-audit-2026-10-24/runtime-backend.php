<?php
/** Independent access/audience audit against trusted git-HEAD backend classes. */
defined('ABSPATH') || exit;

use ArraySubs\Features\MembersAccess\Services\PostRestrictionConfig as Store;
use ArraySubs\Features\MembersAccess\Services\PostRestrictionGate as Gate;
use ArraySubs\Features\MembersAccess\Services\PostRestriction as PostEditor;
use ArraySubs\Features\MembersAccess\Services\ConditionEvaluator;
use ArraySubs\Features\MembersAccess\Services\ContentGating;

$checks = [];
$assert = static function ($actual, $expected, string $name) use (&$checks): void {
    $checks[] = ['passed' => $actual === $expected, 'check' => $name, 'actual' => $actual, 'expected' => $expected];
};
$load_baseline = static function (string $relative, array $classes): void {
    $pipes = [];
    $process = proc_open(['git', '-C', WP_PLUGIN_DIR . '/arraysubs', 'show', 'HEAD:' . $relative], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot load the trusted HEAD baseline.');
    }
    $source = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || strpos($source, '<?php') !== 0) {
        throw new RuntimeException('Cannot load HEAD baseline: ' . $error);
    }
    foreach ($classes as $from => $to) {
        $source = preg_replace('/\b' . preg_quote($from, '/') . '\b/', $to, $source);
    }
    // The source is trusted code retrieved directly from this repository's HEAD;
    // renaming classes isolates it from the current implementation and hooks.
    eval(substr($source, 5));
};
$baseline_names = [
    'ConditionEvaluator' => 'AuditHeadConditionEvaluator',
    'PostRestrictionConfig' => 'AuditHeadPostRestrictionConfig',
    'PostRestrictionGate' => 'AuditHeadPostRestrictionGate',
    'ContentGating' => 'AuditHeadContentGating',
];
$original_user = get_current_user_id();
$original_post = $_POST;
$settings_before = get_option('arraysubs_settings');
$posts = [];
$fatal = null;
$roles = ['guest' => 0, 'member' => 224, 'admin' => 1];
$raw_snapshot = static fn(int $id): string => wp_json_encode(get_post_meta($id));

try {
    foreach (['ConditionEvaluator', 'PostRestrictionConfig', 'PostRestrictionGate', 'ContentGating'] as $name) {
        $load_baseline('src/Features/MembersAccess/Services/' . $name . '.php', $baseline_names);
    }
    $old_store = 'ArraySubs\\Features\\MembersAccess\\Services\\AuditHeadPostRestrictionConfig';
    $old_gate = 'ArraySubs\\Features\\MembersAccess\\Services\\AuditHeadPostRestrictionGate';
    $old_content = 'ArraySubs\\Features\\MembersAccess\\Services\\AuditHeadContentGating';
    $old_evaluator = 'ArraySubs\\Features\\MembersAccess\\Services\\AuditHeadConditionEvaluator';
    if (!get_userdata(224) || !user_can(1, 'manage_options')) {
        throw new RuntimeException('Expected member 224 and administrator 1.');
    }
    $page_id = wp_insert_post(['post_title' => 'QA final backend access audit', 'post_type' => 'page', 'post_status' => 'publish', 'post_content' => 'QA_BACKEND_AUDIT_PRIVATE'], true);
    if (is_wp_error($page_id)) {
        throw new RuntimeException($page_id->get_error_message());
    }
    $page_id = (int) $page_id;
    $posts[] = $page_id;
    $never = 'qa_final_missing_role';
    $base = ['enabled' => true, 'mode' => 'restrict', 'showToAdmins' => false];
    $cases = [
        ['original login-only ANY stale conditions', array_merge($base, ['loginRequired' => true, 'roles' => [$never], 'products' => [2147483000], 'condition' => 'or'])],
        ['original ordinary ANY all failing', array_merge($base, ['roles' => [$never], 'products' => [2147483000], 'condition' => 'or'])],
        ['original ordinary ANY one passing', array_merge($base, ['roles' => ['customer'], 'products' => [2147483000], 'condition' => 'or'])],
        ['original ordinary ALL one failing', array_merge($base, ['roles' => ['customer'], 'products' => [2147483000], 'condition' => 'and'])],
        ['original guest visibility', array_merge($base, ['mode' => 'visibility', 'visShow' => 'logged_out'])],
        ['original logged-in visibility', array_merge($base, ['mode' => 'visibility', 'visShow' => 'logged_in'])],
        ['original disabled rules', array_merge($base, ['enabled' => false, 'roles' => [$never]])],
        ['original truthy string enabled false', array_merge($base, ['enabled' => 'false', 'roles' => [$never]])],
        ['original truthy string login false', array_merge($base, ['loginRequired' => 'false'])],
        ['original explicit null administrator bypass', array_merge($base, ['showToAdmins' => null, 'roles' => [$never]])],
        ['original truthy string administrator bypass', array_merge($base, ['showToAdmins' => 'false', 'roles' => [$never]])],
        ['original string zero administrator bypass', array_merge($base, ['showToAdmins' => '0', 'roles' => [$never]])],
        ['original empty configuration', $base],
        ['original scalar role ignored', array_merge($base, ['roles' => $never])],
        ['original nested malformed token ignored', array_merge($base, ['roles' => [['bad' => true]]])],
        ['original unknown mode defaults restrict', array_merge($base, ['mode' => 'unexpected', 'roles' => [$never]])],
    ];
    $editor = (new ReflectionClass(PostEditor::class))->newInstanceWithoutConstructor();
    $classic_save = static function (array $raw) use ($editor, $page_id): void {
        $_POST = [
            'post_ID' => (string) $page_id,
            PostEditor::NONCE_NAME => wp_create_nonce(PostEditor::NONCE_ACTION),
            PostEditor::FORM_FIELD => wp_slash(wp_json_encode($raw)),
        ];
        $editor->saveClassicMetaBox($page_id, get_post($page_id));
    };
    foreach ($cases as [$label, $raw]) {
        foreach ([Store::META_CONFIG, Store::META_ENABLED, Store::META_CONDITIONS, Store::META_MESSAGE] as $key) {
            delete_post_meta($page_id, $key);
        }
        update_post_meta($page_id, Store::META_CONFIG, wp_slash(wp_json_encode($raw)));
        $snapshot = $raw_snapshot($page_id);
        $head_decisions = [];
        foreach ($roles as $name => $id) {
            wp_set_current_user($id);
            ConditionEvaluator::clearCache();
            $old_evaluator::clearCache();
            $before = (new $old_content())->canUserAccessPost($id, $page_id);
            $head_decisions[$name] = $before;
            $assert((new ContentGating())->canUserAccessPost($id, $page_id), $before, "$label/$name: unchanged raw record matches HEAD audience");
        }
        $assert($raw_snapshot($page_id), $snapshot, "$label: inspecting existing record writes nothing");
        $canonical = Store::get($page_id);
        wp_set_current_user(1);
        $classic_save($canonical);
        foreach ($roles as $name => $id) {
            $assert((new ContentGating())->canUserAccessPost($id, $page_id), $head_decisions[$name], "$label/$name: canonical classic-editor save keeps HEAD audience");
        }
        // Cached old editors can submit their original array without a version.
        Store::save($page_id, $raw);
        foreach ($roles as $name => $id) {
            $assert((new ContentGating())->canUserAccessPost($id, $page_id), $head_decisions[$name], "$label/$name: cached old payload save keeps HEAD audience");
        }
        wp_set_current_user(1);
        $request = new WP_REST_Request('POST', '/wp/v2/pages/' . $page_id);
        $request->set_param('title', 'QA final backend access audit ' . sanitize_title($label));
        $request->set_param(Store::REST_FIELD, wp_json_encode(Store::get($page_id)));
        $response = rest_do_request($request);
        $assert($response->get_status(), 200, "$label: authenticated REST editor save succeeds");
        foreach ($roles as $name => $id) {
            $assert((new ContentGating())->canUserAccessPost($id, $page_id), $head_decisions[$name], "$label/$name: REST editor save keeps HEAD audience");
        }
    }

    $deny_tree = ['logic' => 'and', 'rules' => [['type' => 'user_role', 'roles' => [$never]]]];
    $fallback_cases = [
        ['disabled canonical with active fallback', array_merge($base, ['enabled' => false]), '1', wp_json_encode($deny_tree), true],
        ['empty enabled canonical with active fallback', $base, '1', wp_json_encode($deny_tree), true],
        ['disabled canonical with valid empty fallback', array_merge($base, ['enabled' => false]), '1', '[]', true],
        ['disabled canonical with invalid fallback JSON', array_merge($base, ['enabled' => false]), '1', 'invalid-json', false],
        ['disabled canonical with disabled fallback', array_merge($base, ['enabled' => false]), '0', wp_json_encode($deny_tree), false],
        ['active canonical outranks fallback', array_merge($base, ['roles' => ['customer']]), '1', wp_json_encode($deny_tree), false],
    ];
    foreach ($fallback_cases as [$label, $raw, $meta_enabled, $meta_tree, $adopted]) {
        update_post_meta($page_id, Store::META_CONFIG, wp_slash(wp_json_encode($raw)));
        update_post_meta($page_id, Store::META_ENABLED, $meta_enabled);
        update_post_meta($page_id, Store::META_CONDITIONS, wp_slash($meta_tree));
        update_post_meta($page_id, Store::META_MESSAGE, '<p>QA effective legacy fallback message</p>');
        $snapshot = $raw_snapshot($page_id);
        $before = [];
        foreach ($roles as $name => $id) {
            $before[$name] = (new $old_content())->canUserAccessPost($id, $page_id);
            $assert((new ContentGating())->canUserAccessPost($id, $page_id), $before[$name], "$label/$name: effective fallback matches original audience");
        }
        $loaded = Store::get($page_id);
        $assert(($loaded['legacy']['surface'] ?? '') === 'meta', $adopted, "$label: editor identifies the actually effective source");
        $assert($raw_snapshot($page_id), $snapshot, "$label: effective-source inspection writes nothing");
        if ($adopted) {
            $assert($loaded['showToAdmins'], false, "$label: adopted fallback gains no administrator bypass");
            $assert(Store::message($loaded), '<p>QA effective legacy fallback message</p>', "$label: adopted fallback preserves denied message");
            Store::save($page_id, $raw);
            foreach ($roles as $name => $id) {
                $assert((new ContentGating())->canUserAccessPost($id, $page_id), $before[$name], "$label/$name: exact unchanged cached original payload preserves effective audience");
            }
        }
        wp_set_current_user(1);
        $classic_save($loaded);
        foreach ($roles as $name => $id) {
            $assert((new ContentGating())->canUserAccessPost($id, $page_id), $before[$name], "$label/$name: unchanged classic save preserves effective audience");
        }
        $request = new WP_REST_Request('POST', '/wp/v2/pages/' . $page_id);
        $request->set_param('title', 'QA effective fallback unchanged save');
        $request->set_param(Store::REST_FIELD, wp_json_encode(Store::get($page_id)));
        $assert(rest_do_request($request)->get_status(), 200, "$label: unchanged REST save succeeds");
        foreach ($roles as $name => $id) {
            $assert((new ContentGating())->canUserAccessPost($id, $page_id), $before[$name], "$label/$name: unchanged REST save preserves effective audience");
        }
        if ($adopted) {
            Store::save($page_id, array_merge(Store::get($page_id), ['enabled' => false]));
            $assert(Store::get($page_id)['enabled'], false, "$label: deliberate current UI Off remains available");
            $assert(Gate::decide(0, $page_id), null, "$label: deliberate Off removes the local fallback override");
        }
    }

    // An active inherited parent rule precedes dormant direct-meta on a child.
    $parent_id = wp_insert_post(['post_type' => 'product', 'post_status' => 'publish', 'post_title' => 'QA inherited parent restriction']);
    $variation_id = wp_insert_post(['post_type' => 'product_variation', 'post_status' => 'publish', 'post_parent' => $parent_id, 'post_title' => 'QA dormant child fallback']);
    $posts[] = (int) $parent_id;
    $posts[] = (int) $variation_id;
    Store::save($parent_id, array_merge($base, ['roles' => ['customer']]));
    update_post_meta($variation_id, Store::META_CONFIG, wp_slash(wp_json_encode(array_merge($base, ['enabled' => false]))));
    update_post_meta($variation_id, Store::META_ENABLED, '1');
    update_post_meta($variation_id, Store::META_CONDITIONS, wp_slash(wp_json_encode($deny_tree)));
    foreach ($roles as $name => $id) {
        $assert((new ContentGating())->canUserAccessPost($id, $variation_id), (new $old_content())->canUserAccessPost($id, $variation_id), "Variation/$name: active parent retains priority over dormant child fallback");
    }
    $assert((Store::get($variation_id)['legacy']['surface'] ?? '') === 'meta', false, 'Variation editor does not promote a dormant fallback over its active parent');
    foreach ([
        ['parent guest-only meta and child denies', ['logic' => 'and', 'rules' => [['type' => 'user_login_status', 'login_status' => 'logged_out']]], $deny_tree],
        ['parent denying meta and child guest-only', $deny_tree, ['logic' => 'and', 'rules' => [['type' => 'user_login_status', 'login_status' => 'logged_out']]]],
        ['parent denying meta and no child fallback', $deny_tree, null],
    ] as [$label, $parent_tree, $child_tree]) {
        update_post_meta($parent_id, Store::META_CONFIG, wp_slash(wp_json_encode(array_merge($base, ['enabled' => false]))));
        update_post_meta($parent_id, Store::META_ENABLED, '1');
        update_post_meta($parent_id, Store::META_CONDITIONS, wp_slash(wp_json_encode($parent_tree)));
        if ($child_tree === null) {
            delete_post_meta($variation_id, Store::META_ENABLED);
            delete_post_meta($variation_id, Store::META_CONDITIONS);
        } else {
            update_post_meta($variation_id, Store::META_ENABLED, '1');
            update_post_meta($variation_id, Store::META_CONDITIONS, wp_slash(wp_json_encode($child_tree)));
        }
        foreach ($roles as $name => $id) {
            $assert((new ContentGating())->canUserAccessPost($id, $variation_id), (new $old_content())->canUserAccessPost($id, $variation_id), "Variation $label/$name: inactive parent meta is not inherited");
        }
    }

    $trees = [
        ['nested guest OR customer', ['logic' => 'or', 'rules' => [
            ['type' => 'group', 'logic' => 'and', 'rules' => [['type' => 'user_role', 'roles' => ['customer']], ['type' => 'user_login_status', 'login_status' => 'logged_in']]],
            ['type' => 'user_login_status', 'login_status' => 'logged_out'],
        ]]],
        ['unknown rule cannot pass ANY', ['logic' => 'or', 'rules' => [['type' => 'unknown'], ['type' => 'user_role', 'roles' => [$never]]]]],
        ['malformed rule cannot disappear from ALL', ['logic' => 'and', 'rules' => [['type' => 'user_login_status', 'login_status' => 'logged_in'], 'malformed']]],
        ['empty tree keeps public local precedence', ['logic' => 'and', 'rules' => []]],
    ];
    foreach ($trees as [$label, $tree]) {
        delete_post_meta($page_id, Store::META_CONFIG);
        update_post_meta($page_id, Store::META_ENABLED, '1');
        update_post_meta($page_id, Store::META_CONDITIONS, wp_slash(wp_json_encode($tree)));
        $snapshot = $raw_snapshot($page_id);
        $old_decisions = [];
        foreach ($roles as $name => $id) {
            $old_decisions[$name] = (new $old_content())->canUserAccessPost($id, $page_id);
            $assert((new ContentGating())->canUserAccessPost($id, $page_id), $old_decisions[$name], "$label/$name: direct meta matches HEAD");
        }
        $assert($raw_snapshot($page_id), $snapshot, "$label: original meta reads write nothing");
        Store::save($page_id, Store::get($page_id));
        foreach ($roles as $name => $id) {
            $assert((new ContentGating())->canUserAccessPost($id, $page_id), $old_decisions[$name], "$label/$name: direct-meta first save preserves audience");
        }
    }

    foreach (['and', 'or'] as $logic) {
        $raw = ['enabled' => true, 'accessVersion' => 2, 'roles' => [$never], 'products' => [2147483000], 'loginRequired' => true, 'showToAdmins' => false, 'condition' => $logic];
        Store::save($page_id, $raw);
        foreach ($roles as $name => $id) {
            $assert((new ContentGating())->canUserAccessPost($id, $page_id), false, "Version 2 $logic/$name: login never satisfies a failed match group");
        }
        $assert(in_array('user_login_status', array_column(Store::buildConditions(Store::get($page_id))['rules'], 'type'), true), false, "Version 2 $logic: login is outside the match group");
    }

    // Unknown status filters must never degrade to an unfiltered WP_Query.
    foreach (['qa-final-unknown-status', 'any', 'publish', 'ARRAYSUBS-ACTIVE', null, ['invalid'], 1] as $status) {
        $tree = ['logic' => 'or', 'rules' => [['type' => 'subscription_status', 'statuses' => [$status]], ['type' => 'user_role', 'roles' => [$never]]]];
        foreach ($roles as $name => $id) {
            $assert((new ConditionEvaluator())->evaluate($id, $tree), false, 'Unsupported status ' . wp_json_encode($status) . "/$name cannot unlock ANY");
        }
    }
    foreach (['active', false, 1, null] as $scalar) {
        $tree = ['logic' => 'or', 'rules' => [['type' => 'subscription_status', 'statuses' => $scalar], ['type' => 'user_role', 'roles' => [$never]]]];
        $assert((new ConditionEvaluator())->evaluate(224, $tree), false, 'Malformed scalar status list ' . wp_json_encode($scalar) . ' cannot unlock ANY');
    }
    // Unknown entries in a mixed list must only narrow to the actual valid
    // statuses. A separately registered ArraySubs status remains supported.
    foreach ([['active'], ['arraysubs-active'], ['active', 'qa-final-unknown-status'], ['any', 'active']] as $statuses) {
        $want = (bool) arraysubs_get_customer_subscriptions(224, ['arraysubs-active']);
        $assert((new ConditionEvaluator())->evaluate(224, ['logic' => 'and', 'rules' => [['type' => 'subscription_status', 'statuses' => $statuses]]]), $want, 'Valid/mixed status list preserves explicit active selection ' . wp_json_encode($statuses));
    }
    $custom_status = 'arraysubs-qa-audit';
    register_post_status($custom_status, ['label' => 'QA audit status', 'public' => false, 'internal' => true, 'exclude_from_search' => false]);
    $subscription_id = wp_insert_post(['post_type' => 'arraysubs_data', 'post_status' => $custom_status, 'post_title' => 'QA custom status access audit'], true);
    if (is_wp_error($subscription_id)) {
        throw new RuntimeException($subscription_id->get_error_message());
    }
    $posts[] = (int) $subscription_id;
    update_post_meta($subscription_id, '_customer_id', 224);
    ConditionEvaluator::clearCache();
    $assert((new ConditionEvaluator())->evaluate(224, ['logic' => 'and', 'rules' => [['type' => 'subscription_status', 'statuses' => [$custom_status]]]]), true, 'Registered custom ArraySubs status remains supported');
} catch (Throwable $error) {
    $fatal = get_class($error) . ': ' . $error->getMessage();
    $assert($fatal, null, 'Backend audit completes without an exception');
} finally {
    $_POST = $original_post;
    wp_set_current_user($original_user);
    ConditionEvaluator::clearCache();
    foreach ($posts as $id) {
        wp_delete_post($id, true);
    }
}
$assert(get_option('arraysubs_settings') === $settings_before, true, 'Shared settings were not changed');
foreach ($posts as $id) {
    $assert(get_post($id), null, 'Disposable audit post removed');
}
$passed = count(array_filter($checks, static fn(array $check): bool => $check['passed']));
echo wp_json_encode(['passed' => $passed, 'total' => count($checks), 'fatal' => $fatal, 'pro_loaded' => class_exists('ArraySubsPro', false), 'checks' => $checks], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
if ($passed !== count($checks)) {
    WP_CLI::error('Final backend access audit failed.');
}
