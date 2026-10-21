---
id: 83
title: Developer API DiscountEngine reader reports a wrong member price for Pro subscription bundles; Store API product sale_price for bundles can differ in a fresh request
status: closed
priority: low
created: 2026-10-21T01:34:12.289787944+02:00
updated: 2026-10-21T10:47:53.520580531+02:00
started: 2026-10-21T07:18:54.31098002+02:00
completed: 2026-10-21T10:47:53.520579669+02:00
tags:
    - members
    - discounts
    - pro
    - bundles
    - developer-api
class: standard
---

Active QA task ID and scheduled day: N/A (found 2026-10-20 by the #71-#78 fix verification; fix log qa/issues-65-69-fix-log.md).
Affected IDs: bundle products 33290 and 11095 (read only). Affected users: members (for example user 25).
Route: arraysubs/src/Developer/Discounts.php calculateProduct(); Store API product endpoint for bundles.
Expected: calculateProduct() and the Store API sale price equal what a member pays for the bundle (cart/checkout charge 20.50 for 33290, 39.73 for 11095).
Actual: calculateProduct() gives 25.50 / 46.74 (per-item rule applied to the whole bundle from non-member child prices); the Store API product sale_price can show 21.68 in a fresh request. Cart, checkout and renewals charge the right amount; display/API only.
Proof: memberhtml fixer not_fixed items and verifier LOW in scratchpad fix7x2/result-memberhtml.json.

[[2026-10-21]] Wed 10:47
Closed 2026-10-21 (UTC) after fix + verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md (timeline from 05:18 on 2026-10-21).
Fix (core MembersAccess + Developer API, Pro SubscriptionBundle): products priced from their contents (Pro subscription bundles) declare it through arraysubs_member_discount_priced_from_contents and return the member price through arraysubs_member_discount_contents_unit_price, so the Store API price/sale price, on-sale flag and the Developer API calculateProduct() equal what a member is charged. Live 2026-10-21: Store API as a member (fixture 593 given pro_member temporarily, restored) 33290 price = sale = 20.50, on_sale true, 11095 39.73; member cart line 20.50; guest unchanged (25.00 / 46.74).
