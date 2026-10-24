# Cancellation flow editor verification

Completed 2026-10-24 (Asia/Dhaka) against the actual local WordPress site at http://localhost:10013. Production build passed. All 49 recorded checks passed: 29 admin checks, 8 frontend checks, and 12 runtime checks.

The settings page now opens with a visual customer journey, cancellation-reason summary, retention status, and four offer cards with reason targeting, custom copy, and eligibility conditions. Edit opens a fullscreen editor in the requested seven-step order. Existing helper text, alerts, discount gateway limitations, and downgrade configuration guidance remain with their fields. The final review supports editing individual sections and explicitly saving.

## Admin verification

Verified all steps and their order, reason add/edit/remove, unsaved reason choices in offers, settings retained between steps, master-off messaging, editable review, explicit save/reload, failed-save recovery, keep editing, and discard. Reviewed screenshots at desktop and 390px phone widths. The phone editor has no horizontal overflow and keeps its active progress step visible. Sunrise admin colors follow the WordPress theme variables. Invalid numeric bounds, fractional billing cycles, min/max eligibility ranges, and missing enabled support URLs are rejected. Submitting review returns to and focuses an invalid field on an earlier step; Enter in a valid input advances to the next step.

QA issue [109](../issues/tasks/109-cancellation-editor-continue-can-save-before.md) caught an early implementation where Continue could be reconciled into a submit control during the same click and save prematurely. Separate keyed controls and explicit form submission fixed it. Browser regression confirmed review remains unsaved until Save is activated.

## Actual My Account verification

Used disposable customer 460, subscriptions 20784, 20788, 20791, 20794, 20797, and 20800, products 20780/20782, and a completed fixture order 20806 for lifetime-spend eligibility. No payment gateway was charged.

- Required reason kept Continue disabled until a choice was selected. The edited reason label appeared after the admin save.
- Price reasons showed the saved 23% discount and approved smaller plan. Accepting the discount changed the displayed recurring amount from 40.00 to 30.80 for two renewals. Runtime values confirmed the accepted percentage and duration.
- A temporary-break reason showed the matching pause offer. Accepting it changed the subscription to Paused and exposed Resume Now.
- Accepting the downgrade scheduled the configured 20.00 plan for the next renewal; the pending-switch target and price were verified.
- Technical issues showed the support offer and configured button text. Its link opened the configured Member Support page, leaving the subscription active.
- Turning retention off through the editor allowed ordinary cancellation and recorded the selected reason.
- With Pro inactive, the core customer portal retained edited reasons and successfully cancelled with an optional blank reason.

Screenshots and check results are in [evidence](evidence/). The build log is [build.txt](evidence/build.txt). Runtime checks also confirm that saving preserves the unedited skip offer, existing customer form configuration, other offer settings, and unrelated settings.

## Cleanup and limits

[Restoration verification](evidence/restoration.json) passed for original cancellation settings, customer cancellation policy, unrelated settings, active plugins, and settings-save timestamp. Both test users, six subscriptions and their jobs/retention rows, two products, and the fixture order were removed. Short-lived auth state was invalidated and local private state files removed. Existing unrelated repository edits and QA issues were preserved.

Tests used isolated agent-browser CLI sessions and real browser flows. Only the failed-save response was intercepted to verify error recovery; successful saves and customer actions used the actual site. Empty-input clearing and modal transition timing required adjustments to the browser driver. The original temporary admin auth state stopped working, so remaining checks used disposable administrator 461. Browser captures reported no uncaught errors. Existing React controlled-input and jQuery/Elementor deprecation warnings were observed. No Composer installation, phpcs, version bump, release, or push was performed.

## Easy Setup design correction

The final editor directly reuses Easy Setup's progress component and its fullscreen modal, content, step-header, and footer styles. The custom sidebar and its layout rules were removed. A browser comparison of both modals matched viewport dimensions, progress position, content padding, footer padding, and background exactly. Seven-step navigation, review, edit links, discard, and mobile overflow checks passed. No settings were saved during this correction.

Latest screenshots: [desktop](evidence/corrected-easy-setup-design.png), [mobile](evidence/corrected-easy-setup-mobile.png), and [Easy Setup reference](evidence/easy-setup-reference.png). These supersede the earlier editor layout screenshots.

## Reason item actions

Cancellation reason rows now use a SquarePen edit icon and Trash2 delete icon. The real browser showed eight edit icons and eight trash icons. Editing the sixth reason opened only its fields. Deleting that reason reduced the list from eight to seven and left the other reasons intact. Browser edits were discarded; the saved-settings hash remained unchanged. Production build and source whitespace checks passed. A screenshot attempt hit a CDP capture timeout; DOM and interactive snapshots verified the icons and actions.

Reason actions were restyled to match the supplied reference: 28px gray outline icons, 16px spacing, and transparent controls without individual borders or backgrounds. Browser-computed styles confirmed those values; the rendered screenshot is evidence/reason-icon-reference-style.png. The production build passed. This styling change is scoped to the cancellation editor and saved settings were not changed.

Both wizard modals now center their headers, content, and footers within a 1200px maximum width while keeping the modal itself fullscreen. Browser geometry checks passed for both modals at 1920x1080 and 390x844: centered widths of 1200px and 390px respectively, full viewport modal dimensions, pinned footers, and no horizontal overflow. Results: evidence/modal-1200px-checks.json. Production build passed; no saved settings were changed.
