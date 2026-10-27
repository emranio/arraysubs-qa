---
id: 2
title: Verify runtime customer payloads and premium dynamic text
status: done
priority: high
created: 2026-10-28T02:27:27.109+06:00
updated: 2026-10-28T02:51:16.435188+06:00
class: standard
---

Day2026-10-28 stages3–4. Plan plan.md, implementation issues128–131. After ordinary saved-label baseline passes, verify customer REST labels, checkout inline JSON, HTML content, raw source/admin preservation, language switching, core-only and Pro modes; capture concrete IDs/screenshots and restore fixtures.

Integration checks complete before new settingsUI stage: product5916 custombutton English/Spanish +sourcepreservation; Box5900 custombutton/dataattrs/reviewheadingSpanish; ProBundle5905custombuttonSpanish; checkoutfixtures23612/23613 languages/schema/options/HTML validated, optionalfieldregistration andplaceholderoverlap fixed; customer477/subscription23617 savedcancellationreasonSpanishviaactualREST+modal, EnglishAPI returnsEnglish; nativeemail subject/body Spanish capturedwithoutdelivery; TPeditor originalEnglish+Spanishpair andrawpreviewDTO verified. Core-onlyBox pagepasseswithoutPro (silentsuspension/restoration avoids lifecycleeffects). 84provider+39Box+53Pro assertions and6DOMtests pass. Bothbuilds/POTregeneration pass. Testfixtures intentionallyretained for user-approved settingsUIstage5, cleanupfollows.
