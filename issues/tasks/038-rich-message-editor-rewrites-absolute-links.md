---
id: 38
title: Rich message editor rewrites absolute links relative to wp-admin
status: closed
priority: medium
created: 2026-09-18T18:38:51.546959+06:00
updated: 2026-09-18T18:44:02.229289+06:00
tags:
    - members-access
    - rich-text
class: standard
---

QA task: RM-1 / RM-5, scheduled 2026-09-18. Plan: qa/restricted-messages-2026-09-18/plan.md.
Subscription/order IDs: N/A.
User: WordPress admin, ID 1, administrator; guest verification in isolated session.
URL: http://localhost:10003/wp-admin/post.php?post=28477&action=edit ; page ID 28477.
Browser: agent-browser with Edge 153 (Chrome for Testing 149 has a separate Gutenberg blob-iframe failure).
Steps: select Restricted Content block; enter message, select it, add link http://localhost:10003/my-account/ with the editor link dialog; publish and reload.
Expected: the entered URL remains absolute and works from any frontend route.
Actual: TinyMCE saved ../my-account/, relative to the admin editor; nested frontend routes can resolve it to the wrong destination.
Proof: saved block message and frontend DOM contained <a href="../my-account/">. Screenshot: qa/restricted-messages-2026-09-18/evidence/gutenberg-reloaded.png.
Scope: shared core RichText field (CPT and per-post editors included); Elementor native WYSIWYG retained the full URL.
Fix plan: disable TinyMCE URL conversion in the shared editor, rebuild, repeat insertion/save/reload and inspect guest link destination.

Verified 2026-09-18: rebuilt with convert_urls disabled. Gutenberg and CPT link dialogs now save the full http://localhost:10003/my-account/ URL; reload and guest DOM retain the absolute href. Evidence: qa/restricted-messages-2026-09-18/evidence/gutenberg-guest-final.png and cpt-guest-final.png.
