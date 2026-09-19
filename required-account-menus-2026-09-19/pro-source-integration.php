<?php
/**
 * Focused source integration checks for required menus and prefixed custom routes.
 * Run from wp-content/plugins: php qa/required-account-menus-2026-09-19/pro-source-integration.php
 * Uses actual plugin source and WordPress filter engine with in-memory fixtures.
 * Does not bootstrap the site, connect to a database, or change browser/site state.
 */
define('ABSPATH', dirname(getcwd(), 2) . '/');
require ABSPATH . 'wp-includes/plugin.php';
$fixture_config = []; $fixture_enabled = true;
function __($v, $d = '') { return $v; }
function esc_html__($v, $d = '') { return $v; }
function get_option($name, $default = false) { global $fixture_config; return $name === 'arraysubs_myaccount_menu_config' ? $fixture_config : ($name === 'arraysubs_flush_rewrite_rules' ? 'done' : $default); }
function sanitize_key($v) { return strtolower(preg_replace('/[^a-zA-Z0-9_\\-]/', '', $v)); }
function sanitize_text_field($v) { return (string) $v; }
function sanitize_title($v) { return sanitize_key($v); }
function absint($v) { return abs((int) $v); }
function arraysubs_is_myaccount_menu_items_enabled() { global $fixture_enabled; return $fixture_enabled; }
$reenter = false; $depth = 0;
function wc_get_account_menu_items() {
    global $reenter, $depth;
    ++$depth;
    if ($reenter && $depth === 1) { arraysubs_get_default_myaccount_menu_items(); }
    $items = apply_filters('woocommerce_account_menu_items', ['dashboard'=>'Dashboard','orders'=>'Orders','downloads'=>'Downloads','customer-logout'=>'Log out']);
    --$depth;
    return $items;
}
require 'arraysubs/src/functions/myaccount-menu-helpers.php';
require 'arraysubs/src/Features/CustomerPortal/Services/MyAccountHooks.php';
require 'arraysubspro/src/Features/MyAccountEditor/Services/Hooks.php';
require 'arraysubspro/src/functions/myaccount-editor-helpers.php';
require 'arraysubs/src/Supports/BaseRestController.php';
require 'arraysubspro/src/Features/MyAccountEditor/REST/MenuConfigController.php';
$core = new ArraySubs\Features\CustomerPortal\Services\MyAccountHooks();
$editor = new ArraySubsPro\Features\MyAccountEditor\Services\Hooks();
$controller = new ArraySubsPro\Features\MyAccountEditor\REST\MenuConfigController();
function fixtureItem($id, $label, $enabled = true) { return ['id'=>$id,'label'=>$label,'type'=>'default','enabled'=>$enabled]; }
function verify($name, $ok, $actual = null) { if (!$ok) { throw new RuntimeException($name . ': ' . json_encode($actual)); } echo 'PASS ', $name, PHP_EOL; }
$fixture_config = [fixtureItem('subscriptions','Support',false), fixtureItem('dashboard','Home'), fixtureItem('orders','Purchases',false), fixtureItem('downloads','Files'), fixtureItem('customer-logout','Exit')];
$sanitized = arraysubs_sanitize_myaccount_menu_config($fixture_config);
verify('save forces required visibility while preserving custom labels', $sanitized[0]['enabled'] && $sanitized[0]['label'] === 'Support' && $sanitized[2]['enabled'] && $sanitized[2]['label'] === 'Purchases');
verify('read normalization leaves stored fixture untouched', arraysubs_get_myaccount_menu_config()[0]['enabled'] && !$fixture_config[0]['enabled']);
verify('saved order keeps Subscriptions first despite old hidden flags', array_keys(wc_get_account_menu_items()) === ['subscriptions','dashboard','orders','downloads','customer-logout'], wc_get_account_menu_items());
verify('saved required names appear in navigation despite old hidden flags', wc_get_account_menu_items()['subscriptions'] === 'Support' && wc_get_account_menu_items()['orders'] === 'Purchases');
$strip = static function($items) { unset($items['orders'], $items['subscriptions']); return $items; };
add_filter('woocommerce_account_menu_items', $strip, 20);
add_filter('woocommerce_account_menu_items', $strip, PHP_INT_MAX);
do_action('wp_loaded');
verify('early and maximum priority removals repaired in saved order', array_keys(wc_get_account_menu_items()) === ['subscriptions','dashboard','orders','downloads','customer-logout'], wc_get_account_menu_items());
verify('removed required items recover their saved labels', wc_get_account_menu_items()['subscriptions'] === 'Support' && wc_get_account_menu_items()['orders'] === 'Purchases');
$editor->registerEndpointTitleFilters();
verify('Subscriptions page heading uses its saved custom label', apply_filters('woocommerce_endpoint_subscriptions_title', 'My account', 'subscriptions') === 'Support');
verify('Orders page heading uses its saved custom label', apply_filters('woocommerce_endpoint_orders_title', 'Orders', 'orders') === 'Purchases');
$defaults = arraysubs_get_default_myaccount_menu_items();
verify('defaults restore both required IDs once using default order', array_column($defaults,'id') === ['dashboard','subscriptions','orders','downloads','customer-logout'], $defaults);
$reenter = true;
verify('nested defaults preserve bypass and mandatory IDs', array_column(arraysubs_get_default_myaccount_menu_items(),'id') === ['dashboard','subscriptions','orders','downloads','customer-logout']);
verify('bypass restored after nested discovery', !ArraySubsPro\Features\MyAccountEditor\Services\Hooks::$bypass_menu_filter);
$reenter = false;
$prepare = new ReflectionMethod($controller, 'prepareItemsForResponse');
$response = $prepare->invoke($controller, $fixture_config);
verify('REST metadata protects required rows while preserving their labels', $response[0]['required'] === true && $response[0]['enabled'] && $response[0]['label'] === 'Support' && $response[1]['required'] === false && $response[2]['required'] === true && $response[2]['label'] === 'Purchases');
$validate = new ReflectionMethod($controller, 'validateItems');
foreach (['orders','subscriptions'] as $endpoint) {
    $candidate = [['id'=>'custom_a','label'=>'Hijack','type'=>'custom','endpoint'=>$endpoint,'content_id'=>123]];
    verify('custom endpoint collision rejected: ' . $endpoint, !$validate->invoke($controller, $candidate)['valid']);
}
$fixture_enabled = false;
verify('customization off still restores required menus', array_keys(wc_get_account_menu_items()) === ['dashboard','subscriptions','orders','downloads','customer-logout']);
verify('customization off retains default required labels and headings', wc_get_account_menu_items()['subscriptions'] === 'Subscriptions' && $editor->filterEndpointTitle('Orders', 'orders') === 'Orders');
$fixture_enabled = true; $fixture_config = [];
verify('empty saved configuration cannot omit required rows', array_keys(wc_get_account_menu_items()) === ['dashboard','subscriptions','orders','downloads','customer-logout']);
verify('without Dashboard Subscriptions precedes even leading third-party items', array_keys(arraysubs_ensure_required_myaccount_menu_items(['support'=>'Support','orders'=>'Orders','downloads'=>'Downloads'])) === ['subscriptions','support','orders','downloads']);
verify('existing default Subscriptions moves immediately after Dashboard', array_keys(arraysubs_ensure_required_myaccount_menu_items(['dashboard'=>'Dashboard','support'=>'Support','orders'=>'Orders','subscriptions'=>'Subscriptions'])) === ['dashboard','subscriptions','support','orders']);
verify('empty menu starts with Subscriptions then Orders', array_keys(arraysubs_ensure_required_myaccount_menu_items([])) === ['subscriptions','orders']);
$remove_dashboard = static function ($items) { unset($items['dashboard']); return ['support'=>'Support'] + $items; };
add_filter('woocommerce_account_menu_items', $remove_dashboard, 1000);
verify('late Dashboard removal restores Subscriptions to first', array_key_first(wc_get_account_menu_items()) === 'subscriptions');
remove_filter('woocommerce_account_menu_items', $remove_dashboard, 1000);
$fixture_config = [fixtureItem('orders', ''), fixtureItem('subscriptions', '')];
verify('empty custom labels cannot blank required navigation', wc_get_account_menu_items()['subscriptions'] === 'Subscriptions' && wc_get_account_menu_items()['orders'] === 'Orders');
verify('empty labels retain the default page headings', $editor->filterEndpointTitle('Subscriptions', 'subscriptions') === 'Subscriptions' && $editor->filterEndpointTitle('Orders', 'orders') === 'Orders');
remove_filter('arraysubs_required_myaccount_menu_labels', [$editor, 'getRequiredMenuLabels']);
$named_items = arraysubs_ensure_required_myaccount_menu_items(['orders'=>'Purchase history','subscriptions'=>'Memberships']);
verify('core preserves existing renamed required labels without Pro customization', $named_items['orders'] === 'Purchase history' && $named_items['subscriptions'] === 'Memberships');
add_filter('arraysubs_required_myaccount_menu_labels', [$editor, 'getRequiredMenuLabels']);
$fixture_config = [['id'=>'custom_bad','label'=>'Hijack','type'=>'custom','endpoint'=>'subscriptions','enabled'=>true],['id'=>'custom_good','label'=>'Support','type'=>'custom','endpoint'=>'support','enabled'=>true]];
verify('legacy colliding custom endpoint does not register', array_column(arraysubs_get_custom_myaccount_endpoints(),'endpoint') === ['as-support']);

