---
id: 19
title: Subscription Box uploads are publicly readable by URL (PDF/CSV allowed)
status: blocked
priority: medium
created: 2026-08-26T14:39:51.693780229+02:00
updated: 2026-08-30T14:54:05.724643183+02:00
started: 2026-08-30T14:54:05.72464178+02:00
tags:
    - security
    - subscription-box
    - uploads
blocked: true
block_reason: Plugin side is done and verified (signed, authorised endpoint; no raw upload URL is published anywhere; anonymous and tampered requests get 403). The raw path /wp-content/uploads/arraysubs-box-uploads/<name> still returns 200 because this host runs Caddy, which ignores the .htaccess the plugin writes. Needs a one-line Caddy deny rule for that path — a server change outside the plugins, not applied.
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


---

## Fixed (plugin side) — 2026-08-30 · one server rule still outstanding

`UploadHandler` now serves box uploads through an authorised endpoint instead of publishing the uploads URL:

* `getDownloadUrl()` builds `?arraysubs_box_file=<relative>&sig=<hmac>`; `handleDownloadRequest()` (on `template_redirect`) verifies the signature, then authorises — `manage_woocommerce`/`edit_shop_orders` always, otherwise only the customer of an order or subscription whose frozen contents reference that file — and streams it as `Content-Disposition: attachment` with `X-Content-Type-Options: nosniff`.
* Every render site builds the link from the stored **path**, so snapshots frozen before this change stop handing out the public URL too: `OrderHooks::linkUploadedInputFile()`, the customer portal (which previously showed plain text — that was issue #18 item 2), and the admin subscription detail payload.
* The upload directory gets `.htaccess` (deny) and `web.config` alongside the existing `index.html`; the orphan cleaner skips all three.

**Verified:** order-received, customer portal and admin SPA all link to `?arraysubs_box_file=…&sig=…`. Anonymous GET → **403**; tampered signature → **403**; admin GET → file.

### Outstanding — needs a web-server rule on this host

`GET /wp-content/uploads/arraysubs-box-uploads/<name>` still returns **200**. This site is served by Caddy, which ignores `.htaccess`, so no plugin-side change can block it. The path is no longer published anywhere and the basename is 20 random alphanumerics (~119 bits), so it is not guessable — but to close it completely add a rule to `/home/server-manager/caddy/` for this site:

```
@arraysubs_box_uploads path /wp-content/uploads/arraysubs-box-uploads/*
respond @arraysubs_box_uploads 403
```

then `caddy validate --config /etc/caddy/Caddyfile && systemctl reload caddy`. Not applied — it is a server change outside the plugins.
