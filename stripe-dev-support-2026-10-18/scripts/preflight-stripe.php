<?php
/**
 * Stripe preflight for the staging QA run (read-only, redacted).
 *
 * Run: wp eval-file <abs path> --allow-root
 * Prints JSON. Never prints API keys, webhook secrets or card data: secrets
 * are reported only as presence/prefix-class booleans.
 */

use ArraySubs\Features\AutomaticPayments\Services\StripeWebhookProvisioner;
use ArraySubs\Features\AutomaticPayments\Services\StripeSettingsTab;

$mask = static function (string $id): string {
    if (strlen($id) <= 10) {
        return $id;
    }
    return substr($id, 0, 8) . '…' . substr($id, -4);
};

$settings = get_option('woocommerce_stripe_settings', []);
$settings = is_array($settings) ? $settings : [];

$out = [
    'generated_at_utc' => gmdate('c'),
    'stripe_plugin_version' => defined('WC_STRIPE_VERSION') ? WC_STRIPE_VERSION : null,
    'official_gateway' => [
        'enabled' => $settings['enabled'] ?? null,
        'testmode' => $settings['testmode'] ?? null,
        'mode_class_is_test' => class_exists('\WC_Stripe_Mode') ? \WC_Stripe_Mode::is_test() : null,
        'test_publishable_key_is_pk_test' => 0 === strpos((string) ($settings['test_publishable_key'] ?? ''), 'pk_test_'),
        'test_secret_key_is_test_class' => (bool) preg_match('/^(sk|rk)_test_/', (string) ($settings['test_secret_key'] ?? '')),
        'live_publishable_key_present' => '' !== (string) ($settings['publishable_key'] ?? ''),
        'live_secret_key_present' => '' !== (string) ($settings['secret_key'] ?? ''),
        'test_webhook_secret_is_whsec' => 0 === strpos((string) ($settings['test_webhook_secret'] ?? ''), 'whsec_'),
        'test_webhook_data_url' => (string) ($settings['test_webhook_data']['url'] ?? ''),
        'test_webhook_data_id' => (string) ($settings['test_webhook_data']['id'] ?? ''),
        'test_webhook_data_secret_present' => '' !== (string) ($settings['test_webhook_data']['secret'] ?? ''),
        'test_connection_type' => $settings['test_connection_type'] ?? null,
        'capture' => $settings['capture'] ?? null,
        'saved_cards' => $settings['saved_cards'] ?? null,
        'upe_checkout_experience_enabled' => $settings['upe_checkout_experience_enabled'] ?? null,
        'upe_accepted_payments' => $settings['upe_checkout_experience_accepted_payments'] ?? null,
        'optimized_checkout_element' => $settings['optimized_checkout_element'] ?? null,
        'express_checkout' => $settings['express_checkout'] ?? null,
        'logging' => $settings['logging'] ?? null,
    ],
];

// Woo currency / gateway availability.
$out['store'] = [
    'currency' => get_woocommerce_currency(),
    'home_url' => home_url('/'),
    'checkout_page_id' => wc_get_page_id('checkout'),
    'hpos_enabled' => class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') ? \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() : null,
];

$available = WC()->payment_gateways() ? array_keys(WC()->payment_gateways()->payment_gateways()) : [];
$out['store']['registered_gateways'] = $available;

