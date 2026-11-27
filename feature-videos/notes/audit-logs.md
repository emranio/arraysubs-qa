# Audit logs video (slug `audit-logs`)

Scene: `scenes/audit-logs.mjs`. Output: `$FV_OUT/audit-logs/audit-logs.mp4` + `poster.webp`.
Tier on cards: **Free + Pro** (feature page `web-content/src/pages/features/audit-logs.astro`, catalog tier `free+pro`).

## Storyboard

| # | Kicker | Chapter | What is shown | Tier |
|---|---|---|---|---|
| intro | | Audit logs | card | |
| 1 | Admin | Every subscription keeps its story | Subscription #31492 (Christopher Hamilton, Plus Membership): hero, Subscription Notes with System badge + Customer/Private visibility, Payment Timeline | Free |
| 2 | Admin | Admin edits are written down | Edit Subscription -> billing address line 1/2 + postcode -> Save -> new admin note "changed from X to Y" | Free |
| 3 | Admin | Notes for the team, or the customer | Add note, type "Note to customer", note appears with Customer visibility | Free |
| 4 | My Account | The customer sees their side | Christopher (user 608) sees the customer note in My Account; then edits his billing phone in Addresses | Free |
| 5 | Audit Logs | One trail for the whole store | Activities: filter bar, Author = Customer + search "phone", Meta badges, Changes modal (phone old/new) | Pro |
| 6 | Audit Logs | Old and new, side by side | Author = Admin + Entity = Subscription -> address edit Changes (3 fields). Reset, Entity = Settings + search allow_cancellation -> Yes/No Changes | Pro |
| 7 | Audit Logs | Which button failed, and why | Portal Errors: Valentina Powell's failed Skip Renewal, View (message, suggested resolution, request, context), Resolve (confirm), Activities search "marked resolved" | Pro |
| 8 | Audit Logs | Proof the renewals ran | Jobs: Skipped row for #31492 ("Ran before the invoice was due... Re-queued"), date filter 16 Nov 2026 with Process Renewal / Send Renewal Reminder rows | Pro |
| 9 | Settings | Keep the log useful | Activity logging options popover, Logging Settings (job logging mode dropdown, keep-days), Home dashboard Activity Audit Trail + Scheduled Job Logs cards | Pro |
| outro | | Stop guessing. Start knowing. | card | |

Left out on purpose: Renewal Errors (owner does not want that screen on the site), Access-Rule Conflicts (not sold as free here),
Gateway health (local gateways show 0 enabled / 0 ready), Webhooks audit (endpoint list contains a QA endpoint named "test").

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
