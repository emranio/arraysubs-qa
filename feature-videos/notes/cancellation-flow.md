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
- Pass 3 result: chapters 4-8 recorded. Lily 27813 accepted the discount (22:12 UTC, 69 -> 55.20 for 2 renewals, email to lily.nelson@example.com in local Mailpit); Anthony 27880 accepted the pause (22:17 UTC, paused 30 days, next payment 2026-12-04 -> 2027-01-03, resume scheduled 2026-12-28).
- Pass 4/5 (no store changes): re-recorded chapters 1, 2, 3, 7, 8 tighter (total under 5 minutes) and fixed label placement.

## Final state (2026-11-28 ~04:35 local)
- Video: `$FV_OUT/cancellation-flow/cancellation-flow.mp4` (290.4 s, 8 chapters, H.264 30 fps, 1440x810 like every harness output today), poster `poster.webp` (chapter 4: the "Before You Go..." window with the discount offer ringed).
- Chapters: 1 overview page (23 s), 2 builder reasons (28 s), 3 builder offers + targeting (45 s), 4 My Account cancel -> reason -> offers (50 s), 5 accept discount + toast + new price + email (36 s), 6 second customer pauses (43 s), 7 admin subscription Retention History (29 s), 8 Retention report (37 s).
- Shared settings: none changed (`customer_actions.allow_cancellation` is still `false` in the DB; only the `fv_cf` cookie mu-plugin overrides it per request).
- Leftover helper: `wp-content/mu-plugins/zz-fv-cancellation-flow.php` (inert without the `fv_cf` cookie). Delete it together with the harness mu-plugin after all recordings.

## Demo subscriptions changed (all in block 617-677, all valid demo states, nothing to undo unless wanted)
| Sub | User | What happened | When (UTC) |
|---|---|---|---|
| 27748 | 632 Austin Singh | Discount accepted (55.20 for 2 renewals) | 21:40 |
| 27762 | 634 Jonas Jenkins | Discount accepted | 21:58 |
| 27813 | 640 Lily Nelson | Discount accepted (the one in the final video) | 22:12 |
| 27825 | 641 Sienna Ferrari | Discount accepted (scouting) | 21:00 |
| 27776 | 636 Adam Perry | Paused 30 days by offer, resume action 28810 | 21:47 |
| 27880 | 647 Anthony Hall | Paused 30 days by offer (the one in the final video) | 22:17 |
| 28087 | 671 Xavier Simmons | Paused 30 days by offer (scouting), resume action 28711 | 21:01 |
Undo per sub: paused ones -> Resume in admin (or let the scheduled resume run); discounted ones -> delete `_retention_discount_*`, `_retention_offer_*`, `_accepted_retention_offer`, `_retention_accepted_date` meta (the `_recurring_amount` meta was never changed). Retention log rows (`wp_arraysubs_retention_logs` ids 825+) and subscription notes are history and can stay.

---

## Revision 2 (2026-11-28, after the independent review): native 1080p, 9 chapters

All chapters were recorded again at native 1920x1080 (fixed harness, Playwright capture at DPR 4/3).
No chapter is kept from the 1440x810 takes.

### New storyboard

