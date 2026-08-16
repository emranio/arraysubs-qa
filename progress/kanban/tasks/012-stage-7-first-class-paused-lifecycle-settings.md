---
id: 12
title: 'stage-7: First-class Paused lifecycle, settings migration, REST and My Account production QA'
status: closed
priority: critical
created: 2026-08-16T13:58:17.707151+06:00
updated: 2026-08-16T15:00:42.225621+06:00
started: 2026-08-16T13:58:17.719039+06:00
completed: 2026-08-16T15:00:42.225621+06:00
tags:
    - stage-02
    - stage-07
    - stage-08
    - stage-09
    - stage-16
    - stage-18
    - pause-resume
    - member-access
    - rest
    - qa
class: standard
---

QA plan: qa/stages/02-settings/01-general-settings-each-section.md; qa/stages/07-customer-portal/07-pause-and-resume.md; qa/stages/07-customer-portal/11-action-availability-by-status.md; qa/stages/08-retention/03-pause-offer.md; qa/stages/09-member-access/10-pause-state-access-behavior.md; qa/stages/16-analytics/; qa/stages/18-renewal-followup/10-skip-and-pause-over-renewal-cycles.md. Scope: purchase a fresh subscription; migrate pause/resume controls; verify first-class Paused versus On Hold across settings, customer REST, My Account, scheduling, member access, Gutenberg/Elementor, analytics and filters; run a second independent customer-portal pass. Target: http://localhost:10013. Evidence and IDs will be appended during execution.

[[2026-08-16]] Sun 15:00

Completed production QA evidence (2026-08-16):
- Fresh purchase: WordPress customer ID 11, login paused.productionqa, email paused-production-qa-20260816@example.test, role customer; completed order #1267; subscription #1268 for product #13. Exact customer routes: http://localhost:10013/my-account/subscriptions/ and http://localhost:10013/my-account/view-subscription/1268/. Admin routes: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general, #/settings/skip-pause, #/subscriptions, and #/subscriptions/1268.
- First browser pass: Trial -> Paused for a scheduled 5-day pause. My Account and admin both rendered Paused, while only Resume Now (plus the independently allowed Cancel action) remained available. Renewal invoice/payment actions were absent during pause. Early resume restored Trial, shifted the next-payment/end schedule by actual elapsed seconds rather than the requested 5 days, cleared live pause meta, and recreated exactly one invoice-generation and one renewal-processing action.
- Second independent browser pass: pause modal enforced min=1, max=30, default=30; a 1-day pause again produced Paused and only the dedicated resume lifecycle. The two-dialog customer resume UI completed successfully, restored Trial, cleared live pause meta, and restored the two renewal-chain actions. Final next payment is 2026-08-18 08:25:41 UTC; total pause count is 2.
- REST/auth: generic status Paused -> Active was rejected with HTTP 409 paused_transition_requires_resume. Customer resume disabled returned HTTP 403 resume_disabled and hid the button; customer pause disabled returned HTTP 403 pause_disabled and hid the button. Guest pause/resume returned 401; a logged-in non-owner returned 403. The permission settings were restored enabled.
- On Hold counterexample: generic Trial -> On Hold succeeded, is_paused remained false, My Account rendered On Hold without Resume, and restoration to Trial recreated exactly the two normal renewal actions. This proves payment/admin On Hold remains separate from vacation Paused.
- Settings/migration: Customer Actions contains only Allow Cancellation and Allow Early Renew plus a direct Skip & Pause settings link. Skip & Pause contains Allow Customers to Pause and Allow Resume, both conditionally hidden with the parent pause feature. Legacy customer_actions/portal migration tests preserved modern precedence, moved suspension/reactivation values, mapped portal cancellation, persisted the result, and removed portal, allow_suspension, and allow_reactivation. Final authenticated REST shows customer_actions {allow_cancellation:true, allow_early_renew:false}; pause_subscription {enabled:true, max_duration_days:30, max_pauses_per_subscription:2, min_days_between_pauses:30, customer_can_pause:true, customer_can_resume:true, require_reason:false, access_during_pause:none}; no legacy keys or portal group.
- Members Access: while #1268 was Paused, None denied all implicit scopes, Limited allowed only content viewing, and Full allowed all scopes; explicit subscription_status=paused matched independently. Role, discount, download, ecommerce, comment, session, schedule, and integration scopes were verified against the shared evaluator, then access mode was restored to None. Role Mapping showed distinct Active, Trial, Paused, On Hold, Pending, Cancelled, Expired choices. Gutenberg Restricted Content block and Elementor ArraySubs Content Restrictions exposed the same seven statuses; the temporary Gutenberg post was deleted, Elementor changes were not published, and Elementor was restored inactive.
- Analytics: during pause Churn REST/UI reported paused=1, on_hold=0 and #1268 as Paused; forecast excluded #1268 and reported current MRR 608.8 across 2 billing subscriptions. After resume Churn reported paused=0/on_hold=0 and #1268 Trial; forecast re-added it and reported MRR 913.2 across 3 billing subscriptions. Live-risk and billing populations therefore remain intentionally distinct.
- Clean-browser secondary admin pass after final build: Skip & Pause rendered Allow Resume and the saved No access (fully restricted) label, with no browser errors, no leaked renderFormItems warning, and no FormBuilder debug logging. Only standard WordPress JQMIGRATE and React development notices remained.
- Validation: all changed PHP files passed php -n -l; core and pro production bundles compiled successfully; git diff --check passed in arraysubs, arraysubspro, and qa. PHPCS was intentionally not run per workspace QA instructions. Final state: #1268 Trial, settings restored, live pause meta cleared, Elementor inactive, temporary test content removed.
