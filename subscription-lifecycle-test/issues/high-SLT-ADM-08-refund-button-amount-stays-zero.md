---
title: WooCommerce gateway-refund button stays at $0.00 for populated refund amounts
date: 2026-08-11
watch_day: D09
task_id: 114
task_key: SLT-ADM-08
stage: D09
plan_path: qa/subscription-lifecycle-test/kanban/tasks/114-refund-a-renewal-order-gateway-refund-subscription.md
status: open
severity: low
---

## Task / date / plan

- Date found: `2026-08-11`
- Watch day: `D09`
- Originating task: card `#114` / `SLT-ADM-08`
- Plan file: `qa/subscription-lifecycle-test/kanban/tasks/114-refund-a-renewal-order-gateway-refund-subscription.md`

## Affected IDs

- Subscription ID: `12655` (`SLT Signup Fee Daily`)
- Renewal order ID: `13590`
- WooCommerce refund IDs: `13729` (`$4.00`) and `13732` (`$5.00`)
- Product ID: `12577`
- Original Stripe test charge: `ch_3U2rFUJG5OzSNVs21EkfnKxg`
- Stripe test refund IDs: `re_3U2rFUJG5OzSNVs21cz3iqpg` (`$4.00`) and `re_3U2rFUJG5OzSNVs21u0eb4ul` (`$5.00`)

## Affected WordPress user / customer

- WordPress user ID: `347`
- Login / email: `slt-core` / `slt-core@example.test`
- Role: `customer`

## Gateway, checkout, and settings context

- Gateway: Stripe test
- Checkout type: `N/A` — this occurred on the HPOS admin renewal-order refund screen
- Relevant settings: `refunds.cancellation_behavior=immediate`, `auto_gateway_refund=true`, `allow_prorated_refunds=true`, minimum refund amount `0`
- No settings were changed and no temporary settings bracket was opened.

## Exact route and browser context

- Route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=13590`
- Browser/user context: WordPress administrator in isolated session `admin-SLT-ADM-08`
- First observation: `2026-08-11 06:42:54 UTC+6`
- Repeat observation: `2026-08-11 06:48:18 UTC+6`

## Reproduction steps

1. Sign in as a WordPress administrator and open renewal order `13590`.
2. Click **Refund**.
3. Leave refunded quantity at `0`, enter line total/refund amount `4.00`, and enter reason `SLT-ADM-08 partial`.
4. Observe the amount inputs and both refund-action labels.
5. Click the gateway action, accept the irreversible-action confirmation, and wait for the order to reload.
6. Click **Refund** again, enter the remaining line total/refund amount `5.00`, and enter reason `SLT-ADM-08 full`.
7. Observe the action label a second time before processing the gateway refund.

## Expected result

The gateway button should display the actual amount that its confirmation will process: `Refund $4.00 via Stripe`, then `Refund $5.00 via Stripe`.

## Actual result

The line-total and refund-amount inputs visibly held `4.00` and later `5.00`, but the gateway and manual action buttons remained `Refund $0.00 via Stripe` and `Refund $0.00 manually` in both attempts. The backend nevertheless processed the populated amounts correctly after confirmation.

## Concrete proof

- Pre-click UI, `$4.00` fields with stale `$0.00` buttons: `/home/server-manager/slt-evidence/SLT-ADM-08-02a-partial-ready-stale-label.png`
- Repeat UI, `$5.00` fields with stale `$0.00` buttons: `/home/server-manager/slt-evidence/SLT-ADM-08-04a-full-ready-stale-label.png`
- Post-action rows: `/home/server-manager/slt-evidence/SLT-ADM-08-02-partial.png` and `/home/server-manager/slt-evidence/SLT-ADM-08-04-full.png`
- Partial Mailpit message `7I45BYNAnxiNrYm5uSvwHw`: `Your mirror-help.arrayhash.com order #13590 has been partially refunded`.
- Full Mailpit message `4cogsKDuQGssjfWbN3yhKp`: `Your mirror-help.arrayhash.com order #13590 has been refunded`.
- Stripe API countercheck returned test charge amount/refunded amount `900/900` and both succeeded refund IDs listed above.
- HPOS contains exactly the two task refund rows since the task baseline: `13729` for `-4.00` and `13732` for `-5.00`, both parented only to `13590`.
- Agent-browser console contained only normal debug/migration logs; page-errors output was empty.

## Scope notes and counterexamples

- The stale label reproduced for both partial and remaining-full amounts on the same completed Stripe renewal order.
- This is a display/confirmation defect, not an incorrect-money defect in this run: Stripe and HPOS processed exactly `$4.00` and `$5.00`, and no other order received a task refund.
- The manual path was never clicked.

