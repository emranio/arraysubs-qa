# Member details fix browser verification — 2026-10-23

Issue: #85. Targeted fix regression; prior lifecycle QA plans were not reused. Browser: real local WordPress site `http://localhost:10013`, isolated agent-browser session `member-free-qa`, authenticated administrator `admin` via the documented Local auto-login route. The remote site was not touched.

## Completed checks

| Check | Result | Evidence |
|---|---|---|
| Free subscription #8326 -> customer #224 | Complete member profile and subscription render; member endpoint HTTP 200 | `free-member-224-fresh.png`, `member-success-requests.txt` |
| Free search/select another customer and return | Rowan Sullivan #223, subscription #8323, selected with route `#/manage-members/223`; selecting Finley Sullivan returns route and profile to #224 | `search-rowan-selected.png`, successful #223/#224 request sequence |
| Free Edit Details, save original values | Disabled **Saving...** button with visible spinner; POST member endpoint HTTP 200; **Member details updated.** toast | `edit-save-inflight.png`, `edit-saving-snapshot.txt`, `edit-save-success.png`, `edit-saved-snapshot.txt` |
| Profile/address preservation | Account name/email/roles and all original billing/shipping values unchanged | `profile-values-check.json`, `customer-224-original.json`, `customer-224-after-save.json` |
| Free native submenu | Manage Members appears and is active; core MemberInsight provider booted | `free-member-224-fresh.png`, `free-runtime.json` |
| Free Reports card | Member Insights has an Open report link and no Pro badge | `free-reports.png`, `free-reports-snapshot.txt` |
| Free premium member additions | Store Credit statistic/action and feature entitlement action absent | `free-member-224-fresh.png` |
| Free subscription LIST customer shortcut | Finley Sullivan link opens `#/manage-members/224` and profile renders | `free-list-customer-link.png` |
| Free native User Edit shortcut | Heading Member Details link opens member #224 | `free-user-edit-shortcut.png` |
| Free WC Customers shortcuts | 24 registered rows each have exactly one member shortcut; existing customer #144 opens successfully in a new tab | `wc-free-shortcuts.json`, `free-wc-customers-shortcuts.png`, `free-wc-customer-144.png` |
| Free direct Pro URLs | Store Credit, Checkout Builder, Feature Log (user #224), Cancellation Flow show **Pro Feature** lock and issue zero new Fetch requests | `guard-*.png`, `guard-*-snapshot.txt`, `guard-new-requests.txt` |
| Pro member enrichment | Profile #224 still works; Store Credit statistic, Manage Store Credit, and Show Features links available | `pro-member-224.png`, `pro-member-snapshot.txt` |
| Pro provider alignment | Core and Pro MemberInsight providers each appear once in browser runtime | `pro-runtime.json` |
| Pro WC shortcut duplication | Each of the same 24 registered rows has exactly one Member details action; Store credits appears separately | `wc-pro-shortcuts.json`, `pro-wc-customers-shortcuts.png` |
| Final WC ordinary click | Fresh document displays row shortcuts without hover; ordinary click opens member #144 | `wc-final-visible.png`, `wc-final-ordinary-url.txt`, `wc-final-ordinary-success.png` |
| Final WC keyboard activation | From Finley Parker's name link, Tab focuses the visible Member details anchor; Enter opens #144 | `wc-final-keyboard-focus.json`, `wc-final-keyboard-focus.png`, `wc-final-keyboard-url.txt`, `wc-final-keyboard-success.png` |

## Fixtures

- Main subscription #8326, **Demo · Lifetime Resource Library**, active lifetime subscription; related order #8327.
- Main customer #224, **Finley Sullivan**, login `demo260927_220`, email `demo.260927.220@example.test`, roles `customer`, `demo_member`, `demo_premium`.
- Search-only customer #223, **Rowan Sullivan**, email `demo.260927.219@example.test`; related subscription #8323.
- WC shortcut-only customer #144, **Finley Parker**, login `demo260927_140`, email `demo.260927.140@example.test`; related subscription #7974.
- No fixtures were created or deleted by this browser verification.

The unchanged profile save performed ordinary WooCommerce bookkeeping: `last_update` changed and an absent `_arraysubs_additional_shipping_addresses` meta key was saved as empty. The original account/role/billing/shipping values remain identical; no restoration of these bookkeeping values was needed or performed.

## Runtime/evidence notes

The first post-fix navigation changed only the SPA hash, leaving the pre-fix native submenu/runtime in the existing document. A fresh query forced a complete PHP response and showed the correct Free submenu/provider. `free-member-224.png` captures that transitional stale menu; `free-member-224-fresh.png` is the verified current result.

Initial WC action-click attempts targeted WordPress `.row-actions` links while they were positioned offscreen. Their snapshots exposed the anchors even though screenshots did not show them. Hovering the username cell made the links visible, after which an ordinary click correctly opened member #144. `wc-member-link-click-repro.webm` and `wc-click-step-*` document the hidden-action attempts, not a backend or navigation defect. `wc-hover-visible.png` and `wc-hover-click-success.png` document the successful visible-action test. After the core shortcut container adopted WordPress's `visible` class, a fresh document showed row actions without hover and both ordinary click and Tab/Enter navigation passed. Keyboard evidence confirms an actual visible anchor focus rectangle and native focus ring. Final browser error output is empty (`browser-errors-final.txt`).

Some complete request inventories retain earlier baseline 404s from the same isolated session. `member-success-requests.txt` contains the verified post-fix 200 responses, including the unchanged profile POST.

## Restoration

Pro was reactivated after Free-only checks, before Pro/browser enrichment checks. The exact `active_plugins` array and `arraysubs_settings.modules` value match their initial values: see `state-initial.json`, `state-restored.json`, and `restoration-check.json`. No module switch, remote site, order/subscription action, or unrelated product edit was changed.

Only the isolated `member-free-qa` browser session was closed at completion; other agents' sessions were preserved. No further Pro activation change was performed after restoration.
