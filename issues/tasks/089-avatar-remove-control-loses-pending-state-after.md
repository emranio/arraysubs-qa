---
id: 89
title: Avatar Remove control loses pending state after uploading a new photo
status: closed
priority: medium
created: 2026-10-23T02:34:57.611485+06:00
updated: 2026-10-23T02:45:07.885727+06:00
started: 2026-10-23T02:35:16.09962+06:00
completed: 2026-10-23T02:45:07.873125+06:00
tags:
    - bug
    - customer-portal
    - profile-builder
    - avatar
parent: 86
class: standard
---

Source-confirmed during active QA #86; plan qa/storefront-account-boundaries-2026-10-23/plan.md; scheduled day 2026-10-23 targeted manual run.
Affected subscription/order IDs: N/A. Affected existing WordPress user/customer/avatar IDs: N/A; no existing avatar will be mutated. Browser context: customer My Account > Account Details, local http://localhost:10013/my-account/edit-account/; Profile Builder module and avatar setting must be enabled.
Reproduction: open Account Details without an avatar, upload a photo, then begin another upload or remove the newly created avatar; repeat after removing and re-uploading without refreshing. Expected: active Remove control and upload input block overlapping work, and Remove confirmation/button show a visible loading state. Actual source: profileFields.js caches removeBtn once at line55 but dynamically creates/replaces it around line218; setUploading only disables the stale reference. Remove deletion runs after confirmDialog resolves and has no async confirmation loading state. Proof: exact source references; browser reproduction pending, do not claim a verified visual pass. Scope note: existing server-rendered Remove does disable, but created/replaced button does not.
Plan before implementation: use shared confirmDialog async onConfirm and setButtonLoading for current Remove only; keep file-input label markup intact, resolve Remove dynamically, add processing/dialog guards, restore state on failure/cancel, preserve immediate avatar preview and file controls. Isolated source-only worktree, no build, lint/PHPCS, fixture/user/avatar mutations or draft edits. Lead owns final build and disposable-avatar browser verification.

[[2026-10-23]] Fri 02:37
Implementing source only in isolated /tmp/arraysubs-avatar-ui, branch codex/profile-avatar-loading. Current Remove is now resolved dynamically; processing/dialog guards serialize upload and removal; DELETE runs inside shared confirmDialog async onConfirm with shared setButtonLoading on the real current Remove button, while the original file input remains mounted. Added translatable Removing… text to the existing runtime config. Existing-avatar site state remains untouched.

[[2026-10-23]] Fri 02:38
Source validation: 24 isolated DOM checks passed using the real shared uiHelpers (confirmDialog/setButtonLoading) with deferred fetch responses; no site/network/avatar writes. Covered first upload creating Remove, blocking a second action during upload, duplicate confirmation guard, async modal and actual Remove spinner, API/network failure retained dialog retry, cancellation, remove/re-upload replacement, and preserving the original file input node. Harness /tmp/arraysubs-avatar-ui-check.cjs. Changed files are profileFields.js (309 lines) and ProfileFields/Services/Hooks.php (123 lines). No lint/PHPCS or build run; lead handles real disposable-avatar browser verification and changelog.

[[2026-10-23]] Fri 02:38
Source committed as e8a14b082fd1afce8cd7b5ad03783b7fe322ae96 on codex/profile-avatar-loading; clean isolated worktree /tmp/arraysubs-avatar-ui. Two source files only; source validation passed. Root owns integration/build/changelog and disposable-avatar browser acceptance. Issue remains in-progress pending that verification.

[[2026-10-23]] Fri 02:45
Browser acceptance confirmed by lead/qa_access using only disposable WordPress customer #387 (qa-avatar-jlzmwqexqd / qa-avatar-jlzmwqexqd@example.invalid; customer role), fixture record qa/storefront-account-boundaries-2026-10-23/avatar-fixture.json. Existing customer avatars were untouched; subscriptions/orders N/A. Real My Account Account Details test: first upload creates working Remove; replacement upload shows disabled Change/Remove controls and Uploading; cancel preserves photo; confirmed DELETE runs inside the custom modal with spinner/disabled controls; re-upload creates a new working Remove; second DELETE restores the default avatar and Profile photo removed. No Account Save submitted. Evidence under qa/storefront-account-boundaries-2026-10-23/browser/after/: avatar-387-flow.webm, avatar-replacement-inflight.png, avatar-remove-inflight.png, avatar-remove-cancelled.png, avatar-second-remove-final.png, plus related snapshots. Source commit e8a14b082fd1afce8cd7b5ad03783b7fe322ae96 is merged and lead build passed. Peer read-only review found no actionable issue; 24 isolated source interaction checks had passed before integration. Closing after browser acceptance; lead owns restoring the originally absent avatar option and deleting disposable #387 as part of QA #86 cleanup.