foreach (['support'=>'as-support','as-support'=>'as-support',''=>''] as $raw=>$expected) {
    $items = arraysubs_sanitize_myaccount_menu_config([['id'=>'custom_x','type'=>'custom','label'=>'Support','endpoint'=>$raw,'content_id'=>123]]);
    verify('custom prefix canonicalization: ' . ($raw ?: '(empty)'), $items[0]['endpoint'] === $expected);
}
foreach (['subscriptions','as-subscriptions','view-subscription','as-view-subscription'] as $endpoint) {
    $items = arraysubs_sanitize_myaccount_menu_config([['id'=>'custom_x','type'=>'custom','label'=>'Conflict','endpoint'=>$endpoint,'content_id'=>123]]);
    verify('canonical portal alias rejects: ' . $endpoint, !$validate->invoke($controller, $items)['valid']);
}
$items = arraysubs_sanitize_myaccount_menu_config([['id'=>'orders','type'=>'custom','label'=>'Fake Orders','endpoint'=>'help','content_id'=>123]]);
verify('required custom ID rejected', !$validate->invoke($controller, $items)['valid']);
$fixture_config = $items;
verify('legacy custom required ID removed from read without changing raw data', arraysubs_get_myaccount_menu_config() === [] && count($fixture_config) === 1);
$items = arraysubs_sanitize_myaccount_menu_config([['id'=>'custom_x','type'=>'custom','label'=>'Missing slug','endpoint'=>'as-','content_id'=>123]]);
verify('bare as- rejected', !$validate->invoke($controller, $items)['valid']);
$items = arraysubs_sanitize_myaccount_menu_config([['id'=>'custom_x','type'=>'custom','label'=>'Support','endpoint'=>'support','content_id'=>123], ['id'=>'custom_y','type'=>'custom','label'=>'Duplicate','endpoint'=>'as-support','content_id'=>123]]);
verify('prefix-canonical duplicate custom slugs rejected', !$validate->invoke($controller, $items)['valid']);

