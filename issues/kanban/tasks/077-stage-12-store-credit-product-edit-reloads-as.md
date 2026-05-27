---
id: 77
title: 'stage-12: Store Credit product edit reloads as Simple product'
status: closed
priority: high
created: 2026-05-23T09:32:20.288614226+02:00
updated: 2026-05-24T09:56:31.80825985+02:00
started: 2026-05-24T09:52:45.224304122+02:00
completed: 2026-05-24T09:56:31.808258748+02:00
tags:
    - qa
    - stage-12
    - store-credit
claimed_by: shell-quartz
claimed_at: 2026-05-24T09:56:31.80825975+02:00
class: standard
---

Stage 12 Task 04 Subtask 4.1. Store Credit fixture product #1106 has product_type term arraysubs_store_credit and _arraysubs_credit_product=yes, but wp-admin product edit reload shows Product data selected as Simple product. Standard tabs Inventory, Shipping, Linked Products, Attributes remain visible; Credit Amount Type/Credit Amount/Bonus fields are absent. Expected Store Credit selected, standard tabs hidden, credit fields persisted. wc_get_product(1106)->get_type() returns simple because productClass maps arraysubs_store_credit to WC_Product_Simple.

[[2026-05-24]] Sun 09:56
Fix verified: Store Credit products now use ArraySubsPro\Features\StoreCredit\Services\StoreCreditProduct extending WC_Product_Simple and returning type arraysubs_store_credit. WP-CLI confirms wc_get_product(1106)->get_type()=arraysubs_store_credit and normal product #233 remains simple. Browser reload of product #1106 shows Product data=Store Credit, only General/Advanced/ArraySubs Redirect/Get more options tabs, and persisted fields Credit Amount Type=Fixed Amount, Credit Amount=50.00, Bonus Credit=10.
