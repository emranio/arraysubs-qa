---
id: 131
title: Review TranslatePress dynamic-string contracts and regression coverage
status: closed
priority: high
created: 2026-10-28T02:19:46.21633+06:00
updated: 2026-10-28T02:46:26.969073+06:00
tags:
    - i18n
    - compatibility
parent: 128
class: standard
---

Parent128. Read-only official API research and architectural review, then add focused shared-contract tests after API agreed. No live WP mutations/browser. Core worktree /tmp/arraysubs-multilingual/arraysubs; own tests only after coordination. QA plan/day N/A untilparentplan; IDs N/A.

Read official TranslatePress translation-function docs and actual installed provider source. Confirmed normal HTML DB text is handled by page buffer; script plain strings require trp_translate false and exact encoding; direct unprefixed REST calls are guarded, while native rest_pre_echo_response config supports narrow customer display fields. WP rest_url uses TP-filtered home_url and should carry language without an extra URL abstraction; browser verification remains parent-owned. Added workflow-scripts/tests/dynamic-translations.php: 49 assertions pass loading actual TP wrapper/render source with in-memory runtime/dictionary, no WP bootstrap/DB writes. Covers helper fallback, entities, wrappers, exceptions, actual REST guard, native allowlist traversal, source/ID/price preservation, exact methods/routes/context edit, and premium extension contract. Awaiting parent integration acceptance; no commits or closure.

Expanded HTML regression coverage after real rich-content gap: 66 assertions pass. Standalone test now loads actual WordPress KSES/HTML Tag Processor plus TranslatePress functions/render class without WP bootstrap or DB writes. Verified safe HTML source and output, source sanitized before provider, unsafe translated tags/event handlers/javascript URLs removed by real KSES, rich formatting/entities/links preserved, plain helper remains literal-text safe, and locale restored on rich-provider exceptions. File line count 294; no lint/Composer installs. Parent owns final browser integration and signoff.

Final focused audit found and reported Subscription Box data-step-title/data-element-label and freebieNames display values still passing raw DB text to JS review/reward UI; core agent fixed those output boundaries. Added dynamic-box-attributes.php: 32 assertions pass against actual PHP template/product+input+select branches and actual FrontendHooks.getRuntime. Verifies escaping/localization, unchanged IDs/types/option values/config/runtime/discounts/currency, deduped gift translation and English restoration. Combined focused coverage now 98 assertions (66 provider/helper + 32 box). Reviewed ProfileFields/MyAccount HTML and cancellation form-config admin-only API; normal HTML remains native TranslatePress territory. Pro Bundle frontend is CSS-only and introduces no similar script gap; email subject/content translation follows native wp_mail handling and its deliberate admin-security exclusions.

Final provider-semantics audit: actual TP DOM observer treats newly inserted text as an original; its public dynamic endpoint is read-only (no registration or MT), while editor path can register/MT originals. Recommended shared helper return original source in actual trp_is_translation_editor preview/true mode, leaving native editor discovery intact without blanket exclusions. Core implemented guard; tests now pass 84 provider/helper assertions and 39 actual box-render/runtime assertions (123 total). Verified editor source/zero eager provider calls, rich sanitization, unchanged locale, invalid editor flag public behavior, and box editor source attributes/freebie names. Parent/Pro agent warned syncRequiredLabel must not overwrite native editor translations. File sizes 320 and 194 lines; git diff check clean. Core agent authorized to commit helper plus these two test files as one coordinated follow-up; parent merges.

Parent confirmed shared source and follow-up editor fixes are merged and all 123 focused assertions pass. Provider contract, HTML handling, rendered Box attributes and runtime, editor source behavior, and read-only audit are complete.
