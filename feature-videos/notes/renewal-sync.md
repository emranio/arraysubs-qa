# Renewal sync — feature video notes

Slug: `renewal-sync` · Tier: Free + Pro · Scene: `scenes/renewal-sync.mjs`
Output: `$FV_OUT/renewal-sync/renewal-sync.mp4` + `poster.webp`
Feature page: `web-content/src/pages/features/renewal-sync.astro` (hero placeholder "Renewal sync in ArraySubs").
Customer block for mutations: 983-1043.

## Storyboard

| # | Kicker | Card | What it proves (feature page claim) |
| --- | --- | --- | --- |
| intro | | One billing day. Fair first charges. | |
| 1 | Settings | One switch, one billing day | "One switch in Renewal Settings"; boundaries (1st / week start / Jan 1); prorated or full first charge; renewals bill the full price; supported gateways; existing subscribers keep their dates; busy billing days spread over hours, dates unchanged |
| 2 | Storefront | A fair first charge | Plus Membership ($39/month) on Nov 28: cart line 39.00$ → 2.60$, rows "Renewals: 39.00$ every month" + "Next charge: December 1, 2026"; checkout with bank transfer; order received → next payment Dec 1 (clean storefront: details only as rows under the name) |
| 3 | Admin | The order remembers | Order line meta: Renewal sync / First charge: Prorated amount / Billing cycle starts Nov 1 / First full renewal Dec 1 / First charge amount 2.60$; subscription detail: $39 recurring, next payment Dec 1 |
| 4 | Pro · Per product | Late joiners pay for next month | Monthly Roast Club product editor: Flexible Renewal Sync checkbox, store-defaults line, slider 5/25, legend Full / Prorate / Charge full for next billing cycle, live drag of a cutoff (not saved), per-rule switches |
| 5 | Storefront | Paid today, covers December | Same customer, Monthly Roast Club on day 28 (late window): pays 30.00$, rows "First billing cycle Dec 1" + "Next charge Jan 1"; order received → next payment Jan 1, 2027 |
| 6 | Boxes · Plan wizard | One date for the whole box | Happy Paws Box editor → Flexible Renewal Sync screen: "decided here, never by the products inside it", own segment plan (full-width slider; not saved). Plan wizard (Pro) billing step: "Use a custom renewal segment plan" builder (wizard closed without creating) |
| 7 | Result | Everyone renews together | My Account (Grace): Roast Club next renewal Jan 1, Plus Membership Dec 1. Admin Subscriptions searched "Plus Membership": the new signups all on Dec 1, older subscribers keep their own dates |
| outro | | Bill on the 1st. Charge fairly. | |

Today on the store is 2026-11-28 (site timezone +06:00). Amounts depend on that date: a $39 monthly
plan prorates to 2.60$ (2 of 30 days, plugin formula: days until the boundary minus one). Re-recording on
another day changes the numbers in captions/labels ("$2.60", "Day 28", "November 28").

## How the store-wide switch is shown without touching the shared setting

`arraysubs_settings['renewals']['sync_to_billing_cycle']` stays **false** for everyone (real value).
A temporary mu-plugin, `wp-content/mu-plugins/zz-fv-renewal-sync.php`, is active only for requests with
both `fv_clean=1` and `fv_rs=1` cookies (only this scene's browser sets `fv_rs`). For those requests:
- the two sync keys are read from the private option `fv_rs_overlay`;
- a Save on Renewal Settings (`POST arraysubs/v1/settings/save`) writes only `fv_rs_overlay` and returns a
  normal success response; the shared option is never written (and a guard keeps the stored sync keys if any
  other write happens in such a request).
- the Pro Cart Info Editor flag `cart_info_editor.hide_first_billing_cycle_info` reads as `false`, so the
  cart shows the plugin's default "First billing cycle" row for the next-cycle rule (the shared store has
  this flag set to `true`, which hides the row; it stays `true` for everyone else).
- `woocommerce_product_get_short_description` returns "Unlimited library access and 10 downloads a month."
  for product #6711 (its stored excerpt is the seed text "Plus Membership — demo catalog."). Needed because the
  block cart/checkout received that excerpt through a path the shared clean-map filters did not reach
  (it ended up in localStorage `storeApiCartData`; the Store API cart endpoint itself is cleaned).
