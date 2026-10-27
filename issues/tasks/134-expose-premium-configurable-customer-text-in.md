---
id: 134
title: Expose premium configurable customer text in shared translations screen
status: closed
priority: high
created: 2026-10-28T02:47:32.433274+06:00
updated: 2026-10-28T03:18:34.140442+06:00
tags:
    - i18n
    - settings
parent: 128
class: standard
---

Parent128 userapproveddirectsettingsUI. OwnProsourceenumeration only throughcore registryextension, no duplicateprovider. Include safeCheckoutBuilderstep/field/choice/contentlabels, cancellation/retentiondisplaytext, profile/menucontent/bundlebutton whereapplicable. No IDs/optionsvalues/rules/prices/privatecustomertext/credentials. Coordinatecorefilterdescriptors. Worktree /tmp/arraysubs-multilingual/arraysubspro. RootownsWP/browserQA.

Implemented premium source collector via arraysubs_dynamic_translation_sources(array,request), registered once in ProBoot. Sources are allowlisted raw saved options only: effective checkout layout field/choice/group overrides and HTML paragraphs, cancellation reasons/known offer/form captions, ProfileFields and MyAccount menu labels, bundle button. Stable occurrence IDs hash choice values; no product/customer queries, defaults, conditions, values, secrets or URLs exposed as source text. 165 source tests include actual shared core registry integration plus privacy, reorder identity and literal-vs-HTML boundaries. Fixed presentation-only retention template translation before interpolation through scoped shared helper; 22 tests cover raw defaults/admin acceptance, numbers/conditions and cleanup. Existing53 dynamic assertions pass. Awaiting final source review/commit; root owns live new editor UI testing.

Committed Pro3641e0e with saved-text registry, presentation-only template translation before numeric interpolation, and root-approved native retention REST name-only allowlist to prevent formatted target text entering MT/registration again. Tests:165 source-registry assertions including real core integration;27 retention-template/scope/second-pass assertions;53 existing dynamic text assertions. Diff check passed; all touched code below3000 lines. Two Pro draft entries preserve earlier release notes. No WP mutations/builds from this agent. Awaiting root merge and manual-translations UI browser verification before closure.

Live catalog review follow-up committed Pro f90e3ba: readable occurrence captions now use independent sequential counters, never option values/associative array keys as row numbers. Cancellation form choices use the saved human question label as context (markup stripped, caption bounded), not generic repeated field1. Stable path IDs remain unchanged. Registry tests now173, including20/30 and string keys, visible-order renumbering with stable IDs, sparse step/field counters;27 retention+53 existing dynamic assertions remain passing. PHP-only change, no build needed; root owns catalog recheck and final signoff.

Parent accepted live QA for merged f90e3ba: translations UI exposes readable question/choice contexts; saved Spanish retention template retains raw placeholders, and customer presentation resolves to translated20%/2-cycle text before interpolation while IDs/config/source remain unchanged. Canonical getter restores source wording after scoped display. Native WordPress permissions reject customer access403. ProductBox conditional editor/public flows passed, and prior native Checkout labels/options/rich content/editor behavior passed in18e67a6. Premium scope complete; parent128 owns remaining cleanup, reporting and POT generation.
