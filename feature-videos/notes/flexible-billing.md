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
