# Audit logs video (slug `audit-logs`)

Scene: `scenes/audit-logs.mjs`. Output: `$FV_OUT/audit-logs/audit-logs.mp4` + `poster.webp`.
Tier on cards: **Free + Pro** (feature page `web-content/src/pages/features/audit-logs.astro`, catalog tier `free+pro`).

## Storyboard (final)

| # | Kicker | Chapter | What is shown | Tier |
|---|---|---|---|---|
| intro | | Audit logs | card | |
| 1 | Admin | Every subscription keeps its story | Subscription #31492 (Christopher Hamilton, Plus Membership): hero, Subscription Notes (System author, Customer/Private visibility), Payment Timeline panel | Free |
| 2 | Admin | Admin edits are written down | Edit Subscription -> billing address line 1/2 + postcode -> Save -> new admin note "changed from X to Y" | Free |
| 3 | Admin | Notes for the team, or the customer | Add note typed, "Note to customer" picked, note saved with Customer visibility | Free |
| 4 | My Account | The customer sees their side | Christopher (user 608) sees the customer note (zoomed) in My Account; edits his billing phone in Addresses; "Address changed successfully" | Free |
| 5 | Audit Logs | One trail for the whole store | Activities: filter bar; Author = Customer + search "612 555 0147"; Meta badges (zoom); Changes modal (phone old/new) | Pro |
| 6 | Audit Logs | Old and new, side by side | Author = Admin + Entity = Subscription + search "98 Spring Court" -> Changes (3 fields). Reset, Entity = Settings + search allow_cancellation -> Changes (Yes/No). Poster frame here. | Pro |
| 7 | Audit Logs | Which button failed, and why | Portal Errors: Valentina Powell's real failed Skip Renewal (#27045), View (error, message, suggested resolution, request, context), Resolve (confirm dialog), toast, Activities search "marked resolved" | Pro |
| 8 | Audit Logs | Proof the renewals ran | Jobs: Skipped row for #31492 ("Ran before the invoice was due... Re-queued for ..."), date filter 16 Nov 2026 -> Process Renewal and Send Renewal Reminder rows | Pro |
| 9 | Settings | Keep the log useful | Activity logging options popover (Product/Coupon/Email/Settings), Logging Settings: job logging mode dropdown (every / failures only / off) and keep-days field | Pro |
| outro | | Stop guessing. Start knowing. | card | |

