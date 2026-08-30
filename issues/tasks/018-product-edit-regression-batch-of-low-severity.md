---
id: 18
title: 'Product-edit regression: batch of low-severity findings (display, dead code, hidden-but-posted fields)'
status: closed
priority: low
created: 2026-08-26T14:39:51.627313541+02:00
updated: 2026-08-30T14:53:48.843664222+02:00
started: 2026-08-30T14:53:48.843639266+02:00
completed: 2026-08-30T14:53:48.843639266+02:00
tags:
    - product-edit
    - subscription-box
    - subscription-bundle
    - portal
    - cosmetic
    - tech-debt
class: standard
---

**QA task ID / scheduled day:** N/A — ad-hoc product-edit regression cycle, 2026-08-26. Report: `qa/product-edit-regression-qa-report.md`
**Browser/user context:** agent-browser, session `admin` (WP user 1, administrator); session `guest` (logged out).
**Affected subscription ID(s):** 33278 (box), 33298 / 33302 (bundle)
**Affected order ID(s):** 33277 (box), 33297 (bundle)
**Affected user(s):** WP user 1 (admin); QA customer WP user 484 `peqa-cust`

Collected while running the full product-edit regression. Full detail with evidence lives in `qa/product-edit-regression-qa-report.md` §3 (F-11 … F-22).

1. **Box/bundle contents show a unit price next to a quantity.** `CartHooks::appendCartItemDataRows()` formats `%1\$s × %2\$d — %3\$s` with the **unit** price, so cart/checkout/order-email read `SLT Box Item A × 2 — $4.00` while that line is really $8.00; the printed numbers (4+25+18=47) do not reach the $51.00 subtotal. The bundle admin summary gets this right (QTY | UNIT PRICE | LINE TOTAL). URL: https://mirror-help.arrayhash.com/cart/
2. **Uploaded box file is a link on the order page but plain text in the portal.** `OrderHooks::linkUploadedInputFile()` produces `<a href=".../arraysubs-box-uploads/pDwqQLRwgUlKSfz3aVkF.png">logo.png</a>` on /checkout/order-received/33277/, while /my-account/view-subscription/33278/ renders `<tr class="arraysubs-box-portal-row--input"><th>Logo file:</th><td>logo.png</td></tr>`.
3. **Bundle child subscription looks standalone in the portal list.** `My account → Subscriptions` shows `#33302 BUNDLE-CHILD-SUB … $0.00 Every month` with no bundle badge. The detail page for 33302 is correct ("Part of: PEQA Bundle QA (#33298)", no independent actions).
4. **`Change Plan` always dead-ends on box/bundle subscriptions** — the modal on 33278 only says "No plan changes available for this subscription."
5. **Variable parent price range has no billing suffix** — 33313 shows `$15.00 – $25.00` with no "/ month"; `subscriptionPriceHtml()` early-returns unless `is_type(['simple','variation'])`. Selecting a variation then correctly shows `$15.00 / month`.
6. **Bundle discount input is not clamped.** Entering `50` on a $30 bundle leaves 50 in the field; the preview clamps to `Discount −$30.00 / Bundle total $0.00`. A $0.00 recurring bundle should probably be rejected.
7. **Variation `Renewal Shipping Override` is always visible** — `views/simple-product-fields.php` wraps it in `.show_if_recurring_shipping`; `views/variation-fields.php` does not, so it shows under one-time shipping where it has no effect.
8. **Hidden-but-posted fields write meaningless meta:** `_renewal_price=0` / `_renewal_price_after=1` are stored with the toggle off (33252); shipping meta is stored for Virtual/Downloadable products; Box 33263 and Bundle 33290 carry `_arraysubs_subscription_mode=fixed`, `_arraysubs_flexible_periods=a:1:{i:0;s:5:"month";}` and `_arraysubs_fixed_end_renewal=expire` from the hidden subscription panel.
9. **`Product Redirect [AS]` tab has no product-type gate** (`arraysubs_redirect_options arraysubs_redirect_tab`) so it renders for every type including Store Credit and Grouped. Confirm intent.
10. **Dead CSS:** `arraysubspro/src/Features/FixedPeriodMembership/views/simple-product-fields.php:29` adds `arraysubs-fixed-end-date-section--hidden`, but no rule for that modifier exists in either plugin — verified `getComputedStyle(...).display === "block"` with the class applied.
11. **Dead code:** `arraysubs/src/Features/SubscriptionProducts/Services/Validation.php::validateBeforeSave()` is never hooked (the constructor registers only `handleCustomQuery`). It duplicates the rules that actually live in `Hooks::validateProduct()` and will drift.
12. **Bundle wizard help text is wrong:** it says products that no longer fit a changed cycle are "removed from the product list", but they are flagged in place ("Not available on this billing cycle — remove it or change the schedule") and `Continue` is blocked. The behaviour is better than the text; fix the text.
13. **Second Paddle notice on a broken save:** saving priceless product 33324 also produced "Paddle catalogue synchronization failed for product #33324. Review the Paddle sync log, then save the product again." with no link to the named log.


