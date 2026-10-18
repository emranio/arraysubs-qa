<?php

use ArraySubs\Developer\Customer;
use ArraySubs\Developer\Features;
use ArraySubs\Developer\Product;

$checks = 0;
$assert = static function ($condition, $message) use (&$checks) {
    ++$checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$error = static function ($value, $code) { return is_wp_error($value) && $value->get_error_code() === $code; };
$users = [];
$posts = [];
$original_user = get_current_user_id();
$original_profile_config = get_option('arraysubs_profile_fields_config', null);
$profile_available = class_exists('ArraySubsPro\\Developer\\CustomerProfile');
try {
    $admin_ids = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
    wp_set_current_user((int) $admin_ids[0]);
    foreach (['one', 'two'] as $suffix) {
        $users[] = wp_insert_user(['user_login' => 'developer-api-' . $suffix . '-' . wp_generate_password(6, false), 'user_email' => 'developer-' . $suffix . '-' . wp_generate_password(6, false) . '@example.test', 'user_pass' => wp_generate_password(30), 'role' => 'customer']);
    }
    $customer = Customer::fromUserId($users[0]);
    $assert($customer instanceof WC_Customer && $customer->get_id() === $users[0], 'Canonical customer extends WC_Customer.');
    $assert(Customer::fromEmail($customer->get_email())->get_id() === $users[0], 'Lookup by account email.');
    $assert($error(Customer::fromEmail('*' . $customer->get_email()), 'not_found'), 'Legal email asterisks never become identity wildcards.');
    $assert(Customer::fromUsername($customer->get_username())->get_id() === $users[0], 'Lookup by username.');
    $assert($error(Customer::fromUserId(0), 'not_found'), 'Zero is not a registered customer.');
    $assert($error($customer->data(['raw_meta']), 'invalid_field'), 'Unknown customer fields rejected.');
    $assert(array_keys($customer->data(['identity'])) === ['identity'], 'Partial customer projection.');

    $product = new WC_Product_Simple();
    $product->set_name('Developer API fixture ' . wp_generate_password(6, false));
    $product->set_status('publish');
    $product->set_regular_price('12');
    $product_id = $product->save();
    $posts[] = $product_id;
    $product = wc_get_product($product_id);
    update_post_meta($product_id, '_is_subscription', 'yes');
    update_post_meta($product_id, '_subscription_period', 'month');
    update_post_meta($product_id, '_subscription_interval', 2);
    update_post_meta($product_id, '_arraysubs_features', wp_json_encode(arraysubs_sanitize_product_features([
        ['name' => 'Seats', 'value' => '10', 'type' => 'number', 'enabled' => true, 'reset_period' => 'day'],
    ])));
    $subscription_id = wp_insert_post(['post_type' => 'arraysubs_data', 'post_status' => 'arraysubs-active', 'meta_input' => ['_customer_id' => $users[0], '_customer_email' => 'historical@example.test', '_product_id' => $product_id]]);
    $posts[] = $subscription_id;
    $guest_id = wp_insert_post(['post_type' => 'arraysubs_data', 'post_status' => 'arraysubs-active', 'meta_input' => ['_customer_id' => 0, '_customer_email' => $customer->get_email(), '_product_id' => $product_id]]);
    $posts[] = $guest_id;
    $assert(Customer::fromSubscriptionId($subscription_id)->get_id() === $users[0], 'Subscription identity resolves registered account.');
    $guest = Customer::fromSubscriptionId($guest_id);
    $assert(is_array($guest) && $guest['guest'] && null === $guest['user_id'] && $guest['subscription_id'] === $guest_id, 'Guest stays explicit even when email matches an account.');
    $updated = $customer->update(['account' => ['first_name' => 'Developer'], 'billing' => ['email' => 'billing@example.test']]);
    $assert(!is_wp_error($updated) && $updated['account']['first_name'] === 'Developer' && $customer->get_email() !== 'billing@example.test', 'Billing email is separate from account email.');
    $assert(get_post_meta($subscription_id, '_customer_email', true) === 'historical@example.test', 'Customer update preserves historical subscription identity.');
    $bad = $customer->update(['account' => ['first_name' => 'Wrong', 'arbitrary_meta' => 'No']]);
    $assert($error($bad, 'invalid_field') && Customer::fromUserId($users[0])->get_first_name() === 'Developer', 'Whole account request validates before persistence.');
    $assert($error($customer->update(['account' => ['email' => Customer::fromUserId($users[1])->get_email()]]), 'conflict'), 'Account email cannot merge registered users.');

    $resolved = Product::fromId($product_id);
    $assert($resolved instanceof Product && Product::fromSlug($product->get_slug())->object()->get_id() === $product_id, 'Product resolves by ID and slug.');
    $url_product = Product::fromUrl($product->get_permalink() . '#ignored');
    $assert($url_product instanceof Product && $url_product->object()->get_id() === $product_id, 'Canonical local URL resolves, fragment ignored.');
    $assert($error(Product::fromUrl('https://example.invalid/product/test/'), 'validation'), 'External URL rejected without fetch.');
    $assert($error(Product::fromUrl(add_query_arg('unknown', 'value', $product->get_permalink())), 'validation'), 'Unknown product URL query rejected.');
    $terms = $resolved->data(['billing', 'pricing']);
    $assert($terms['billing']['period'] === 'month' && $terms['billing']['interval'] === 2 && $terms['pricing']['price'] === '12', 'Catalog terms match existing helpers.');
    $assert($error($resolved->data(['secret']), 'invalid_field'), 'Unknown product field rejected.');
    $assert(!method_exists($resolved, 'save') && !method_exists($resolved, 'update') && !method_exists($resolved, 'delete'), 'Product facade exposes no writes.');
    update_post_meta($product_id, '_is_subscription', 'no');
    $assert($error(Product::fromId($product_id), 'unsupported'), 'Ordinary products rejected.');
    update_post_meta($product_id, '_is_subscription', 'yes');
    wp_update_post(['ID' => $product_id, 'post_status' => 'draft']);
    wp_set_current_user(0);
    $assert($error(Product::fromId($product_id), 'forbidden'), 'Unpublished products honor current actor visibility.');
    wp_set_current_user((int) $admin_ids[0]);
    wp_update_post(['ID' => $product_id, 'post_status' => 'publish']);

    $variable = new WC_Product_Variable();
    $variable->set_name('Developer variable API ' . wp_generate_password(6, false));
    $variable->set_status('publish');
    $attribute = new WC_Product_Attribute();
    $attribute->set_name('Size');
    $attribute->set_options(['Small size', 'Large size']);
    $attribute->set_variation(true);
    $variable->set_attributes([$attribute]);
    $variable_id = $variable->save();
    $posts[] = $variable_id;
    update_post_meta($variable_id, '_is_subscription', 'yes');
    $variation_ids = [];
    foreach (['Small size', 'Large size'] as $size) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($variable_id);
        $variation->set_status('publish');
        $variation->set_attributes(['size' => $size]);
        $variation->set_regular_price('10');
        $variation_id = $variation->save();
        $variation_ids[] = $variation_id;
        $posts[] = $variation_id;
        update_post_meta($variation_id, '_is_subscription', 'yes');
    }
    $variable = wc_get_product($variable_id);
    $variation_url = add_query_arg('attribute_size', 'Large size', $variable->get_permalink());
    $url_variation = Product::fromUrl($variation_url);
    $assert($url_variation instanceof Product && $url_variation->object()->get_id() === $variation_ids[1], 'Local attribute values with spaces resolve to the correct variation.');
    $ambiguous = Product::fromUrl(add_query_arg('attribute_size', '', $variable->get_permalink()));
    $assert(is_wp_error($ambiguous), 'Unmatched or ambiguous variation selection returns an error.');
    $variation_page = Product::fromId($variable_id)->variations(['per_page' => 1, 'output' => 'ids']);
    $assert($variation_page['items'] === [$variation_ids[0]] && $variation_page['has_more'], 'Related variations use stable independent pagination.');
    wp_update_post(['ID' => $variation_ids[0], 'post_status' => 'private']);
    wp_set_current_user(0);
    $assert($error(Product::fromId($variation_ids[0]), 'forbidden'), 'A disabled variation is private even when its parent is published.');
    wp_set_current_user((int) $admin_ids[0]);

    $feature_api = Features::forSubscription($subscription_id);
    $assert($error($feature_api->consume('Seats', 1), 'validation'), 'Metering facade requires an audit reason.');
    $assert(!is_wp_error($feature_api->consume('Seats', 2, ['reason' => 'Regression reservation', 'operation_id' => 'reservation'])), 'Facade uses atomic core consume.');
    $feature_api->consume('Seats', 2, ['reason' => 'Regression reservation', 'operation_id' => 'reservation']);
    $assert($feature_api->usage('Seats')['used'] === 2.0, 'Facade preserves idempotency.');
    $effective = $feature_api->effective();
    $assert(!isset($effective['items'][0]['type']) && $effective['items'][0]['source_subscription_id'] === $subscription_id, 'Effective features hide internal types and identify source.');
    $customer_features = Features::forCustomer($users[0], 'max');
    $assert($customer_features->usage('Seats')['source_subscription_id'] === $subscription_id, 'Customer feature mode selects its source.');
    $assert($error(Features::forProduct($product_id)->usage('Seats'), 'unsupported'), 'Product configuration has no usage counter.');
    if ($profile_available) {
        $saved = Features::forProduct($product_id)->updateConfiguration('Seats', ['value' => '12'], ['expected' => ['value' => '10']]);
        $assert(!is_wp_error($saved), 'Feature assignment uses shared Pro validator.');
        $assert($error(Features::forProduct($product_id)->updateConfiguration('Seats', ['value' => '14'], ['expected' => ['value' => '10']]), 'conflict'), 'Feature update detects stale expected state.');
        $assert($error(Features::forProduct($product_id)->updateConfiguration('Missing', ['value' => 5]), 'not_found'), 'Feature update cannot create assignments.');
        $assert($error(Features::forProduct($product_id)->updateConfiguration('Seats', ['reset_period' => 'invalid']), 'validation'), 'Invalid feature periods rejected.');
        update_option('arraysubs_profile_fields_config', arraysubs_sanitize_profile_fields_config([
            ['key' => 'reference', 'type' => 'text', 'enabled' => true],
            ['key' => 'birthday', 'type' => 'date', 'enabled' => true],
            ['key' => 'region', 'type' => 'select', 'enabled' => true, 'options' => [['value' => 'EU', 'label' => 'Europe'], ['value' => 'US', 'label' => 'United States']]],
            ['key' => 'document', 'type' => 'upload', 'enabled' => true],
        ]));
        foreach ($users as $id) {
            arraysubs_save_user_profile_field($id, 'reference', 'shared-reference');
        }
        arraysubs_save_user_profile_field($users[0], 'document', 'private.pdf');
        $profile = $customer->data(['profile']);
        $assert(in_array('document', $profile['profile']['redacted'], true) && !isset($profile['profile']['values']['document']), 'Uploads are redacted.');
        $assert('' === get_user_meta($users[0], 'arraysubs_pf_dir_token', true), 'Profile reads never create upload tokens.');
        $matches = Customer::queryByProfileField('reference', 'shared-reference', ['per_page' => 1, 'output' => 'ids']);
        $assert($matches['items'] === [$users[0]] && $matches['has_more'] && $matches['total'] === 2, 'Profile query uses stable bounded pagination.');
        $assert($error(Customer::fromProfileField('reference', 'shared-reference'), 'ambiguous'), 'Singular nonunique profile lookup is ambiguous.');
        $assert($error(Customer::queryByProfileField('not_registered', 'x'), 'invalid_field'), 'Arbitrary metadata cannot be queried.');
        $assert($error(Customer::queryByProfileField('document', 'private.pdf'), 'forbidden'), 'File fields are not searchable.');
        $assert($error($customer->update(['profile' => ['birthday' => '2026-02-30']]), 'validation'), 'Impossible profile dates rejected.');
        $assert($error($customer->update(['profile' => ['region' => 'Unknown']]), 'validation'), 'Profile select uses configured choices.');
        $assert(!is_wp_error($customer->update(['profile' => ['birthday' => '2026-02-28', 'region' => 'EU']])), 'Valid typed profile updates persist.');
        $reject_profile_write = static function ($check, $id, $key) use ($users) {
            return $id === $users[0] && 'arraysubs_pf_region' === $key ? false : $check;
        };
        add_filter('update_user_metadata', $reject_profile_write, 10, 3);
        $failed_profile = $customer->update(['account' => ['first_name' => 'Wrong'], 'profile' => ['birthday' => '2027-01-01', 'region' => 'US']]);
        remove_filter('update_user_metadata', $reject_profile_write);
        $assert(is_wp_error($failed_profile) && get_user_meta($users[0], 'arraysubs_pf_birthday', true) === '2026-02-28' && Customer::fromUserId($users[0])->get_first_name() === 'Developer', 'Failed profile persistence rolls back the batch and leaves account unchanged.');
        $assert($error($customer->update(['account' => ['first_name' => 'Wrong']], ['expected_email' => 'stale@example.test']), 'conflict'), 'Expected account email is checked under the mutation lock.');

        $disable = static function ($enabled, $module) { return in_array($module, ['feature_manager', 'profile_builder'], true) ? false : $enabled; };
        add_filter('arraysubs_is_module_enabled', $disable, 10, 2);
        $assert($error(Customer::queryByProfileField('reference', 'shared-reference'), 'unavailable'), 'Disabled Profile Builder is unavailable.');
        $assert($error(Features::forProduct($product_id)->updateConfiguration('Seats', ['value' => 15]), 'unavailable'), 'Disabled feature configuration refuses writes.');
        $assert($feature_api->usage('Seats')['defined'], 'Stored feature entitlement remains readable with UI module off.');
        remove_filter('arraysubs_is_module_enabled', $disable);
    } else {
        $assert($error(Customer::queryByProfileField('reference', 'x'), 'unavailable'), 'No Pro gives predictable profile availability.');
        $assert($error(Features::forProduct($product_id)->updateConfiguration('Seats', ['value' => 12]), 'unavailable'), 'No Pro gives predictable configuration availability.');
        $assert($feature_api->usage('Seats')['defined'], 'Core metering remains available without Pro.');
    }
    WP_CLI::success($checks . ' customer/feature/product API assertions passed (' . ($profile_available ? 'Pro' : 'core-only') . ').');
} finally {
    wp_set_current_user($original_user);
    if ($profile_available) {
        if (null === $original_profile_config) {
            delete_option('arraysubs_profile_fields_config');
        } else {
            update_option('arraysubs_profile_fields_config', $original_profile_config);
        }
    }
    foreach (array_reverse($posts) as $id) {
        wp_delete_post($id, true);
    }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach ($users as $id) {
        wp_delete_user($id);
    }
}
