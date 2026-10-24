# Responsive admin header verification

Completed 2026-10-24 (Asia/Dhaka). Targeted fix for QA issue #26; no active QA execution plan, scheduled task, or scheduled day. Subscriptions/orders/customer fixtures: N/A. Browser: real local WordPress site http://localhost:10013, authenticated admin (administrator), isolated agent-browser session arraysubs-header-local. The documented live mirror was inspected as an additional baseline; the changed assets were verified on the actual local development site.

The shared core header now grows with its content, uses compact wrapping navigation links above 782px, and provides a full-width native section selector at 782px and below. Mobile selector labels identify the section; the selected option follows the longest matching route. Links to external WordPress admin pages retain their destination. Pages without child navigation use a semantic page heading. The header gutters and sticky offset follow WordPress's 782px and 600px breakpoints. Focus and active accents use the current WordPress admin theme variables.

The implementation lives in ArraySubs core. Both core and premium menu entries use the shared component; Pro files and the Pro changelog draft did not require a change. Browser verification ran with Pro active, including premium Member Access and Store Credit sections. The optional Overview trial card was not displayed by this licensed site's current offer state; its container remains flexible, bounded, and stacked on mobile.

## Browser results

- 65 layout cases passed across all 41 visible routes that use the shared header. Every shared-header route was checked at 390px. Checkout Builder is a separate full-screen interface and does not mount this header.
- Settings was checked at 320, 390, 600, 601, 768, 782, 783, 820, 960, 1024, 1280, 1440, and 1920px. Member Access, Audits, Profile, Subscriptions, Home, and Help also passed at 820 and 1440px.
- All measured headers stayed inside the viewport, with no internal horizontal overflow, navigation/subtitle overlap, or clipped header content. Two-row navigation at 783/820px expands the header instead of overlapping page content.
- Mobile Settings navigation General -> Refunds and browser Back -> General passed. Keyboard typeahead selected Toolkit and updated both route and selected option after the route settled. Nested pages select their own entry, including New custom subscription, Credit History, and Member Access rule pages.
- Desktop tabs navigate correctly. Tab/Shift+Tab show an explicit visible focus ring, matching the active admin accent (#3858e9 in this site's Modern scheme).
- Selecting Emails opens the real WooCommerce email settings screen at admin.php?page=wc-settings&tab=email.
- Scrolling passed: header top is 0px at width 390 after the absolute WordPress toolbar scrolls away, 46px at width 768 beneath the fixed tablet toolbar, and 32px at width 1440 beneath the desktop toolbar.
- Final heading specificity was checked against WordPress's .wrap h1 rules: 18px text, zero extra padding, and a 77.69px mobile Help header. Singleton routes were rechecked after that refinement.
- Production build passed. Final browser error list was empty. No lint or PHPCS was run. Changed source files contain 110 and 496 lines, below the 3000-line limit.

## Evidence

Measurements: [measurements.json](measurements.json). Interaction observations: [interactions.json](interactions.json). Build: [build.log](build.log).

- [Mobile before](evidence/settings-390-before.png), [mobile after](evidence/settings-general-390.png), [320px](evidence/settings-general-320.png).
- [Wrapping at 783px](evidence/settings-general-783.png), [desktop](evidence/settings-general-1440.png), [Member Access at 820px](evidence/members-access-820.png).
- [Mobile keyboard navigation](evidence/mobile-keyboard-navigation.png), [desktop keyboard focus](evidence/desktop-keyboard-focus.png), [mobile sticky header](evidence/sticky-390.png).

## Separate content issue

QA issue #110 records pre-existing horizontal overflow from the main Subscriptions list (document width 674-675px) and Credit History table (598px) at a 390px viewport. Their headers remain exactly 390px wide. This does not invalidate the header checks; other checked mobile pages retain a 390px document width. Proof is in the two *-content-overflow.png images and interactions.json. No records, settings, balances, or subscription lifecycle data were changed.
