---
id: 84
title: 'Pro subscription shipping: a plan switch to a product with blank shipping overrides drops renewal shipping to 0'
status: closed
priority: medium
created: 2026-10-21T12:24:22.648552359+02:00
updated: 2026-10-21T14:31:42.487341453+02:00
started: 2026-10-21T14:31:42.487340641+02:00
completed: 2026-10-21T14:31:42.487340641+02:00
tags:
    - pro
    - shipping
    - plan-switching
    - amounts
class: standard
---

Active QA task ID and scheduled day: N/A (no active QA plan; found 2026-10-21 by the #81 follow-up review; fix log qa/issues-65-69-fix-log.md).
Affected subscription/order IDs: N/A (found with a rolled-back probe; no real subscription changed). Staging physical subscription products whose blank overrides are saved as "0"/"0": 21, 197, 202, 206, 209, 231, 233, 8652, 12385, 33252, 33313, 33324.
Affected users: N/A.
Route: arraysubspro/src/Features/SubscriptionShipping/Services/Hooks.php getProductShippingContext() (plan switch, in place and deferred); related: getInitialShippingOverride() and checkout capture.
Context: wp-admin product editor (classic) saves an empty Subscription Shipping override as "0". The editor describes 0 as "use WooCommerce shipping" / "same as initial shipping". Checkout capture (!empty) and the checkout fee treat "0" as no override.
Steps:
1. Customer buys a physical subscription product with paid shipping (Flat rate 10.00); renewals charge 10.00.
2. Customer switches to another physical subscription product whose shipping overrides were left blank (stored "0"/"0").
3. Next renewal.
Expected: renewals keep charging the checkout shipping (Flat rate 10.00), as with the overrides absent.
Actual: the switch path uses is_numeric(), so "0" becomes an override of 0: the renewal shipping total and _arraysubs_shipping_total become 0 and later renewals have no shipping line. The store stops collecting renewal shipping after the switch.
Proof (probe P8, rolled back): live and HEAD switch code both give deferred renewal line none; in place initial=0.00 renewal=0.00; next renewal none. With the overrides absent, renewals keep "Flat rate" 10.00.
Related mismatch (probe P5a): variation 33317 stores "0"/"0", parent 150/100. Checkout charged no initial shipping fee, capture stored initial 150 (paid=no) and renewal 100, and a switch to sibling variation 33315 ("0"/"0") left no shipping line. A variation's "0" blocks the parent fallback in the switch path and the checkout fee, but not in capture.
Scope: existed in committed HEAD (released code). Amounts, not labels; unrelated to the #81 label fix. Not fixed (user asked to finish #81 only).
Fix idea: treat override values <= 0 as "no override" consistently in getProductShippingContext(), getInitialShippingOverride() and capture, with one parent-fallback rule for variations.

[[2026-10-21]] Wed 12:47
Review note 2026-10-21: the same is_numeric() handling also leaves the renewal order that applies a deferred switch without a shipping line. When fixing, treat an override as set only when (float) value > 0 (as capture does) so stored totals carry over, and apply the renewal-to-initial-total fallback when a pending switch's context renewal total is 0.

[[2026-10-21]] Wed 14:31
Fixed 2026-10-21 (Pro SubscriptionShipping Hooks, committed with this round). getShippingOverride(): an override counts only when > 0 (capture, checkout fee, plan switch); a variation uses its parent's only when it never stored the field. Plan switch without an override renews with the checkout package cost (getShippingPackageTotal(): stored _arraysubs_shipping_package_total, or the purchase order's shipping lines read with capture's allocation; unknown -> keep current totals; a one-time plan's first-order charge is never repeated). Verified with rolled-back probes on real code paths: paid Flat rate 10 -> '0'/'0' target keeps 10 (was none); one-time 300 legacy -> renews 0/10 per its purchase package (not 300); stale/no-attribution multi-package orders keep current totals; 0 money changes elsewhere; 3 review rounds. Known limits: a renewal amount set via the admin REST shipping route resets to the checkout package on a switch to a no-override product; a checkout-migration's own shipping is not recorded (original purchase package stays the reference).
