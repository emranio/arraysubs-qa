---
id: 122
title: Audit and repair PHP translation coverage in both plugins
status: closed
priority: high
created: 2026-10-28T01:12:16.522842+06:00
updated: 2026-10-28T01:34:56.211469+06:00
started: 2026-10-28T01:12:50.432316+06:00
completed: 2026-10-28T01:34:56.211469+06:00
tags:
    - i18n
    - php
parent: 119
class: standard
---

User-authorized translation readiness audit. Active QA plan/day N/A; subscription/order/customer IDs N/A. Scope: both plugins PHP display strings, domains, pluralization and translator comments. Parent issue #119 contains the overall plan.

[[2026-10-28]] Wed 01:22
PHP handoff ready in /tmp/arraysubs-translation-readiness/{arraysubs,arraysubspro}. Repaired untranslated shared REST duplicate errors/success/copy titles; human-readable WP_Error messages preserve stable machine codes; cancellation built-in labels translate at display time only when stable key and original text match, leaving merchant labels untouched. Added semantic gettext contexts for wallet/card, display/login, payment-method identifier and plan/billing terms. Pro scheduled job labels, statuses and detail sentences now translate, including singular/plural day counts. Core email defaults were captured in constructors before email locale switches: all 25 email classes now use lazy subject/heading getters, preserving RenewalReminder contexts. A shared core LocalizedEmailOptions trait prevents cached fallback language across reused email instances and resolves default footers lazily, preserving merchant custom/explicitly empty content; four Pro credit emails reuse it. Missing placeholder comments from extraction are repaired, including final _n attachment fixes. Standalone regression tests: workflow-scripts/tests/php-translation-coverage.php passed 42 assertions; workflow-scripts/tests/email-locale-defaults.php passed 559 assertions across 29 classes with alternating locales and merchant overrides. Tokenizer audit reviewed over 6,700 gettext calls for literal sources/domains. All touched PHP under 3000 lines. No PHPCS/lint/dependency installs, no builds/POT regeneration by this agent. Technical webhook protocol/log diagnostics intentionally stay raw. Awaiting parent integration and browser verification before closing #122.

[[2026-10-28]] Wed 01:34
Parent integration signoff: core commit 8532f16 and Pro commit 6d5a403 merged. Both builds and POT generations pass with no missing-placeholder warnings. The standalone regression suite passes 42 PHP and 559 email assertions. Actual WooCommerce NewSubscriptionEmail tested with a real Spanish MO catalog switches its heading Spanish -> English -> Spanish on the same email instance. Browser PHP translations work from bundled, system and Loco catalog locations. Site/admin languages and plugin activation states were restored, and subscriptions were unchanged. PHP translation audit is verified complete.
