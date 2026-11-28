# Flexible billing — feature video notes

Slug: `flexible-billing` · Tier chip: **Free + Pro** (catalog tier `free+pro`) · Scene: `scenes/flexible-billing.mjs`
Output: `$FV_OUT/flexible-billing/flexible-billing.mp4` + `poster.webp`
Customer block for mutations: 861-921. Hero customer: **Freya Kelly (user 866)**, owns sub #29430 (Membership Tier – Basic, $24/month, untouched).

## Storyboard (admin set-up -> customer -> outcome in admin)

| # | Kicker | Card | What is shown | Free/Pro |
|---|---|---|---|---|
| intro | | Billing that fits the way you sell | title card | |
| 1 | Admin | Every plan on one screen | ArraySubs > Subscription Plans: type pills, Price & billing column (zoom), Published + Variable filter, Coffee Club row "Billing varies by variation", Edit subscription billings, Duplicate | Free |
| 2 | Plan wizard | A new plan in 7 steps | Add new plan -> 7-step wizard: Simple, title "Herbal Tea Club" + $18, Recurring billing (Fixed/Flexible Length/Full Flexible cards, period/interval/length), Trial 14 days + $5 sign-up fee, Fixed Membership (Pro, skipped), Subscription Shipping (Pro, recurring, $4.95 initial override), Review & create with Edit links, Create product confirm, success cards | Free (+Pro steps) |
| 3 | Variable plans | Monthly, yearly and lifetime | Coffee Club Membership editor: 3 variations, variation Subscription Billings card, 7-day trial on Monthly, per-variation checkout links; product page: Monthly (7-day trial line), Annual $120/year, Lifetime $299 | Free |
| 4 | Pro · Customer choice | Customers set their own cycle | Fresh Roast Coffee Plan editor (Full Flexible, Week+Month, max 12); product page: picks Week, price reads "/ week", length 8; one-click checkout shows Renewals every week, Duration 8 billing cycles; order placed (bank transfer) | Pro |
| 5 | Pro · Fixed dates | One end date for everyone | Studio Season Pass editor: Fixed Period Membership, annual cutoff Dec 31, enrollment window, Renew; product page "Membership ends: December 31, 2026", auto-renews line, enrollment window | Pro |
| 6 | Settings | Rules for every plan | Settings > General: cart rules toggles, mixed checkout / different cycles, One Click Checkout, trial rules | Free |
| 7 | Storefront | Trial and fee, spelled out | Herbal Tea Club product page ($18/month, + $5 signup fee, 14 days free trial, shipping line) -> checkout item rows + totals (sign-up fee, shipping, trial) -> order placed | Free (+Pro shipping) |
| 8 | Result | Terms locked in | My Account Memberships cards (Fresh Roast weekly, Herbal Tea Club trial), admin Subscriptions searched by "Freya", Herbal Tea Club detail: stats + Plan & Billing | Free |
| outro | | Sell plans, not workarounds | outro card | |

## Store change log (every change, with undo)

