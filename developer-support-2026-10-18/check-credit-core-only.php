<?php
/** Run with --skip-plugins=arraysubspro, args: funded subscription ID, cheaper target ID. */
use ArraySubs\Developer\StoreCredit;
use ArraySubs\Features\PlanSwitching\Services\ProrationCalculator;
if (class_exists(\ArraySubsPro\Features\StoreCredit\Services\CreditLedger::class)) { throw new RuntimeException('Pro was not skipped.'); }
if (ProrationCalculator::isPlanSwitchCreditSupported()) { throw new RuntimeException('Core must not promise store credit.'); }
echo 'PASS: Core reports Store Credit unsupported without Pro.' . PHP_EOL;
$api=StoreCredit::fromCustomerId(1);
if (!$api instanceof StoreCredit || !is_wp_error($api->data()) || !is_wp_error($api->adjust(1,['operation_id'=>'core','reason'=>'core check','currency'=>get_woocommerce_currency()]))) { throw new RuntimeException('Pro-only methods must fail safely.'); }
echo 'PASS: Core-only credit facade fails safely without a Pro adapter.' . PHP_EOL;
$subscription_id=(int)$args[0];$target=(int)$args[1];$before=get_post_meta($subscription_id);
$result=ProrationCalculator::calculate($subscription_id,$target,'prorate_immediately',1);
if (empty($result['success']) || $result['proration_type']!=='apply_at_renewal' || $result['credit_policy']!=='deferred_without_store_credit' || $result['refund_amount']!=0 || $before!==get_post_meta($subscription_id)) { throw new RuntimeException(wp_json_encode($result)); }
echo 'PASS: Funded downgrade defers without writing subscription state in core-only mode.' . PHP_EOL;