// Stripe account (test mode).
if (class_exists('\WC_Stripe_API')) {
    if (method_exists('\WC_Stripe_API', 'set_secret_key_for_mode')) {
        \WC_Stripe_API::set_secret_key_for_mode('test');
    }
    $acct = \WC_Stripe_API::retrieve('account');
    $out['stripe_account'] = (is_object($acct) && empty($acct->error)) ? [
        'id_masked' => $mask((string) ($acct->id ?? '')),
        'country' => $acct->country ?? null,
        'default_currency' => $acct->default_currency ?? null,
        'charges_enabled' => ! empty($acct->charges_enabled),
        'livemode_flag_on_endpoint_objects' => null,
    ] : ['error' => is_object($acct) && ! empty($acct->error) ? ($acct->error->message ?? 'error') : 'no response'];

    $list = \WC_Stripe_API::retrieve('webhook_endpoints?limit=100');
    $endpoints = [];
    $secondary = null;
    $official = null;
    $secondary_url = StripeWebhookProvisioner::getEndpointUrl();
    if (is_object($list) && ! empty($list->data)) {
        foreach ($list->data as $ep) {
            $row = [
                'id' => $ep->id ?? '',
                'url' => $ep->url ?? '',
                'status' => $ep->status ?? '',
                'livemode' => $ep->livemode ?? null,
                'api_version' => $ep->api_version ?? null,
                'description' => $ep->description ?? '',
                'enabled_events' => array_values((array) ($ep->enabled_events ?? [])),
            ];
            $endpoints[] = $row;
            if (untrailingslashit((string) $row['url']) === untrailingslashit($secondary_url)) {
                $secondary = $row;
            }
            if (false !== strpos((string) $row['url'], 'wc-api=wc_stripe')) {
                $official = $row;
            }
        }
    }
    $out['webhook_endpoints_count'] = count($endpoints);
    $out['webhook_endpoints'] = array_map(static function ($r) {
        $r['enabled_events_count'] = count($r['enabled_events']);
        return $r;
    }, $endpoints);

    $required = StripeWebhookProvisioner::getRequiredEvents();
    $out['secondary_webhook'] = [
        'expected_url' => $secondary_url,
        'found_remote' => null !== $secondary,
        'remote_id' => $secondary['id'] ?? null,
        'remote_status' => $secondary['status'] ?? null,
        'required_events_from_code' => $required,
        'required_events_count' => count($required),
        'remote_enabled_events' => $secondary['enabled_events'] ?? [],
        'missing_required_events' => null !== $secondary ? array_values(array_diff($required, $secondary['enabled_events'])) : $required,
        'extra_remote_events' => null !== $secondary ? array_values(array_diff($secondary['enabled_events'], $required)) : [],
        'wildcard' => null !== $secondary && in_array('*', $secondary['enabled_events'], true),
        'includes_payment_method_attached' => null !== $secondary && in_array('payment_method.attached', $secondary['enabled_events'], true),
        'includes_customer_updated' => null !== $secondary && in_array('customer.updated', $secondary['enabled_events'], true),
        'stored_status' => (static function () {
            $s = StripeWebhookProvisioner::getStoredStatus();
            return $s; // contains only endpoint id/url/secret_present/fingerprint/last_error/updated_at.
        })(),
        'code_fingerprint' => StripeWebhookProvisioner::getRequiredEventsFingerprint(),
        'stored_secret_is_whsec' => 0 === strpos(StripeSettingsTab::getSecondaryWebhookSecret('test'), 'whsec_'),
        'health_check' => StripeWebhookProvisioner::checkEndpointHealth(),
    ];
    $out['secondary_webhook']['stored_fingerprint_matches_code'] = ($out['secondary_webhook']['stored_status']['schema_fingerprint'] ?? '') === $out['secondary_webhook']['code_fingerprint'];
    $out['secondary_webhook']['stored_endpoint_matches_remote'] = ($out['secondary_webhook']['stored_status']['endpoint_id'] ?? '') === ($secondary['id'] ?? '');

    $out['official_webhook'] = [
        'found_remote' => null !== $official,
        'remote_id' => $official['id'] ?? null,
        'remote_url' => $official['url'] ?? null,
        'remote_status' => $official['status'] ?? null,
        'remote_enabled_events_count' => isset($official['enabled_events']) ? count($official['enabled_events']) : 0,
        'stored_id_matches_remote' => ($settings['test_webhook_data']['id'] ?? '') === ($official['id'] ?? ''),
    ];
    if (class_exists('\WC_Stripe_Webhook_State')) {
        $succ = \WC_Stripe_Webhook_State::get_last_webhook_success_at();
        $fail = \WC_Stripe_Webhook_State::get_last_webhook_failure_at();
        $out['official_webhook']['wc_state'] = [
            'monitoring_began_at' => \WC_Stripe_Webhook_State::get_monitoring_began_at() ? gmdate('c', (int) \WC_Stripe_Webhook_State::get_monitoring_began_at()) : null,
            'last_success_at' => $succ ? gmdate('c', (int) $succ) : null,
            'last_failure_at' => $fail ? gmdate('c', (int) $fail) : null,
            'last_error_reason' => \WC_Stripe_Webhook_State::get_last_error_reason(),
            'status_code' => \WC_Stripe_Webhook_State::get_webhook_status_code(),
            'status_message' => wp_strip_all_tags((string) \WC_Stripe_Webhook_State::get_webhook_status_message()),
        ];
    }

    // Recent provider events and delivery state.
    $since = time() - DAY_IN_SECONDS;
    $recent = \WC_Stripe_API::retrieve('events?limit=15');
    $undelivered = \WC_Stripe_API::retrieve('events?limit=50&delivery_success=false&created[gte]=' . $since);
    $out['stripe_recent_events'] = [];
    if (is_object($recent) && ! empty($recent->data)) {
        foreach ($recent->data as $ev) {
            $out['stripe_recent_events'][] = [
                'id' => $ev->id,
                'type' => $ev->type,
                'created' => gmdate('c', (int) $ev->created),
                'pending_webhooks' => $ev->pending_webhooks ?? null,
                'livemode' => $ev->livemode ?? null,
            ];
        }
    }
    $out['stripe_undelivered_events_last_24h'] = [];
    if (is_object($undelivered) && isset($undelivered->data)) {
        foreach ($undelivered->data as $ev) {
            $out['stripe_undelivered_events_last_24h'][] = [
                'id' => $ev->id,
                'type' => $ev->type,
                'created' => gmdate('c', (int) $ev->created),
                'pending_webhooks' => $ev->pending_webhooks ?? null,
            ];
        }
    } elseif (is_object($undelivered) && ! empty($undelivered->error)) {
        $out['stripe_undelivered_events_last_24h'] = ['error' => $undelivered->error->message ?? 'error'];
    }
}

// ArraySubs local webhook processing log.
global $wpdb;
$table = $wpdb->prefix . 'arraysubs_webhook_events';
if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table))) {
    $out['arraysubs_webhook_log'] = [
        'rows_arraysubs_stripe' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE gateway_slug IN (%s,%s)", 'arraysubs_stripe', 'stripe')),
        'latest' => $wpdb->get_results($wpdb->prepare("SELECT gateway_slug, event_id, event_type, processed_at FROM {$table} WHERE gateway_slug IN (%s,%s) ORDER BY processed_at DESC LIMIT 5", 'arraysubs_stripe', 'stripe'), ARRAY_A),
        'columns' => $wpdb->get_col("SHOW COLUMNS FROM {$table}"),
    ];
}

echo wp_json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