| When (UTC) | Change | IDs | Undo |
| --- | --- | --- | --- |
| 2026-11-27 ~21:00 | Display-only clean map `clean-map.d/flexible-billing.json`: strips the URL-encoded `Demo%20%5Cu00b7%20` prefix (block checkout preloads cart JSON this way), drops `Demo ` from product category names, shows `hello@brightside.market` instead of `dev-email@wpengine.local`. Only affects `fv_clean=1` requests. | – | delete the json file |
| 2026-11-27 ~21:00 | Untrashed product #31767 "Demo · Studio Season Pass" (made by the 2026-11-25 capture agent: $29/month, virtual, Fixed Membership annual cutoff 12-31, Renew, enrollment 2026-11-01 → 2027-01-31, thumbnail #25765, category #50) and set it to `publish`. Scene setup keeps it published. | product 31767 | `wp post delete 31767 --allow-root` (back to Trash) |
| 2026-11-27 21:0x | Scouting: customer #866 cart briefly held #25875 (week/8) + #31342; emptied (session row + `_woocommerce_persistent_cart_1`). No order. | user 866 | – |
| every recording of ch 1/2 | `before` trashes earlier "Herbal Tea Club" products made by the wizard (wp post delete = Trash). | e.g. 32152 (first take, 21:19 UTC) | they are in Trash; `wp post delete <id> --force` to purge |
| every recording of ch 2 | The plan wizard creates a new published simple product **Herbal Tea Club**: $18/month, 14-day trial, $5 sign-up fee, physical, Pro shipping recurring with $4.95 initial override. Then off camera: image #25742, short description, category #19 (Demo Coffee & Pantry). | latest id: see recording log below | `wp post delete <id> --allow-root` |
| every recording of ch 4 | Customer 866 buys Fresh Roast Coffee Plan (week, 8 payments) with Direct bank transfer: new order + subscription. `before` force-deletes the previous take's order + subscription for 866 on product 25875. | see log below | delete order + `arraysubs_data` post |
| every recording of ch 7 | Customer 866 buys Herbal Tea Club with Direct bank transfer (trial + fee): new order + subscription. `before` force-deletes the previous take. | see log below | same |
| every recording of ch 8 | `before` moves those two bank-transfer orders On hold -> Processing (as an admin confirming the transfer) so the subscriptions start. | see log below | – |

No shared settings were changed. No plugin was (de)activated. No plugin code edited.

## Recording log

(filled in below after each pass)

### Pass log (UTC 2026-11-27)

