# SLT2 granular task catalog

All 133 lifecycle cards are fresh `todo` work. Due dates are primary/start dates; timed cards remain in progress through their registered natural gates.

| ID | Key | Due | Priority | Task |
|---:|---|---|---|---|
| 1 | `SLT-CHK-01` | 2026-08-23 | critical | Block checkout happy path: slt2-core buys SLT2 Daily Core on Stripe 4242 (control record) |
| 2 | `SLT-CHK-02` | 2026-08-23 | critical | Classic checkout parity: same SLT2 Daily Core purchase, meta diffed field-by-field against CHK-01 |
| 3 | `SLT-CHK-14` | 2026-08-23 | critical | Buy SLT2 Lifetime One Time and prove no renewal is ever scheduled (12-day negative control) |
| 4 | `SLT-LIFE-04` | 2026-08-23 | critical | SLT2 Fixed Three Cycles: two renewals, short-horizon expiring-soon, final expiry |
| 5 | `SLT-PROD-01` | 2026-08-23 | critical | SLT-PROD-01 Create SLT2 Daily Core, the day/1 workhorse subscription product |
| 6 | `SLT-PROD-06` | 2026-08-23 | high | SLT-PROD-06 Create SLT2 Fixed Three Cycles, a day/2 subscription whose final renewal expires it on 2026-08-27 |
| 7 | `SLT-PROD-07` | 2026-08-23 | high | SLT-PROD-07 Create SLT2 Lifetime One Time, the never-renews negative control |
| 8 | `SLT-PROD-13` | 2026-08-23 | high | SLT-PROD-13 Create SLT2 Flex Week Segments, the single week-interval flexible-sync product |
| 9 | `SLT-REN-01` | 2026-08-23 | critical | SLT2 Daily Core renews unattended overnight — first cycle, spread-offset window, cron-not-CLI proof |
| 10 | `SLT-SETUP-01` | 2026-08-23 | critical | SLT-SETUP-01 Recon environment, create SLT2 evidence + classic checkout pages, publish registry |
| 11 | `SLT-SETUP-02` | 2026-08-23 | critical | SLT-SETUP-02 Apply and record the five window-wide baseline setting changes |
| 12 | `SLT-SETUP-03` | 2026-08-23 | critical | SLT-SETUP-03 Create the SLT2 account matrix (9 slt2-* users) and document the guest path |
| 13 | `SLT-SYN-01` | 2026-08-24 | critical | SLT-SYN-01 Audit simple-product Flexible Renewal Sync UI, validation and meta keys |
| 14 | `SLT-SYN-05` | 2026-08-23 | critical | Segment 1 full: prove the full recurring charge now and the exact next-cycle boundary date |
| 15 | `SLT-CHK-03` | 2026-08-24 | critical | Guest to new account: block checkout mints slt2-guest-d0 mid-flow and owns order + subscription |
| 16 | `SLT-CPN-01` | 2026-08-24 | critical | SLT2PCT20REC 20% recurring coupon on block checkout - discounted first charge, discount persists to every renewal |
| 17 | `SLT-CPN-02` | 2026-08-24 | critical | SLT2FIX5FIRST $5 fixed one-time coupon on classic checkout - first order discounted, first renewal at full price |
| 18 | `SLT-EML-06` | 2026-08-24 | high | Prove new_subscription + admin_new_subscription at a real Stripe block checkout |
| 19 | `SLT-LIFE-05` | 2026-08-24 | high | SLT2 Renewal Price Step: prove the $5 -> $20 crossover lands on renewal #2, not renewal #3 |
| 20 | `SLT-PROD-05` | 2026-08-24 | high | SLT-PROD-05 Create SLT2 Renewal Price Step with a different renewal price after 2 cycles |
| 21 | `SLT-PROD-12` | 2026-08-24 | high | SLT-PROD-12 Create SLT2 Flex Month Segments, the single month-interval flexible-sync product |
| 22 | `SLT-PROD-14` | 2026-08-24 | high | SLT-PROD-14 Create the two daily flex-sync partition products (2-active and 1-active) |
| 23 | `SLT-PROD-16` | 2026-08-24 | critical | SLT-PROD-16 Create SLT2 Retry Daily and SLT2 Paddle Daily, the two gateway-path products |
| 24 | `SLT-REN-03` | 2026-08-24 | high | Renewal-invoice leg: pending order and invoice email at due+offset-6h, before the charge leg |
| 25 | `SLT-SETUP-04` | 2026-08-24 | high | SLT-SETUP-04 Create the six SLT2 coupons covering recurring, one-time, N-cycle, fee and reject paths |
| 26 | `SLT-SETUP-05` | 2026-08-24 | high | SLT-SETUP-05 Verify Paddle sandbox readiness and record the two-gateway capability matrix |
| 27 | `SLT-SYN-03` | 2026-08-24 | critical | SLT-SYN-03 Create the sync-group control product: SLT2 Sync Global Daily |
| 28 | `SLT-SYN-08` | 2026-08-24 | high | Two-active and one-active partitions, and that META_SEG1_END is positional |
| 29 | `SLT-CHK-04` | 2026-08-25 | critical | Paddle sandbox purchase: SLT2 Paddle Daily overlay, webhook-paid order, remote schedule sync |
| 30 | `SLT-CHK-05` | 2026-08-25 | high | Classic checkout with Stripe SCA card: 3DS at signup, then requires_action on the off-session renewal |
| 31 | `SLT-CHK-15` | 2026-08-25 | critical | Complete both $0.00-today trial checkouts: card still collected, first real charge scheduled |
| 32 | `SLT-CPN-03` | 2026-08-25 | critical | SLT2REC3 vs SLT2REC3NOINIT on block checkout - N-cycle counting and the exact renewal where the discount stops |
| 33 | `SLT-DUN-01` | 2026-08-25 | critical | First renewal declines on SLT2 Retry Daily: order failed, subscription stays active, retry #1 queued +24h |
| 34 | `SLT-EML-09` | 2026-08-25 | critical | Trial started, trial-ending reminder at 3 days, and paid trial conversion |
| 35 | `SLT-IMP-01` | 2026-08-25 | high | UTC+6 midnight-boundary renewal: date correctness in admin, portal, email and order |
| 36 | `SLT-MYA-05` | 2026-08-25 | high | Member access reacting to status: pro_member add/remove across active-on-hold-cancelled, My Features entitlements, SLT2 URL gate |
| 37 | `SLT-PROD-02` | 2026-08-25 | high | SLT-PROD-02 Create SLT2 Free Signup Daily, the $0.00-today free-signup-then-paid product |
| 38 | `SLT-PROD-03` | 2026-08-25 | high | SLT-PROD-03 Create SLT2 Trial Four Day, the trial product with an in-window reminder boundary |
| 39 | `SLT-PROD-09` | 2026-08-26 | medium | SLT-PROD-09 Create SLT2 Grouped Set, a grouped product with two subscription children |
| 40 | `SLT-PROD-15` | 2026-08-25 | medium | SLT-PROD-15 Create SLT2 Flex Variable Daily with per-variation flexible-sync configuration |
| 41 | `SLT-REN-02` | 2026-08-25 | critical | Second unassisted renewal of the same subscription — schedule re-arms at the same offset, no drift |
| 42 | `SLT-REN-04` | 2026-08-25 | critical | Paddle sandbox subscription renews unattended with remote/local reconciliation |
| 43 | `SLT-REN-05` | 2026-08-25 | high | Stripe SCA renewal observation: verify SLT-CHK-05 requires_action email and pay link |
| 44 | `SLT-SYN-02` | 2026-08-25 | critical | SLT-SYN-02 Audit variation-level Flexible Renewal Sync UI, [$loop] meta and per-variation independence |
| 45 | `SLT-SYN-06` | 2026-08-25 | critical | Segment 2 prorate: prove the arithmetic to the cent on week and month cycles |
| 46 | `SLT-SYN-13` | 2026-08-25 | high | Variation-level flexible sync: prove the purchased variation''s segment plan wins over a parent decoy |
| 47 | `SLT-ADM-05` | 2026-08-26 | critical | Admin-create a day/1 subscription and prove natural invoice scheduling |
| 48 | `SLT-ADM-06` | 2026-08-26 | critical | Renewal orders are correctly typed and linked to the parent subscription (HPOS) |
| 49 | `SLT-ADM-07` | 2026-08-26 | high | Renewal invoice output and pay-link settling an unpaid invoice on Stripe and Paddle |
| 50 | `SLT-CHK-09` | 2026-08-26 | high | Quantity 3 on a subscription line — assert order total, _quantity, unit _recurring_amount and the renewal amount |
| 51 | `SLT-CPN-04` | 2026-08-26 | high | SLT2NOSUB rejected on a subscription-only classic cart - exact message, mixed-cart partial discount, undiscounted renewal |
| 52 | `SLT-EML-01` | 2026-08-26 | high | Renewal ALERT: prove the 3-day upcoming-renewal reminder fires once, on the right subscription, and never twice |
| 53 | `SLT-EML-03` | 2026-08-26 | critical | Payment received email after real unattended renewals on Stripe and Paddle |
| 54 | `SLT-EML-07` | 2026-08-26 | high | On-hold, pending-cancellation and cancelled emails through real status transitions |
| 55 | `SLT-EML-11` | 2026-08-26 | critical | Toggle the Subscription On Hold customer email OFF, prove silence, restore ON, prove delivery |
| 56 | `SLT-EML-12` | 2026-08-26 | high | Override subject, heading and content on New Subscription with merge tags and prove real-value rendering |
| 57 | `SLT-EML-15` | 2026-08-26 | critical | Reconcile the full Mailpit set for one SLT2 Daily Core renewal — no double-send, nothing missing |
| 58 | `SLT-PROD-04` | 2026-08-26 | high | SLT-PROD-04 Create SLT2 Signup Fee Daily with a $15.00 one-time signup fee |
| 59 | `SLT-PROD-10` | 2026-08-26 | high | SLT-PROD-10 Create SLT2 Box Daily (free Subscription Box) plus its three eligible children |
| 60 | `SLT-PROD-11` | 2026-08-26 | high | SLT-PROD-11 Create the four-product plan ladder and wire upgrade/downgrade/crossgrade links |
| 61 | `SLT-SYN-04` | 2026-08-26 | critical | SLT-SYN-04 Prove global sync_to_billing_cycle=true + first_charge_mode=full, and that flex overrides it |
| 62 | `SLT-SYN-14` | 2026-08-29 | high | Quantity 3 on a segment-2 prorated first charge: prove the proration multiplies per unit |
| 63 | `SLT-ADM-03` | 2026-08-27 | critical | Admin schedule change must reschedule both renewal legs; next-payment-date is API-locked |
| 64 | `SLT-CHK-08` | 2026-08-27 | high | Existing active subscriber buys a second, different subscription — auto_migrate_on_checkout is gated off; document what migration would do |
| 65 | `SLT-CHK-13` | 2026-08-27 | high | Buy SLT2 Box Daily through the wizard: contents selection, order lines and box meta on the subscription |
| 66 | `SLT-DUN-03` | 2026-08-27 | high | Grace phase 1: active to on-hold one day after the due date, with the customer-only on-hold email |
| 67 | `SLT-EML-02` | 2026-08-28 | high | Renewal invoice email: content, UTC+6 due date, and a pay-link that resolves to a real payable order |
| 68 | `SLT-EML-13` | 2026-08-27 | high | Disable all four admin emails, prove admin silence with customer mail unaffected, restore and re-prove |
| 69 | `SLT-IMP-03` | 2026-08-27 | high | Concurrent renewals in one Action Scheduler window: no skips, no double charges, offsets stagger |
| 70 | `SLT-MYA-01` | 2026-08-27 | critical | My Account subscriptions list and detail for slt2-core - every field and every self-service action under the frozen baseline |
| 71 | `SLT-PROD-08` | 2026-08-27 | high | SLT-PROD-08 Create SLT2 Variable Daily with four subscription variations incl. a $0 probe |
| 72 | `SLT-SW-00` | 2026-08-27 | critical | SLT-SW-00 Seed the plan ladder: slt2-switch buys SLT2 Plan Basic and SLT2 Plan Pro |
| 73 | `SLT-SW-09` | 2026-08-27 | critical | Retention: accept the 20%-for-3-cycles discount and prove exactly 3 discounted renewals, plus a downgrade offer |
| 74 | `SLT-SYN-07` | 2026-08-27 | critical | Segment 3 next_cycle: full charge now, first renewal exactly one cycle past segment 1''s |
| 75 | `SLT-SYN-11` | 2026-08-27 | high | Flexible sync exclusivity negatives: renewal-price, trial and lifetime products refuse the plan even when meta is force-set |
| 76 | `SLT-ADM-09` | 2026-08-28 | high | Subscription notes (auto + manual) and the pro Audits screens for a failed renewal |
| 77 | `SLT-CHK-06` | 2026-08-28 | high | Two different subscription products in one cart must be rejected — capture the exact string on every add-to-cart surface |
| 78 | `SLT-CHK-10` | 2026-08-28 | high | Anonymous guest checkout of a non-subscription cart must still work — forced registration is scoped to subscription carts only |
| 79 | `SLT-CHK-11` | 2026-08-28 | high | Buy two SLT2 Variable Daily tiers and prove per-variation config lands on the subscription |
| 80 | `SLT-CHK-12` | 2026-08-28 | medium | EXPLORATORY: SLT2 Grouped Set rendering, add-to-cart probes, and one order through the grouped form |
| 81 | `SLT-DUN-02` | 2026-08-28 | high | Retry attempts 2 and 3 reuse the same failed order, then the 4th charge hits the 3-retry cap |
| 82 | `SLT-EML-04` | 2026-08-28 | critical | Payment failed: one customer + one admin email per retry attempt, and whether the attempt number is visible |
| 83 | `SLT-IMP-02` | 2026-08-28 | high | Webhook replay idempotency: one Stripe and one Paddle renewal event, no duplicates |
| 84 | `SLT-LIFE-03` | 2026-08-28 | medium | SKIP renewal on S5: one cycle, max-three clamp, undo, notifications and shifted charge |
| 85 | `SLT-MYA-02` | 2026-08-28 | critical | Update the Stripe payment method from My Account and prove the next unassisted renewal charges the new card |
| 86 | `SLT-SW-01` | 2026-08-28 | high | Upgrade SLT2 Plan Basic to SLT2 Plan Pro on Stripe with prorate_immediately arithmetic |
| 87 | `SLT-SW-06` | 2026-08-28 | critical | Mid-cycle Basic→Pro upgrade, then prove the D6 renewal charges $15.00 on the unchanged due date |
| 88 | `SLT-SYN-12` | 2026-08-28 | critical | Gateway gating for flexible sync: Paddle hidden from the DOM and blocked at submit, Stripe syncs to the midnight boundary |
| 89 | `SLT-ADM-04` | 2026-08-29 | critical | Admin status ladder active to on-hold to active to cancelled: emails and scheduler side effects |
| 90 | `SLT-CHK-07` | 2026-08-29 | high | Mixed cart: subscription + plain product — one order, only the subscription line creates a subscription, only it renews |
| 91 | `SLT-EML-05` | 2026-08-29 | medium | HTML vs plain-text rendering of the renewal invoice and payment-received emails, and link resolution |
| 92 | `SLT-IMP-04` | 2026-08-29 | medium | Orphan and edge states: product trashed, price edited, customer deleted mid-cycle (exploratory) |
| 93 | `SLT-LIFE-02` | 2026-08-29 | high | EARLY renew from the customer portal: full amount, next date anchored to the original due date, legs replaced |
| 94 | `SLT-MYA-03` | 2026-08-29 | high | Update Paddle payment method and prove the next remote renewal uses it |
| 95 | `SLT-SW-03` | 2026-08-29 | high | Crossgrade SLT2 Plan Pro to the equal-priced SLT2 Plan Peer and prove the date does not shift |
| 96 | `SLT-SW-05` | 2026-08-29 | critical | Upgrade a Paddle-billed ladder subscription and prove remote price synchronization |
| 97 | `SLT-SW-07` | 2026-08-29 | high | Variable-product variation switch (Starter→Plus) plus on-hold and pending-cancellation switch refusals |
| 98 | `SLT-SW-10` | 2026-08-29 | critical | Pending cancellation with required reason, declined retention offers, natural cancel, reactivation |
| 99 | `SLT-SYN-10` | 2026-08-29 | high | Calendar overflow on the month flex product: the 31st day is absorbed into the last active segment |
| 100 | `SLT-ADM-01` | 2026-08-30 | high | Subscriptions list: status tabs, search, gateway filter, columns, pagination, delete guardrails |
| 101 | `SLT-DUN-04` | 2026-08-30 | high | Grace phase 2: terminal cancellation three days after the hold, with customer and admin cancel emails |
| 102 | `SLT-DUN-05` | 2026-08-30 | high | Mid-grace recovery: new card in My Account, pay the failed renewal, and prove the next-payment anchor |
| 103 | `SLT-MYA-04` | 2026-08-30 | high | Customer view of an unpaid renewal invoice and paying it from the portal before the automatic charge leg |
| 104 | `SLT-SW-04` | 2026-08-30 | medium | Admin-side repeat of the Basic to Pro upgrade and record-for-record diff against SLT-SW-01 |
| 105 | `SLT-SW-08` | 2026-08-30 | medium | Charge a non-zero $7.50 upgrade switch fee on Pro→Enterprise and restore the fee to 0 in-task |
| 106 | `SLT-SYN-09` | 2026-08-30 | critical | Renewal execution after a synced first charge: second charge full on the boundary, third on the grid |
| 107 | `SLT-EML-08` | 2026-08-31 | high | Expired, reactivated and auto-downgrade emails, incl. the expiry-suppression negative |
| 108 | `SLT-EML-10` | 2026-08-31 | high | Expiring-soon at 7 days and Stripe card-expiring notification |
| 109 | `SLT-EML-14` | 2026-08-31 | critical | Negative sweep: cancelled gets no reminders, lifetime gets no renewal mail, expired gets no further mail |
| 110 | `SLT-LIFE-01` | 2026-08-31 | high | LATE renewal on S5: prove the schedule catches up to the cycle grid and that a past computed date queues zero legs |
| 111 | `SLT-SW-02` | 2026-08-31 | high | Customer downgrade applies now, and targeted D8 expiry auto-downgrade with its email |
| 112 | `SLT-TT-00` | 2026-08-31 | critical | SLT-TT-00 D8 time-travel owner: pre-flight plus targeted month-segment-2 and week-segment-3 renewals |
| 113 | `SLT-ADM-02` | 2026-09-01 | critical | Subscription detail screen: every field, dates, schedule, related orders, gateway panel |
| 114 | `SLT-ADM-08` | 2026-09-01 | high | Refund a renewal order: gateway refund, subscription effect, and emails |
| 115 | `SLT-ADM-10` | 2026-09-01 | medium | Capability gating: shop_manager vs editor vs subscriber on every admin surface |
| 116 | `SLT-IMP-05` | 2026-09-01 | high | End-of-window log sweep, failed-action audit and cycle-to-order reconciliation |
| 117 | `SLT-OWN-01` | 2026-09-02 | critical | SLT-OWN-01 Core-only Stripe and Paddle ownership regression with Pro inactive |
| 118 | `SLT-SETUP-99A` | 2026-09-03 | critical | SLT-SETUP-99A D11 settings restore and partial cancellation — no deletions |
| 119 | `SLT-WATCH-12` | 2026-09-04 | critical | SLT-WATCH-12 D12 read-only final lifecycle tail and cross-system reconciliation |
| 120 | `SLT-SETUP-99B` | 2026-09-05 | high | SLT-SETUP-99B Post-watch teardown on 2026-09-05 — delete every SLT2 artifact |
| 121 | `SLT-RET-01` | 2026-08-26 | critical | SLT-RET-01 Cancellation reasons setup, required validation, Other text and persistence |
| 122 | `SLT-RET-02` | 2026-08-27 | critical | SLT-RET-02 Retention discount offer eligibility, acceptance, cycle accounting and limits |
| 123 | `SLT-RET-03` | 2026-08-27 | critical | SLT-RET-03 Pause retention offer, limits, access, billing shift and auto-resume |
| 124 | `SLT-RET-04` | 2026-08-28 | critical | SLT-RET-04 Downgrade retention offer target, no-target, proration and renewal conditions |
| 125 | `SLT-RET-05` | 2026-08-28 | high | SLT-RET-05 Contact-support retention offer URL, logging and no-mutation conditions |
| 126 | `SLT-RET-06` | 2026-08-31 | critical | SLT-RET-06 Retention eligibility, card order, dismissal, decline and offer-history matrix |
| 127 | `SLT-RET-07` | 2026-09-01 | high | SLT-RET-07 Retention analytics KPIs, charts, activity filters and source reconciliation |
| 128 | `SLT-GW-01` | 2026-08-28 | critical | SLT-GW-01 Stripe product, cart, checkout, SCA and cancellation matrix |
| 129 | `SLT-GW-02` | 2026-08-29 | critical | SLT-GW-02 Paddle product, cart, hosted checkout and lifecycle parity matrix |
| 130 | `SLT-GW-03` | 2026-08-30 | critical | SLT-GW-03 Cross-gateway identity, idempotency, migration and cleanup integrity |
| 131 | `SLT-GW-00` | 2026-08-23 | critical | SLT-GW-00 D0 Stripe and Paddle checkout, webhook and scheduler preflight |
| 132 | `SLT-GW-04` | 2026-09-02 | critical | SLT-GW-04 Independent browser, portal, HPOS, scheduler and Mailpit audit |
| 133 | `SLT-MATRIX-99` | 2026-09-02 | critical | SLT-MATRIX-99 Full subscription scenario and Stripe/Paddle parity reconciliation |

Machine-readable key-to-ID mapping: `automation/key-to-task-id.json`. Exact execution order: `calendar.md`. Cell-level coverage is closed by `SLT-MATRIX-99` (task 133).
