<?php
/**
 * Review r1 L3 check on a fixture: give a renewal whose decline is already recorded a leftover
 * pending deadline (as an attempt that could not finish its Stripe search leaves it), then run the
 * real reconcile-sweep step for that order. Expect the deadline released and no duplicate action.
 *
 * Usage: wp eval-file <abs>/qa/fix-65-69/scripts/r1-l3-sweep.php <order_id> <subscription_id> --allow-root
 */
use ArraySubs\Supports\ActionScheduler;

$order_id = (int) ($args[0] ?? 0);
$sid      = (int) ($args[1] ?? 0);
$order    = wc_get_order($order_id);
if (! $order) { WP_CLI::error('order not found'); }
$state = static function () use ($order_id, $sid) {
    $o = wc_get_order($order_id); $o->read_meta_data(true);
    $pending = ActionScheduler::getNextPendingAction(ActionScheduler::HOOK_PROCESS_RENEWAL, [$sid], ActionScheduler::GROUP_RENEWALS);
    return ['deadline' => (string) $o->get_meta('_arraysubs_pending_deadline'), 'marker' => (string) $o->get_meta('_arraysubs_recorded_failure_attempt'), 'stored_pi' => (string) $o->get_meta('_stripe_intent_id'), 'attempts' => (string) get_post_meta($sid, '_payment_retry_attempts', true), 'next_retry_at' => (string) get_post_meta($sid, '_payment_retry_next_attempt_at', true), 'pending_action' => $pending ? $pending['id'] . ' ' . gmdate('Y-m-d H:i:s', $pending['timestamp']) : null];
};
$out = ['before' => $state()];
$order->update_meta_data('_arraysubs_pending_deadline', gmdate('Y-m-d H:i:s', time() + 2 * DAY_IN_SECONDS));
$order->save();
$out['with_leftover_deadline'] = $state();
$hooks = (new ReflectionClass(\ArraySubs\Features\AutomaticPayments\Services\Hooks::class))->newInstanceWithoutConstructor();
$m = new ReflectionMethod($hooks, 'reconcilePendingRenewalOrder');
$m->setAccessible(true);
$m->invoke($hooks, wc_get_order($order_id));
$out['after_sweep_step'] = $state();
echo wp_json_encode($out, JSON_PRETTY_PRINT), "\n";