---

## Resolved — 2026-08-30

| # | Item | Outcome |
|---|---|---|
| 1 | Box/bundle unit price next to a quantity | **Fixed.** Rows above quantity 1 now print `Name × 2 — $4.00 each ($8.00)`; quantity 1 is unchanged. Verified in cart, checkout and the order-received table for order 35312: `8 + 25 = 33` matches the $33.00 subtotal. Same change in the bundle's `CartHooks`. |
| 2 | Uploaded file plain text in the portal | **Fixed** with #19 — the portal renders the authorised download link. |
| 3 | Bundle child looks standalone in the portal list | **Fixed.** New `arraysubs_my_subscriptions_row_product_meta` hook; box (free) and bundle (pro) render a badge plus a link to the owner. Verified: `#33302 BUNDLE-CHILD-SUB · Included in a subscription bundle · PEQA Bundle QA (#33298)`. |
| 4 | `Change Plan` always dead-ends on box/bundle | **Fixed.** `ProrationCalculator::hasAvailableSwitchOptions()` (same eligibility as `getAvailableSwitchOptions()`, without the proration previews) gates the button. Verified: hidden on box subscription 35313, shown on 35323 (Basic Monthly) where the modal really lists *Pro Monthly — UPGRADE*. |
| 5 | Variable parent price range has no billing suffix | **Fixed.** `subscriptionPriceHtml()` handles variable parents, appending the suffix only when every subscription variation shares one schedule. Verified: 33313 → `$15.00 – $25.00 / month` on the product page and shop archive; `[QA] Multi-Plan Bundle` (#69, lifetime + month + year) correctly gets none; selecting a variation still shows `$25.00 / month`. |
| 6 | Bundle discount input not clamped | **Fixed.** `commitAmount()` writes the clamped value on blur and an inline warning shows while the typed value is over the cap. A zero recurring total is now rejected by the wizard and reported at save time by the bundle saver. Verified: typing `50` on a $30 bundle warns and snaps to `30` on blur; a 100% discount blocks *Continue* with *"The discount leaves nothing to charge…"*. |
| 7 | Variation `Renewal Shipping Override` always visible | **Skipped** — tagged `[skip]` by the maintainer. |
| 8 | Hidden-but-posted fields write meaningless meta | **Skipped** — tagged `[skip]`. (The lifetime subset is covered by #16.) |
| 9 | `Product Redirect [AS]` has no product-type gate | **Not a defect** — confirmed intentional: the feature supports every product type, so the tab is always visible. |
| 10 | Dead CSS `arraysubs-fixed-end-date-section--hidden` | **Not dead — left alone.** The rule *does* exist (`arraysubspro/src/resources/scss/admin/fixed-period-membership.scss:2`) and is compiled into `public/build/admin/fixed-period-membership.css`. It renders `display:block` only because jQuery's `.show()` on `.show_if_subscription` writes an inline style that outranks it; its real job is hiding the section before that JS runs. Removing it would introduce a flash of unhidden content. |
| 11 | Dead code `Validation::validateBeforeSave()` | **Removed** (with its private `addValidationError()`). It was never hooked and duplicated `Hooks::validateProduct()`. `handleCustomQuery()` **is** live — `arraysubs_get_subscription_products()` passes `is_subscription => true` — so the class and its constructor stay. Verified after removal: `wc_get_products(['is_subscription' => true])` still returns subscription products. |
| 12 | Bundle wizard help text says ineligible products are "removed" | **Fixed** — the text now describes what actually happens (flagged in place, left out of the subtotal, *Continue* blocked). |
| 13 | Second Paddle notice on a broken save | **Fixed** — see #19's sibling report `qa/paddle-catalogue-sync-findings.md`. The sync now stands down on a blocked save and the notice links to the log it names. |
