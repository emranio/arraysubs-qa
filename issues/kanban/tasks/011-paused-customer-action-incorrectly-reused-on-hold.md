---
id: 11
title: Paused customer action incorrectly reused On Hold and settings were split across tabs
status: closed
priority: critical
created: 2026-08-16T13:58:30.730296+06:00
updated: 2026-08-16T15:00:39.168409+06:00
started: 2026-08-16T13:58:34.430076+06:00
completed: 2026-08-16T15:00:39.168408+06:00
tags:
    - bug
    - stage-02
    - stage-07
    - stage-08
    - stage-09
    - stage-16
    - stage-18
    - pause-resume
    - member-access
    - rest
class: standard
---

QA progress task: #12 (stage-7: First-class Paused lifecycle, settings migration, REST and My Account production QA). QA plans: qa/stages/02-settings/01-general-settings-each-section.md; qa/stages/07-customer-portal/07-pause-and-resume.md; qa/stages/07-customer-portal/11-action-availability-by-status.md; qa/stages/08-retention/03-pause-offer.md; qa/stages/09-member-access/10-pause-state-access-behavior.md; qa/stages/16-analytics/; qa/stages/18-renewal-followup/10-skip-and-pause-over-renewal-cycles.md. Affected subscription/order IDs: N/A before fresh verification purchase. Affected WordPress users: admin (administrator); fresh customer ID/login/email will be appended. Exact routes: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general, #/settings/skip-pause, #/subscriptions and the fresh customer's /my-account/subscriptions/ detail route. Browser contexts: isolated admin and customer agent-browser sessions. Reproduction: enable customer pause, buy an active subscription, pause it from My Account, then inspect admin/portal status, REST payloads, renewal actions, analytics, member conditions, Gutenberg and Elementor status controls. Expected: pause transitions to the distinct arraysubs-paused / Paused state; On Hold remains exclusively a payment/admin hold; Allow Pause and Allow Resume live under Skip & Pause; Members Access none/limited/full policy and explicit Paused status are consistent everywhere. Actual before fix: pause reused arraysubs-on-hold / On Hold; Customer Actions exposed Allow Suspension and Allow Reactivation; condition builders and downstream consumers could not distinguish a vacation pause from dunning/admin hold. Proof: user-provided Customer Actions screenshot and the pre-fix stage-07/stage-18 QA expectations both identified On Hold as the pause state. Known scope/counterexample: genuine failed-payment/admin holds must remain arraysubs-on-hold and must not gain Resume controls or paused access semantics.

[[2026-08-16]] Sun 15:00

Fix and verification evidence (2026-08-16):
- Affected entities: customer ID 11, paused.productionqa / paused-production-qa-20260816@example.test (customer); order #1267; subscription #1268; administrator admin. No other persistent test user remains.
- Reproduced and fixed on http://localhost:10013 using isolated admin, customer, guest, and wrong-owner agent-browser sessions. Customer routes: /my-account/subscriptions/ and /my-account/view-subscription/1268/. Admin routes: /wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general, #/settings/skip-pause, #/subscriptions, #/subscriptions/1268, Member Access Role Mapping, WooCommerce Churn Analysis, and Revenue Forecast. Builder routes: the real Gutenberg editor and Elementor editor.
- Result: pause now performs Trial/Active -> arraysubs-paused and renders Paused everywhere. Resume is the only supported exit and restores the recorded pre-pause status. The generic status REST endpoint rejects entering or leaving Paused. During pause there are no renewal charge actions; resume shifts dates by exact elapsed pause time and durably restores one invoice and one payment action. Remote billing is paused/resumed through provider filters, with Paddle implemented remotely and Stripe/PayPal/Mollie/manual remaining intentionally local-only; provider payment callbacks reject Paused subscriptions.
- Settings result: Allow Suspension is removed. Allow Reactivation was not relabeled in place; terminal reactivation remains a separate internal lifecycle and is disabled by default. The customer pause controls now live together as Allow Customers to Pause and Allow Resume in Skip & Pause, with a linking notice from Customer Actions and parent-feature conditional visibility. Legacy portal/customer-action values migrate once into pause_subscription, modern values win, and obsolete storage is removed.
- Members Access result: shared scoped status evaluation now applies None/Limited/Full pause policy consistently to content, roles, discounts, downloads, ecommerce, comments, sessions, schedules, and integrations. Explicit Paused conditions remain available regardless of implicit access mode. Gutenberg, Elementor, Role Mapping, and the shared restriction builder all expose distinct Paused and On Hold choices.
- Analytics/filter result: Paused is a first-class registered status across CPT counts, lists, exports, condition builders, member/customer/admin views, lifecycle filters, integrations, and churn analytics. Forecast/billing MRR excludes Paused; live churn population includes and labels it. On Hold remains a yellow payment/admin-risk state and never gains resume or pause-access semantics.
- Concrete proof: first 5-day and second 1-day browser pauses both displayed Paused; My Account showed Resume and no retry/early-renew/plan-switch/auto-renew actions; admin Paused count became 1 while On Hold stayed 0. A genuine Trial -> On Hold counterexample showed On Hold, is_paused=false, and no Resume. REST authorization returned 409 for generic paused transitions, 403 for disabled pause/resume and wrong ownership, and 401 for guests. During pause churn showed paused=1/on_hold=0 and forecast 608.8 MRR/2 billing subscriptions; after resume paused=0/on_hold=0 and forecast 913.2 MRR/3 subscriptions.
- Final steady state: #1268 is arraysubs-trial with next payment 2026-08-18 08:25:41 UTC, pause count 2, live pause metadata cleared, pause/resume enabled, access mode None, cooldown 30, no portal/allow_suspension/allow_reactivation keys, and Elementor inactive. Core/pro builds, PHP syntax checks, cross-repo diff checks, and a fresh error-free admin browser-console pass all succeeded.
- Known scope/counterexample retained: payment failure or administrator hold remains arraysubs-on-hold, stays charge-recovery oriented, is counted separately in analytics, and cannot use Resume or paused Members Access policy.
