---
id: 39
title: CPT rich messages render inside invalid paragraph wrappers
status: closed
priority: medium
created: 2026-09-18T18:41:25.363122+06:00
updated: 2026-09-18T18:44:02.248461+06:00
tags:
    - members-access
    - rich-text
class: standard
---

QA task: RM-3 / RM-4, scheduled 2026-09-18. Plan: qa/restricted-messages-2026-09-18/plan.md.
Subscription/order IDs: N/A.
User/customer: WordPress admin, ID 1, administrator; guest (no user ID).
URL: http://localhost:10003/qa-rich-post-rule-2026-09-18/ ; admin http://localhost:10003/wp-admin/post.php?post=28480&action=edit . Page IDs: 28479 (CPT), 28480 (per-post).
Browser: agent-browser, Edge admin and Chrome guest.
Steps: set login-only per-post restriction, type a message and italicize it, apply, publish; visit as guest.
Expected: one formatted paragraph without extra whitespace or invalid nested paragraphs.
Actual: server wrapped editor-generated paragraphs inside another p; browser repaired it into <p></p><p><em>Post members only</em></p><p></p>.
Proof: DOM observation and qa/restricted-messages-2026-09-18/evidence/post-rule-guest.png.
Scope: shared ContentGating formatter for CPT and per-post denial messages; the block/Elementor formatter was already corrected in this change.
Fix plan: remove the outer paragraph, apply wpautop and wp_kses_post, recheck both guest pages.

Verified 2026-09-18: removed the outer paragraph and formatted with wpautop + wp_kses_post. CPT and per-post guest DOM now contain a single formatted paragraph with no empty surrounding p elements. Supplemental rendering assertions pass.
