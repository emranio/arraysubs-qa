---
id: 126
title: Review Codex SDK translation runtime and cost controls
status: closed
priority: medium
created: 2026-10-28T01:51:58.078086+06:00
updated: 2026-10-28T02:05:51.066354+06:00
started: 2026-10-28T01:52:23.106909+06:00
completed: 2026-10-28T02:05:51.066353+06:00
tags:
    - i18n
    - workflow
    - review
parent: 123
claimed_by: nithing-stract
claimed_at: 2026-10-28T02:05:51.066354+06:00
class: standard
---

Review actual SDK interfaces/configuration, authentication, strict structured outputs, isolated no-tool execution, token reporting, caching/resume and failure handling. Main task123 scope; no AI calls until parent authorizes Spanish test. QA plan/day and data IDs N/A.

[[2026-10-28]] Wed 01:55
SDK 0.164.0 actual type/API research complete. Verified outputSchema plus AbortSignal, fresh thread per batch, pinned gpt-6-luna/medium, usage subset accounting, supported instruction override and feature controls. Verified empty mcp_servers={} does not disable inherited servers; per-name enabled=false required. Existing auth retained; no auth file or credentials inspected. Reviewed safe CLI metadata only. Awaiting parent SDK/runtime files for implementation review; no live model calls made.

[[2026-10-28]] Wed 02:02
Implemented isolated SDK adapter and 13 passing tests, including installed-CLI configuration regression without a model call. One authorized diagnostic attempt exposed startup failure before inference: quoted dotted MCP overrides create malformed server names. Fixed with root inline TOML tables preserving literal MCP/plugin keys; CLI metadata tests verify disablement. Read-only real SDK create/close bootstrap passed. Adapter records completed usage before parsing, aborts unexpected tools, supports caller cancellation/timeout, preserves existing authentication, and never falls back models. Parent reviewing Spanish smoke; parent runner received resume/review/cache/resource and nested-retry-cost findings.

[[2026-10-28]] Wed 02:05
SDK adapter and runtime review completed. Fixed plugin-provided virtual MCP transport startup errors by inventorying servers only after plugins/apps are disabled. Corrected SDK non-fatal ErrorItem handling for skills-budget warnings, and exposed usageRecorded to prevent double counting null-usage completed turns. Sixteen SDK tests pass. Authorized real Spanish diagnostic with full translation schema succeeded: Add new plan -> Añadir nuevo plan; exact usage input6298, cached0, cache_write0, output28, reasoning0. Fresh pinned Luna medium session, no tools and no model fallback. Files sdk.mjs and tests/sdk.test.mjs remain under3000 lines. Parent integrated runner review feedback and owns remaining Spanish workflow test.
