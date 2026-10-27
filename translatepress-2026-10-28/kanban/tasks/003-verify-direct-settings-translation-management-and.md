---
id: 3
title: Verify direct settings translation management and clean fixtures
status: done
priority: high
created: 2026-10-28T02:51:16.532598+06:00
updated: 2026-10-28T03:26:33.161688+06:00
started: 2026-10-28T03:26:33.161687+06:00
completed: 2026-10-28T03:26:33.161687+06:00
class: standard
---

User-approved planstage5, day2026-10-28. Test new /settings/translations UI nativeTranslatePressmetadata, configuredlocale/group/search/productscopes, source readonly, rowedit/save/clear, conditionaltext notpreviouslyvisited, source/defaultlanguagepreservation, placeholderstale/permissionguards, HTMLfragments, providerinactivehelp. Verify savedtranslation inrealfrontend. Then restore selectedbusinesssettings and remove fixturepages/users/subscription/actions while retainingTP en/es andsourcecode. Capture proof/report andcloseparent128 onlyaftercomplete.

[[2026-10-28]] Wed 03:26
Passed the real settings UI and storefront checks, validation/permissions/stale-source checks, provider-inactive fallback, source preservation and final fixture restoration. Both builds/POTs and focused regressions pass. See ../report.md and ../screenshots/12 through 19. Site configuration restored and temporary fixtures removed; TranslatePress remains active en_US/es_ES with automatic translation disabled.
