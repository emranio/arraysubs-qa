<?php
/**
 * Plugin Name: SQA lifecycle capture (TEST ONLY - staging Stripe QA 2026-10-18)
 * Description: Appends arraysubs_lifecycle_event notifications for recorded QA fixtures to a JSONL file in the QA folder. Remove during cleanup.
 *
 * Installed by the staging Stripe QA run (phase 1). Source copy:
 * wp-content/plugins/qa/stripe-dev-support-2026-10-18/scripts/mu-plugin-sqa-lifecycle-capture.php
 *
 * Scope: only events whose subscription_id is listed in fixture-scope.json, or
 * whose user_id / customer_email belongs to a listed fixture customer (needed
 * because a checkout's first events fire before its new subscription ID can be
 * recorded). Every other event is ignored. Payload values are scalar by
 * contract; anything else is dropped here as well.
 */

defined('ABSPATH') || exit;

if (! defined('SQA_LIFECYCLE_CAPTURE_DIR')) {
    define('SQA_LIFECYCLE_CAPTURE_DIR', WP_CONTENT_DIR . '/plugins/qa/stripe-dev-support-2026-10-18/capture');
}

add_action('arraysubs_lifecycle_event', static function ($event, $payload = []): void {
    try {
        if (! is_array($payload)) {
            return;
        }
        static $scope = null;
        if (null === $scope) {
            $raw = @file_get_contents(SQA_LIFECYCLE_CAPTURE_DIR . '/fixture-scope.json');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            $scope = [
                'subscription_ids' => array_map('intval', (array) ($decoded['subscription_ids'] ?? [])),
                'customer_ids' => array_map('intval', (array) ($decoded['customer_ids'] ?? [])),
                'customer_emails' => array_map('strtolower', array_map('strval', (array) ($decoded['customer_emails'] ?? []))),
            ];
        }
        $sub = (int) ($payload['subscription_id'] ?? 0);
        $uid = (int) ($payload['user_id'] ?? 0);
        $email = strtolower((string) ($payload['customer_email'] ?? ''));
        $in_scope = ($sub > 0 && in_array($sub, $scope['subscription_ids'], true))
            || ($uid > 0 && in_array($uid, $scope['customer_ids'], true))
            || ('' !== $email && in_array($email, $scope['customer_emails'], true));
        if (! $in_scope) {
            return;
        }
        $clean = [];
        foreach ($payload as $k => $v) {
            if (is_scalar($v) || null === $v) {
                $clean[(string) $k] = $v;
            }
        }
        $path = isset($_SERVER['REQUEST_URI']) ? (string) wp_parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
        $line = [
            'captured_at' => gmdate('Y-m-d\TH:i:s') . sprintf('.%06dZ', (int) ((microtime(true) - floor(microtime(true))) * 1e6)),
            'event' => (string) $event,
            'context' => [
                'pid' => getmypid(),
                'sapi' => PHP_SAPI,
                'wp_cli' => defined('WP_CLI') && WP_CLI,
                'doing_cron' => function_exists('wp_doing_cron') && wp_doing_cron(),
                'rest' => defined('REST_REQUEST') && REST_REQUEST,
                'path' => $path,
                'wc_ajax' => isset($_GET['wc-ajax']) ? sanitize_key((string) $_GET['wc-ajax']) : '',
                'current_action' => current_action(),
            ],
            'payload' => $clean,
        ];
        $file = SQA_LIFECYCLE_CAPTURE_DIR . '/lifecycle-events.jsonl';
        $json = wp_json_encode($line, JSON_UNESCAPED_SLASHES);
        if (is_string($json)) {
            @file_put_contents($file, $json . "\n", FILE_APPEND | LOCK_EX);
        }
    } catch (\Throwable $e) {
        // Test capture must never affect the request.
    }
}, PHP_INT_MAX, 2);
