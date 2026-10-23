<?php
/**
 * Real registered REST/data regression supplement for the Plans browser cycle.
 * Run: /tmp/plans-wp eval-file <absolute-script-path> pro
 * Core: /tmp/plans-wp --skip-plugins=elementor,elementor-pro,arraysubspro eval-file <absolute-script-path> core
 * Only test-owned products/users are changed; cleanup runs in finally.
 */
$mode = $args[0] ?? 'pro';
$report = ['mode' => $mode, 'date' => '2026-10-24', 'timezone' => 'Asia/Dhaka', 'checks' => [], 'fixtures' => [], 'cleanup' => []];
$GLOBALS['plans_qa_report'] =& $report;
$created_products = [];
$created_users = [];
$admin = get_user_by('login', 'admin');
$admin_id = $admin ? $admin->ID : 1;
wp_set_current_user($admin_id);

function plans_rest_call($method, $route, $body = null, $query = []) {
    $request = new WP_REST_Request($method, '/arraysubs/v1' . $route);
    $request->set_query_params($query);
    if (null !== $body) {
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(is_string($body) ? $body : wp_json_encode($body));
    }
    $response = rest_do_request($request);
    $data = $response->get_data();
    return ['status' => $response->get_status(), 'data' => $data['content'] ?? $data];
}
function plans_check($name, $passed, $evidence = []) {
    $report =& $GLOBALS['plans_qa_report'];
    $report['checks'][] = ['name' => $name, 'passed' => (bool) $passed, 'evidence' => $evidence];
    if (!$passed) {
        fwrite(STDERR, 'FAIL: ' . $name . ' ' . wp_json_encode($evidence) . "\n");
    }
}
function plans_snapshot($id) {
    $product = wc_get_product($id);
    $product->read_meta_data(true);
    return json_decode(wp_json_encode($product->get_data()), true);
}
function plans_input($id) {
    $response = plans_rest_call('GET', '/quick-products/' . $id);
    $data = $response['data'];
    $values = $data['values'] ?? [];
    return [
        'type' => $data['type'] ?? 'simple', 'version' => $data['version'] ?? '',
        'title' => $values['title'] ?? '', 'description' => $values['description'] ?? '',
        'regular_price' => $values['regular_price'] ?? '', 'sale_price' => $values['sale_price'] ?? '',
        'virtual' => $values['virtual'] ?? false,
        'settings' => array_filter($values, static fn($key) => str_starts_with($key, '_'), ARRAY_FILTER_USE_KEY),
    ];
}
function plans_update($id, $changes = [], $settings = []) {
    $input = plans_input($id);
    $input = array_merge($input, $changes);
    $input['settings'] = array_merge($input['settings'], $settings);
    return plans_rest_call('POST', '/quick-products/' . $id, $input);
}