Left out on purpose: Renewal Errors (owner does not want that screen on the site), Access-Rule Conflicts (not sold as free here),
Gateway health (local gateways show 0 enabled / 0 ready), Webhooks audit (endpoint list contains a QA endpoint named "test"),
Home dashboard Activity/Job cards (their "latest three" list is live and showed another agent's "QA Black Friday Offer" product).

## Store changes (all in customer block 556-616 unless noted)

Baselines: newest note on subscription #31492 before the video = note ID 31509. Customer 608 billing_phone = `+1 202 555 0181`.
Subscription #31492 `_billing_address` original JSON:
`{"first_name":"Christopher","last_name":"Hamilton","company":"","address_1":"98 Spring Court","address_2":"","city":"Minneapolis","state":"MN","postcode":"55401","country":"US","email":"christopher.hamilton@example.com","phone":"+1 202 555 0181"}`

1. **Scouting test (reverted):** customer 608 changed billing phone in My Account (note 32119), reverted by WP-CLI (note 32120); both notes deleted.
2. **Skipped job row:** ran Action Scheduler action 25683 (`arraysubs_generate_renewal_invoice`, args [31492], due 2026-12-22 01:09:38 UTC) early with `wp action-scheduler action run 25683`. Core created nothing and re-queued the leg at the same time (new action 28729). Pro logged job note 32124 (status skipped). Chapter 8 `before` repeats this (runs the pending invoice leg of #31492) when the newest job row is not already ours. Each run adds one Skipped job note on #31492 and re-queues the leg at the same due time. Undo: delete those `arraysubs_sub_note` posts (`_job_status = skipped`, `_subscription_id = 31492`); nothing else to undo.
3. **Portal error row:** as customer 565 (Valentina Powell) sent `POST /arraysubs/v1/my-subscriptions/27045/skip` (cycles 1) through `rest_do_request`; it failed for real with "Cannot skip within 2 days of renewal date." (next payment 2026-11-29). Pro logged portal failure note 32126. Chapter 7 resolves it on camera (sets `_portal_action_status = resolved` + adds an admin activity note "Portal action failure marked resolved by admin: Skip Renewal (400)."). Chapter 7 `before` reopens it and deletes the earlier resolve note. Undo: delete note 32126 and any resolve note on #27045.
4. **Recording state changes (chapters 2-4, reset by chapter 1 `before`):**
   - Subscription #31492 billing address -> `2150 Lakeview Drive`, `Apt 4B`, postcode `55408` (admin edit through the UI; adds an admin note with audit changes).
   - Note to customer on #31492: "We updated your billing address to 2150 Lakeview Drive. Thanks for letting us know!"
   - Customer 608 billing phone -> `+1 612 555 0147` via My Account > Addresses (adds a Member note authored by 608).
   - Reset (chapter 1 `before`, `resetSubscriptionStory()` in the scene): deletes notes on #31492 with ID > 31509 and notes `Member #608 ...` with ID > 32000, restores `_billing_address` JSON above, restores `billing_phone` by direct usermeta update (no audit hook).
5. **Audit cache:** chapters call `ArraySubs\Supports\AuditDataCache::invalidate()` (bumps `arraysubs_audit_cache_generation`). Only forces fresh reads; harmless.
6. **Display-only:** `clean-map.d/audit-logs.json` renames old QA customers on the Portal Errors list (demo.260927.188/.202 emails, "RQA 2112 Entitled"). Chapter 7 hides, in the recording browser only, the two old QA rows for a logged-out request on subscription #1 (CSS in the frame).

No shared settings were changed.

## Incidents during recording

- Pass 1 (21:10-21:12 UTC) overlapped the window when ArraySubs Pro was deactivated in the DB by an outside session
  (21:07:48-~21:12 UTC). Pass 1 was discarded. The mu-plugin now forces Pro on for the recording browser and for
  `FV_PRO=1` WP-CLI calls; this scene only uses `wp()` from lib/director.mjs, which sets it.
- A duplicate instance of this agent (spawned by mistake ~21:11 UTC, stopped ~21:21 UTC) ran
  `run.mjs audit-logs --chapters=2..9` in parallel. It created admin note 32154 (billing address edit) and customer
  note 32156 on #31492 and the same billing address change. Chapter 1's `before` reset removes them (notes > 31509,
  `_billing_address` restored). It did not reach the customer phone change, Portal Errors or Jobs.
- Pass 2 recorded all 9 chapters from a clean reset.

## Final store state after recording (2026-11-28 ~05:35 local, final native-1080p pass)

Shared settings: none changed (Activity logging options and Logging Settings were only opened, never saved;
`audits.job_log_mode` = all, job log retention = 30 days, unchanged).

Records left by the final take (all in customer block 556-616 or display-only):
- #31492 (customer 608): billing address = 2150 Lakeview Drive / Apt 4B / 55408 (chapter 2 admin edit, note 32516),
  customer note 32529 ("We updated your billing address..."), Skipped job note 32560 (invoice leg run early in chapter 8
  `before`; leg re-queued at the same due time 2026-12-22 01:09:38 UTC, pending action 29096).
  Undo: `resetSubscriptionStory()` in the scene (deletes notes > 31509 on #31492, restores the `_billing_address` JSON above).
- Customer 608: billing phone = +1 612 555 0147 (chapter 4 My Account edit), member note 32530.
  Undo: `resetCustomerPhone()` in the scene (direct usermeta update back to +1 202 555 0181, deletes the member note).
- #27045 (customer 565): portal failure note 32126 resolved on camera, admin resolve note 32553.
  Undo: `reopenPortalError()` in the scene, or delete notes 32126 and 32553.
- These are left in place on purpose (they are realistic history). Run the three helpers above to undo all of them.

Harness note: lib/director.mjs changed mid-session (by the lead) to capture at device scale (native 1920x1080).
Earlier takes were 1440x810. The final video was fully re-recorded at native 1080p, so it has no upscaled segments.

Scene file and clean map stay: `scenes/audit-logs.mjs`, `clean-map.d/audit-logs.json`.

## Revision 2: reviewer critique fixes (2026-11-28, ~06:00-06:50 local)

All 9 chapters re-recorded at native 1920x1080 (pass 10 = all chapters; pass 11 = chapters 4-9 with
trims; passes 12-13 = chapter 4 framing). Final: `$FV_OUT/audit-logs/audit-logs.mp4`, 297.4 s, every
segment 1920x1080 (checked with ffprobe). Poster = chapter 6 Change details modal (three fields, old/new).

What changed in the scene:
- Intro sub shortened ("Who changed what, when, and what it was before."), intro hold 3.2 s.
- Card subs shortened to one line (ch3, ch4, ch5, ch6); ch6 title is now "Before and *after*" (no orphan word).
- Story contradiction fixed: ch2 caption is "An admin corrects Christopher's billing address." and ch4 `before`
  sets customer 608's account billing address to 2150 Lakeview Drive / Apt 4B / 55408 by direct usermeta write
  (no audit entry), so the My Account Addresses screen matches the corrected subscription address.
  `resetSubscriptionStory()` restores 98 Spring Court / '' / 55401.
- ch3 note text shortened ("... 2150 Lakeview Drive. Thanks!"); "Visible to Christopher" label now sits beside the
  green Customer badge.
- ch4: My Account notes zoom (2x, left aligned, bottom edge under the third newest note) keeps the older
  "Changed by Gateway (Bacs)" note off camera; storefront header items "Checkout Classic" and "Cart Page" hidden in
  the recording browser only (scene `setup` init script, CSS on `/checkout/` and `/cart-2/` nav items).
- ch5: caption "Show only what customers changed." with the Author select ringed ("System, Admin, Customer or Gateway"),
  then "Then search the new number."
- ch6: Entity select ringed ("8 record types"); settings part shows the row's author and date as a label
  (read live from the row) before the modal; modal caption built from the modal values
  ("Customer cancellations: Yes before, No after.").
- ch7: caption "Valentina tried to skip a renewal too close to its due date."; Activities search narrowed to
  "resolved by admin: Skip Renewal" (only the on-camera row returns); new ending: click the row's
  "Subscription #27045" link -> subscription notes, ring on the "Email sent: [ArraySubs] Renewal Invoice" note.
- ch8: old ring cleared before "Pick a date"; zoom 1.7x on the From/To fields, both dates typed visibly
  (16112026), ring "Only 16 Nov 2026", then the day's Process Renewal / Send Renewal Reminder rows.
  The table loader is hidden (opacity) only while typing under the zoom.
- ch9: dropdown zoomed 1.5x with "Log failures only" hovered and ringed ("Fewer rows on busy days"); retention ring
  label moved left ("0 to 365 days, 0 = forever"). Nothing selected or saved.
- clean-map.d/audit-logs.json: display-only "role demo_plus." -> "role plus_member.", "role demo_member." -> "role member."

Store changes in this revision (customer block 556-616 only; no shared settings changed;
`audits.job_log_mode` = all and retention = 30 days, verified after recording):
- #31492 (customer 608): admin edit note 32769, customer note 32771, Skipped job notes 32795 and 32812 (invoice leg run
  early twice; pending leg re-queued at the same due time, now action 29268 at 2026-12-22 01:09:38 UTC).
  `_billing_address` = 2150 Lakeview Drive / Apt 4B / 55408.
- Customer 608: account billing_address_1/2/postcode = 2150 Lakeview Drive / Apt 4B / 55408 (direct usermeta),
  billing_phone = +1 612 555 0147 (My Account edit, member note 32855).
- #27045 (customer 565): portal failure note 32126 resolved on camera, resolve note 32803.
- Undo everything: `resetSubscriptionStory()` (also restores the account address and phone) and `reopenPortalError()`
  from the scene (both are exported), then delete note 32803 if the row should stay resolved-free; delete the
  Skipped job notes on #31492 if unwanted (nothing else depends on them).
