---
id: 130
title: Fix all medium- and low-prefixed lifecycle QA issues
status: done
priority: medium
created: 2026-08-14T20:27:15.851062757+02:00
updated: 2026-08-14T22:50:44.238483639+02:00
started: 2026-08-14T22:50:44.238482387+02:00
completed: 2026-08-14T22:50:44.238482387+02:00
tags:
    - plugin
    - bugfix
    - maintenance
claimed_by: lotus-tide
claimed_at: 2026-08-14T22:50:44.238483529+02:00
class: standard
---

Scope: investigate, plan, implement, security-review, and live browser-retest every unresolved medium-* and low-* report in issues/.

Protocol: process reports one by one; verify each against its originating task, plan, live data, and owning code in arraysubs/arraysubspro; add regression coverage where supported; skip PHPCS/lint per workspace issue-fix workflow; rename each report with done- only after passing; preserve unrelated worktree state.

Initial inventory: 9 medium reports and 5 low reports (14 total).

[[2026-08-14]] Fri 20:30
Plan — medium SLT-ADM-01 product-title search: reproduce against product 11927/subscription 12760; extend the existing sanitized admin REST search to resolve matching product/product-variation IDs and combine their subscription matches with ID/customer matches without weakening gateway/status constraints; verify exact product title, customer email, numeric ID, unrelated term, and filtered search in the live admin.

Plan — low SLT-ADM-01 empty-state copy: use the DataList-provided search/status/filter context so the first-run onboarding appears only for the unfiltered all-state; render a neutral translatable no-match state for any active search/filter/status; verify Trial(0), impossible search, and the restored unfiltered list.

[[2026-08-14]] Fri 20:34
DONE medium SLT-ADM-01 product-title search. Confirmed live false-negative; core REST search now includes product/variation titles with bounded, sanitized queries and preserves status/gateway intersections. WP REST checks covered product/email/ID/no-match; live browser rendered 33 matches including #12760. Evidence: FIX-MEDIUM-SLT-ADM-01-before.png / after.png. Report renamed done-medium-*.

[[2026-08-14]] Fri 20:36
DONE low SLT-ADM-01 empty-state copy. Confirmed on populated 403-subscription site. Filter/search-aware neutral state implemented; first-run onboarding remains only for unfiltered All with zero rows. Production build passed; live no-match and Trial(0) checks passed, All restored table, no page errors. Report renamed done-low-*.

[[2026-08-14]] Fri 20:40
Plan — medium SLT-ADM-08 cancellation actor: verify persisted actor and historical mail/note; make actor resolution explicit for system/customer/admin/gateway, use it in the admin cancellation subject/body, and stop AutoNotes from falling back to the current administrator when _cancelled_by=system. Test with an isolated direct SLT fixture, Mailpit delta, customer-visible note, and exact cleanup.

Plan — medium SLT-ADM-08 prorated action: determine whether the earlier canonical order-link/refund work already removes the legacy-key dependency; inspect the original record and a currently active SLT paid subscription; open and close the live preview without processing; only add code if the action remains absent.

[[2026-08-14]] Fri 20:42
DONE medium SLT-ADM-08 prorated action. Original finding valid but canonical order-link/refund repair already removed the legacy-key dependency. Historical #13593 resolves to #12234 without backfill. Live active SLT #13277 rendered action and $25.25 preview; modal dismissed, status/refund history unchanged, no errors. Report renamed done-medium-*.

[[2026-08-14]] Fri 20:45
DONE medium SLT-ADM-08 actor attribution. Shared sanitized actor resolver added; AutoNotes honors stored system before request user; admin email subject/body now use cancellation_actor. Existing system/admin/customer mappings passed. Disposable #26653 produced System note and Mailpit 6PjD29mDKopj2lhPnHbzsT, then exact post/notes/action cleanup verified zero. Report renamed done-medium-*.

[[2026-08-14]] Fri 20:46
Plan — medium SLT-EML-12: preserve wc_price HTML for email bodies, but enforce an RFC-safe plain-text boundary in BaseSubscriptionEmail::get_subject() after all placeholders and filters have resolved. Decode entities, strip tags and line breaks, and test the exact saved-template string against #12786 plus a live Mailpit send under an exact option/status restoration bracket.

[[2026-08-14]] Fri 20:50
DONE medium SLT-EML-12 subject HTML. Base email subject boundary now decodes entities and strips tags/line breaks while body wc_price HTML remains. Exact #12786 render and CRLF probe passed. Browser activation of disposable #26663 produced plain Mailpit subject 4Oqu8vRlr4orfQId9DnH4y; option/post/notes/actions restored and verified absent. Report renamed done-medium-*.

[[2026-08-14]] Fri 20:55

