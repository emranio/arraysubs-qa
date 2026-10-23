<?php
defined('ABSPATH') || exit;

use ArraySubs\Features\ElementorIntegration\Services\OriginalHooks;
use ArraySubs\Features\MembersAccess\Services\ConditionEvaluator;

require_once __DIR__ . '/original-elementor-hooks.php';
$original = (new ReflectionClass(OriginalHooks::class))->newInstanceWithoutConstructor();
$old_apply = new ReflectionMethod($original, 'applyRestriction');
$users = ['guest' => 0, 'customer' => 455, 'member' => 224, 'admin' => 1];
$private = 'QA_FINAL_NATIVE_PRIVATE';
$custom = 'QA_FINAL_NATIVE_CUSTOM';
$checks = 0;
$failures = [];
$scratch = 0;
$assert = static function ($actual, $expected, string $label) use (&$checks, &$failures): void {
    ++$checks;
    if ($actual !== $expected) {
        $failures[] = [$label, 'actual' => $actual, 'expected' => $expected];
    }
};
$heading = static fn(string $text): array => [
    'id' => substr(md5(wp_generate_uuid4()), 0, 7), 'elType' => 'widget', 'widgetType' => 'heading',
    'settings' => ['title' => $text], 'elements' => [],
];
$container = static fn(array $settings, array $elements, string $id = 'auditdup'): array => [
    'id' => $id, 'elType' => 'container', 'settings' => $settings, 'elements' => $elements,
];
$render = static function (array $data) use ($assert): string {
    $instance = \Elementor\Plugin::$instance->elements_manager->create_element_instance($data);
    if (!$instance) {
        throw new RuntimeException('Native Elementor instance creation failed.');
    }
    $level = ob_get_level();
    ob_start();
    try {
        $instance->print_element();
        $html = (string) ob_get_clean();
        $assert(ob_get_level(), $level, 'Actual native render leaves all output buffers balanced');
        return $html;
    } finally {
        while (ob_get_level() > $level) {
            ob_end_clean();
        }
    }
};
$base = ['arraysubs_enable' => 'yes', 'arraysubs_show_to_admins' => '', 'arraysubs_message' => $custom, 'arraysubs_products' => [2147483000], 'arraysubs_condition' => 'or'];

