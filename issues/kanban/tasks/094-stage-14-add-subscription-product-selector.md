---
id: 94
title: 'stage-14: Add subscription product selector navigates away from form'
status: closed
priority: critical
created: 2026-05-23T11:19:35.925704303+02:00
updated: 2026-05-24T12:11:07.119238254+02:00
started: 2026-05-24T11:51:42.985108136+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
class: standard
---

Original task: stages/14-admin-subscriptions/02-create-subscription-from-admin.md\n\nBrowser test on Add New Subscription form: after selecting customer successfully, clicking the Subscription Product field placeholder caused URL to change from #/subscriptions/form to #/subscriptions and returned to the list. Reproduced on a clean form by clicking only Subscription Product -> same navigation away.\n\nExpected: product AJAX/search selector opens and remains on form so PM Tool can be selected. Actual: form is lost, create flow cannot be completed by browser.

[[2026-05-24]] Sun 12:11
Fixed shared FormBuilder Select control so custom selects render as real `type="button"` controls with `aria-haspopup="listbox"`, `aria-expanded`, keyboard handling, and listbox/option semantics. Added CSS reset/width styling for the button control and rebuilt ArraySubs frontend assets with `npm run build`.

Verification: Alumnium reproduced the original route loss before the patch. After rebuild Alumnium still mis-clicked this page, so per AGENTS fallback rule I verified with Playwright screenshots: `qa/artifacts/issue-94-product-select/06-after-fix-product-dropdown.png`, `07-after-fix-pm-tool-selected.png`, `08-after-fix-variation-dropdown.png`. Playwright confirmed clicking Subscription Product stays on `#/subscriptions/form`, opens one dropdown, filters/selects `PM Tool (variable)`, and then shows the Product Variation selector with `PM Tool - Weekly` / `PM Tool - Bi-weekly` while URL remains `#/subscriptions/form`.
