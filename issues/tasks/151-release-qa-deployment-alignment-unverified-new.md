---
id: 151
title: Release QA deployment alignment unverified; new Webhooks and changed UI absent on live target
status: open
priority: high
created: 2026-10-31T02:17:52.316307+06:00
updated: 2026-10-31T02:17:52.316307+06:00
tags:
    - release-qa
    - environment
    - coverage-blocker
class: standard
---

QA progress tasks #1 and #2; scheduled day2026-10-31 Asia/Dhaka. Plan:qa/release-2026-10-31-0208/plan.md.
Subscription/order IDs:N/A, discovery only. User:WordPress admin1, roleadministrator; no customer data affected.
LiveURL:https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/modules and sidebar/settings/CheckoutBuilder.
Browser:rqa0208-admin (coordinator), rqa0208-premium-admin. ProfileA both pluginsactive.
Steps:inspect active Plugins (core2.1.0,Pro1.3.0); load Modules and inspect all cards/sidebar; compare local registrations and unreleased drafts. CheckoutBuilder live still shows Copy Custom Fields to Subscriptions switch.
Expected:release QA target deployment alignment known, registered release capabilities inspectable.
Actual:live has17 module cards, no Webhooks card/menu, no Modules search, legacy General settings contain renewal controls; local source/drafts register Webhooks and RenewalFlow/new UI. Deployed git revisions cannot be verified with current access. LocalHEADcore65083a2/Procbc348b; equal version strings do not establish identical code.
Proof:qa/release-2026-10-31-0208/evidence/modules.png,modules-snapshot.txt,plugins.txt; inventory.md; premium-checkout-settings evidence.
Scope:environment/coverage blocker, not a confirmed plugin implementation defect. No deployment or code edits performed. New Webhooks delivery/how-to/audit and changed release UI remain NOT-TESTED against local release code. Existing live flows continue browser testing; external webhook send/secret changes excluded.
Restoration:N/A read-only discovery.