| # | Kicker | Card title | What is shown |
|---|---|---|---|
| intro | | Keep subscribers about to *leave* | One-line subline (no overlap with the corner circle) |
| 1 | Admin | One page for the whole *flow* | Journey strip, reasons + offers cards (scrolled clear of the caption), discount offer conditions (label below, nothing covered) |
| 2 | Flow builder | Ask why in your *own words* | Editor opened off camera (no repeat of chapter 1), 7 steps, require-reason switch, a reason row's drag/edit/delete, **adds the reason "Prefer one-off orders" on camera** (typed key + label), translate icon, step 2 offers on/off switch |
| 3 | Flow builder | Offers that fit the *reason* | Step 3 discount (reasons, 20%/2 renewals, conditions), step 4 pause, step 5 cheaper plan, step 6 support URL + headline (URL shown as https://example.com/help/), step 7 overview with the new reason, **real Save** (enabled), overview shows 9 reasons |
| 4 | My Account | They click *Cancel* | Customer A (Plus Membership $39/month) view page, gentler choices above the cancel link ("Cancel stays easy to find"), reason survey (end-of-period copy), "Too expensive" -> discount + Starter plan, Never trapped |
| 5 | My Account | One click *keeps* them | Accept from the current view (no extra zoom cycle), toast zoomed and readable (button shown at rest), recurring amount ring on the value only, email (caption bottom-right) |
| 6 | My Account | Need a break? *Pause* | Customer B opens straight on the offers window (no repeated survey), pause + cheaper plan, accept pause, Paused, next payment "Was <date>", scheduled resume |
| 7 | My Account | Or they still *leave* | Customer C: cancel window says it stays active until the period ends, "Other" + own words, Contact Support card, "No thanks, continue to cancel", Pending cancellation + Cancels On, customer undo button |
| 8 | Admin | The story on each *subscription* | A: discount stat + 2 renewals left + Retention History; C: Scheduled Cancellation panel (reason Other, their words, offers shown), admin **Undo Scheduled Cancellation** -> Active |
| 9 | Reports | Know why people *leave* | Retention report: cohort, voluntary/involuntary switched to Percent, Top 10 reasons, Activity Log filtered to Offer Accepted |
| outro | | Stop one-click *goodbyes* | Survey + undo free (both now shown), offers/targeting/report Pro |

Final: `$FV_OUT/cancellation-flow/cancellation-flow.mp4`, 295.8 s, 9 chapters, native 1920x1080 H.264 30 fps, 42.9 MB.
Segment lengths: 18.5 / 32.6 / 39.7 / 42.6 / 28.0 / 30.3 / 37.1 / 34.6 / 32.4 s (all checked with ffprobe: 1920,1080).
Poster: `poster.webp` (1920x1080) from the native chapter 4 frame: "Before You Go..." with the discount offer ringed.

### Review fixes (what changed against the critique)
- 1080p: every chapter recorded again with the fixed harness (no upscaled segment).
- Step 7: a real edit (new reason "Prefer one-off orders", typed on camera) enables Save; Save is ringed, clicked, and the overview then shows 9 reasons. No disabled control is ringed.
- Outro claim "survey and undo are free" is now shown: chapter 7 books a cancel for the period end (Other + own words, Contact Support card, "No thanks, continue to cancel", Pending cancellation, Cancels On, customer undo button), chapter 8 shows the saved reason/words and undoes it from admin, chapter 9 rings "Booked, then undone" in the Activity Log.
- Support URL: displayed as https://example.com/help/ (fv_cf override), and the ring covers URL + headline ("Your help page, your words").
- Cancel window copy: end-of-period ("remain active until the end of your current billing period") via the fv_cf override.
- Labels: "Cancel stays easy to find"; recurring-amount labels ring only the value text; "Was <date>" for the pause; chapter 8 "$31.20 instead of $39.00"; builder labels moved off info boxes; chapter 1 cards scrolled clear of the caption.
- Cursor: hidden before rings on selects, toasts, status rows and the Save button.
- Toast: zoomed (1.35x) and readable; the Accept button is shown at rest (its spinner markup restored from the button's saved HTML) while the toast is annotated.
- Email beat: caption bottom-right.
- Money: "$69.00"-style everywhere in this video (currency position forced left for fv_cf requests), labels use `$` first.
- Dev nav items "Checkout Classic" / "Cart Page" hidden for fv_cf requests.
- Report: scroll offsets -90 under the sticky header, labels below the cards, chart switched to Percent on camera before it is ringed.
- Pacing: builder opened inside v.act (no chapter 1 replay), accept from the current view (no zoom cycle), chapter 6 opens straight on the offers window; step 2 (offers on/off) now shown; zoom framing widened (cancel window 1.5x).
- Wording: "Hannah's subscription, kept.", "Mateo barely uses it: "Not using it enough"."
- Intro/card sublines kept to one line so they never reach the bottom-left circle (no harness change).
- "[ArraySubs] Retention Discount Accepted" timeline row is no longer on camera (the timeline beat was cut for length), so no clean-map entry was needed.

### Store changes in revision 2 (all logged, all in block 617-677 unless noted)

#### mu-plugin `wp-content/mu-plugins/zz-fv-cancellation-flow.php` (rewritten)
Still active only for requests with both `fv_clean=1` and `fv_cf=1` cookies (this scene sets `fv_cf` for its admin and customer sessions). Per-request display/runtime overrides, never persisted:
- `arraysubs_settings[customer_actions][allow_cancellation]` -> true (stored: false)
- `arraysubs_settings[cancellation][cancel_immediately]` -> false (stored: true), so customer cancels are booked for the period end
- `arraysubs_settings[cancellation][retention_offers][contact_support][support_url]` -> https://example.com/help/ (stored: http://localhost:10013/demo-member-support/)
- `option_woocommerce_currency_pos` -> left (stored: right)
- `render_block_core/navigation-link`: hides "Checkout Classic" and "Cart Page"
- `pre_update_option_arraysubs_settings` guard restores the stored value of all three overridden settings keys on any write in such a request (verified: after the on-camera saves, allow_cancellation=false, cancel_immediately=true, support_url unchanged).
Undo: delete the file.

#### Shared setting written on camera, then restored
- Chapter 3 saves the Cancellation Flow builder (adds reason `prefer_one_off` / "Prefer one-off orders"). Saved 3 times in total (recording passes 2026-11-28 ~05:50, ~05:56 and ~06:08 local). Window per pass: from the chapter 3 save until the run's teardown (a few minutes).
- Restore: `setup()` and `teardown()` remove that reason by key (raw DB write of `arraysubs_settings[cancellation][reasons]` only). After the final pass the whole `cancellation` group was compared with a backup taken before any save (`$FV_OUT/cancellation-flow/settings-backup-cancellation.json`): identical. Side effect: `arraysubs_settings_last_saved_at` moved from 1795820999 to 1795825230 (timestamp only).

#### Customers (revision 2)
| Sub | User | What happened | Kept in video |
|---|---|---|---|
| 27676 | 624 Hannah Rogers | Plus Membership: opened offers ("Too expensive"), accepted the 20% discount for 2 renewals ($39.00 -> $31.20), email in local Mailpit | yes (ch4, ch5, ch8, ch9) |
| 27996 | 660 Mateo Khalil | Plus Membership: "Not using it enough", accepted the 30-day pause; next payment 2026-12-20 -> 2027-01-19, resume scheduled 2026-12-28 | yes (ch6, ch9) |
| 27933 | 653 Stella Okonkwo | Starter: Other + "Moving abroad next month, will be back.", Contact Support shown, cancel booked for 2026-12-12, then undone by admin | no (first take of ch7/ch8, replaced) |
| 28076 | 670 Micah Barnes | Starter: same flow, booked for 2026-12-17, undone by admin on camera; now Active, renewing normally | yes (ch7, ch8, ch9) |
| 27723 | 630 Elias Pemberton | Scouting probe: Other + words, booked cancel, undone by admin (probe) | no |
- Undo for any of these (if ever needed): resume 27996 in admin (or let the scheduled resume run); for 27676 delete `_retention_discount_*`, `_retention_offer_*`, `_accepted_retention_offer`, `_retention_offer_accepted`, `_retention_accepted_date`. The booked cancels are already undone. Retention log rows and notes are history.
- Note: undoing a booked cancel after an offer was shown sets `_retention_offer_accepted` (Pro `CancellationFlow\Services\Hooks::onCancellationUndone`), so 27933, 28076 and 27723 will not see save offers again. The scene's candidate check now treats that meta as "used".
- Remaining fresh candidates for another take: A/B `27842`, `28034` (Plus); C `27632`, `27736`, `28109` (Starter).

Shared settings at the end (verified with WP-CLI): `customer_actions.allow_cancellation` false, `cancellation` group identical to the pre-recording backup, `woocommerce_currency_pos` right.
