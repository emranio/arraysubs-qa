---
id: 130
title: Connect premium database-saved display text to shared multilingual support
status: closed
priority: high
created: 2026-10-28T02:19:46.17113+06:00
updated: 2026-10-28T02:46:16.24963+06:00
tags:
    - i18n
    - compatibility
parent: 128
class: standard
---

Parent128. Audit premium configurable customer-facing labels/notices/product content; connect display consumers to core translation contract without duplicate provider layer. Worktree /tmp/arraysubs-multilingual/arraysubspro. Do not edit core or both changelog drafts yet. QA plan/day pendingparent; IDs N/A initial. Root owns site/browser mutations.

Audited premium DB display paths. Plain HTML relies on TranslatePress native buffering. Implemented CheckoutBuilder FrontendTranslations on the final JavaScript payload only, preserving saved layout, IDs, default/option values, rules, design and URLs. Added CancellationFlow Translations adapter for exact customer GET reasons and POST subscription retention-offers, leaving admin form config, acceptance and analytics unchanged. Shared core helper/filter contract agreed with core agent. Isolated 39 assertions pass in php tests/dynamic-translations-regression.php; line counts 1603/76/51/120 for touched PHP source/test files. Awaiting parent real TranslatePress browser verification and merge signoff; no commits/site mutations.

Follow-up review found rich HTML allowed by stored paragraph/alert content and classic rendering, while block JS rendered it as literal tags. Added shared-core HTML-helper routing for block content and safe display helper: sanitized content retains formatting; fallback labels remain textContent. Pro now passes 40 PHP assertions and 3 targeted Jest DOM tests. All touched code below 3000 lines (largest1693). Wrote optional disposable synthetic-customer/block/classic checkout fixture script under /tmp/arraysubs-translatepress-state/pro-fixture.php without executing it; parent owns setup/restore/browser tests. Updated draft entry includes formatted content. Tests are git-trackable and excluded from packaged zip by existing .zipignore.

Parent browser QA found native WooCommerce block registration still supplied English optional labels and select options despite localized custom JS config. Follow-up commit18e67a6 translates final Woo field presentation labels/optionalLabel/placeholder/help/choice labels while validation and persistence retain original field definitions and values. It also identifies TranslatePress preview in runtime data and prevents conditional-required label reconciliation from overwriting provider-owned preview nodes. Targeted CSS floats labels for visible native builder text inputs with a saved placeholder, excluding hidden composite adapters. 53 PHP assertions plus6 DOM tests pass; all touched source below3000 lines. Awaiting parent rebuild/browser recheck and signoff; no live WP mutations from this agent.

Parent confirms Pro18e67a6 merged and actual Spanish browser verification passed: native block labels and optional labels, option captions, allowed rich HTML and editor preview flag; source and submitted values remain unchanged. Premium implementation complete; parent128 owns final QA cleanup.