So other recordings and real visitors never see store-wide sync on, and their cart rows are unchanged.

## Store change log (every change, with undo)

| When (site time) | Change | IDs | Undo |
| --- | --- | --- | --- |
| 2026-11-28 ~03:40 | Added mu-plugin `wp-content/mu-plugins/zz-fv-renewal-sync.php` (request-scoped overlay, see above). | – | delete the file |
| 2026-11-28 ~03:40 | New private option `fv_rs_overlay` (`{"sync_to_billing_cycle":true|false,"sync_first_charge_mode":"prorate"}`), read only by the mu-plugin. | option | `wp option delete fv_rs_overlay --allow-root` |
| 2026-11-28 ~06:20 | mu-plugin extended: display-only excerpt override for product #6711, `fv_rs` requests only. | – | delete the file |
| 2026-11-28 ~05:40 | mu-plugin extended: for `fv_rs` requests only, `cart_info_editor.hide_first_billing_cycle_info` reads `false` (shared value stays `true`). | – | delete the file |
| 2026-11-28 ~04:00 | Display-only clean map `clean-map.d/renewal-sync.json`: "Plus Membership — demo catalog." → "Unlimited library access and 10 downloads a month.", SKU "DEMO-260927-PLUS" → "MEM-PLUS" (plus the `&#8212;`, `&mdash;` and JSON `\u2014` spellings the block cart preloads). Only affects `fv_clean=1` requests. | – | delete the json file |
| 2026-11-28 04:07 | Scouting: customer #1001 Parker Sinclair bought Plus Membership with the overlay on (bank transfer). Order #32257 (2.60$), synced subscription #32258 (next payment 2026-12-01 00:00 site time). Order set to Processing ("Bank transfer received."), subscription Active. Kept as one of the "new signups" in chapter 7. Its renewal invoice for Dec 1 is generated by the normal schedule (lead 3 days). | user 1001, order 32257, sub 32258 | delete order 32257 + sub 32258 (and its renewal invoice order) |
| 2026-11-28 04:10 | Scouting: #1001's cart held Monthly Roast Club; emptied by the scene setup. | user 1001 | – |
| recording | Chapter 2: customer #1019 Grace Woods buys Plus Membership (bank transfer) → synced subscription (Dec 1). Chapter 3 `before` marks the order Processing. Earlier takes for #1019 + product 6711 are deleted (subscription, parent and renewal orders) before each take. | user 1019, product 6711 | scene `removeTakes(1019, 6711)` |
| recording | Chapter 5: #1019 buys Monthly Roast Club (bank transfer) → next-cycle synced subscription (first cycle Dec 1, next payment Jan 1, 2027). Chapter 7 `before` marks it Processing. Earlier takes removed first. | user 1019, product 26114 | `removeTakes(1019, 26114)` |
| recording | Chapter 7 `before`: #1003 Hudson Gray and #1012 Isabella Tran each buy Plus Membership off camera (only if they do not already own a synced one), orders marked Processing. | users 1003, 1012, product 6711 | `removeTakes(1003, 6711)`, `removeTakes(1012, 6711)` |
| recording | Chapter 4 drags a cutoff handle on Monthly Roast Club (#26114) and chapter 6 ticks the box's own-plan checkbox / fills the plan wizard — **none of these are saved** (product meta, box config and wizard untouched). | – | – |

No shared setting was changed. Product #26114's saved plan (seg1_end 5, seg2_end 25, all three active) was
already there before this session.

## Final state after recording (2026-11-28 ~06:10 site time)

Shared settings verified unchanged: `renewals.sync_to_billing_cycle` = false, `sync_first_charge_mode` = prorate,
`cart_info_editor.hide_first_billing_cycle_info` = true. `fv_rs_overlay` option deleted (the scene setup recreates it).
Carts of #1019, #1003, #1012, #1001 are empty.

| Customer | Product | Subscription | Parent order | Next payment (UTC) | Renewal invoice created by the normal schedule |
| --- | --- | --- | --- | --- | --- |
| #1019 Grace Woods | Plus Membership #6711 | #32712 (active, synced, prorated 2.60$) | #32711 (processing) | 2026-11-30 18:00 = Dec 1 00:00 site | #32738 (pending) |
| #1019 Grace Woods | Monthly Roast Club #26114 | #32681 (active, next-cycle 30.00$) | #32680 (processing) | 2026-12-31 18:00 = Jan 1 00:00 site | – |
| #1001 Parker Sinclair | Plus Membership | #32258 (active, synced) | #32257 | Dec 1 | #32373 |
| #1003 Hudson Gray | Plus Membership | #32567 (active, synced) | #32566 | Dec 1 | #32590 |
| #1012 Isabella Tran | Plus Membership | #32601 (active, synced) | #32600 | Dec 1 | #32618 |

Earlier takes deleted by the scene (subscription + parent + renewal orders): #32510/#32509/#32525, #32656/#32655/#32667 (Plus,
#1019) and #32548/#32547 (Roast Club, #1019).

To undo everything: delete the five subscriptions above with their parent and renewal orders (the scene's
`removeTakes(cust, product)` does exactly that), delete `mu-plugins/zz-fv-renewal-sync.php` and
`clean-map.d/renewal-sync.json`.

## Record IDs used

- Products: Plus Membership #6711 ($39/month), Monthly Roast Club #26114 ($30/month, Pro plan), Happy Paws Box #31424.
- Customers: #1019 Grace Woods (hero), #1001 Parker Sinclair, #1003 Hudson Gray, #1012 Isabella Tran.

## Revision 2 (2026-11-28 ~06:20–07:30 site time): reviewer fixes

Reviewer verdict "fix" (2 major, 12 minor). What changed:

| Issue | Fix |
| --- | --- |
| Major: add-to-cart jumped to checkout, then cart, then checkout again (ch2, ch5) | mu-plugin `zz-fv-renewal-sync.php` (fv_rs requests only) now filters `woocommerce_add_to_cart_redirect` to the cart (priority `PHP_INT_MAX`, after ArraySubs' one-click redirect at 20) and reads `woocommerce_cart_redirect_after_add` as `yes` (notice says "Continue shopping"). Scene waits for `.wc-block-cart-item__prices`; no "View cart" click. Shared store unchanged. |
| Major: caption hid the Place Order button (ch2, ch5) | `v.say(null)` before every Place Order / Proceed / page-changing click. |
| ch1 first caption covered the Renewal Settings tab | first line at `bl` + ring on `a.arraysubs-tab-link--active`; rest of chapter `tr`. |
| ch7 timeline (Parker synced before the save) | ch7 `before` now removes and re-buys Plus Membership for #1001, #1003, #1012 (CLUSTER) every take, so all synced signups postdate ch1's Save. "Earlier" ring = unsynced rows below the block (all created before the Save). |
| Sale badge / poster | cart label "Today: only the days left in November"; poster moved to ch7 list ("4 new signups, one billing day"). Badge itself is a product issue (see result `product_bugs`). |
| ch3 zoom cropped totals + admin menu sliver; IP `::1` and PDF notice | order opens already scrolled to the items (header with IP never on screen); zoom = whole item row framed x 182–1142 at 1.5x (totals visible, no menu/sidebar slivers). |
| ch6 clipped 1.15x info zoom, half-slider 1.8x legend zoom, permalink row | both zooms removed (1x focus); box editor opens scrolled to Product data (permalink off screen) + ring on the "Flexible Renewal Sync: Store default" fact. |
| Captions carried over to the next screen | `say(null)` before page changes; new captions on checkout (ch2 "She checks out with bank transfer.", ch5 "At checkout she pays $30.00 today."), plans list (ch6) and admin list (ch7). |
| ch4 rule switch only claimed; 21 s zoom | late rule switched off on camera (legend → 1–15 / 16–30, "Two rules now cover all 30 days"), back on (editor resets to thirds 10/20), then both handles dragged to 5 and 25 on camera. Zoom only for the three legend labels (~6 s). Nothing saved. |
| Zoom holds | cart zooms 1.6x with shorter holds (~6–7 s incl. annotation changes); ch7 list now 1x (scrolled so the table header is at the top). |
| Pending status next to "renews December 1" | order-received focus uses `dim: true`. |
| "Checkout offers methods…" over a one-method checkout | reworded: caption "She checks out with bank transfer." + label "Can bill on the shared day" (settings copy: manual/offline gateways, Stripe, Mollie). |
| Cramped checkout summary column | theme layout (narrow sidebar at 1440 px); checkout now held ~4 s only, attention on the payment method. Not changed further. |

Store changes in this revision: mu-plugin edit above (undo: delete the file). `fv_rs_overlay` re-created
(`{"sync_to_billing_cycle":true,"sync_first_charge_mode":"prorate"}`) — delete with
`wp option delete fv_rs_overlay --allow-root` after recording. New takes listed below (old ones removed by the scene).

### Revision 2 recording runs

- Full pass (all 7 chapters, `run-g.log`), then chapters 2, 3, 5, 7 again (`run-h.log`): the order-received labels moved
  below the date cell (`side: 'bottom'`, they hid the "Next Payment" header) and the ch7 list scroll got `offset: -64`
  (the table header and first synced row had slid under the sticky ArraySubs page header).
- ch1 on-camera Save time shown: November 28, 2026 6:42 am. All synced signups in ch7 are later (6:59–7:13 am).
- ch4 log: editor reset to thirds after the rule was switched back on (`handles before drag 10 20`), dragged to `5 25`.
- Final: 248.8 s, 1920x1080, 30.3 MB. Poster = ch7 list ("4 new signups, one billing day").

### Final state after revision 2 (2026-11-28 ~07:16 site time)

Shared settings verified unchanged: `renewals.sync_to_billing_cycle` = false, `sync_first_charge_mode` = prorate,
`cart_info_editor.hide_first_billing_cycle_info` = true, `woocommerce_cart_redirect_after_add` = no.
`fv_rs_overlay` deleted again (the scene setup recreates it). Carts of #1019, #1001, #1003, #1012 emptied by teardown.

| Customer | Product | Subscription | Parent order | Next payment (UTC) | Renewal invoice (normal schedule) |
| --- | --- | --- | --- | --- | --- |
| #1019 Grace Woods | Plus Membership #6711 | #32998 (active, synced, prorated 2.60$) | #32997 (processing) | 2026-11-30 18:00 = Dec 1 00:00 site | #33009 |
| #1019 Grace Woods | Monthly Roast Club #26114 | #33025 (active, next-cycle 30.00$) | #33024 (processing) | 2026-12-31 18:00 = Jan 1 00:00 site | – |
| #1001 Parker Sinclair | Plus Membership | #33044 (active, synced) | #33043 | Dec 1 | #33087 |
| #1003 Hudson Gray | Plus Membership | #33070 (active, synced) | #33069 | Dec 1 | #33122 |
| #1012 Isabella Tran | Plus Membership | #33105 (active, synced) | #33104 | Dec 1 | #33131 |

Takes deleted by the scene during revision 2 (subscription + parent + renewal orders): #32832/#32831/#32851 and
#32712/#32711/#32738 (Plus, #1019), #32858/#32857 and #32681/#32680 (Roast, #1019), #32258/#32257/#32373 and
#32902/#32901/#32938 (#1001), #32567/#32566/#32590 and #32919/#32918/#32941 (#1003), #32601/#32600/#32618 and
#32950/#32949/#32970 (#1012). The rows in the revision-1 "Final state" table above are superseded by this one.

Undo everything: run the scene's `removeTakes(cust, product)` for the five pairs above (deletes subscription, parent
and renewal orders), delete `wp-content/mu-plugins/zz-fv-renewal-sync.php` and `clean-map.d/renewal-sync.json`.

### Product issue seen (not fixed, plugin code untouched)

Block cart / block checkout show the prorated renewal-sync first charge as a WooCommerce sale: struck-through
39.00$ next to 2.60$ in the price row and a "Save 36.40$" badge in the Total column (cart) / summary (checkout).
Repro: Renewal Settings → Sync Renewals to Next Billing Cycle on, First Charge "Prorate until the synced renewal date";
on Nov 28 add Plus Membership (#6711, $39/month) and open `/cart-2/` and `/checkout-2/`. Expected: the proration is
not presented as a discount; the Total column holds the amount only (clean-storefront rule). The video keeps the
badge (not hidden) and labels the price "Today: only the days left in November".
