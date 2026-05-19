---
id: 1
title: Consolidate workspace instructions into AGENTS.md and add QA workflow
status: in-progress
priority: high
created: 2026-05-19T22:12:46.716767214+02:00
updated: 2026-05-19T22:12:46.716767214+02:00
claimed_by: lunar-dart
claimed_at: 2026-05-19T22:12:46.716785288+02:00
class: standard
---

Remove redundant instruction files (.github/copilot-instructions.md, CLAUDE.md, GEMINI.md). Update AGENTS.md to be the sole canonical instruction file and add mandatory QA workflow: read qa/stages/README.md first, use test URLs and credentials from QA docs, use globally installed Playwright for applicable real browser E2E, log active QA work in qa/progress/kanban, log bugs in qa/issues/kanban, and do not install Composer; use pre-built assets.
