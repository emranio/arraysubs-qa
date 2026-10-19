# Staging QA scope (verbatim from the owner)

Test the developer-support changes against the **official WooCommerce Stripe Gateway in test mode**, using real browser checkout, Stripe payments/webhooks, and Action Scheduler processing.

Verify staging contains these source revisions and matching built assets:

- **Core:** `a5c9711fdc955a54b7c396827fd7b223b48408ef`
- **Pro:** `561ed9235bb4859c2ec8ae1d809f8240f3d071e7`

Matching version numbers alone are insufficient.

Before testing, follow the staging workspace's `AGENTS.md`, preserve existing QA issues, and create a fresh focused QA plan, board and execution schedule. Use isolated admin/customer/guest `agent-browser` sessions, test-owned fixtures, WP-CLI with `--allow-root`, and the staging email sink.

Confirm Stripe test credentials, scheduler operation, and actual webhook health. Verify the ArraySubs secondary endpoint subscribes to every event returned by `StripeWebhookProvisioner::getRequiredEvents()`, including `payment_method.attached` and `customer.updated`. Record configuration without exposing secrets.

Run this matrix:

| ID | Test | Required result |
|---|---|---|
| S01 | **Checkout and saved method** | Complete successful recurring purchases through Classic and Block checkout. Verify correct customer/order/subscription relationships, price/currency, active entitlement, reusable payment method, and future renewal actions. Separately verify an initial payment failure does not grant successful-payment benefits. |
| S02 | **Automatic due renewal** | Generate the invoice and run the actual due payment action using the saved method. Verify one successful collection, correct renewal order/history, completed-payment count, billing-date advancement, and aligned invoice/payment jobs for the next cycle. |
| S03 | **Decline and authentication** | Cause genuine test-mode renewal failures and an off-session payment requiring authentication. Verify unpaid order state, useful customer/admin feedback, failure/recovery snapshots, and the customer verification/payment path. Authentication-required payments must remain recoverable without duplicate collection. |
| S04 | **Retries and grace** | Verify automatic retry timing/counts, retry-disabled behavior, configured hard-decline handling, and exhaustion. Check entitlement through the configured grace windows, then on-hold/cancellation when applicable. Retry exhaustion alone must not imply grace has ended. Include individual grace overrides and settlement guards. |
| S05 | **Recovery and missed callback** | Replace/fix the payment method and recover through supported customer/admin retry paths. Verify one settlement, cleared retry state, correct access and future dates. Test a successful Stripe payment whose local callback was delayed: pre-retry verification must reconcile it without charging again. |
| S06 | **Replay, races and late events** | Redeliver real signed test webhooks, overlap callback delivery with a retry, and deliver an older event after a newer renewal. Verify no duplicate payment, billing advancement, feature-cycle transition or credit adjustment. A late failure must not downgrade an already-paid order. Invalid signatures must cause no mutation. |
| S07 | **Billing-cycle feature allowance** | Consume usage before renewal. Successful paid renewal starts one new allowance cycle. Invoice creation, failed attempts and date edits do not reset usage. Consume usage after payment, replay its callback, and confirm that usage survives. Check isolation between two subscriptions belonging to one customer. |
| S08 | **Trials and cancellation** | Exercise saved-method trial conversion and its first collection. Test scheduled cancellation, undo, immediate cancellation, and cancellation with a pending invoice/payment. Verify access, lifecycle events and remaining jobs. Cancelling one subscription must preserve benefits supplied by another valid subscription. |
| S09 | **Paid and deferred plan switches** | Complete an immediate paid upgrade through Stripe and replay its callback. Verify one charge/finalization. For an apply-at-renewal switch, retain the current plan/access until the target-plan invoice is paid. Verify invoice freezing, failure/retry behavior, and paid-but-unprocessed finalization state. |
| S10 | **Store Credit with Stripe** | With Pro and renewal auto-apply enabled, verify one ledger-backed excess-credit grant per switch, including replay and an intervening balance adjustment. Test partial-credit renewal, fully credit-funded renewal, and failed-payment reversal/retry. Stripe must collect only the remaining amount. With Pro absent, Store Credit off, or auto-apply off, excess-credit switches must defer to renewal with accurate messaging. Availability changes after preview must require reconfirmation or a recoverable refusal. |

