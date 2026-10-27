---
id: 125
title: Configure target locales and software localization instructions
status: closed
priority: high
created: 2026-10-28T01:50:44.895748+06:00
updated: 2026-10-28T02:06:17.529489+06:00
started: 2026-10-28T01:52:05.863719+06:00
completed: 2026-10-28T02:06:17.529488+06:00
tags:
    - i18n
    - workflow
parent: 123
claimed_by: anabatic-potently
claimed_at: 2026-10-28T02:06:17.529489+06:00
class: standard
---

Verify EU official language set and WordPress locale/plural metadata; configure EU plus Indonesian, Thai, Arabic, Hebrew, Ukrainian, Simplified/Traditional Chinese; author strong UI localization guidance and glossary. Spanish is default test language; parent123 scope. QA plan/day and data IDs N/A.

[[2026-10-28]] Wed 01:57
Implementation ready for parent integration review. Added workflow-scripts/translation/{languages.json,config.json,instructions.md,glossary.json,prompts.mjs} in /tmp/arraysubs-plugin-translate/arraysubs. Locale array covers all 24 official EU languages plus Indonesian, Thai, Arabic, Hebrew, Ukrainian, Simplified Chinese and Traditional Chinese (31). Exact codes, directions, nplurals and expressions match both current official GlotPress and WordPress.org source metadata; generated examples cover every plural index and formula ranges 0..10000 were checked. Notable WP rules: mlt=4, ga=5, he_IL=2, fr_FR=2, id_ID=2. Spanish default only; config model gpt-6-luna, medium effort, 160 entries/24000 characters, concurrency 2, retries 2, timeout 180000ms, flagged review, shared local cache. Added 54 semantic glossary concepts and approved Spanish terms to avoid a glossary AI call. Prompt resources enforce native software/WooCommerce UI usage, exact placeholders/markup/identifiers, locale-specific plurals and RTL, legal/billing meaning, JSON-only replies, untrusted source data and no model tools. Prompt payload/contracts, glossary isolation and all resource JSON validated locally; no SDK calls, builds or npm mutations performed. File line counts: languages 1338, config 16, instructions 33, glossary 490, prompts 120. Awaiting parent integration signoff before task closure.

[[2026-10-28]] Wed 02:03
Additional parent-requested runner/memory read-only review complete. Added only workflow-scripts/translation/tests/runner.test.mjs (344 lines). Eleven mocked engine tests pass: Spanish default approved glossary and invalid-only repair; complete PO/MO/Jed outputs; interrupted flagged review resumes; sample preserves all live outputs and later full run reuses cache; manual PO reuse without AI; generated non-Spanish glossary cached once across interruption; bounded retryable SDK errors versus terminal failures; dry run writes nothing; none-to-all/higher-effort review reuses translations; reviewed unchanged brand avoids repeat review; manual edits receive review; source POT mutation prevents publication but preserves reusable work; core/Pro share memory with distinct domains. Review identified retryable errors not being retried and redundant review/retranslation costs; parent implemented fixes and all regression cases pass. No runner/memory/CLI edits by this agent.

[[2026-10-28]] Wed 02:06
Final handoff accepted by parent: all 31 locale definitions, UI localization prompts, glossary/config resources and 11 mocked runner regression tests pass in the integrated full suite. Parent is assembling the shared code commit and verifying a representative Spanish sample across both plugins. Task complete; no code or board commit performed by this agent.