### Plan — medium SLT-IMP-05
Reproduce all five My Account routes as the original customer using link-driven navigation, clearing the browser error buffer between routes; inspect active theme/plugin/core ownership of view-transition code; avoid masking browser exceptions in ArraySubs. Close as a corrected non-product/transient finding only if the routes, network, and current error buffers are clean and neither product owns the API call.

[[2026-08-14]] Fri 20:56

### Result — medium SLT-IMP-05
Closed as a historical transient/platform finding with no product patch. Same customer and all five authored routes now render through link navigation with an empty per-route error buffer; cache-busted dashboard load is also clean; captured requests contain no 4xx/5xx. Neither ArraySubs plugin nor Twenty Twenty-Five owns startViewTransition; only WordPress core router bundles match. Avoided a global rejection suppressor because it would mask real failures. Report renamed done-medium-SLT-IMP-05-my-account-routes-emit-view-transition-aborterror.md.

[[2026-08-14]] Fri 20:57

### Plan — medium SLT-LIFE-03
Capture the exact live skip_renewal option, reproduce the same-save disable failure inside a reversible browser bracket, and inspect FormBuilder/unmounted-field semantics plus the REST merge path. Fix the settings submit boundary so preserved conditional values are included without weakening server sanitization/capability/nonce checks; use the shared loading button while touching the AJAX action. Build, repeat the one-save restore path, verify exact stored values, then restore the original option byte-for-byte.

[[2026-08-14]] Fri 21:17

### Result — medium SLT-LIFE-03
Confirmed the failure before editing: cutoff 2 disappeared from the request after its parent unmounted, so storage stayed 0. Fixed SkipPauseSettings to submit only changed hidden values from this page plus mounted values, merge the post-save baseline, and use SpinnerButton. Fresh-session browser retest sent enabled=false plus hidden cutoff_days=2 in one save; unrelated defaults and unchanged pause children stayed out. npm build passed; browser errors empty; option restored byte-identically (MD5 a048a48ef244ecc35ff965497fb295fb, 12906 bytes); exact test audit notes removed with zero post/meta residue. Report renamed done-medium-SLT-LIFE-03-disabled-save-leaves-hidden-cutoff.md.

[[2026-08-14]] Fri 21:18

### Plan — medium SLT-SYN-01
Compare the lifecycle report against the current Stage 21 Flexible Renewal Sync contract, inspect simple/variation and box/bundle UI implementations, and verify both affected products live without saving. If disabled rows are intentional re-enable controls and current authoritative QA expects an Off row, correct the stale lifecycle oracle instead of hiding the only recovery control; verify active schedule ranges, handles, reactivation, meta invariance, and browser errors.

[[2026-08-14]] Fri 21:23
Result — medium SLT-SYN-01: Closed as a verified false positive after correcting the stale origin-task oracle. Stage 21 requires inactive segments to remain as visible `Off` controls while being excluded from the colored partition and slider count. Live checks on products 12099 and 12102 confirmed correct 2/1 active-range behavior, immediate reactivation through the retained controls, unchanged metadata after the read-only test, and no browser errors. No product code changed. Report renamed `done-medium-SLT-SYN-01-disabled-segments-remain-visible.md`.

[[2026-08-14]] Fri 21:30
Plan — medium SLT-SYN-09: treat this as a confirmed QA-oracle timing defect, not a product runtime defect. Preserve the persisted weekly boundary and spread-offset contract. Amend the D12 watch row so SUB_W1/SUB_W are explicitly pending carry-forwards rather than false D12 successes, require late-D12 invoice and D13 charge reconciliation, and keep the existing task-119 teardown STOP gate. Validate against subscription 12039, prior renewal 13170, current order/action relationship, live admin date display, browser diagnostics, Mailpit baseline, and zero data mutation.

[[2026-08-14]] Fri 21:32
Result — medium SLT-SYN-09: Confirmed and fixed the D12 QA timing oracle. Live subscription 12039, prior cycle 13170, current pending cycle 26536, admin date rendering, and action pair 21879/21880 all prove the persisted 2026-08-14 18:00Z boundary charges on D13 at 05:01:52 site. The watch schedule now carries weekly rows through late-D12 invoice/D13 charge and cannot score D12 silence as failure; existing calendar/task-119 teardown STOP gates align. Browser errors were empty and no live data was mutated. Report renamed `done-medium-SLT-SYN-09-d12-watch-expects-sub-w1-one-day-early.md`.

[[2026-08-14]] Fri 21:33
Plan — medium SLT-SYN-12: confirm the task-88 source absence across product, order, subscription, action, registry, and storefront; inspect core/pro gateway-gating paths to exclude a hidden runtime failure. Then make task 88 part of the authoritative source-outcome overlays and condition every downstream D10 watch/calendar and D13 teardown reference on numeric registry fixtures. Test the corrected documents against the live zero-source state and a real relationship-resolved renewal counterexample; no late fixture or product-code change.

