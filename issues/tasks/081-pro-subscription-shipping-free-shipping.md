---
id: 81
title: 'Pro subscription shipping: free-shipping subscriptions with a renewal shipping override get renewal lines labelled with the checkout package name'
status: closed
priority: low
created: 2026-10-21T01:34:12.183142751+02:00
updated: 2026-10-21T12:47:10.025397445+02:00
started: 2026-10-21T07:18:54.103763302+02:00
completed: 2026-10-21T10:47:53.268394783+02:00
tags:
    - pro
    - shipping
    - renewals
class: standard
---

Active QA task ID and scheduled day: N/A (found 2026-10-20 by the #71-#78 fix verification; fix log qa/issues-65-69-fix-log.md).
Affected IDs: N/A (code reading + verifier probe). Affected users: N/A.
Route: arraysubspro/src/Features/SubscriptionShipping/Services/Hooks.php (zero-share capture branch ~:250-265). Since the #72 follow-up, a subscription bought with free shipping (0.00) stores that package's method and title.
Expected: a renewal shipping line that charges the product's renewal shipping override is labelled with that override, not 'Free shipping'.
Actual: label from the checkout package ('Free shipping'), amount from the override.
Proof: verifier LOW in scratchpad fix7x2/result-attribution.json.

[[2026-10-21]] Wed 10:47
Closed 2026-10-21 (UTC) after fix + verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md (timeline from 05:18 on 2026-10-21).
Fix (Pro SubscriptionShipping Hooks): a subscription whose checkout package cost nothing (free shipping, free pickup) and whose product has a renewal shipping override now labels the override charge 'Subscription Shipping' on renewals and as its shipping method, instead of the checkout package's 'Free shipping'. A new subscription meta, _arraysubs_shipping_package_total (the checkout package's own cost, written at capture), decides this on later plan switches too, so a paid package always keeps the method the customer chose. Verified by fixer and verifier probes (paid package keeps 'Flat rate'; free package + override -> 'Subscription Shipping' at the override amount; no override -> unchanged) and a final verification run.

[[2026-10-21]] Wed 11:53
Follow-up 2026-10-21 (user asked for a browser test and the older-subscription fix). 1) Live browser test passed: fixture customer 2595 bought product 61 with Free shipping (order 97429, sub 97430); its renewal order 97541 shows 'Shipping: $100.00 via Subscription Shipping' in My Account, on the pay page and in the admin order screen. 2) Older subscriptions: the free/paid decision on plan switches no longer reads _arraysubs_shipping_total (each switch overwrites it, so a paid 'Flat rate' subscription bought before the package total existed became 'Subscription Shipping' on its second switch). Without _arraysubs_shipping_package_total it now uses the stored method: empty or free_shipping = free, anything else keeps its name. Uncommitted. Log: qa/issues-65-69-fix-log.md.

[[2026-10-21]] Wed 12:47
Final 2026-10-21: verify run 2 passed. The suite matches run 1 (L1 keeps Flat rate). Review probes changed only labels, as intended: legacy zero-cost local pickup / pickup location / flat rate captures, carried-over totals and admin-REST renewal totals on a free package now renew as 'Subscription Shipping'; paid packages keep their names; 0 money or tax changes. Adversarial review: pass. Known small leftover (not fixed): for those admin-REST-edited or legacy subscriptions the stored Shipping Method shown in admin detail / shipping REST still names the free option, while renewal orders say 'Subscription Shipping'. Staging has 0 such subscriptions. Older amount bug filed as #84.
