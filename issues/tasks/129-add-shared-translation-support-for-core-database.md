---
id: 129
title: Add shared translation support for core database-saved display text
status: closed
priority: high
created: 2026-10-28T02:19:46.122919+06:00
updated: 2026-10-28T02:46:12.801603+06:00
tags:
    - i18n
    - compatibility
parent: 128
class: standard
---

Parent128. Audit saved core customer-facing options/postmeta and runtime display paths; implement shared translation contract and core wiring. Source stored/admin editable values must remain raw; do not translate IDs, prices, billing behavior. Review official WPML/TranslatePress APIs. Worktree /tmp/arraysubs-multilingual/arraysubs. QA plan/day pendingparent; IDs N/A initial. Root owns live WP/browser mutations.

Implemented core Supports DynamicTranslations and translation-helpers.php plus Boot registration. TranslatePress native REST config is scoped to exact plan-switch presentation routes/methods; premium display endpoints extend the same filter, explicit edit contexts stay raw, and depth extends only for supported responses. Plain inline-JS text helper encodes/decode once, suppresses provider wrappers and restores TP language in finally. Ordinary HTML saved labels and raw settings getters unchanged because TranslatePress already handles rendered HTML. PHP syntax checks and line counts pass; provider-contract regression and parent real-browser verification pending. WPML was removed from scope at user request.

Core source and rich/plain helper contract now pass 66 regression assertions using real installed TranslatePress APIs and actual WordPress KSES/HTML processing. Added rich HTML helper to preserve allowed checkout block markup while sanitizing source/provider output; plain helper retains literal text. Parent authorized source commit for live-browser verification; task remains in-progress until signoff. Disposable local customer/subscription fixture creation/cleanup script prepared at /tmp/arraysubs-translatepress-state/customer-fixture.php, syntax checked and not executed by this agent.

Follow-up audit fixed actual Subscription Box dynamic sources: data-step-title/data-element-label attributes feeding JS review labels and freebieNames runtime map values. Visible HTML remains a native TranslatePress buffer target; IDs, source configs, option values and discount metadata remain raw. Added actual PHP template/getRuntime regression: 32 assertions pass; provider helper suite still 66/66. Parent owns merge/live-browser verification; no direct site data mutation by this agent.

Visual-editor integration follow-up: actual TranslatePress trp_is_translation_editor() preview/true modes now preserve source text in script-fed UI rather than translating it before the native picker sees it. Public translation remains unchanged; rich HTML source remains KSES-sanitized. Tests expanded to 84 provider/helper assertions plus 39 rendered Box/runtime assertions, all passing; implementation and two reviewed test files committed for parent merge. No blanket no-translation exclusions added.

Parent signoff received: core source through commit6b5e6af is merged; actual Spanish Subscription Box review, shared helpers and core-only verification passed. Core implementation complete. Parent task128 retains final QA and fixture cleanup. A newly requested settings translation UI will be tracked separately and is not part of this closed implementation task.