Run the ordinary Stripe checkout, renewal, failure/recovery and replay cases with **core alone and core plus Pro**. Verify Pro-only feature/credit behavior separately. Restore original plugin activation and settings afterward.

For applicable cases, capture actual `arraysubs_lifecycle_event` notifications and compare them with authoritative developer snapshots: status, current/pending plan, recovery, order references and schedule. Verify scalar payloads and usable operation identities. Business effects must remain idempotent; **do not require exactly-once notification delivery**.

Return:

- PASS/FAIL/BLOCKED for every case and plugin configuration.
- Exact customer, subscription, order, Stripe payment/event and scheduled-action references.
- Browser screenshots, relevant email evidence, redacted webhook/payment logs, before/after snapshots and ledger evidence.
- Reproduction steps linked to existing or newly recorded QA issues.
- Exact fixture cleanup and settings-restoration results.

Use real provider outcomes rather than manually marking orders paid or firing PHP hooks as payment proof. Keep action execution and cleanup confined to recorded fixtures. This is a verification scope; report defects before making unrelated changes. Manual publication and licensing work remain excluded.

---

# Addendum from the lead (facts for this run)

- **Staging = the live test site** https://mirror-help.arrayhash.com (WordPress at /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public). Its plugins are the git working trees in /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/{arraysubs,arraysubspro}. Admin credentials and site rules: plugins/AGENTS.md (read it first and follow it: agent-browser usage, QA workflow, `qa/issues/` rules and required issue fields, kanban-md from the board directory, WP-CLI with `--allow-root`, Mailpit via `mailpit-agent`).
- **Revisions on staging now:** Pro HEAD `561ed9235bb4859c2ec8ae1d809f8240f3d071e7`. Core HEAD is `cfcfaac` (local commit, not pushed yet), which descends from the requested `a5c9711fdc955a54b7c396827fd7b223b48408ef` plus two newer fixes under test as part of this run:
  - `44036ed` fix(gateways): PayPal error status reset on recovery; Mollie failure classification, webhook failures through the retry ladder, one-click Retry Payment (adds `RenewalProcessor::recordProviderPaymentFailure()`).
  - `cfcfaac` fix(integrations): each payment failure and each charge attempt gets its own `arraysubs_lifecycle_event` `event_id` (renewal_failed keyed on the recorded failure; attempt/result events keyed on a per-subscription attempt sequence in `_arraysubs_lifecycle_attempt_seq`).
  Verify: `git merge-base --is-ancestor a5c9711... HEAD` in arraysubs, working tree clean, Pro at 561ed92 and clean, and built assets current: `git diff --stat a5c9711..HEAD -- src/resources` is empty for core (the newer commits are PHP only), and `public/build` assets were built from that JS source today (both plugins were rebuilt after the pull; record build file mtimes/hashes). Report the actual HEADs.
  - Pay special attention to the two newer fixes: lifecycle `event_id` uniqueness per failure/attempt (S03/S04/S05/S06) and that nothing in Stripe renewals/retries changed behavior.
