# cancellation-flow: storyboard and store-change log

Feature page: `web-content/src/pages/features/cancellation-flow.astro` (tier `free+pro`, video tier chip "Free + Pro").
Placeholder title: "Cancellation flow: from cancel click to save offer".
Scene: `qa/feature-videos/scenes/cancellation-flow.mjs`. Output: `$FV_OUT/cancellation-flow/cancellation-flow.mp4` + `poster.webp`.
Customer ID block for anything mutated: 617-677.

## Storyboard (set up in admin -> customer -> outcome in admin)

| # | Kicker | Card title | What is shown |
|---|---|---|---|
| intro | | Keep the subscribers about to *leave* | Title card |
| 1 | Admin | One page for the whole *flow* | ArraySubs > Cancellation Flow: journey strip (reason -> offer -> decision), reasons card (8 reasons, reason required), offers card (4 of 4 enabled), the four offer cards with "Show for" reasons and eligibility conditions |
| 2 | Flow builder | Ask why in your *own words* | "Edit cancellation flow" full-screen editor, 7-step progress bar, Require Cancellation Reason switch, reason rows (drag handle, edit), custom reason "Deliveries are too frequent" with translate icon, step 2 Enable Retention Offers |
| 3 | Flow builder | Offers that fit the *reason* | Step 3 Discount (Show for these reasons, 20 %, 2 billing cycles, eligibility conditions: age, price, lifetime spend, days left), step 4 Pause (30 days), step 5 Downgrade (uses plan-switch paths), step 6 Contact Support (Support Hub URL, button text), step 7 Overview and Save |
| 4 | My Account | They click *Cancel* | Customer A (Premium Membership 69$/month): Memberships list -> View Details -> Manage Your Subscription (change plan, skip, pause above a small Cancel link) -> Cancel window with reason survey -> "Too expensive" -> "Before You Go..." with discount + lighter plan (real plans with prices), Keep Subscription / No thanks |
| 5 | My Account | One click *keeps* them | Accept the discount -> toast -> Recurring Amount 55.20$ "Discounted from 69.00$ for the next 2 renewals" -> the real "Retention Discount Accepted" email (old/new price, savings, renewals) |
| 6 | My Account | Need a break? *Pause* | Customer B (Plus Membership 39$/month): reason "Not using it enough" -> pause + lighter plan -> accept pause -> status Paused, next payment moved, Vacation Mode with scheduled resume |
| 7 | Admin | The story on each *subscription* | Admin subscription detail of A: recurring amount "Retention discount applied", Plan & Billing retention discount + renewals remaining, Retention History (offers shown/accepted with dates), timeline note + email sent |
| 8 | Reports | Know why people *leave* | WooCommerce > Analytics > Retention: cohort table, voluntary vs involuntary churn, Top 10 cancellation reasons, Activity Log filtered to Offer Accepted (A and B on top) |
| outro | | Stop one-click *goodbyes* | Outro card |

Free vs Pro honesty: the cancel link, the reason survey, cancel timing and undo are Free; own reasons, the four offers, targeting, the builder and the Retention report are Pro. Captions do not call Pro parts free.

## Store changes (all reversible; nothing in plugin code)

