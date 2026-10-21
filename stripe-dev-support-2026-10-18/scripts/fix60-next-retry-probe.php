<?php
foreach ([68512, 68525, 68881, 68996, 68439, 68665] as $id) {
  $d = \ArraySubs\Developer\Subscriptions::fromId($id)->data();
  $r = $d['recovery'] ?? [];
  printf("%d status=%s attempts=%s meta_next=%s derived=%s dev.next_retry_at=%s\n", $id, get_post_status($id), get_post_meta($id,'_payment_retry_attempts',true), get_post_meta($id,'_payment_retry_next_attempt_at',true) ?: '-', var_export(arraysubs_get_payment_retry_next_attempt_at($id), true), json_encode($r['next_retry_at'] ?? null));
}
global $wpdb;
$rows = $wpdb->get_results("SELECT action_id, args, scheduled_date_gmt FROM {$wpdb->prefix}actionscheduler_actions WHERE hook='arraysubs_process_renewal' AND status='pending' ORDER BY scheduled_date_gmt LIMIT 200");
$n=0; foreach ($rows as $row) { $a = json_decode($row->args, true); $sid = (int)($a[0] ?? 0); if ($sid && (int)get_post_meta($sid,'_payment_retry_attempts',true) > 0) { $n++; printf("pending retry: sub %d action %d at %s derived=%s\n", $sid, $row->action_id, $row->scheduled_date_gmt, var_export(arraysubs_get_payment_retry_next_attempt_at($sid), true)); } }
echo "subs with attempts>0 and pending action: $n\n";