try {
    $report['environment'] = ['admin_id' => $admin_id, 'pro_loaded' => defined('ARRAYSUBSPRO_VERSION'), 'rest_base' => rest_url()];
    plans_check('Expected plugin mode is loaded', ('pro' === $mode) === defined('ARRAYSUBSPRO_VERSION'), $report['environment']);
    $product = new WC_Product_Simple();
    $product->set_name('Plans QA 2026-10-24 REST supplement ' . $mode);
    $product->set_description('<p>Preserve <strong>rich description</strong> and shortcode [qa_plan].</p>');
    $product->set_status('private');
    $product->set_slug('plans-qa-rest-' . $mode . '-' . wp_generate_uuid4());
    $product->set_sku('PLANS-QA-REST-' . $mode . '-' . wp_generate_uuid4());
    $product->set_regular_price('79.90');
    $product->set_sale_price('59.90');
    $product->set_date_on_sale_from(time() + DAY_IN_SECONDS);
    $product->set_date_on_sale_to(time() + 5 * DAY_IN_SECONDS);
    $product->set_price('79.90');
    $product->set_virtual(false);
    $product->set_manage_stock(true);
    $product->set_stock_quantity(17);
    $product->set_stock_status('instock');
    $product->set_backorders('notify');
    $product->set_weight('2.5');
    $product->set_length('12');
    $product->set_width('9');
    $product->set_height('3');
    $product->set_tax_status('taxable');
    $product->set_sold_individually(true);
    $product->set_purchase_note('Plans REST preservation sentinel');
    $product->set_short_description('Preserve short description');
    $product->set_reviews_allowed(false);
    $product->set_menu_order(42);
    $product->set_catalog_visibility('search');
    $attribute = new WC_Product_Attribute();
    $attribute->set_name('QA size');
    $attribute->set_options(['Small', 'Large']);
    $attribute->set_visible(true);
    $product->set_attributes([$attribute]);
    foreach (['_is_subscription' => 'yes', '_subscription_period' => 'month', '_subscription_interval' => 1, '_subscription_length' => 0, '_trial_length' => 0, '_trial_period' => 'day', '_signup_fee' => '2', '_plans_qa_unrelated' => ['a' => 'preserve', 'b' => 17]] as $key => $value) {
        $product->update_meta_data($key, $value);
    }
    $id = $product->save();
    $created_products[] = $id;
    $report['fixtures']['plan'] = $id;
    $before = plans_snapshot($id);
    $get = plans_rest_call('GET', '/quick-products/' . $id);
    plans_check('Existing plan GET returns prefilled prices/billing/version', 200 === $get['status'] && $get['data']['values']['regular_price'] === '79.90' && !empty($get['data']['version']), ['status' => $get['status'], 'values' => $get['data']['values'] ?? []]);
    $catalog = plans_rest_call('GET', '/plans', null, ['search' => 'Plans QA 2026-10-24 REST supplement ' . $mode, 'status' => 'private']);
    plans_check('Dedicated private plan appears in subscription catalog', 200 === $catalog['status'] && in_array($id, array_column($catalog['data'], 'id'), true), ['status' => $catalog['status'], 'ids' => array_column($catalog['data'], 'id')]);

    $invalids = [
        'Changed product type rejected' => ['type' => 'box'],
        'Invalid title shape rejected' => ['title' => ['invalid']],
        'Empty title rejected' => ['title' => ''],
        'Invalid description shape rejected' => ['description' => ['invalid']],
        'Zero price rejected' => ['regular_price' => 0],
        'Negative sale rejected' => ['sale_price' => -2],
        'Sale above regular price rejected' => ['sale_price' => 90],
        'Invalid settings shape rejected' => ['settings' => 'invalid'],
        'Nested billing value rejected' => ['settings' => ['_subscription_interval' => ['invalid']]],
        'Out-of-range interval rejected' => ['settings' => ['_subscription_interval' => 13]],
        'Invalid period rejected' => ['settings' => ['_subscription_period' => 'invalid']],
        'Missing version rejected' => ['version' => null],
    ];
    foreach ($invalids as $name => $changes) {
        $input = plans_input($id);
        $input = array_merge($input, $changes);
        $response = plans_rest_call('POST', '/quick-products/' . $id, $input);
        plans_check($name, in_array($response['status'], [400, 409], true) && plans_snapshot($id) === $before, ['status' => $response['status'], 'error' => $response['data']['code'] ?? '', 'unchanged' => plans_snapshot($id) === $before]);
    }
    foreach (['Malformed JSON rejected' => '{invalid', 'Oversized JSON rejected' => ['type' => 'simple', 'description' => str_repeat('a', 262145)]] as $name => $body) {
        $response = plans_rest_call('POST', '/quick-products/' . $id, $body);
        plans_check($name, 400 === $response['status'] && plans_snapshot($id) === $before, ['status' => $response['status'], 'unchanged' => plans_snapshot($id) === $before]);
    }

    $stale = plans_input($id);
    $save = plans_update($id, [], ['_subscription_interval' => 2, '_trial_length' => 3, '_trial_period' => 'day', '_signup_fee' => '8.25']);
    plans_check('Valid billing update succeeds', 200 === $save['status'] && 2 === (int) get_post_meta($id, '_subscription_interval', true), ['status' => $save['status']]);
    $after = plans_snapshot($id);
    $fields = ['status', 'slug', 'sku', 'stock_quantity', 'stock_status', 'manage_stock', 'backorders', 'weight', 'length', 'width', 'height', 'tax_status', 'sold_individually', 'purchase_note', 'short_description', 'reviews_allowed', 'menu_order', 'catalog_visibility', 'attributes', 'date_on_sale_from', 'date_on_sale_to', 'regular_price', 'sale_price', 'price'];
    $different = [];
    foreach ($fields as $field) {
        if ($before[$field] !== $after[$field]) $different[$field] = ['before' => $before[$field], 'after' => $after[$field]];
    }
    plans_check('Scheduled sale and unrelated WooCommerce properties are preserved', !$different && ['a' => 'preserve', 'b' => 17] === get_post_meta($id, '_plans_qa_unrelated', true), ['differences' => $different]);
    $response = plans_rest_call('POST', '/quick-products/' . $id, $stale);
    plans_check('Stale version rejected without overwriting the new billing', 409 === $response['status'] && plans_snapshot($id) === $after, ['status' => $response['status'], 'error' => $response['data']['code'] ?? '']);

    $nonplan = new WC_Product_Simple();
    $nonplan->set_name('Plans QA 2026-10-24 REST supplement nonplan ' . $mode);
    $nonplan->set_regular_price('10');
    $nonplan_id = $nonplan->save();
    $created_products[] = $nonplan_id;
    $report['fixtures']['nonplan'] = $nonplan_id;
    foreach (['GET', 'POST'] as $method) {
        $response = plans_rest_call($method, '/quick-products/' . $nonplan_id, 'POST' === $method ? $stale : null);
        plans_check('Nonplan ' . $method . ' denied', 404 === $response['status'], ['status' => $response['status']]);
    }
    $response = plans_rest_call('GET', '/quick-products/2147483647');
    plans_check('Missing plan GET denied', 404 === $response['status'], ['status' => $response['status']]);
    $response = plans_rest_call('POST', '/plans/' . $nonplan_id . '/actions', ['action' => 'trash']);
    plans_check('Nonplan row action denied', 404 === $response['status'], ['status' => $response['status']]);
    $response = plans_rest_call('POST', '/plans/' . $id . '/actions', ['action' => 'bogus']);
    plans_check('Unknown row action denied', 400 === $response['status'], ['status' => $response['status']]);

    $user_suffix = strtolower(str_replace('-', '', wp_generate_uuid4()));
    $customer_id = wp_insert_user(['user_login' => 'plans_qa_rest_customer_' . $user_suffix, 'user_email' => 'plans-qa-rest-customer-' . $user_suffix . '@example.invalid', 'user_pass' => wp_generate_password(32), 'role' => 'customer']);
    $editor_id = wp_insert_user(['user_login' => 'plans_qa_rest_editor_' . $user_suffix, 'user_email' => 'plans-qa-rest-editor-' . $user_suffix . '@example.invalid', 'user_pass' => wp_generate_password(32), 'role' => 'subscriber']);
    if (is_wp_error($customer_id) || is_wp_error($editor_id)) throw new RuntimeException('Fixture user creation failed.');
    $created_users = [$customer_id, $editor_id];
    $editor = new WP_User($editor_id);
    foreach (['manage_woocommerce', 'edit_products', 'edit_others_products', 'edit_published_products', 'edit_private_products', 'read_private_products'] as $cap) $editor->add_cap($cap);
    $editor->add_cap('publish_products', false);
    $report['fixtures']['customer'] = ['id' => $customer_id, 'login' => 'plans_qa_rest_customer_' . $user_suffix, 'role' => 'customer'];
    $report['fixtures']['editor'] = ['id' => $editor_id, 'login' => 'plans_qa_rest_editor_' . $user_suffix, 'role' => 'subscriber with individual edit/manage caps; publish_products=false'];
    $authorized_input = plans_input($id);
    foreach (['anonymous' => 0, 'customer' => $customer_id] as $context => $user_id) {
        wp_set_current_user($user_id);
        foreach ([['GET', '/plans'], ['GET', '/plans/counts'], ['GET', '/quick-products/options'], ['GET', '/quick-products/' . $id], ['POST', '/quick-products/' . $id], ['POST', '/plans/' . $id . '/actions']] as [$method, $route]) {
            $body = 'POST' === $method ? (str_contains($route, '/actions') ? ['action' => 'featured'] : $authorized_input) : null;
            $response = plans_rest_call($method, $route, $body);
            plans_check($context . ' denied ' . $method . ' ' . $route, in_array($response['status'], [401, 403], true), ['status' => $response['status']]);
        }
    }
    wp_set_current_user($editor_id);
    foreach (['/plans', '/quick-products/options', '/quick-products/' . $id] as $route) {
        $response = plans_rest_call('GET', $route);
        plans_check('Editor without publish can read ' . $route, 200 === $response['status'], ['status' => $response['status'], 'can_publish' => current_user_can('publish_products')]);
    }
    $response = plans_update($id, [], ['_signup_fee' => '6.50']);
    plans_check('Editor without publish can update existing billing', 200 === $response['status'], ['status' => $response['status'], 'can_publish' => current_user_can('publish_products')]);
    $response = plans_rest_call('POST', '/quick-products', array_merge($authorized_input, ['request_id' => 'plans-qa-no-publish-' . $user_suffix]));
    plans_check('Editor without publish cannot create a product', 403 === $response['status'], ['status' => $response['status']]);
    wp_set_current_user($admin_id);

    $options = plans_rest_call('GET', '/quick-products/options')['data'];
    $report['environment']['capabilities'] = $options['capabilities'];
    if ('pro' === $mode) {
        $enabled = plans_update($id, [], ['_trial_length' => 0, '_enable_renewal_price' => true, '_renewal_price' => '45', '_renewal_price_after' => 3]);
        plans_check('Different renewal price can be enabled', 200 === $enabled['status'] && 'yes' === get_post_meta($id, '_enable_renewal_price', true), ['status' => $enabled['status']]);
        $disabled = plans_update($id, [], ['_enable_renewal_price' => false]);
        plans_check('Disabling different renewal price clears all owned keys', 200 === $disabled['status'] && !metadata_exists('post', $id, '_enable_renewal_price') && !metadata_exists('post', $id, '_renewal_price') && !metadata_exists('post', $id, '_renewal_price_after'), ['status' => $disabled['status']]);

        $enabled = plans_update($id, [], ['_arraysubs_fixed_end_date_enabled' => true, '_arraysubs_fixed_end_date_type' => 'recurring_annual', '_arraysubs_fixed_end_date' => '12-31', '_arraysubs_fixed_end_renewal' => 'renew', '_arraysubs_enrollment_window_start' => '2026-01-01', '_arraysubs_enrollment_window_end' => '2027-12-31']);
        plans_check('Fixed period membership can be enabled', 200 === $enabled['status'] && 'yes' === get_post_meta($id, '_arraysubs_fixed_end_date_enabled', true), ['status' => $enabled['status']]);
        $disabled = plans_update($id, [], ['_arraysubs_fixed_end_date_enabled' => false]);
        $fixed_keys = ['_arraysubs_fixed_end_date_enabled', '_arraysubs_fixed_end_date_type', '_arraysubs_fixed_end_date', '_arraysubs_fixed_end_renewal', '_arraysubs_enrollment_window_start', '_arraysubs_enrollment_window_end'];
        $fixed_left = array_values(array_filter($fixed_keys, static fn($key) => metadata_exists('post', $id, $key)));
        plans_check('Disabling fixed period membership clears owned keys', 200 === $disabled['status'] && !$fixed_left, ['status' => $disabled['status'], 'remaining' => $fixed_left]);

        $enabled = plans_update($id, [], ['_arraysubs_subscription_mode' => 'full_flexible', '_arraysubs_flexible_periods' => ['month', 'year'], '_arraysubs_flex_sync_enabled' => true, '_arraysubs_flex_sync_seg1_end' => 10, '_arraysubs_flex_sync_seg2_end' => 20]);
        plans_check('Flexible mode and renewal sync can be enabled', 200 === $enabled['status'] && 'full_flexible' === get_post_meta($id, '_arraysubs_subscription_mode', true) && 'yes' === get_post_meta($id, '_arraysubs_flex_sync_enabled', true), ['status' => $enabled['status']]);
        $disabled = plans_update($id, [], ['_arraysubs_subscription_mode' => 'fixed', '_arraysubs_flex_sync_enabled' => false]);
        $sync_left = array_values(array_filter(['_arraysubs_flex_sync_enabled', '_arraysubs_flex_sync_seg1_end', '_arraysubs_flex_sync_seg2_end', '_arraysubs_flex_sync_seg1_active', '_arraysubs_flex_sync_seg2_active', '_arraysubs_flex_sync_seg3_active'], static fn($key) => metadata_exists('post', $id, $key)));
        plans_check('Disabling renewal sync clears owned keys', 200 === $disabled['status'] && !$sync_left, ['status' => $disabled['status'], 'remaining' => $sync_left]);

        $enabled = plans_update($id, ['virtual' => false], ['_arraysubs_shipping_type' => 'recurring', '_arraysubs_initial_shipping_override' => '7.50', '_arraysubs_renewal_shipping_override' => '9']);
        plans_check('Physical shipping overrides can be enabled', 200 === $enabled['status'] && '7.50' === get_post_meta($id, '_arraysubs_initial_shipping_override', true), ['status' => $enabled['status']]);
        $disabled = plans_update($id, ['virtual' => true]);
        $shipping_left = array_values(array_filter(['_arraysubs_shipping_type', '_arraysubs_initial_shipping_override', '_arraysubs_renewal_shipping_override'], static fn($key) => metadata_exists('post', $id, $key)));
        plans_check('Switching virtual clears shipping owned keys', 200 === $disabled['status'] && !$shipping_left, ['status' => $disabled['status'], 'remaining' => $shipping_left]);
    } else {
        foreach (['_enable_renewal_price' => 'yes', '_renewal_price' => '44', '_renewal_price_after' => 2, '_arraysubs_shipping_type' => 'one-time', '_arraysubs_initial_shipping_override' => '7.50', '_arraysubs_subscription_mode' => 'full_flexible', '_arraysubs_flexible_periods' => ['month', 'year'], '_arraysubs_fixed_end_date_enabled' => 'yes', '_arraysubs_fixed_end_date' => '12-31', '_arraysubs_flex_sync_enabled' => 'yes'] as $key => $value) update_post_meta($id, $key, $value);
        $protected_keys = ['_enable_renewal_price', '_renewal_price', '_renewal_price_after', '_arraysubs_shipping_type', '_arraysubs_initial_shipping_override', '_arraysubs_subscription_mode', '_arraysubs_flexible_periods', '_arraysubs_fixed_end_date_enabled', '_arraysubs_fixed_end_date', '_arraysubs_flex_sync_enabled'];
        $protected_before = [];
        foreach ($protected_keys as $key) $protected_before[$key] = get_post_meta($id, $key, true);
        $saved = plans_update($id, [], ['_subscription_interval' => 3, '_signup_fee' => '11']);
        $protected_after = [];
        foreach ($protected_keys as $key) $protected_after[$key] = get_post_meta($id, $key, true);
        plans_check('Core-only billing update succeeds and preserves unavailable Pro metadata', 200 === $saved['status'] && $protected_before === $protected_after, ['status' => $saved['status'], 'before' => $protected_before, 'after' => $protected_after]);
    }

    $lifetime = plans_update($id, [], ['_subscription_period' => 'lifetime', '_subscription_interval' => 8, '_subscription_length' => 12, '_trial_length' => 7, '_enable_renewal_price' => true, '_renewal_price' => '39', '_arraysubs_fixed_end_date_enabled' => true]);
    $life_meta = [];
    foreach (['_subscription_period', '_subscription_interval', '_subscription_length', '_trial_length', '_trial_period', '_enable_renewal_price', '_arraysubs_fixed_end_date_enabled', '_arraysubs_flex_sync_enabled'] as $key) $life_meta[$key] = get_post_meta($id, $key, true);
    plans_check('Lifetime normalizes interval/length/trial and saves', 200 === $lifetime['status'] && 'lifetime' === $life_meta['_subscription_period'] && 1 === (int) $life_meta['_subscription_interval'] && 0 === (int) $life_meta['_subscription_length'] && 0 === (int) $life_meta['_trial_length'], ['status' => $lifetime['status'], 'meta' => $life_meta]);
    if ('pro' === $mode) plans_check('Lifetime clears incompatible active optional fields', '' === $life_meta['_enable_renewal_price'] && '' === $life_meta['_arraysubs_fixed_end_date_enabled'] && '' === $life_meta['_arraysubs_flex_sync_enabled'], $life_meta);

    $trash = plans_rest_call('POST', '/plans/' . $id . '/actions', ['action' => 'trash']);
    plans_check('Plan can be moved to trash', 200 === $trash['status'] && 'trash' === get_post_status($id), ['status' => $trash['status']]);
    foreach (['GET', 'POST'] as $method) {
        $response = plans_rest_call($method, '/quick-products/' . $id, 'POST' === $method ? $stale : null);
        plans_check('Trashed plan billing ' . $method . ' denied', 404 === $response['status'], ['status' => $response['status']]);
    }
    $restore = plans_rest_call('POST', '/plans/' . $id . '/actions', ['action' => 'restore']);
    plans_check('Trash restore succeeds', 200 === $restore['status'] && 'trash' !== get_post_status($id), ['status' => $restore['status'], 'restored_status' => get_post_status($id)]);
} catch (Throwable $error) {
    plans_check('Suite completed without unexpected exception', false, ['exception' => get_class($error), 'message' => $error->getMessage(), 'line' => $error->getLine()]);
} finally {
    wp_set_current_user($admin_id);
    foreach ($created_products as $product_id) {
        wp_delete_post($product_id, true);
        $report['cleanup']['products'][$product_id] = !get_post($product_id);
    }
    if (!function_exists('wp_delete_user')) require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach ($created_users as $user_id) {
        wp_delete_user($user_id);
        $report['cleanup']['users'][$user_id] = !get_user_by('id', $user_id);
    }
    $report['passed'] = count(array_filter($report['checks'], static fn($check) => $check['passed']));
    $report['failed'] = count($report['checks']) - $report['passed'];
    $report['report_path'] = dirname(__DIR__) . '/evidence/rest-data-' . $mode . '.json';
    file_put_contents($report['report_path'], wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    echo wp_json_encode(['mode' => $mode, 'passed' => $report['passed'], 'failed' => $report['failed'], 'fixtures' => $report['fixtures'], 'cleanup' => $report['cleanup'], 'report' => $report['report_path']], JSON_UNESCAPED_SLASHES) . "\n";
}
