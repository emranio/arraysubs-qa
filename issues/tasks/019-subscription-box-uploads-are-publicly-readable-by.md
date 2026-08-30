---
id: 19
title: Subscription Box uploads are publicly readable by URL (PDF/CSV allowed)
status: open
priority: medium
created: 2026-08-26T14:39:51.693780229+02:00
updated: 2026-08-26T14:39:51.693780229+02:00
tags:
    - security
    - subscription-box
    - uploads
class: standard
---

**QA task ID / scheduled day:** N/A — ad-hoc product-edit regression cycle, 2026-08-26. Report: `qa/product-edit-regression-qa-report.md`
**Browser/user context:** agent-browser, session `admin` (WP user 1, administrator); session `guest` (logged out).
**Affected subscription ID(s):** 33278
**Affected order ID(s):** 33277
**Affected user(s):** any unauthenticated visitor holding the URL

**Test URL:** https://mirror-help.arrayhash.com/wp-content/uploads/arraysubs-box-uploads/pDwqQLRwgUlKSfz3aVkF.png

## Steps
1. Build box 33263, attach a file to its `Upload` element, complete the order.
2. Fetch the stored file URL with no cookies.

## Expected
Customer-supplied attachments on an order should not be world-readable; the Members Access download rules already have a signed/PHP-served mechanism.

## Actual
```
GET /wp-content/uploads/arraysubs-box-uploads/pDwqQLRwgUlKSfz3aVkF.png   -> 200  (unauthenticated)
GET /wp-content/uploads/arraysubs-box-uploads/                            -> 404  (listing blocked)
```
Directory contents:
```
index.html                       0 bytes
pDwqQLRwgUlKSfz3aVkF.png        75 bytes
```
Protection is a 20-character random basename plus an empty `index.html` — security by obscurity only. There is no `.htaccess`/nginx deny and no signed-URL layer.

## Scope notes
`BoxConfig::UPLOAD_TYPE_GROUPS` allows **images, PDF and CSV**, so a merchant who asks for an ID document, a signed form or a customer list gets the same protection level. Consider serving these through an authenticated PHP endpoint keyed to the order/subscription owner.
