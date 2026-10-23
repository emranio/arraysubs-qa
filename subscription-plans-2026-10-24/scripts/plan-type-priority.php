<?php
// Run through WP-CLI eval-file --allow-root. Exercises preparation without writes.

wp_set_current_user(1);
$controller = new ArraySubs\Features\QuickProductCreation\REST\ProductController();
$prepare = new ReflectionMethod($controller, 'prepare');
$prepare->setAccessible(true);
$checks = 0;
$assert = static function ($condition, $description) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($description);
    }
    ++$checks;
};
$base = [
    'type' => 'simple', 'title' => 'Preparation check only', 'regular_price' => '29', 'virtual' => true,
];
$settings = [
    '_subscription_length' => 9,
    '_subscription_interval' => 2,
    '_arraysubs_fixed_end_date_enabled' => true,
    '_arraysubs_fixed_end_date' => '01-31',
    '_arraysubs_fixed_end_date_type' => 'recurring_annual',
    '_arraysubs_flexible_periods' => ['week'],
];

if (arraysubs_has_module('FlexibleSubscription') !== 1) {
    $core = $prepare->invoke($controller, $base + ['settings' => $settings + ['_arraysubs_subscription_mode' => 'full_flexible']]);
    $assert(!is_wp_error($core), 'Core creation accepts inactive premium settings');
    $assert($core['meta']['_subscription_length'] === 9, 'Core creation keeps the finite billing length');
    $assert(!isset($core['meta']['_arraysubs_subscription_mode']), 'Core does not enable an unavailable premium mode');
    $assert(!isset($core['meta']['_arraysubs_fixed_end_date_enabled']), 'Core does not enable an unavailable membership cutoff');
    echo wp_json_encode(['passed' => $checks, 'products_created' => 0, 'context' => 'core-only'], JSON_PRETTY_PRINT) . "\n";
    return;
}

foreach (['flexible_length', 'full_flexible'] as $mode) {
    $result = $prepare->invoke($controller, $base + ['settings' => $settings + ['_arraysubs_subscription_mode' => $mode]]);
    $assert(!is_wp_error($result), "$mode accepts a stale fixed-date flag");
    $assert($result['meta']['_arraysubs_subscription_mode'] === $mode, "$mode remains the selected type");
    $assert($result['meta']['_subscription_length'] === 9, "$mode keeps its maximum length");
    $assert(!isset($result['meta']['_arraysubs_fixed_end_date_enabled']), "$mode has no fixed membership cutoff");
    if ($mode === 'full_flexible') {
        $assert($result['meta']['_subscription_interval'] === 1, 'Full Flexible keeps interval one');
        $assert($result['meta']['_subscription_period'] === 'week', 'Full Flexible uses an offered billing period');
    }
    $invalid_date = array_replace($settings, ['_arraysubs_subscription_mode' => $mode, '_arraysubs_fixed_end_date' => 'invalid', '_arraysubs_enrollment_window_start' => 'invalid']);
    $assert(!is_wp_error($prepare->invoke($controller, $base + ['settings' => $invalid_date])), "$mode ignores inactive membership dates");
}

$fixed_settings = $settings + ['_arraysubs_subscription_mode' => 'fixed'];
$fixed = $prepare->invoke($controller, $base + ['settings' => $fixed_settings]);
$assert(!is_wp_error($fixed), 'Fixed accepts a valid membership cutoff');
$assert($fixed['meta']['_subscription_length'] === 0, 'Fixed membership uses the calendar cutoff');
$assert($fixed['meta']['_arraysubs_fixed_end_date_enabled'] === 'yes', 'Fixed membership remains enabled');
$assert(is_wp_error($prepare->invoke($controller, $base + ['settings' => array_replace($fixed_settings, ['_arraysubs_fixed_end_date' => 'invalid'])])), 'Fixed validates active membership dates');
$plain = $prepare->invoke($controller, $base + ['settings' => array_replace($fixed_settings, ['_arraysubs_fixed_end_date_enabled' => false])]);
$assert($plain['meta']['_subscription_length'] === 9, 'Fixed without membership preserves its finite length');
$assert(is_wp_error($prepare->invoke($controller, $base + ['settings' => array_replace($settings, ['_arraysubs_subscription_mode' => 'invalid'])])), 'Invalid subscription types remain rejected');

echo wp_json_encode(['passed' => $checks, 'products_created' => 0], JSON_PRETTY_PRINT) . "\n";
