---
id: 93
title: 'stage-14: Add subscription form layout and address controls differ from QA plan'
status: closed
priority: medium
created: 2026-05-23T11:19:35.907016094+02:00
updated: 2026-05-24T14:35:10.419689493+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
class: standard
---

Original task: stages/14-admin-subscriptions/02-create-subscription-from-admin.md\n\nExpected visible sections in order: General, Product & Subscription Details, Trial Settings, Different Renewal Price, Billing Information & Addresses. Browser showed: General, Product & Subscription Details, Different Renewal Price, Billing Information, Billing Address, Shipping Address. Trial fields are present but no Trial Settings section.\n\nExpected address Country uses WooCommerce country select and US State becomes select. Browser showed Billing/Shipping Country and State / Province as plain text inputs.\n\nImpact: documented form contract and address UX do not match QA plan.

[[2026-05-24]] Sun 14:25
Fix pass started 2026-05-24 by shell-quartz. Verifying issue, source QA task, and add-subscription form implementation before code changes.

[[2026-05-24]] Sun 14:35
Fix 2026-05-24: Add Subscription form now has documented top-level sections in order: General, Product & Subscription Details, Trial Settings, Different Renewal Price, Billing Information & Addresses. Trial fields moved into the Trial Settings card; billing and shipping were merged under Billing Information & Addresses. Boot now exposes WooCommerce allowed countries, shipping countries, and states in window.arraySubs.env. Billing/Shipping Country and State / Province are FormBuilder Select controls; selecting United States exposes Texas/TX through the Woo state list. Verified php -l src/Boot.php and npm run build. agent-browser confirmed section order and dropdown controls. agent-browser proof: h3_sections=[General, Product & Subscription Details, Trial Settings, Different Renewal Price, Billing Information & Addresses], country_button_count=2, state_button_count=2, selected_texts=[United States (US), Texas, United States (US), Texas]. Screenshot: qa/artifacts/issue-93-add-form-sections-address-selects.png.
