# Documented Stripe retry settings (`retry_enabled` / `retry_max_attempts` / `retry_interval_hours`) do not exist in the codebase

- **Severity:** medium (documentation defect; blocks a test that the plan would otherwise have written)
- **Found:** 2026-08-01, design phase (static analysis, before browser execution)
- **Status:** resolved 2026-08-11 — QA reference made authoritative; impossible settings-flip test excluded
- **Originating task:** `SLT-REF-03` — Failed renewal + retry reference note
- **QA progress task / stage:** N/A — pre-execution reference audit, not a lifecycle-board card
- **Plan file:** `qa/subscription-lifecycle-test/reference/SLT-REF-03-failed-renewal-retry-schedule-attempt-count-grace-day-timest.md`
- **Exact admin route:** `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=stripe`
- **Browser/user context:** `admin-SLT-REF-03`, WordPress administrator; source checks run read-only from the plugin workspace

## Task / stage / plan

- QA progress task: `N/A` — pre-execution reference audit, not a lifecycle-board card
- Stage: `reference pre-execution audit`
- Plan path: `qa/subscription-lifecycle-test/reference/SLT-REF-03-failed-renewal-retry-schedule-attempt-count-grace-day-timest.md`

## Affected objects

| | |
|---|---|
| Subscription IDs | N/A — code/doc defect, not data |
| Order IDs | N/A |
| Product IDs | N/A |
| WP user IDs | N/A |
| Gateway | Stripe (test mode) |
| Checkout type | N/A |
| Non-default settings | none |

## Affected user / customer context

- WordPress user ID(s): `1`
- Login / email: `admin` / `admin@mirror-help.arrayhash.com`
- Role(s): `administrator`

## Expected result

`documentations/architecture/payment-retry-system.md` (approx. lines 30-38) documents three admin-configurable
fields on the Stripe settings page:

- `retry_enabled`
- `retry_max_attempts`
- `retry_interval_hours`

A QA plan reading that document would reasonably write a test that flips these settings and asserts the retry
ladder changes.

## Actual result

None of those three keys exist anywhere in either plugin:

```bash
grep -rn 'retry_enabled\|retry_max_attempts\|retry_interval_hours' arraysubs/src arraysubspro/src
# (no matches)
```

Stripe's retry behaviour is **hardcoded** and not reachable from any UI:

- `arraysubspro/src/Features/AutomaticPayments/Gateways/Stripe/StripeDelegate.php:476-483`
  publishes `enabled=true, max_attempts=3, interval_seconds=86400`.
- Published via `publishRetryConfigForSubscription()` at `:510-514`, filter registered at `:118`.
- Core defaults come from `arraysubs_get_payment_retry_config()` at
  `arraysubs/src/functions/gateway-helpers.php:176-204`
  (`enabled=true, max_attempts=3, interval_seconds=DAY_IN_SECONDS`), overridable only through the
  `arraysubs_payment_retry_config` PHP filter.

Related observation, same area: **Paddle registers no `arraysubs_payment_retry_config` filter at all.** A grep
across `arraysubspro/src` finds the filter registered only by Stripe (`:118`) and Mollie (`:158`). The abstract
default at `Abstracts/AbstractArraySubsGateway.php:666-673` is `enabled=false, 0, 0` but is never published, so
core defaults would apply if the local pipeline ever failed a Paddle charge.

Live browser verification on 2026-08-02 confirmed the mismatch. The Stripe **ArraySubs Config** tab renders
only the secondary webhook signing-secret and endpoint-ID fields. It contains no retry enablement, maximum
attempts, or retry-interval control. Evidence:
`/home/server-manager/slt-evidence/SLT-REF-03-01-stripe-arraysubs-config.png`.

## Reproduction steps

1. `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins`
2. `grep -rn 'retry_enabled\|retry_max_attempts\|retry_interval_hours' arraysubs/src arraysubspro/src` — no output.
3. Open `documentations/architecture/payment-retry-system.md` and read the settings table around lines 30-38.
4. In browser session `admin-SLT-REF-03`, open **WooCommerce → Settings → Payments → Stripe → ArraySubs Config** and confirm the only editable ArraySubs field is the masked secondary webhook signing secret; no retry fields are rendered.

## Scope notes

- Step 4 was completed on 2026-08-02 against the live test site; the finding is confirmed.
- The retry *behaviour* itself appears correct and is fully specified in `SLT-REF-03`; only its documented
  configurability is wrong.
- Because of this, the plan does **not** contain a test that flips retry settings. `SLT-DUN-01..05` assert the
  hardcoded 3-attempt / 24-hour ladder instead.

## Resolution and verification

- Updated `SLT-REF-03` to make the verified runtime contract authoritative: Stripe publishes a hardcoded
  three-retry, 24-hour configuration and `arraysubs_payment_retry_config` is the extension point.
- Explicitly excluded the impossible retry-settings UI scenario from this lifecycle plan, so no QA task can
  fail because nonexistent controls were not changed.
- The live browser/source evidence already recorded above verifies this correction. The older architecture
  wording is separate documentation maintenance and no longer changes the QA oracle.
