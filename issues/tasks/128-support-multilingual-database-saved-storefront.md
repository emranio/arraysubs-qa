---
id: 128
title: Support multilingual database-saved storefront text in both plugins
status: closed
priority: high
created: 2026-10-28T02:19:27.455255+06:00
updated: 2026-10-28T03:26:33.416842+06:00
started: 2026-10-28T03:26:33.41684+06:00
completed: 2026-10-28T03:26:33.41684+06:00
tags:
    - feature
    - i18n
    - compatibility
class: standard
---

User requests installation and real integration verification of TranslatePress and WPML, especially configurable Subscribe now/add-to-cart labels and other DB-saved storefront text outside POT catalogs. Active QA plan/day: fresh scoped plan to be created before browser testing. Subscription/order/user IDs: N/A initially. Local site http://localhost:10013 per AGENTS.md. Plan: audit both plugins saved display text; implement one shared dynamic-text translation contract without translating identifiers/prices/settings editing; integrate premium text; install/configure real translation plugins independently; verify browser language switching, source preservation, custom labels and representative cart/account flows; document concrete results and limitations. WPML licensed installer path requested while independent TranslatePress work continues.

Scope update: user explicitly said skip WPML, so no commercialinstaller or WPMLverification remains required. Fresh plan qa/translatepress-2026-10-28/plan.md and progress board created. TranslatePress3.3.8 active, en_US/es_ES configured viaUI. Baseline product5916 guest button custom source Subscribe to the studio translated through nativevisualeditor to Suscribirse al estudio on/es/. SourceDBlabel staysEnglish. Actualcheckout inline RESTURL already contains/es/wp-json, confirming nativeTP languagepropagation. Implementation targets realplainJSON/inlineJSgaps; ordinaryHTML andadminsourcegetters remainraw.

Reproduced checkout defect before deployment. Active QA task2, day2026-10-28, plan qa/translatepress-2026-10-28/plan.md. Route http://localhost:10013/es/arraysubs-tp-block-checkout-sqwkps/; browser trp-guest, guest user(N/A), product5916, no order/subscription created by checkout. Saved manual TPdictionary translations for Delivery note etc exist, but window.arraySubsCheckout.blockFields still contains raw Englishlabels and raw <strong>Please confirm</strong> markup is visible literally. Expected localized runtime display text and formatted safeHTML; actual screenshot04/05 + actualbrowserJSobservations prove missinginlineJSONtranslation/richcontentrenderer. Scope: ordinary savedproductbutton already translates correctly throughnormalHTMLbuffer. Prosourcefix andsharedhelper in reviewedcommits awaitingdeployment.

User extended scope and confirmed ok: add direct translation management inside ArraySubssettingsUI because conditional DBtext is difficult to reach onstorefront. Preserve originalTranslatePressintegration objective and finishedQA. Newfrontend/backend/Proregistrytasks132–134 willuse nativeTPdictionary andconfiguredlanguages; manualeditingonly, no paidAI/cloudcharges or secondtranslationstore. Existingfixturestrings remain temporarily forend-to-endsettingsUI verification beforecleanup.

[[2026-10-28]] Wed 03:26
Completed and merged: core 709d86d and Pro f90e3ba. Direct Settings > Translations UI verified in real English/Spanish browser flows with native TranslatePress storage, conditional product text, source preservation, variable validation, stale and permission guards. Both production builds and regenerated POT files pass; zero missing JavaScript reference paths. 443 PHP assertions and 16 JavaScript tests passed. Original business settings restored; temporary pages, customers, subscription and fixture actions removed. Final evidence and scope limits: qa/translatepress-2026-10-28/report.md, screenshots 01-19. WPML skipped per user. No remaining work in this scope.