function WC() {
    static $wc;
    if (!$wc) {
        $wc = (object)['query' => new class {
            public function get_query_vars() {
                static $depth = 0;
                if (++$depth > 5) { throw new RuntimeException('Recursive WC query vars lookup'); }
                try { return apply_filters('woocommerce_get_query_vars', ['orders'=>'orders','downloads'=>'downloads']); }
                finally { --$depth; }
            }
        }];
    }
    return $wc;
}
function arraysubs_get_setting($key, $default = null) { return $default; }
require 'arraysubspro/src/Features/StoreCredit/Services/MyAccountHooks.php';
require 'arraysubspro/src/Features/FeatureManager/Services/MyAccountHooks.php';
$credit = new ArraySubsPro\Features\StoreCredit\Services\MyAccountHooks();
$features = new ArraySubsPro\Features\FeatureManager\Services\MyAccountHooks();
$fixture_config = [
    ['id'=>'custom_bad_credit','label'=>'Hijack credit','type'=>'custom','endpoint'=>'store-credit','enabled'=>true],
    ['id'=>'custom_bad_features','label'=>'Hijack features','type'=>'custom','endpoint'=>'features','enabled'=>true],
    ['id'=>'custom_bad_view','label'=>'Hijack view','type'=>'custom','endpoint'=>'view-subscription','enabled'=>true],
    ['id'=>'custom_good','label'=>'Support','type'=>'custom','endpoint'=>'support','enabled'=>true]
];
$query_vars = WC()->query->get_query_vars();
verify('prefixed custom query vars registered without recursion', isset($query_vars['as-support']) && $query_vars['as-support'] === 'as-support');
verify('legacy built-in alias collisions excluded from query map', !isset($query_vars['as-store-credit']) && !isset($query_vars['as-features']) && !isset($query_vars['as-view-subscription']), $query_vars);
verify('built-in logical keys retain public aliases', $query_vars['store-credit'] === 'as-store-credit' && $query_vars['features'] === 'as-features' && $query_vars['view-subscription'] === 'as-view-subscription');
verify('runtime custom rendering excludes all registered built-in aliases', array_column(arraysubs_get_custom_myaccount_endpoints(),'endpoint') === ['as-support']);
foreach (['store-credit','as-store-credit','features','as-features','view-subscription','as-view-subscription'] as $endpoint) {
    $items = arraysubs_sanitize_myaccount_menu_config([['id'=>'custom_new','type'=>'custom','label'=>'Conflict','endpoint'=>$endpoint,'content_id'=>123]]);
    verify('REST rejects registered built-in alias: ' . $endpoint, !$validate->invoke($controller, $items)['valid']);
}
$menu = wc_get_account_menu_items();
verify('custom alias collisions cannot replace required or Pro menu labels', $menu['store-credit'] === 'Store Credit' && $menu['features'] === 'My Features' && $menu['as-support'] === 'Support' && !isset($menu['as-store-credit']) && !isset($menu['as-features']));
