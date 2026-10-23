---
id: 100
title: Elementor unversioned custom-message settings lose conditions during display filtering
status: closed
priority: critical
created: 2026-10-24T03:43:48.303379+06:00
updated: 2026-10-24T03:47:11.345871+06:00
started: 2026-10-24T03:43:59.084788+06:00
completed: 2026-10-24T03:47:11.333442+06:00
tags:
    - restriction
    - compatibility
class: standard
---

Active QA task: 2; scheduled 2026-10-24; plan qa/restriction-legacy-compatibility-2026-10-24/plan.md. Subscription/order IDs: N/A. User/customer: guest ID0; disposable customer440; admin1; member224; roles guest/customer/administrator/demo_premium. URL http://localhost:10013/qa-legacy-elementor-20261024/ (page20081), guest agent-browser legacy-guest. Reproduction: fixture.php state transitional_custom writes arraysubs_custom_message_enabled=yes, login_required=yes, nonexistent role and product, ANY, no version/mode. Navigate actual page. Expected: guest denied with saved custom message. Actual: QA LEGACY PROTECTED ELEMENTOR appears. Proof: evidence/pro-transitional_custom-guest-elementor.txt and evidence/transitional-custom-guest-elementor-failure.png in active QA folder. Scope: per-post and Gutenberg pass same state; all old-mode Elementor cases passed. Runtime adapter test alone passes raw settings; actual get_settings_for_display conditional filtering likely removes unversioned transitional conditions before applyRestriction. Fix plan: preserve actual saved fields during runtime display filtering for transitional/current records as well as legacy, retaining resolved dynamic values; rerun original browser matrix and runtime.

Fixed: runtime now overlays actually stored fields for every configuration format before evaluating restrictions, preserving resolved dynamic values. Retest: Probrowsermatrix288/288 passed; transitional guest renders QA LEGACY CUSTOM saved message and hides protected heading. Actual native Elementor runtimecases pass in589/589 Pro and589/589 core matrices. Preserved failure snapshot: activeQA evidence/transitional-custom-guest-elementor-failure.txt; postfix screenshot evidence/transitional-custom-guest-elementor-fixed.png. No exposed content after fix.