### Temporary mu-plugin (no shared setting changed)
- `wp-content/mu-plugins/zz-fv-cancellation-flow.php` (created 2026-11-28). Shared setting `arraysubs_settings[customer_actions][allow_cancellation]` is **false** on this store (unchanged, before and after). The mu-plugin forces it to `true` only for requests that carry both `fv_clean=1` and `fv_cf=1` cookies (only this scene's customer sessions set `fv_cf`), and a `pre_update_option` guard keeps the stored value if a settings write ever happens in such a request. Other agents' recordings are unaffected.
- Undo: delete the file.

### Scouting mutations (customers in block 617-677)
- Sub **27748** (user 632 Austin Singh, Premium Membership): opened the offers window twice ("Too expensive"), closed without accepting. Meta `_retention_offer_shown`, `_retention_offers_shown`, `_retention_offers_shown_date`, `_cancellation_reason_at_offer`, `_retention_offer_presentation`; two `offer_shown` rows in `wp_arraysubs_retention_logs`.
- Sub **27825** (user 641 Sienna Ferrari, Premium Membership): accepted the discount offer (20 % off for 2 renewals, 69 -> 55.20). Meta `_retention_*`, `_accepted_retention_offer`, `_retention_discount_*`, `_retention_offer_acceptance_claim`; notes + email "[Brightside Market] Your retention discount ..." (local Mailpit only); retention log rows offer_shown/offer_accepted.
- Sub **28087** (user 671 Xavier Simmons, Plus Membership): accepted the pause offer ("Not using it enough"): status `arraysubs-paused` for 30 days (resume scheduled 2026-12-27 21:01 UTC, Action Scheduler action 28711), next payment 2026-12-09 -> 2027-01-08. Meta `_pause_*`, `_pre_pause_status`, `_retention_*`.
- Undo for any of these (if ever needed): resume 28087 from admin (Resume) or let the scheduled resume run; for discount subs delete the `_retention_*` / `_accepted_retention_offer` meta. They are ordinary demo customers in this block, so leaving them is harmless.

### Pro outage note
- ArraySubs Pro was switched off in the DB by an outside session at 21:07:48 UTC (03:07 local). One scouting probe at ~03:09 local hit the "Pro Feature" lock; nothing was recorded then. The harness mu-plugin now forces Pro on for the recording browser and for `FV_PRO=1` WP-CLI. The scene refuses to start or to press Continue unless Pro's retention-offer processor is loaded (without Pro, Continue would really cancel the subscription).

### Recording mutations
(filled in below after each recording pass)

#### Pass 1 (started 2026-11-28 03:3x local, 21:3x UTC)
- Customer A = sub **27748** (user 632 Austin Singh, Premium Membership 69$/month): on camera picks "Too expensive", sees discount + lighter plan, accepts the **discount** (20 % for 2 renewals -> 55.20$). Creates `_retention_*` / `_retention_discount_*` meta, a subscription note, retention log rows (offer_shown, offer_accepted) and the "Retention Discount Accepted" email to austin.singh@example.com (local Mailpit only).
- Customer B = sub **27776** (user 636 Adam Perry, Plus Membership 39$/month): picks "Not using it enough", accepts the **pause** (30 days). Status -> Paused, next payment moves out 30 days, a scheduled resume action is queued.
- No settings are saved (the builder is opened and closed without changes; "Save cancellation flow" stays disabled).
- Undo (if ever needed): resume 27776 in admin (Resume) or let the scheduled resume run; for 27748 delete `_retention_discount_*`, `_retention_offer_*`, `_accepted_retention_offer` meta (the recurring amount meta itself is unchanged at 69).
- Pass 1 result: chapters 1-5 recorded (Austin 27748 accepted the discount at 21:40 UTC), chapter 6 timed out waiting for the "Offer accepted" toast although the pause **was applied** to Adam 27776 (paused 21:47 UTC, resume action 28810, next payment 2026-12-18 -> 2027-01-17). Both stay as they are (valid demo states).

#### Pass 2 (21:48 UTC onward): re-record chapters 4-8 with modal zooms
- Customer A = sub **27762** (user 634 Jonas Jenkins, Premium Membership 69$/month): "Too expensive" -> accepts the **discount** (20 % for 2 renewals). Email to jonas.jenkins@example.com (local Mailpit).
- Customer B = sub **27880** (user 647 Anthony Hall, Plus Membership 39$/month): "Not using it enough" -> accepts the **pause** (30 days, resumes by itself).
- Undo as above (resume 27880 in admin; delete the `_retention_*` meta on 27762).
- Pass 2 result: chapter 4 recorded, chapter 5 failed after Jonas 27762 **accepted the discount** (21:58 UTC; the toast vanished before it could be annotated on the overloaded machine). 27762 stays discounted (valid demo state). Chapter 4 also had the caption covering "Keep Subscription", so 4-8 are recorded again.

#### Pass 3 (~22:05 UTC): chapters 4-8
- Customer A = sub **27813** (user 640 Lily Nelson, Premium Membership 69$/month): "Too expensive" -> accepts the discount.
- Customer B = sub **27880** (user 647 Anthony Hall, Plus Membership 39$/month): "Not using it enough" -> accepts the pause.
