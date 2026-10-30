# Release QA plan — 2026-10-31

Scope: ArraySubs and ArraySubsPro active together on https://mirror-help.arrayhash.com, with required WooCommerce dependencies. Credentials came from the workspace AGENTS.md Live QA access section and are excluded from artifacts.

Start: 2026-10-31 02:08:13 Asia/Dhaka (2026-10-30 20:08:13 UTC). Testing cutoff: 03:01:13. Hard finish: 03:08:13. All workers used the single original clock.json. This is a fresh schedule and board; no previous lifecycle plan or automation was reused.

| Phase | Local schedule | Work |
| --- | --- | --- |
| Preflight | 02:08:13–02:14:13 | Scope, baseline, inventory, fixtures and fresh board |
| Sweep | 02:14:13–02:32:13 | Distinct templates and independent editor saves |
| Journeys | 02:32:13–02:52:13 | Commerce, lifecycle, access and ownership |
| Contrasts | 02:52:13–03:01:13 | Targeted A/B/C variations |
| Cleanup/report | 03:01:13–03:08:13 | Restoration, reconciliation and delivery |

The coordinator owned all global settings and modules. Workers owned isolated records and sessions, held affected actions during coordinator mutations, stopped new scenarios before minute51 and handed cleanup back by minute52. Progress tasks: #1 preflight; #2 admin; #3 commerce/lifecycle; #4 access/premium; #5 contrasts/restoration/report. Scheduled day: 2026-10-31.

## Live baseline and safety

Browser-observed active versions: ArraySubs2.1.0, ArraySubsPro1.3.0, WooCommerce11.0.1 and official Stripe10.9.0. Pro license was active; six always-on and eleven optional module cards were enabled. The local revisions core65083a2 and Procbc348b are supporting evidence only. Deployment alignment is unverified: several locally registered release surfaces are absent or older on the live site. No deployment was performed.

Stripe TEST was verified before four intended sandbox payments. The live SMTP screen corroborated the documented Mailpit sink: loopback127.0.0.1:1025, authentication and AutoTLS disabled. No real payments, real recipient messages, refunds, external webhook registration, license mutation, dependency installation, builds, PHPCS or product fixes were authorized or performed. Local WP-CLI could not connect to its database; no local site writes or remote WP-CLI writes were attempted.

## Roles and fixtures

Admin1; new ordinary customer3252 (rqa0208.customera, rqa0208-a@example.invalid); existing tagged customer2595 (sqa-fix81-c1); existing entitled customer595 (sqa-fix56-account); isolated guests. Browser sessions used run-prefixed names and distinct authentication contexts.

Run-owned paid product109433 and trial/shipping product109466 generated orders109452,109474,109485 and109510, plus subscriptions109453,109475 and109511. Admin-created Pending subscription109525 tested different renewal pricing without a charge. Page109473 tested per-page access. Product copies109500/109529/109542/109573 tested isolated editors; draft109521 retains grouped-product issue evidence. Exact mutations and dispositions are in the lane ledgers.

## Actual profiles

**A — baseline:** both plugins active. General settings allowed multiple subscriptions, mixed carts and different cycles; one-per-customer/product disabled; trial payment and one trial/customer enabled. Grace3days, cancel after7on-hold, invoice6hours before, retry24/24/24 with hard-decline stop. Renewal sync disabled. Customer cancellation, early renewal and auto-renew-off enabled; Cancel Immediately=true. Job-log retention30days. Paid monthly virtual product7.31; block Stripe TEST checkout, early renewal, customer account, ownership/access and credit reconciliation exercised.

**B — commerce contrast:** seven-day trial, signup2.00, initial shipping3.00 and renewal shipping1.00; classic checkout collected5.00. A repeated trial attempt was rejected and the cart remained empty. Variable Silver/Gold choices and box selection/required gift validation were sampled; bundle rendering was inspected. These checks do not prove a completed bundle/box purchase. Credit1.00 reduced a7.31 purchase to6.31 and reconciled to zero.

**C — behavior contrast:** Skip and customer permission false→true, with maximum3cycles and cutoff2days preserved. Invalid0/4 rejected, Skip1 then Undo restored the date. Pause/customer/resume flags false→true with max30days/count2/cooldown0/accessnone; Require Reason=true exposed issue153. Require Reason=false allowed Pause/Resume. All flags and required-reason policy were restored. Cancel Immediately true→false allowed scheduled trial cancellation and Undo, then true was restored before immediate cancellation cleanup. Audits module true→false→true gave a clean direct-link disabled state and preserved earlier history; all other modules remained enabled. No worker writes occurred while Audits was off.

## Evidence and limits

Actual browser observations, screenshots and changed-value save/reload results are in coverage-register.md and the coverage shards. Invoice/payment future queues were inspected for all three purchased subscriptions; due execution, failed-renewal retry and locking were not exercised. Narrow checkout/account/admin and keyboard samples are recorded separately from the broader untested device/accessibility matrix.

Missing release surfaces, providers, fixtures, unvisited modes and independent save gaps remain in scope as NOT-TESTED or BLOCKED. Inspect-only exclusions include activation, secret changes, destructive import/reset and service provisioning. An additional free-only run was outside this request. A failed release verdict does not remove coverage gaps or close historical issues.
