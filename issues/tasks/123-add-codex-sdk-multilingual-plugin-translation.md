---
id: 123
title: Add Codex SDK multilingual plugin translation workflow
status: closed
priority: high
created: 2026-10-28T01:48:31.938204+06:00
updated: 2026-10-28T02:09:39.136388+06:00
started: 2026-10-28T01:50:44.871984+06:00
tags:
    - feature
    - i18n
    - workflow
class: standard
---

User requests npm run plugin:translate in both plugins using latest Luna through Codex SDK, configurable EU/additional WordPress locales, software UI translation instructions, low-cost resumable generation, PO/MO/JSON output and validation. Test Spanish only; do not execute all languages. Active QA plan/day N/A; subscription/order/customer IDs N/A. Plan: inspect official SDK/model docs; implement shared engine, validated gettext support and locale/prompt configuration; test units and actual Spanish SDK run; document commands, update drafts, merge and close.

Implementation ready: shared core command used by both plugins; 31 WordPress locales with Spanish default, professional UI policy and approved Spanish glossary, bounded Luna batches, validation/repair, shared resumable memory, native review, PO/MO/Jed compiler and safe publication. Offline integration suite currently passes 46 tests. All-language checks were dry-run only. Live Spanish SDK startup is being verified; no catalogs published yet.

Completed and merged to main: core c6afb43; Pro cfef535. Final offline suite: 47/47 passing. Actual gpt-6-luna Spanish integration used 22 representative core messages and 17 Pro messages, including Add new plan, contexts, plurals, printf/template variables and HTML. Generated core PO/MO plus 15 script JSON files; Pro PO/MO plus 4 JSON files. Five Pro messages reused core memory. Spanish Add new plan renders in the catalog as Añadir nuevo plan. Re-ran both commands in the real workspace: 22/17 entries reused, zero SDK attempts. Test catalogs are isolated under arraysubs/workflow-scripts/translation/.translation-cache/integration; no partial catalogs installed in live languages/. All-language selection was validated only by no-write dry-runs. Preserved concurrent UI edits and both drafts additional entries. Earlier SDK startup incompatibilities were fixed and covered by regression tests; unknown attempts remain explicit in the usage ledger. No release/version bump or remote push.
