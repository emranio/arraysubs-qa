<?php
// Read-only Action Scheduler health snapshot for the staging QA preflight.
global $wpdb;
$t = $wpdb->prefix . 'actionscheduler_actions';
$now = gmdate('Y-m-d H:i:s');
$out = [
  'captured_at_utc' => gmdate('c'),
  'disable_wp_cron' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
  'os_cron' => '/etc/cron.d/mirror-help-arrayhash-wordpress: every minute, www-data, flock + php wp-cron.php',
  'counts' => $wpdb->get_results("SELECT status, COUNT(*) c FROM {$t} GROUP BY status", ARRAY_A),
  'pending_past_due_over_5min' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE status='pending' AND scheduled_date_gmt < DATE_SUB(%s, INTERVAL 5 MINUTE)", $now)),
  'in_progress' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE status='in-progress'"),
  'completed_last_15min' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE status='complete' AND last_attempt_gmt > DATE_SUB(%s, INTERVAL 15 MINUTE)", $now)),
  'failed_last_24h' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE status='failed' AND last_attempt_gmt > DATE_SUB(%s, INTERVAL 1 DAY)", $now)),
  'recent_completed_arraysubs' => $wpdb->get_results($wpdb->prepare("SELECT action_id, hook, scheduled_date_gmt, last_attempt_gmt FROM {$t} WHERE status='complete' AND hook LIKE %s ORDER BY last_attempt_gmt DESC LIMIT 8", 'arraysubs%'), ARRAY_A),
];
echo wp_json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
