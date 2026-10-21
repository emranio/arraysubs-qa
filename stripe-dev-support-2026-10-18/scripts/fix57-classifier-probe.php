<?php
$d = \ArraySubs\Features\AutomaticPayments\Services\GatewayResolver::resolveBySlug('stripe');
$m = new ReflectionMethod($d, 'stripeFailureCode'); $m->setAccessible(true);
$cases = [
 ['code'=>'card_declined','decline_code'=>'generic_decline','advice_code'=>'try_again_later'],
 ['code'=>'card_declined','decline_code'=>'insufficient_funds'],
 ['code'=>'card_declined','decline_code'=>'lost_card','advice_code'=>'do_not_try_again'],
 ['code'=>'card_declined','decline_code'=>'generic_decline','advice_code'=>'do_not_try_again'],
 ['code'=>'card_declined','decline_code'=>'do_not_honor'],
 ['code'=>'card_declined','decline_code'=>'transaction_not_allowed','advice_code'=>'try_again_later'],
 ['code'=>'card_declined'],
 ['code'=>'expired_card','decline_code'=>'expired_card'],
 ['code'=>'incorrect_cvc','decline_code'=>'incorrect_cvc'],
 ['code'=>'processing_error'],
 ['code'=>'card_declined','decline_code'=>'card_velocity_exceeded'],
 ['code'=>'card_declined','decline_code'=>'stolen_card'],
 ['code'=>'card_declined','advice_code'=>'confirm_card_data'],
 [],
];
foreach ($cases as $c) { $code = $m->invoke($d, $c, 'stripe_api_error'); $cat = arraysubs_classify_payment_failure_reason($code); printf("%-75s => %-28s %-22s %s\n", json_encode($c), $code, $cat, arraysubs_is_hard_payment_decline($cat)?'HARD':'soft'); }
foreach (['renewal_payment_method_binding_invalid','renewal_payment_method_verification_failed','missing_credentials','unexpected_status'] as $c) { $cat = arraysubs_classify_payment_failure_reason($c); printf("%-75s => %-28s %-22s %s\n", $c, '', $cat, arraysubs_is_hard_payment_decline($cat)?'HARD':'soft'); }
echo "--- with gateway message (as RenewalProcessor classifies)\n";
foreach ([
 [['code'=>'card_declined','decline_code'=>'some_new_reason'], 'Your card was declined.'],
 [['code'=>'card_declined','decline_code'=>'some_new_reason','advice_code'=>'do_not_try_again'], 'Your card was declined.'],
 [['code'=>'card_declined','decline_code'=>'card_velocity_exceeded'], 'Your card was declined.'],
 [['code'=>'card_declined','decline_code'=>'generic_decline'], 'Your card was declined.'],
] as [$c, $msg]) { $code = $m->invoke($d, $c, 'stripe_api_error'); $cat = arraysubs_classify_payment_failure_reason($code, $msg); printf("%-75s => %-22s %-18s %s\n", json_encode($c), $code, $cat, arraysubs_is_hard_payment_decline($cat)?'HARD':'soft'); }