- ~21:12 pass A (ch 1-3): ch2 wizard created product **32152** (later trashed by the next pass). Killed at ch3 when the coordinator stopped a duplicate agent (~21:21).
- A duplicate instance of this agent ran ~21:11-21:21; it only edited the scene (`>> visible=true` in wizard type selectors) and wrote `error-ch2.jpg`; it created no store records (checked: only #32152 existed with that title, no new orders/subscriptions in block 861-921).
- Nothing was recorded during the Pro-off window (21:07:48-21:12). All final segments were recorded after 21:25.
- pass C/D/E (~21:30-22:15): seg-01 (34.7s), seg-02 (81.4s, wizard product **32216** "Herbal Tea Club", published, image 25742, category 19), seg-03..06.
  - ch4 order **32289** (bank transfer, Freya Kelly) -> subscription **32290** Fresh Roast Coffee Plan, every 1 week, 8 payments. Ch8 `before` set order 32289 to Processing -> sub Active.
  - ch7 first take: order 32343 / sub 32344 (Herbal Tea Club, trial) — removed by the ch7 re-take's `before` (order + sub force-deleted, their pending Action Scheduler actions cancelled).
- seg-04/seg-05 from pass E came out 1440x810 (the shared harness was being changed at that time); re-recorded at 1920x1080 in pass H.
- Pass G (~22:44-23:27): trimmed re-takes of ch 1, 2, 3, 6, 7, 8 (video was 5:22, too long). Ch1 `before` removed take 32421/32422 and trashed wizard product 32216. Ch2 created the final wizard product **32500**. Ch7 created order **32532** -> sub **32533** (Herbal Tea Club, Trial, $18/month after 14 days, $5 fee, $4.95 shipping).
- Pass H (~23:33-23:45): ch 4, 5, 8 at full resolution. Ch4 removed 32289/32290 and created order **32624** -> sub **32625** (Fresh Roast Coffee Plan, every 1 week, 8 payments). Ch8 `before` set orders 32624 and 32532 to Processing (subs Active / Trial).
- Pass I: ch8 only (My Account card order fix), no store change.

## Final state of store records made for this video

| Record | State | Undo |
| --- | --- | --- |
| Product 31767 Studio Season Pass | published (was in Trash) | `wp eval 'wp_trash_post(31767);' --allow-root` |
| Product 32500 Herbal Tea Club (wizard) | published, image 25742, cat 19 | `wp eval 'wp_trash_post(32500);' --allow-root` |
| Products 32152, 32216 (earlier wizard takes) | Trash | `wp post delete 32152 32216 --force --allow-root` |
| Freya Kelly (866) sub 32625 / order 32624 | Active, weekly, 8 payments, bank transfer (Processing) | delete order 32624 and post 32625, cancel its pending Action Scheduler actions (args `[32625]`) |
| Freya Kelly (866) sub 32533 / order 32532 | Trial until 2026-12-12, bank transfer (Processing) | same for 32532 / 32533 |
| Deleted takes | orders/subs 32289/32290, 32343/32344, 32421/32422 force-deleted; their pending Action Scheduler actions were cancelled | – |

Cart of 866 emptied in setup/teardown. No shared settings changed at any point.

## Result

Final `flexible-billing.mp4`: 295.7 s (4:56), 1920x1080, 30 fps, no audio, 8 chapters. Poster = ch7 product page with the "Price, sign-up fee, trial, shipping" ring.

## Revision 2 (2026-11-28, after the independent review)

All 8 chapters re-recorded at native 1920x1080 (every `seg-NN.mp4` checked with ffprobe). Final
`flexible-billing.mp4`: 261.7 s (4:22), 1920x1080, 30 fps, no audio. Poster = ch7 Herbal Tea Club
product page, ring without dim, label below the ring (no title covered, no test nav).

### What changed in the scene (fixes for the critique)

| Critique item | Fix |
| --- | --- |
| Junk SKUs in the Plans list | Display-only clean-map pairs map the plan SKUs `DEMO-260927-*` (plan products) and `MEDIA-*-20261125` to empty, so the column shows "-". Ch1 filter beat now types "Coffee" into the search after Published + Variable, so only Coffee Club Membership is listed (Field Notes / Membership Tier rows never show). Caption: "Search, or filter by status and type." |
| localhost links in variation helper links | Before scrolling to them, the scene rewrites the two input values in the DOM (display only) to `https://brightside.market/cart/?add-to-cart=…` / `/checkout/?…`. Never saved. |
| Test nav items, header wrap, empty Reviews tab | Scene-local init script (storefront frames only, recording browser only) hides the header nav items linking to `/checkout/`, `/checkout-2/`, `/cart-2/`, the footer nav, the Reviews tab and its panel, and the product-details block when it only holds Reviews. Shared mu-plugin untouched. Checkout header now fits on one line. |
| Labels over product titles / dimmed poster | Ch7 product focus: `side: 'bottom'`, no dim; poster taken there. Ch4 price focus: `side: 'bottom'`. |
| Interval caption vs length ring | Split into two beats: "Bill every 1 to 12 days, weeks, months or years." -> the wizard period is set to **Week** and the interval to **2** on camera, ring on Billing period + Billing interval ("Every 2 weeks"); then "Stop after a set number of payments, or never." -> ring on Subscription length ("0 = never ends"). |
| Caption over order-received totals | Caption hidden before Place Order; after the page loads the scene scrolls the totals + Related Subscriptions into view, captions, and rings the new subscription row (ch4 "Billed every week", ch7 "Next payment after the trial"). Cursor hidden. |
| Minor items | Ch1 ring on rows 1-4 (bundle, simple, bundle, variable) labelled "The plan type under each name"; ch3 variation ring label "Price, schedule, 7-day trial" (no fee claim); Annual label "Billed yearly" (side right); ch5 last caption cut; ch8 final caption cut (ends on Plan & Billing ring); ch6 caption "Trials can require a payment method and stay one per customer."; Fixed Membership step 1.1 s caption + "More in chapter 5" ring; shipping step caption top-right; ch7 totals zoom caption bottom-right, totals-only framing; My Account opens scrolled so the "Need to modify or cancel…" line is off screen; ch8 label "Three plans, three schedules" (now literally true: every 2 weeks / every week / every month); ch3 editor opens off camera and only lifts the card once variations are loaded; cursor hidden before zooms and focus holds. |
| Coverage: intro price (Pro) | Ch3: on the Monthly variation the scene ticks "Different Renewal Price", types 15 and 3 ("15.00$ after 3 payments"; code: renewal price applies once completed payments >= N). Never saved (beforeunload dropped, then navigate away). |
| Coverage: interval > 1 | The wizard plan Herbal Tea Club is now **$18 every 2 weeks** (14-day trial, $5 fee, $4.95 initial shipping); product page, checkout, order received, My Account and admin all show "every 2 weeks". |
| Coverage: "Tick Subscription" | Ch3 opens on the product-data header ring "Subscription on" (label[for=_is_subscription]). |
| Placeholder email | Display-only clean-map pair `freya.kelly@example.com` -> `freya.kelly@mail.com`; block checkout's email input (filled from the Store API, not reached by the clean map) is masked by a scene init script (DOM value only; React state and the submitted orders keep the real address — verified: orders 33015/33035 billing email = freya.kelly@example.com). Teardown also re-sets user meta `billing_email` of 866 to the real value. |
| Caption holds | Captions followed by an action/focus now hold ~1 s (they stay visible through the action), which cut ~15 s; video went from 4:56 to 4:22 with three new beats. |

### Store changes in revision 2

| When (UTC 2026-11-28) | Change | IDs | Undo |
| --- | --- | --- | --- |
| ~00:05 | clean-map.d/flexible-billing.json: + 20 SKU pairs (DEMO-260927-STARTER/PLUS/PREMIUM/WEEKLY/FORTNIGHT/QUARTERLY/ANNUAL/TRIAL/LIFETIME/FIXED/DAILY/FLEXIBLE/VARIABLE-PLAN/TIER-BASIC/TIER-PROFESSIONAL/BUNDLE/BOX, MEDIA-TEAM/FAMILY-MEMBERSHIP/COFFEE-CLUB-BUNDLE-20261125 -> "") and `freya.kelly@example.com` -> `freya.kelly@mail.com`. Display only, all fv_clean requests. | – | remove those keys |
| ch1 `before` | Removed previous take order 32532 / sub 32533; trashed wizard product 32500. | 32500 (Trash) | `wp post delete 32500 --force` |
| ch2 takes | Wizard created 32798 (first pass, trashed by the next ch2 `before`) and **32818** (final: Herbal Tea Club, $18 every 2 weeks, trial 14 d, fee $5, Pro shipping recurring, $4.95 initial; off camera image 25742, short description, category 19). | 32798 (Trash), 32818 (published) | `wp eval 'wp_trash_post(32818);'` |
| ch4 takes | Order/sub 32845/32846 (removed by the next take); final order **33015** -> sub **33016** Fresh Roast Coffee Plan, every week, 8 payments (Pending, then Processing/Active via ch8 `before`). Earlier take 32624/32625 removed. | 33015/33016 | delete order + `arraysubs_data` post, cancel pending AS actions with args `[33016]` |
| ch7 takes | Orders/subs 32874/32875 and 32963/32964 removed by later takes; final order **33035** -> sub **33036** Herbal Tea Club (Trial until 2026-12-12, every 2 weeks). | 33035/33036 | same as above |
| ch8 `before` | Orders 33015 and 33035 moved On hold -> Processing ("Bank transfer received."). | – | – |
| every run | Cart of 866 emptied in setup/teardown; `billing_email` meta of 866 re-set to freya.kelly@example.com (it was unchanged). | – | – |

No shared settings changed, no plugin (de)activated, no plugin code, shared mu-plugin or harness edited.
Products in Trash from this video: 32152, 32216, 32500, 32798 (`wp post delete … --force` to purge).

### Final records for this video (revision 2)

| Record | State |
| --- | --- |
| Product 31767 Studio Season Pass | published |
| Product 32818 Herbal Tea Club (wizard) | published, $18 every 2 weeks |
| Freya Kelly (866) sub 33016 / order 33015 | Active, Fresh Roast, weekly, 8 payments |
| Freya Kelly (866) sub 33036 / order 33035 | Trial until 2026-12-12, Herbal Tea Club |
