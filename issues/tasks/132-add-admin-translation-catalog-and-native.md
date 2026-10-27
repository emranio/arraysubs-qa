---
id: 132
title: Add admin translation catalog and native TranslatePress storage API
status: closed
priority: high
created: 2026-10-28T02:47:32.125089+06:00
updated: 2026-10-28T03:18:27.980807+06:00
tags:
    - i18n
    - settings
parent: 128
class: standard
---

Parent128 newly approved UI scope. Own corebackend only: safeallowlisted savedcustomertext sourcesregistry and extension forPro, TPconfiguredlanguage metadata, admin-only paginatedGET+validatedsavePOST nativeTPdictionary APIs. No paralleltranslatedoptions store, no sourcevaluewrites, no secret/customerdata scanning, no automatic/paidAI. Preserve formatting/placeholders, HTMLsource segment matching and stale-source protection. Worktree /tmp/arraysubs-multilingual/arraysubs. RootownsWP/browserfixtures. QAplan qa/translatepress-2026-10-28/plan.md extendedUI stage.

Implemented admin GET/POST translation catalog plus bounded AJAX product lookup, known source registry + premium extension, native TP DOM/parser-normalized text segments, dictionary-based dedup/usages and source-version row IDs. Manual saves resolve server sources, reject stale/invalid batches, preserve runtime placeholders, and write only native TP human-reviewed regular rows; GET/save never use renderer/machine translation. Shared plain helper now reads existing native templates before REST interpolation. New61assertion catalog/API suite passes with actual TP parser/normalizer and WordPress KSES plus in-memory native-query spy; existing84provider+39Box assertions pass. PHPsyntax/diff/linecounts pass. Source committed for parent merge/live UI QA; no site mutations by this agent.

Live UI follow-up completed: variable validation now names the actual required source tokens (unique, in source order) instead of suggesting an unrelated {site_title} example. Error code translation_variables_changed and HTTP400 remain unchanged; extra variables on a token-free source get a direct correction. New regression assertions verify {percent}/{cycles}, duplicates/order and absence of unrelated examples. Catalog/API suite67pass; existing84provider+39Boxpass. Parent already verified actual native-save/frontend application, customer403 and rejected-placeholder400 with source preservation.

Parent live-QA signoff accepted after source merged through709d86d: actual catalog lists68sources; quoted/entity translation save writes native TranslatePress row and renders Spanish while original settings remain unchanged; selectedproduct5900scope updates Subscription Box review data attributes; invalid template variables return400 with required tokens and valid correction saves; retention templates render Spanish with20percent/2cycles while IDs, conditions and raw source remain unchanged. Stale source409, customer403, invalid language/product/page guards pass. Inactive TranslatePress shows friendly setup help without fatal errors, then provider restored. Backend implementation complete; parent128 retains fixture cleanup and final POT/report. No further code changes.
