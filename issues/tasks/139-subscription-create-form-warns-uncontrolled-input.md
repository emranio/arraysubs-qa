---
id: 139
title: 'Subscription create form warns: uncontrolled input becomes controlled (billing First Name / Address 1)'
status: open
priority: low
created: 2026-10-28T06:07:59.945031+06:00
updated: 2026-10-28T06:07:59.945031+06:00
tags:
    - bug
    - subscriptions
    - react
    - form-builder
class: standard
---

Found during the per-field translate icon QA (2026-10-28); pre-existing FormBuilder behaviour, not caused by that change.

- Active QA task ID / day / plan: N/A
- Affected subscription ID(s) / order ID(s): N/A
- Affected user: admin (ID 1, administrator)
- URL: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/form

Steps: type into billing First Name ("Enter First Name") and Address Line 1.

Expected: no console errors.

Actual: console [error] "Warning: A component is changing an uncontrolled input to be controlled. This is likely caused by the value changing from undefined to a defined value…"; component stack input → rc-field-form chunk → form chunk (no translate-field frames). Likely cause: Text fields receive value undefined until first change; FieldBase normalize() maps null → undefined, so rc-field-form renders the input uncontrolled first. Fix: default the field value to "" for text controls (e.g. initialValues or a normalize that returns "" for text fields).