[[2026-08-14]] Fri 21:35
Result — medium SLT-SYN-12: Confirmed a QA dependency defect, not a runtime failure. Product/order/subscription/action queries are all zero, the authored storefront URL is a clean 404, and task 88 explicitly never started the fixture. Added task 88 to both source overlays; made D7 reminder, D10 renewal, calendar, and task-119 teardown consumers numeric-fixture conditional. Core/pro gateway-gating code was inspected but remains honestly UNVERIFIED for this absent scenario. No live state changed. Report renamed `done-medium-SLT-SYN-12-d10-watch-expects-missing-source.md`.

[[2026-08-14]] Fri 21:36
Plan — low SLT-ADM-02 empty shipping address: keep REST/subscription address data untouched because the creation form intentionally seeds editable account-name fallbacks. Fix only the shared core detail renderer: treat a shipping address as present only when a postal field exists, compose non-empty lines without unconditional punctuation, and let React escape values. Rebuild assets; browser-test 12760 for the empty-state and 11959 as the complete-address counterexample; verify endpoint/data hashes and browser errors remain unchanged.

[[2026-08-14]] Fri 21:40
Result — low SLT-ADM-02 shipping address: Confirmed the detail renderer selected a name-only JSON object and emitted an unconditional comma. Core now requires postal content and composes escaped non-empty address lines. Build passed; fresh live assets show the empty state on 12760 and preserve the full address on 11959 with zero browser errors or data mutation. Report renamed `done-low-SLT-ADM-02-empty-shipping-address-renders-stray-comma.md`.

[[2026-08-14]] Fri 21:42
Plan — low SLT-ADM-02 singular billing schedule: keep raw interval/period fields for API consumers, add a server-computed translated display field using the existing `arraysubs_get_duration_string()` plural rules, and render that field in the core detail UI instead of appending `(s)`. This centralizes pluralization and avoids trusting/rendering an ad-hoc label. Rebuild; test both reported day/1 subscriptions plus a live plural interval and lifetime control, detail REST status/payload, browser errors, and unchanged records.

[[2026-08-14]] Fri 21:45
Result — low SLT-ADM-02 billing schedule: Confirmed raw day/1 data was correct and only the detail JSX emitted `(s)`. Detail REST now derives an escaped translated display string from the shared duration plural helper; raw API fields remain intact. Build passed and fresh browser/REST checks passed day/1, day/3, and lifetime cases with zero errors or mutation. Report renamed `done-low-SLT-ADM-02-singular-billing-schedule-uses-day-s.md`.

[[2026-08-14]] Fri 22:12
Plan — low SLT-CHK-02 classic shipping snapshot: treat the checkout order as the authoritative address source. When a classic virtual order has no shipping fields, fall back only to the authenticated order customer's saved shipping profile; never synthesize shipping from billing, and persist only when address_1 or address_2 is non-empty. Keep this in core SubscriptionCreationTrait so block/classic and Pro consumers share one snapshot contract; do not alter Pro shipping flags, renewal totals, order linkage, or existing records. Reproduce historical 11991/11990 first, then run a disposable real classic checkout with a complete saved shipping profile, prove the source order remains shipping-empty while the new subscription stores the exact profile snapshot and renders it in admin, inspect block and physical controls, and remove the exact test user/order/subscription/actions. PHP-only change; no build required and PHPCS/lint remain skipped per instruction.

[[2026-08-14]] Fri 22:45
Result — low SLT-CHK-02 classic shipping snapshot: confirmed genuine Woo classic/store-api virtual-order difference; core now preserves order shipping and falls back only to an authenticated customer saved shipping profile with a street line. Live classic BACS checkout order 26774 / subscription 26775 proved an empty-shipping classic order produces exactly one matching subscription snapshot and a complete admin Shipping Address; browser errors empty; block control 11959 unchanged. Exact test records deleted, zero matching actions, counts restored 403/728, cart empty. Report closed and renamed done-low-SLT-CHK-02-classic-checkout-omits-shipping-address-meta.md; task 002 linked to resolution. PHP-only; no build; lint/PHPCS skipped as instructed.

[[2026-08-14]] Fri 22:50
Final audit — COMPLETE: all 14 initial reports are now done-prefixed (9 medium, 5 low); zero unresolved medium-* or low-* filenames remain. Eight required product fixes, one prorated-refund report was already remediated by the canonical order-link repair and passed live verification, and five findings were proven transient/vendor/QA-oracle false positives with their authoritative docs corrected where needed. Core/pro ownership, callers, REST/security boundaries, scheduler behavior, and saved-option/data isolation were reviewed. Relevant production builds passed; live browser, REST/WP-CLI, Mailpit, and scheduler checks are recorded per report. Disposable records/options/carts/browser sessions were restored or removed. git diff --check is clean. PHPCS/lint intentionally skipped per instructions; no commit or push performed; unrelated deleted done-SLT-SYN-04 and untracked issue-fix-prompt.txt preserved.
