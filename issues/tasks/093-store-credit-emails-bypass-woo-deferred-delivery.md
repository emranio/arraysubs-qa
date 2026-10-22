---
id: 93
title: Store credit emails bypass Woo deferred delivery and render HTML in plain text
status: closed
priority: high
created: 2026-10-23T03:22:10.036397+06:00
updated: 2026-10-23T03:38:53.225574+06:00
started: 2026-10-23T03:38:53.164284+06:00
completed: 2026-10-23T03:38:53.164284+06:00
tags:
    - bug
    - email
    - pro
parent: 91
class: standard
---

Active QA task: 91; scheduled day: 2026-10-23; plan: qa/email-boundaries-2026-10-23/plan.md. Affected subscription/order IDs: N/A; in-memory WC_Order id0 only. Affected customer: disposable393, customer role, qa-credit-email generated login/email at example.invalid, deleted by probe finally; no existing user changes. Browser context: source/runtime validation in actual local WordPress at http://localhost:10013; browser email preview validation is part of parent91.

Reproduction: initialize live Woo email classes; call WC_Emails::send_queued_transactional_email for arraysubs_customer_credit_added with valid disposable customer; observe intercepted pre_wp_mail count. Separately fire raw action with a request-local queue observer and no synchronous WC dispatcher. Expected: one message from notification dispatch and no raw-action premature message. Actual: queued notification0, raw deferred1. Pro EmailManager registers raw transactional actions but constructors listen on raw events rather than Woo notification events. Existing eager mailer means the initial raw check is warm; no cold-boot runtime claim.

Render all four StoreCredit plain templates with name A & B and amount12.50; filtered HTML footer. Expected literal text and currency, no footer tags. Actual A &amp; B, 12.50&#036;, raw p/a footer markup. Proof: qa/email-boundaries-2026-10-23/pro-credit-before.json. All mail intercepted; settings/activation unchanged. Preview rendering additionally defaults actual template amounts to0 despite sample placeholders25/50; parent91 browser evidence confirms.

Plan before changes: use Woo _notification hooks; retain registered raw actions for Woo queue ownership; wrap Pro plain rendered content in shared core arraysubs_format_plain_email_content helper owned by root; render coherent sample amounts/name only when customer_id is0; preserve real trigger data including0 amounts; validate actual Woo notification and isolated synchronous/deferred paths, plaintext and HTML render samples, deleted customers and module-off gating. Source only in isolated worktree; no builds, drafts or deployment. Parent91 owns supported browser preview checks and full render suite.

[[2026-10-23]] Fri 03:23
Confirmed runtime before results captured with disposable user393 (deleted): queued notification dispatcher0 mails; raw deferred-event observer1 premature mail; every plain body includes literal HTML footer and encoded customer/currency text. Initial local mailer already eager-loaded; record will not claim actual cold boot. Source changes now in /tmp/arraysubs-credit-emails, codex/credit-email-notifications: notification hooks, shared core plaintext helper call, coherent zero-context preview defaults and slug-aware sample order URL. Root owns helper and parent browser confirmation.

[[2026-10-23]] Fri 03:26
Implemented source commit in codex/credit-email-notifications, worktree /tmp/arraysubs-credit-emails. Actual Woo request-isolated before/after regression now28/28 passes after: initial cold raw1, warm raw1, all5 queued notification routes1 each, deferred raw0 before worker; coherent previews John Doe and amounts25/20/30/15 with used-order12345 and expiry7days; real zero balances remain0; deleted customers no mail. Shared formatter loaded from root worktree helper source for test only. Evidence: qa/email-boundaries-2026-10-23/pro-credit-regression.php and pro-credit-{isolated-before,worktree-after}.json. Before fixture406 and after405 were deleted in finally; activation/settings unchanged; no real transport, stored queue, product/order/subscription/lifecycle changes. This is actual PHP/Woo dispatcher/render testing with request-local mailer reset/manual provider registration, not a full Pro boot/browser verification. Source files53–334lines, whitespace check passed; no lint/PHPCS/build/draft. Await root integration and browser preview verification before closing.

[[2026-10-23]] Fri 03:30
Extended by explicit user scope to all identified email defects: actual GBP currency proof qa/email-boundaries-2026-10-23/pro-credit-currency-before.json shows all4 bodies/placeholders12.50$ for GBP and Used mixes customer5USD+subscription7GBP into12.00$. Own pre-fix fixtures customer417/sub19711/draft credit19714/pending order19715 fully deleted; no existing ledger changes. Plan: propagate existing order/subscription/ledger currency into money placeholders and both templates; consume the expired action existing4args; include only same-owner/same-currency balances. No new currency system. After implementation16/16 currency checks pass plus original28/28 delivery/preview/plaintext checks. Latest own currency fixtures425/19777/19780/19781 and general customer426 deleted; exact settings/activation unchanged. Source behavior preserves real0 and customer store balance currency; verifies same-currency combination, foreign/different-owner exclusion, missing ledger fallback. Await peer review and root full-Pro/browser confirmation.

[[2026-10-23]] Fri 03:33
Final browser preview exposed a mock order12345 detail link. Plan: only zero-customer preview links to the valid Woo Orders list while retaining displayed sample12345; real email args reuse the populated actual order URL. Update HTML/plain templates and regression to prove preview list versus actual order detail.

[[2026-10-23]] Fri 03:34
Preview order-link follow-up source committed: constructor sample order URL is now valid Orders list; HTML/plain use the same explicit order URL arg; actual trigger keeps WC_Order::get_view_order_url(). Updated actual Woo regression to use own pending order and verify both destinations. Before29checks only preview-link check fails; after29/29pass. Evidence qa/email-boundaries-2026-10-23/pro-credit-order-link-{before,after}.json. Own pre-fix customer431/order19811 and after432/order19813 deleted, settings/activation unchanged, all mail intercepted. Only3 source files touched, max316lines; whitespace check passes; no build/lint/draft. Await parent browser verification.

[[2026-10-23]] Fri 03:38
Accepted after integration and real browser verification under parent QA91. All source commits78a25bc,8d6600e,9816e40 merged into primary main. Final CreditUsed browser preview: both sample order links open valid Orders list; all4 credit classes show coherent customer names and sample amounts. Actual primary runtime regression29/29 and currency16/16 pass: qa/email-boundaries-2026-10-23/pro-credit-primary.json and pro-credit-currency-primary.json. Native Free email UI verifies25 core /0Pro classes, restored active-Pro29classes. Exact original activation/config/email-option state comparison7/7 true: qa/email-boundaries-2026-10-23/browser/state-comparison.json. Peer code review clean. Parent91 completed final cleanup/report/changelog drafts; all transport intercepted and own fixtures removed. No claim of external SMTP delivery or full lifecycle coverage. Closing93 and releasing bang-sural claim.
