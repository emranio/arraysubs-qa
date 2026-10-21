<?php
/**
 * #68 probe: Mollie rebinder after removing the dead WooCommerce token listeners.
 *
 * Usage: wp eval-file <abs>/qa/fix-65-69/scripts/fix68-probe.php --allow-root
 * Writes nothing: subscription meta reads for one fake id are answered in-process and every
 * meta write to it is recorded and short-circuited. Mollie calls go to the QA suite's fake client.
 */

require_once WP_CONTENT_DIR . '/plugins/qa/automation/gateway-tests/lib.php';

use ArraySubs\Features\AutomaticPayments\Gateways\Mollie\MollieDelegate;
use ArraySubs\Features\AutomaticPayments\Services\MollieMandateRebinder;

$out = [];
$names = static function (string $hook): array {
    global $wp_filter;
    $found = [];
    foreach ((isset($wp_filter[$hook]) ? $wp_filter[$hook]->callbacks : []) as $priority => $callbacks) {
        foreach ($callbacks as $cb) {
            $f = $cb['function'];
            $found[] = $priority . ':' . (is_array($f) ? (is_object($f[0]) ? get_class($f[0]) : $f[0]) . '::' . $f[1] : (is_string($f) ? $f : 'closure'));
        }
    }
    return $found;
};
foreach (['woocommerce_payment_token_added', 'woocommerce_add_payment_method_success', 'arraysubs_gateway_rebind_mollie_mandate', 'arraysubs_gateway_rebind_payment_method', 'arraysubs_gateway_revoke_payment_authorisation'] as $hook) {
    $out['hooks'][$hook] = $names($hook);
}

$delegate = new MollieDelegate(false);
$out['capability_payment_method_update'] = $delegate->supportsSubscriptionCapability('payment_method_update');
$out['update_mechanism'] = $delegate->getPaymentMethodUpdateMechanism(1);
$out['unsupported_messages_keys'] = array_keys($delegate->getUnsupportedCapabilityMessages());

// Fake Mollie subscription (no DB writes).
$fake_id = 999999001;
$meta = ['_payment_gateway' => 'mollie', '_gateway_customer_id' => 'cst_probe', '_gateway_mandate_id' => 'mdt_old', '_payment_method_type' => 'creditcard', '_gateway_status' => 'errored'];
$writes = [];
add_filter('get_post_metadata', static function ($value, $id, $key, $single) use ($fake_id, &$meta) {
    if ((int) $id !== $fake_id) {
        return $value;
    }
    if ('' === $key) {
        return array_map(static fn($v) => [$v], $meta);
    }
    return array_key_exists($key, $meta) ? ($single ? $meta[$key] : [$meta[$key]]) : ($single ? '' : []);
}, 1, 4);
foreach (['update_post_metadata', 'add_post_metadata', 'delete_post_metadata'] as $filter) {
    add_filter($filter, static function ($check, $id, $key, $value = null) use ($fake_id, &$meta, &$writes, $filter) {
        if ((int) $id !== $fake_id) {
            return $check;
        }
        $writes[] = [$filter, $key, is_scalar($value) ? $value : gettype($value)];
        if ('delete_post_metadata' === $filter) {
            unset($meta[$key]);
        } else {
            $meta[$key] = $value;
        }
        return true;
    }, 1, 4);
}

$rebinder = new MollieMandateRebinder();

// No Mollie key for the store's mode: revoke and rebind fail cleanly.
remove_all_filters('arraysubs_mollie_api_client');
add_filter('arraysubs_mollie_api_client', static fn () => null);
set_error_handler(static function ($no, $str) use (&$out) { $out['php_warnings'][] = $str; return true; });
$out['revoke_without_client'] = $rebinder->filterRevokeResult(null, $fake_id, true);
$out['rebind_without_client'] = $rebinder->rebindSubscription($fake_id);
restore_error_handler();

// The persisting scenarios need a real subscription post: a temporary draft that is deleted
// again (with its notes) at the end.
$temp = wp_insert_post(['post_type' => 'arraysubs_data', 'post_status' => 'draft', 'post_title' => 'SQA #68 probe (temporary)']);
foreach (['_payment_gateway' => 'mollie', '_gateway_customer_id' => 'cst_probe', '_gateway_mandate_id' => 'mdt_old', '_payment_method_type' => 'creditcard', '_gateway_status' => 'errored'] as $k => $v) {
    update_post_meta($temp, $k, $v);
}
$out['temp_subscription'] = $temp;
$status = static function () use ($temp) { clean_post_cache($temp); wp_cache_delete($temp, 'post_meta'); return (string) get_post_meta($temp, '_gateway_status', true); };

// A fresh valid mandate: bound, and an errored subscription becomes chargeable again.
remove_all_filters('arraysubs_mollie_api_client');
$client = new ArraySubs_Fake_Mollie_Client([
    'GET customers/cst_probe/mandates/mdt_old' => new WP_Error('mollie_api_error', 'gone', ['status' => 404]),
    'GET customers/cst_probe/mandates'         => ['_embedded' => ['mandates' => [['id' => 'mdt_new', 'status' => 'valid', 'method' => 'creditcard', 'details' => ['cardLabel' => 'Visa', 'cardNumber' => '4242', 'cardExpiryDate' => '2031-01-31']]]]],
]);
add_filter('arraysubs_mollie_api_client', static fn () => $client);
$out['rebind_new_mandate'] = $rebinder->rebindSubscription($temp);
$out['rebind_new_mandate_status'] = $status();
$out['rebind_new_mandate_bound'] = (string) get_post_meta($temp, '_gateway_mandate_id', true);

// Same mandate again while errored: reported unchanged, status restored.
update_post_meta($temp, '_gateway_status', 'errored');
$client->routes = ['GET customers/cst_probe/mandates/mdt_new' => ['id' => 'mdt_new', 'status' => 'valid', 'method' => 'creditcard', 'details' => ['cardLabel' => 'Visa', 'cardNumber' => '4242', 'cardExpiryDate' => '2031-01-31']]];
$out['rebind_same_mandate'] = $rebinder->rebindSubscription($temp);
$out['rebind_same_mandate_status'] = $status();

// A cancelled subscription's gateway status stays inactive.
update_post_meta($temp, '_gateway_status', 'inactive');
$rebinder->rebindSubscription($temp);
$out['rebind_same_mandate_inactive_status'] = $status();

// Cleanup: notes written for the temporary subscription, then the post itself.
$notes = get_posts(['post_type' => 'arraysubs_sub_note', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_subscription_id', 'meta_value' => $temp]);
foreach ($notes as $note_id) {
    wp_delete_post($note_id, true);
}
wp_delete_post($temp, true);
$out['cleanup'] = ['notes_deleted' => count($notes), 'post_exists_after' => null !== get_post($temp)];

echo wp_json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
