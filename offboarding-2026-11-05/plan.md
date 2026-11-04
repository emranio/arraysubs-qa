# Offboarding QA - 2026-11-05

Scope: build both plugins and verify the implemented offboarding flow in a real browser, including the production portal records. This is focused feature verification, not a full plugin release sweep.

Targets: local checkout at http://localhost:10013/ (documented LocalWP site), production https://user-portal.arrayhash.com/ and contact https://arrayhash.com/contact/. Local credentials: user-provided LocalWP admin; live portal uses the user's existing signed-in Edge session. Additional access-control and failure tests use an isolated local portal/schema with run-owned accounts. Do not submit the external contact form. The user's later request explicitly authorizes offboarding admin emails to emran@arrayhash.com; test the new notification through a labelled browser-generated event after deployment.

Schedule (Asia/Dhaka): start 00:39 on 2026-11-05; sequential phases build/baseline, popup/contact, deactivation/recovery, portal review, restoration/report. Complete each scenario and its state restoration before advancing; no recurring watcher.

Progress tasks: #1 build/baseline; #2 popup/contact; #3 deactivation/recovery; #4 portal records/access; #5 restoration/report; #6 admin notification email (added by user request during QA).

## Test matrix

- Core and Pro production builds, POT generation and catalog refresh.
- Popup opening, cancellation, Escape, keyboard focus and narrow viewport.
- Seven optional multi-select reasons; textarea toggles, entered text and empty selection.
- Get Help Directly opens contact in another tab, keeps plugin active and records one matching help event. Repeat interaction produces a separate event.
- Pro deactivation with selected reasons; core deactivation after Pro is inactive; reactivation.
- Empty reasons, direct and bulk deactivation; per-event uniqueness.
- Delivery failure preserves local receipt and allows deactivation; recovery acknowledges once.
- Production portal list/detail fields match browser-generated events; filters/search; unauthenticated/nonadmin access denied.
- Admin email uses the existing subscriber mailer, contains the matching event details and record link, and does not duplicate on retries. Verify failures/recovery in isolated tests and a real production send in the portal email log.
- Restore original plugin activation state and any targeted test settings. Retain clearly labelled QA records as evidence.

## Baseline and mutation policy

Both plugins initially active, core 2.1.0 and Pro 1.3.0. No unrelated plugins/settings will be changed. Saved Help recipient will be read without changing notification settings, preventing notification emails. Feedback text uses OFFBOARD-QA-20261105. Subscription/order fixtures are N/A. All mutations and outcomes go in coverage/report.
