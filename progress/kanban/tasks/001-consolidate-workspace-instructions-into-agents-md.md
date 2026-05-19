---
id: 1
title: Consolidate workspace instructions into AGENTS.md and add QA workflow
status: closed
priority: high
created: 2026-05-19T22:12:46.716767214+02:00
updated: 2026-05-19T22:19:05.940198677+02:00
started: 2026-05-19T22:19:05.940197615+02:00
completed: 2026-05-19T22:19:05.940197615+02:00
claimed_by: lunar-dart
claimed_at: 2026-05-19T22:19:05.940198577+02:00
class: standard
---

Remove redundant instruction files (.github/copilot-instructions.md, CLAUDE.md, GEMINI.md). Update AGENTS.md to be the sole canonical instruction file and add mandatory QA workflow: read qa/stages/README.md first, use test URLs and credentials from QA docs, use globally installed Playwright for applicable real browser E2E, log active QA work in qa/progress/kanban, log bugs in qa/issues/kanban, and do not install Composer; use pre-built assets.

[[2026-05-19]] Tue 22:19
Completed: AGENTS.md is now the sole canonical instruction file. Added mandatory QA workflow covering qa/stages README-first execution, QA-sourced credentials, globally installed Playwright for browser E2E when applicable, QA progress/issues board logging, and no-Composer/pre-built workflow. Deleted CLAUDE.md, GEMINI.md, and .github/copilot-instructions.md. No QA issues were found during this documentation task.
