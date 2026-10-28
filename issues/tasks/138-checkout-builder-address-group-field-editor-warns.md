---
id: 138
title: 'Checkout Builder address-group field editor warns: setState during render (onSave inside setFieldsConfig updater)'
status: open
priority: low
created: 2026-10-28T06:07:59.930432+06:00
updated: 2026-10-28T06:07:59.930432+06:00
tags:
    - bug
    - checkout-builder
    - react
class: standard
---

Found during the per-field translate icon QA (2026-10-28); pre-existing React warning, not caused by that change.

- Active QA task ID / day / plan: N/A
- Affected subscription ID(s) / order ID(s): N/A
- Affected user: admin (ID 1, administrator)
- URL: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/checkout-builder → Billing Address → Edit element

Steps: type into a per-field label override input (e.g. "Label for billing_phone") or toggle a field switch, then read the console.

Expected: no React warnings while editing override labels.

Actual: console [error] "Warning: Cannot update a component (`Us`) while rendering a different component (`ke`)…" (logged once per page load). Cause: arraysubs/src/resources/pages/CheckoutBuilder/FieldSettingsPanel.jsx GroupFieldsConfig handleToggle/handleLabelChange call onSave({...field, fields_config: updated}) inside the setFieldsConfig((prev) => …) updater, which React runs during render. Fix: compute the next config outside the updater (or call onSave in an effect after state settles).

Related observation on the same screen: each per-field label input is pre-filled with the WooCommerce default label as its value (not as placeholder), so a merchant cannot tell an override from the default until clearing the input.
