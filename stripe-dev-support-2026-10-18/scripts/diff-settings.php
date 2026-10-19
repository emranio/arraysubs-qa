<?php
/**
 * Diff two settings snapshots written by snapshot-settings.php.
 *
 * Usage: wp eval-file <abs>/scripts/diff-settings.php <before.json> <after.json> --allow-root
 * Prints JSON: per-option / per-hash-only / per-section equality, and the
 * dotted paths of changed arraysubs_settings leaves (values shown; no secrets
 * are stored in these snapshots).
 */

[$a_file, $b_file] = [$args[0] ?? '', $args[1] ?? ''];
$a = json_decode((string) file_get_contents($a_file), true);
$b = json_decode((string) file_get_contents($b_file), true);
if (! is_array($a) || ! is_array($b)) {
    WP_CLI::error('Both arguments must be snapshot JSON files.');
}

$flatten = static function ($value, string $prefix = '') use (&$flatten): array {
    if (! is_array($value)) {
        return [$prefix => $value];
    }
    if ([] === $value) {
        return [$prefix => []];
    }
    $out = [];
    foreach ($value as $k => $v) {
        $out += $flatten($v, '' === $prefix ? (string) $k : $prefix . '.' . $k);
    }
    return $out;
};

$result = ['before' => $a['captured_at_utc'] ?? null, 'after' => $b['captured_at_utc'] ?? null, 'options' => [], 'hash_only' => [], 'sections' => [], 'arraysubs_settings_changed_paths' => []];

foreach (array_unique(array_merge(array_keys($a['options'] ?? []), array_keys($b['options'] ?? []))) as $name) {
    $result['options'][$name] = ($a['options'][$name]['sha256'] ?? null) === ($b['options'][$name]['sha256'] ?? null) ? 'identical' : 'CHANGED';
}
foreach (array_unique(array_merge(array_keys($a['hash_only'] ?? []), array_keys($b['hash_only'] ?? []))) as $name) {
    $result['hash_only'][$name] = ($a['hash_only'][$name]['sha256'] ?? null) === ($b['hash_only'][$name]['sha256'] ?? null) ? 'identical' : 'CHANGED';
}
foreach (array_unique(array_merge(array_keys($a['sections'] ?? []), array_keys($b['sections'] ?? []))) as $name) {
    $result['sections'][$name] = ($a['sections'][$name]['sha256'] ?? null) === ($b['sections'][$name]['sha256'] ?? null) ? 'identical' : 'CHANGED';
}

$sa = unserialize(base64_decode((string) ($a['options']['arraysubs_settings']['b64'] ?? '')));
$sb = unserialize(base64_decode((string) ($b['options']['arraysubs_settings']['b64'] ?? '')));
$fa = $flatten(is_array($sa) ? $sa : []);
$fb = $flatten(is_array($sb) ? $sb : []);
foreach (array_unique(array_merge(array_keys($fa), array_keys($fb))) as $path) {
    $va = array_key_exists($path, $fa) ? $fa[$path] : '__missing__';
    $vb = array_key_exists($path, $fb) ? $fb[$path] : '__missing__';
    if (serialize($va) !== serialize($vb)) {
        $result['arraysubs_settings_changed_paths'][$path] = ['before' => $va, 'after' => $vb];
    }
}
$result['all_identical'] = ! in_array('CHANGED', array_merge($result['options'], $result['hash_only'], $result['sections']), true) && [] === $result['arraysubs_settings_changed_paths'];

echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION), "\n";