try {
    $cases = [];
    foreach (['absent', 'off', 'yes'] as $login) {
        foreach (['absent', '', 'restrict'] as $mode) {
            foreach (['qa_nonexistent_role', 'demo_premium'] as $role) {
                $raw = array_merge($base, ['arraysubs_roles' => [$role]]);
                if ($mode !== 'absent') {
                    $raw['arraysubs_mode'] = $mode;
                }
                if ($login !== 'absent') {
                    $raw['arraysubs_login_required'] = $login === 'yes' ? 'yes' : '';
                }
                $cases[] = ["ordinary ANY/$login/$mode/$role", $raw];
            }
        }
    }
    foreach (['logged_out', 'logged_in'] as $show) {
        foreach (['', $custom, '[qa_final_fallback]'] as $fallback) {
            $cases[] = ["visibility/$show/" . $fallback, array_merge($base, [
                'arraysubs_mode' => 'visibility', 'arraysubs_vis_show' => $show,
                'arraysubs_vis_fallback' => $fallback, 'arraysubs_roles' => ['qa_nonexistent_role'],
            ])];
        }
    }
    foreach ([
        ['roles scalar CSV', ['arraysubs_roles' => 'customer,qa_nonexistent_role']],
        ['roles scalar mixed case', ['arraysubs_roles' => 'Customer,DEMO_PREMIUM']],
        ['roles array mixed case', ['arraysubs_roles' => ['Customer', 'DEMO_PREMIUM']]],
        ['status scalar CSV and case', ['arraysubs_status' => 'AcTiVe,expired', 'arraysubs_roles' => 'qa_nonexistent_role']],
        ['products scalar negative IDs', ['arraysubs_products' => '-1,2147483000', 'arraysubs_roles' => 'customer']],
        ['products array negative IDs', ['arraysubs_products' => [-1, 2147483000], 'arraysubs_roles' => ['customer']]],
        ['purchase scalar CSV', ['arraysubs_purchased' => '-1,2147483000', 'arraysubs_roles' => 'customer']],
        ['variation scalar CSV', ['arraysubs_variations' => '-1,2147483000', 'arraysubs_roles' => 'customer']],
    ] as [$label, $values]) {
        $cases[] = [$label, array_merge($base, ['arraysubs_mode' => 'restrict'], $values)];
    }
    add_shortcode('qa_final_fallback', static fn(): string => 'QA_FINAL_EXPANDED_FALLBACK');
    foreach ($cases as [$label, $raw]) {
        foreach ($users as $actor => $id) {
            wp_set_current_user($id);
            ConditionEvaluator::clearCache();
            $expected = (string) $old_apply->invoke($original, $raw, $private);
            $actual = $render($container($raw, [$heading($private)]));
            $assert(str_contains($actual, $private), str_contains($expected, $private), "$label/$actor: actual native audience equals original HEAD adapter");
            if (!str_contains($expected, $private)) {
                $assert(trim(strip_tags($actual)), trim(strip_tags($expected)), "$label/$actor: actual native denied output equals original HEAD adapter");
            }
        }
    }

    $deny = array_merge($base, ['arraysubs_access_version' => '2', 'arraysubs_login_required' => 'yes', 'arraysubs_roles' => ['qa_nonexistent_role']]);
    $member = array_merge($base, ['arraysubs_access_version' => '2', 'arraysubs_login_required' => 'yes', 'arraysubs_roles' => ['demo_premium']]);
    $login = ['arraysubs_enable' => 'yes', 'arraysubs_access_version' => '2', 'arraysubs_login_required' => 'yes', 'arraysubs_show_to_admins' => ''];
    foreach ($users as $actor => $id) {
        wp_set_current_user($id);
        ConditionEvaluator::clearCache();
        foreach ([
            ['public ancestor / denied child', [], $deny, true, false],
            ['denied ancestor / login child', $deny, $login, false, false],
            ['login ancestor / denied child / duplicate saved IDs', $login, $deny, $id > 0, false],
            ['public ancestor / member child', [], $member, true, $id === 224],
            ['member ancestor / login child', $member, $login, $id === 224, $id === 224],
        ] as [$label, $outer, $inner, $outer_expected, $inner_expected]) {
            $html = $render($container($outer, [$heading('QA_FINAL_OUTER'), $container($inner, [$heading($private)])]));
            $assert(str_contains($html, 'QA_FINAL_OUTER'), $outer_expected, "$label/$actor: outer decision");
            $assert(str_contains($html, $private), $inner_expected, "$label/$actor: inner decision");
        }
        $html = $render($container([], [$heading($private)]));
        $assert(str_contains($html, $private), true, "$actor: later public output is not swallowed by a previous restriction buffer");
    }

    wp_set_current_user(1);
    $scratch = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'QA final native cache disposable'], true);
    if (is_wp_error($scratch)) {
        throw new RuntimeException($scratch->get_error_message());
    }
    update_post_meta($scratch, '_elementor_edit_mode', 'builder');
    update_post_meta($scratch, '_elementor_data', '[]');
    $document = \Elementor\Plugin::$instance->documents->get($scratch);
    $document->set_document_cache(['content' => 'QA_FINAL_STALE_PRIVATE', 'styles' => [], 'scripts' => []]);
    $assert(get_post_meta($scratch, '_elementor_element_cache', true) !== '', true, 'Positive control: public document cache can be warmed');
    $nested = [$container([], [$heading('QA_FINAL_CACHE_OUTER'), $container($member, [$heading($private)])])];
    update_post_meta($scratch, '_elementor_data', wp_slash(wp_json_encode($nested)));
    $original_source = get_post_meta($scratch, '_elementor_data', true);
    $assert(get_post_meta($scratch, '_elementor_element_cache', true), '', 'Nested gate ignores an already-warmed shared document cache');
    foreach (['member', 'guest', 'customer', 'admin', 'member', 'guest'] as $actor) {
        wp_set_current_user($users[$actor]);
        ConditionEvaluator::clearCache();
        ob_start();
        $document->print_elements($nested);
        $html = (string) ob_get_clean();
        $assert(str_contains($html, 'QA_FINAL_STALE_PRIVATE'), false, "$actor: actual document renderer never uses stale private cache");
        $assert(str_contains($html, 'QA_FINAL_CACHE_OUTER'), true, "$actor: actual document renderer retains public ancestor");
        $assert(str_contains($html, $private), $actor === 'member', "$actor: repeated actual cache path evaluates this visitor independently");
        $assert(get_post_meta($scratch, '_elementor_element_cache', true), '', "$actor: visitor output never becomes readable shared HTML cache");
    }
    $assert(get_post_meta($scratch, '_elementor_data', true), $original_source, 'Rendering and caching never rewrite source configuration');
} finally {
    if ($scratch) {
        wp_delete_post((int) $scratch, true);
    }
    remove_shortcode('qa_final_fallback');
    wp_set_current_user(0);
}
echo 'QA_RESULT:' . wp_json_encode(['checks' => $checks, 'failures' => $failures, 'scratchDeleted' => $scratch ? get_post($scratch) === null : true]);
if ($failures) {
    exit(1);
}
