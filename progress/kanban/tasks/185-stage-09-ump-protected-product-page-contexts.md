---
id: 185
title: 'stage-09: UMP protected product page contexts'
status: closed
priority: high
created: 2026-06-28T17:21:47.640843963+02:00
updated: 2026-06-28T17:34:27.425137772+02:00
started: 2026-06-28T17:33:53.989882301+02:00
completed: 2026-06-28T17:34:27.42513708+02:00
tags:
    - qa
    - member-access
    - ump
    - regression
class: standard
---

Focused QA for Ultimate Membership Pro / Indeed-protected WooCommerce product pages across logged-out guest, logged-in non-member, and valid member/admin contexts. Watch for is_type() on string, white screen/500, missing product summary, broken restriction message, and allowed-user add-to-cart UI.

\nQA note 2026-06-28: Indeed Membership Pro is active, but wp_ihc_woo_products, wp_ihc_woo_product_level_relations, wp_ihc_memberships, and wp_ihc_user_levels returned no configured product/member rows. No exact client-configured UMP-protected WooCommerce product exists in this environment, so guest/non-member/member UMP context testing is N/A for this run. Product-page regression still covered with Indeed active on standard/pro product pages.
