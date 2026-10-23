<?php
defined('ABSPATH') || exit;
$mode = $args[0] ?? 'inspect';
$state = $args[1] ?? 'transitional_off';
$manifest = json_decode(file_get_contents(__DIR__ . '/fixtures.json'), true);
$id = (int) $manifest['author_elementor'];
if ($mode === 'seed') {
    $s = [
        'arraysubs_enable' => 'yes',
        'arraysubs_show_to_admins' => '',
        'arraysubs_roles' => ['qa_nonexistent_role'],
        'arraysubs_products' => [2147483000],
        'arraysubs_condition' => 'or',
        'arraysubs_message' => '<p>QA FINAL ELEMENTOR AUDIT CUSTOM</p>',
    ];
    if (str_starts_with($state, 'transitional') || str_starts_with($state, 'imported')) {
        $s['arraysubs_login_required'] = 'yes';
        $s[$state === 'transitional_inverse_off' ? 'arraysubs_use_default_message' : 'arraysubs_custom_message_enabled'] = '';
        if (str_starts_with($state, 'imported')) {
            $s['arraysubs_access_version'] = $state === 'imported_negative' ? -1 : '2garbage';
        }
    } else {
        $s['arraysubs_mode'] = 'restrict';
        if (str_contains($state, 'explicit_off')) {
            $s['arraysubs_login_required'] = '';
        }
        if ($state === 'legacy_login_only') {
            $s['arraysubs_login_required'] = 'yes';
        }
        if (str_contains($state, 'pass')) {
            $s['arraysubs_roles'] = ['demo_premium'];
        }
        if (str_starts_with($state, 'visibility')) {
            $s['arraysubs_mode'] = 'visibility';
            $s['arraysubs_vis_show'] = 'logged_out';
            $s['arraysubs_vis_fallback'] = $state === 'visibility_empty' ? '' : '<p>QA FINAL ELEMENTOR AUDIT FALLBACK</p>';
            if ($state === 'visibility_imported_nbsp') {
                $s['arraysubs_access_version'] = "\u{00a0}2";
            }
            if ($state === 'visibility_imported_overflow') {
                $s['arraysubs_access_version'] = json_decode('9223372036854775808', true);
            }
        }
    }
    $data = [[
        'id' => 'finalgate1', 'elType' => 'container', 'settings' => $s,
        'elements' => [[
            'id' => 'finaltitle1', 'elType' => 'widget', 'widgetType' => 'heading',
            'settings' => ['title' => 'QA FINAL ELEMENTOR UNOPENED PRIVATE'], 'elements' => [],
        ]], 'isInner' => false,
    ]];
    if (str_starts_with($state, 'nested_')) {
        $data = [['id' => 'finalpublic', 'elType' => 'container', 'settings' => [], 'elements' => $data, 'isInner' => false]];
    }
    update_post_meta($id, '_elementor_data', wp_slash(wp_json_encode($data)));
    delete_post_meta($id, '_elementor_element_cache');
    delete_post_meta($id, '_elementor_css');
    clean_post_cache($id);
}
echo 'QA_RESULT:' . wp_json_encode([
    'id' => $id,
    'url' => get_permalink($id),
    'data' => json_decode(get_post_meta($id, '_elementor_data', true), true),
]);
