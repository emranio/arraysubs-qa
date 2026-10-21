<?php
/**
 * Restore options captured by snapshot-settings.php (exact serialized values).
 *
 * Usage: wp eval-file <abs>/scripts/restore-settings.php <snapshot.json> [option,option] [--apply] --allow-root
 *   - Without --apply this is a dry run that only reports what differs.
 *   - Default option list: arraysubs_settings.
 *   - active_plugins is never written here: plugin activation must be restored
 *     with `wp plugin activate|deactivate` so activation hooks run. It is only
 *     compared.
 * Every decoded value is checked against its stored sha256 before writing.
 */

$file = $args[0] ?? '';
$names = isset($args[1]) && ! in_array($args[1], ['--apply', 'apply'], true) ? array_filter(array_map('trim', explode(',', $args[1]))) : ['arraysubs_settings'];
$apply = in_array('--apply', $args, true) || in_array('apply', $args, true); // WP-CLI rejects unknown --flags, so pass the bare word 'apply'.
$snap = json_decode((string) file_get_contents($file), true);
if (! is_array($snap) || empty($snap['options'])) {
    WP_CLI::error('Not a snapshot file: ' . $file);
}

$report = ['snapshot' => $file, 'apply' => $apply, 'results' => []];
foreach ($names as $name) {
    if ('active_plugins' === $name) {
        $report['results'][$name] = 'skipped: use wp plugin activate/deactivate';
        continue;
    }
    if (! isset($snap['options'][$name])) {
        $report['results'][$name] = 'not in snapshot';
        continue;
    }
    $entry = $snap['options'][$name];
    $ser = base64_decode((string) $entry['b64'], true);
    if (false === $ser || hash('sha256', $ser) !== $entry['sha256']) {
        $report['results'][$name] = 'ABORT: snapshot hash mismatch';
        continue;
    }
    $value = unserialize($ser);
    if ('arraysubs_settings' === $name && ! is_array($value)) {
        $report['results'][$name] = 'ABORT: decoded value is not an array';
        continue;
    }
    $current_hash = hash('sha256', serialize(get_option($name, null)));
    if ($current_hash === $entry['sha256']) {
        $report['results'][$name] = 'already identical';
        continue;
    }
    if (! $apply) {
        $report['results'][$name] = 'differs (dry run)';
        continue;
    }
    if (empty($entry['exists'])) {
        delete_option($name);
    } else {
        update_option($name, $value);
    }
    wp_cache_delete($name, 'options');
    $after = hash('sha256', serialize(get_option($name, null)));
    $report['results'][$name] = $after === $entry['sha256'] ? 'restored (hash verified)' : 'RESTORE FAILED (hash ' . $after . ')';
}
$active = hash('sha256', serialize(get_option('active_plugins', null)));
$report['active_plugins_exact_array_matches_snapshot'] = $active === ($snap['options']['active_plugins']['sha256'] ?? '');
// Deactivate/reactivate re-indexes the array, so the meaningful check is the set.
$now_set = array_values((array) get_option('active_plugins', []));
$was_set = array_values((array) ($snap['options']['active_plugins']['readable'] ?? []));
sort($now_set);
sort($was_set);
$report['active_plugins_set_matches_snapshot'] = $now_set === $was_set;
$report['active_plugins_now'] = $now_set;
echo wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
