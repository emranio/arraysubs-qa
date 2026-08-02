# Documented Stripe retry settings (`retry_enabled` / `retry_max_attempts` / `retry_interval_hours`) do not exist in the codebase

- **Severity:** medium (documentation defect; blocks a test that the plan would otherwise have written)
- **Found:** 2026-08-01, design phase (static analysis, before browser execution)
- **Status:** pending browser confirmation on the Stripe settings screen
- **Originating task:** `SLT-REF-03` — Failed renewal + retry reference note
- **Plan file:** `qa/subscription-lifecycle-test/reference/SLT-REF-03-failed-renewal-retry-schedule-attempt-count-grace-day-timest.md`

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

## Reproduction steps

1. `cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins`
2. `grep -rn 'retry_enabled\|retry_max_attempts\|retry_interval_hours' arraysubs/src arraysubspro/src` — no output.
3. Open `documentations/architecture/payment-retry-system.md` and read the settings table around lines 30-38.
4. In a browser as admin, open **WooCommerce → Settings → Payments → Stripe** and confirm no retry fields are rendered.

## Scope notes

- Step 4 is **not yet done** — this finding is currently static-analysis only. Confirm in the browser before
  treating it as final.
- The retry *behaviour* itself appears correct and is fully specified in `SLT-REF-03`; only its documented
  configurability is wrong.
- Because of this, the plan does **not** contain a test that flips retry settings. `SLT-DUN-01..05` assert the
  hardcoded 3-attempt / 24-hour ladder instead.

## Suggested resolution

Either remove the settings table from `payment-retry-system.md` and document the
`arraysubs_payment_retry_config` filter as the only extension point, or implement the documented fields.
Given the workspace rule that no backward compatibility is required, implementing them is viable — but the
doc must not describe UI that does not exist.
