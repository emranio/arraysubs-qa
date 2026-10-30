---
id: 150
title: Canonical protected page denies an entitled signed-in customer while fresh URL allows access
status: open
priority: high
created: 2026-10-31T02:16:00.85822+06:00
updated: 2026-10-31T02:44:16.621392+06:00
tags:
    - release-qa
    - access
    - environment
    - cache
class: standard
---

 — Canonical protected content denies an entitled signed-in customer; cache-busted URL grants access

Severity: high environment/access regression; release impact for coordinator triage. Root cause not established. Browser behavior is consistent with shared frontend page caching; this is an inference, not code-confirmed.

- QA progress task: #4 Customer access and premium workflows; scheduled day2026-10-31 Asia/Dhaka.
- Plan: qa/release-2026-10-31-0208/plan.md.
- Subscription:69245 (Active, SQA Daily Stripe Plan,7.31/day as shown Manage Members detail); orders:N/A (read-only entitlement assertion).
- WordPress user/customer:595, login sqa-fix56-account, tagged test email sqa-fix56-account@example.com, Customer role shown admin. Negative context user2595 sqa-fix81-c1 box subscription97430; guest separate isolated session.
- Browser contexts: rqa0208-entitled via supported Login as Customer; rqa0208-customer-b; rqa0208-guest. ProfileA current settings, both plugins active.
- Canonical URL:https://mirror-help.arrayhash.com/sqa-members-daily-content/.
- Fresh comparison URL:https://mirror-help.arrayhash.com/sqa-members-daily-content/?rqa0208=entitled.
- Steps:1) In live Manage Members open595 and verify Active Daily Stripe Plan sub69245.2) Use supported Login as Customer; /my-account/ explicitly identifies Sqa sqa-fix56-account and return-to-admin bar.3) Open canonical protected URL.4) Open same URL with harmless rqa0208 query.
- Expected:eligible signed-in customer sees protected content on canonical URL; guest/unentitled sees deny.
- Actual:canonical renders guest navigation Login, no identity bar, and SQA-DENIED-7731. Cache-busted same page displays authenticated identity and SQA-ENTITLED-CONTENT-7731 protected text. Positive entitlement logic works when fresh; canonical member experience fails.
- Proof:evidence/access-entitled-content.png canonical deny; evidence/access-entitled-fresh.png positive with identity and protected body; admin snapshot identified user595/sub69245. Guest-after-entitled same URL still denied (no protected body leak), and unentitled2595 fresh comparison denied with correct identity. Screenshots:access-guest-after-entitled.png,access-unentitled-fresh.png.
- Known scope:canonical homepage also appeared guest while account dynamic pages correctly retained user identity; no account authentication-loss conclusion. No global cache purge performed. No restriction condition or subscription state changed by access lane. No protected download evaluated yet.
- Reproduction bounded to canonical versus one harmless query; no extended cache debugging.

Coordinator triage: high priority access availability regression on deployed QA site. Root cause unconfirmed; caching is an inference. Guest/unentitled query checks deny protected text; no cross-user content leak confirmed. Retained subscriptions/customer records unchanged.

Additional scope2026-10-31: newly created Page109473 /rqa0208-access/ with active-product109433 restriction reproduced canonical guest-style denial for eligible A3252/sub109453, while ?rqa0208=owner displays tagged protectedbody. B2595 and guest remain denied on freshqueries, including guest same-owner URL. This rules out an obsolete original postfixture as the only affectedcontent. Proof:access-runpage-owner.png,access-runpage-owner-fresh.png,access-runpage-b-fresh.png,access-runpage-guest-fresh.png. Run-owned Page109473 scheduled for trash after evidence, original DailyContent fixture untouched.


Complete later fixture occurrence: customerA3252 loginrqa0208.customera,emailrqa0208-a@example.invalid,rolecustomer; product109433,subscription109453,initialorder109452. Page109473/rqa0208-access/wastrashedthroughnativeUIandPageslistconfirmed. ExistingDailyContentfixtureunchanged. CanonicaldenialvsfreshURLaccess isbrowserproof; cachingremainsinference,noauthenticationlossorguestleakproven.