- **QA records:** create a fresh folder `plugins/qa/stripe-dev-support-2026-10-18/` with the plan, an execution schedule, and a fresh kanban-md progress board (initialize it inside that folder and run kanban-md only from there). Log defects in `plugins/qa/issues/` (kanban-md run from that directory) with every required field from AGENTS.md. Preserve all existing issues. Earlier developer-support artifacts in `plugins/qa/developer-support-2026-10-18/` belong to a previous run: read them for context, do not modify them.
- **Lifecycle capture:** to capture real `arraysubs_lifecycle_event` notifications, you may add a test-only must-use plugin in `wp-content/mu-plugins/` that appends each event name and payload (scalars) as JSON lines to a file inside your QA folder, limited to fixture subscription IDs you list. Remove it during cleanup and record that.
- **Fixtures:** create your own customers (use example.com emails), products (for example a daily subscription product so due dates arrive quickly), and subscriptions; record every ID in the plan. Bring due dates forward only on fixtures, with the shipped API (`\ArraySubs\Developer\Subscriptions::fromId( $id )->setNextPaymentDate( time() + 120 )`), then run the specific fixture actions (`wp action-scheduler action run <id> --allow-root`) or let the scheduler run them. Never run or cancel non-fixture actions.
- **Stripe:** official gateway 10.9.0 is connected in test mode. Use Stripe test cards (for example 4242 4242 4242 4242 success; 4000 0000 0000 0341 attaches but fails when charged later; 4000 0000 0000 9995 insufficient funds; 4000 0027 6000 3184 always requires authentication) per https://docs.stripe.com/testing. Replays: re-send stored webhook payloads with a valid signature computed from the endpoint's signing secret (never print the secret); an invalid-signature delivery must change nothing.
- **Core alone:** deactivate ArraySubs Pro with WP-CLI only for the core-alone runs, then reactivate it and confirm the original active plugin list: arraysubs, arraysubspro, woocommerce, woocommerce-gateway-stripe, wp-mail-smtp.
- **Settings:** snapshot every setting you change (retry ladder, grace, store credit, plan switching, auto-renew toggle) before changing it, and restore it exactly afterward; prove restoration with a before/after diff.
- **Secrets:** never print or store API keys, webhook secrets, or full customer card data in reports or evidence.
- Do not change plugin code. If a case fails, record the defect with reproduction steps and continue; the lead decides on fixes.


---

# Restart addendum (2026-10-19)

- **Restart.** The first attempt (2026-10-18) stopped in phase 1 when the site database became unreachable (`Error establishing a database connection` at 15:45 during fixture setup; see `evidence/fixtures-phase1-setup.stderr`). No fixtures were created, the progress board is empty, and the capture mu-plugin was never installed. The DB is reachable again. Treat this as a fresh run in the same folder: reuse the existing `scripts/` after reading them, re-run preflight (keep the old preflight files but write new ones with a `-r2` suffix), and re-take the settings snapshot (`snapshots/settings-original-r2.json`) unless you prove it is identical to `snapshots/settings-original.json`. Record the outage and restart in the plan.
- **Revisions under test now** (supersede the HEADs in the earlier addendum):
  - Core HEAD `4791832` (local, not pushed) on top of `cfcfaac` and `44036ed`, descending from `a5c9711`. `4791832`: the customer shipping-address route (core, both editions) fires `arraysubs_shipping_address_update_failed` on refusals and `arraysubs_shipping_address_updated` on success; `cancelOverdueSubscription()` passes the unpaid renewal order as a second argument to `arraysubs_data_cancelled_overdue`, and the `grace_ended` lifecycle event carries that `order_id` with an operation-scoped `event_id`.
  - Pro HEAD `edaa401` (local, not pushed) on top of `561ed92`: removed Pro's unreachable duplicate customer shipping-address POST route.
  - Verify ancestry of `a5c9711` (core) and `561ed92` (Pro), clean working trees, and that `git diff --stat a5c9711..HEAD -- src/resources` (core) and `git diff --stat 561ed92..HEAD -- src/resources` (Pro) are empty, so the built assets still match.
- **Phase 2 (S04)** must also check that `grace_ended` carries the overdue renewal order ID and a stable `event_id` on redelivery.
- **Database resilience:** if WP-CLI reports a database connection error again, wait and retry a few times (up to ~5 minutes total). If it persists, stop, write the handoff file with exactly what was done, and report BLOCKED rather than continuing on partial state.
