---
id: 18
title: 'stage-03: Variable subscription variations display monthly schedule'
status: closed
priority: high
created: 2026-05-20T08:34:14.913576894+02:00
updated: 2026-05-22T00:56:56.924075168+02:00
started: 2026-05-22T00:50:25.620949818+02:00
completed: 2026-05-22T00:56:56.924073755+02:00
tags:
    - qa
    - stage-03
    - variable-products
    - display
claimed_by: mold-glade
claimed_at: 2026-05-22T00:56:56.924075057+02:00
class: standard
---

Observed with agent-browser on 2026-05-20 for PM Tool #243 and Coffee Plan #249. Variation meta is correct in WP-CLI: PM Weekly week/1, Bi-weekly week/2; Coffee Daily day/1 trial 7, Weekly week/1 signup 4.99. Storefront initial variable product area was not empty and displayed '.99 Every month' / '.99 Every month'. Selecting PM Weekly still showed '.99 Every month'; selecting PM Bi-weekly showed only '4.99' without every-2-weeks text. Coffee Weekly selection showed '4.99' plus stale '.99 Every month', no signup fee text. Expected dynamic per-variation subscription info.

[[2026-05-22]] Fri 00:52
Verified Stage 03 Tasks 07/08 and reproduced in browser on PM Tool #243: before selecting variation, product page shows stale '.99 Every month'; after selecting Weekly, it still shows '.99 Every month'. Variation meta is correct in WP-CLI. Root cause: displaySubscriptionInfo() checks parent _is_subscription first and includes simple product info for variable parents, then returns before rendering the hidden variable-product subscription info container that JS updates. Plan: render the variable subscription container first for variable products with subscription variations, then fall back to simple product info only for non-variable/simple subscription products; rebuild frontend asset only if JS changes are needed; browser-verify PM Tool Weekly/Bi-weekly and Coffee Daily/Weekly/Bi-weekly.

[[2026-05-22]] Fri 00:56
Fix applied. Variable product display now renders the hidden variable subscription-info container before simple product info, so parent subscription defaults no longer print stale monthly terms. Also changed variable signup-fee i18n to positional placeholder so JS substitutes the fee amount. Browser QA: PM Tool initial page has no '.99 Every month' stale subscription block; Weekly shows '.99 Every week'; Bi-weekly shows '4.99 Every 2 weeks'. Coffee Plan initial page has no stale monthly block; Daily shows '.99 Every day' + 'Includes 7-day free trial'; Weekly shows '4.99 Every week' + '+ .99 signup fee'; Bi-weekly shows '4.99 Every 2 weeks' and no signup/trial. debug.log remains 0 bytes.
