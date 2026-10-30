# Release QA report — FAIL

Both ArraySubs 2.1.0 and ArraySubsPro 1.3.0 were active on https://mirror-help.arrayhash.com throughout this timed browser QA. WooCommerce 11.0.1 and official Stripe 10.9.0 were active. The run began 2026-10-31 at 02:08:13 Asia/Dhaka; the one-hour deadline is 03:08:13. Completed at 03:01:16 Asia/Dhaka in 53 minutes 4 seconds, within the one-hour limit. Browser testing ended before minute53; final reconciliation and reporting used the reserved cleanup window.

## Release blockers and findings

| Finding | Browser result | Release implication |
| --- | --- | --- |
| [153 — Required pause reason cannot be supplied](../issues/tasks/153-customer-pause-cannot-submit-when-a-reason-is.md) | Customer flow offers days and confirmation but no reason field; server rejects the pause. With reason optional, Pause/Resume works. | Blocks the enabled required-reason workflow. |
| [19 — Raw box uploads remain public](../issues/tasks/019-subscription-box-uploads-are-publicly-readable-by.md) | An unauthenticated fresh browser receives HTTP 200 for the known synthetic 8×8 upload. | Known deployment security blocker remains. No actual personal document exposure was asserted. |
| [150 — Canonical protected page denies an entitled customer](../issues/tasks/150-canonical-protected-page-denies-an-entitled-signed.md) | Eligible customers are denied on the canonical URL while fresh query URLs allow them; unentitled customer and guest remain denied. | Membership access is unreliable. Cache involvement is an inference; no guest content leak was observed. |
| [151 — Release deployment alignment unverified](../issues/tasks/151-release-qa-deployment-alignment-unverified-new.md) | Live Webhooks and newer settings/layout changes are missing despite matching plugin version strings. | Exact local release source cannot be cleared by this live run. No deployment was performed. |
| [152 — Repeated storefront copy and amount-column explanations](../issues/tasks/152-live-storefront-repeats-subscription-details-and.md) | Trial/renewal details repeat; explanatory copy appears in amount areas. Arithmetic remained correct. | Violates clean storefront copy requirements on the deployed templates. |

Previously closed [146 — related-orders mobile overflow](../issues/tasks/146-subscription-related-orders-table-pushes-invoice.md) and [115 — HTTP code wrapping](../issues/tasks/115-portal-action-failures-splits-http-status-code.md) were observed on the live deployment; their statuses were preserved because current-source deployment is unverified. The [127 — repeated-Escape renderer freeze](../issues/tasks/127-escape-freezes-cancellation-editor-before-discard.md) remains a suspected, bounded observation, not a confirmed current-source regression. [8 — grouped subscription metadata](../issues/tasks/008-subscription-meta-is-saved-onto-grouped-and.md) was partially reconfirmed in a retained draft; External product, database/helper and purchase paths were not retested. Existing issue evidence and statuses were preserved.

## Proven workflows and profiles

All completion claims below come from actual browser interactions and post-action customer/admin views. Source observations only corroborate them.

- **A, baseline:** paid block checkout $7.31→order 109452/subscription 109453; early renewal $7.31→one order 109485 and the previewed next date; admin custom Pending subscription with different renewal pricing; customer account/address persistence; private order/subscription ownership denial; product feature values shown to an ordinary customer, then removed and reloaded.
- **B, commerce contrast:** classic seven-day trial with signup $2.00 and shipping $3.00 collected $5.00→order 109474/subscription 109475. Repeat trial was rejected. Variable choices, box required-input recovery and cart review were sampled. Credit $1.00 applied to a $7.31 purchase→payment $6.31/order 109510/subscription 109511→balance $0.00 with exactly one grant and debit.
- **C, behavior contrast:** invalid skip 0/4 rejected; Skip 1 and Undo; required-reason Pause failed, optional-reason Pause/Resume passed; scheduled trial cancellation and Undo; immediate cancellations passed during cleanup. Audits off/on cleanly gated its direct page, restored functionality and preserved historical data.
- Real changed-value Save/reload checks covered the live email editors, representative shared settings, product editors, selected access rules, Checkout Builder configuration and Feature Manager settings. Each tested original was restored.
- Narrow checkout/account/admin and keyboard checks were sampled. The related-orders overflow remained visible. Future invoice/payment pairs were browser-inspected before cancellation, and fixture-specific pending queues were empty afterward.

Four intended Stripe TEST orders total $25.93: three Completed and the physical trial order Processing. No duplicate order was observed in the exercised single-submit/refresh cases. This is not concurrency or retry proof. The documented Mailpit loopback sink was verified in the browser before email-producing actions.

## Coverage and gaps

**Coverage: 87/97 template families visited (all 87 live families); 141/160 meaningful modes visited (141/149 live); 57/74 independent save editors passed real changed-value save/reload checks.** There are 10 source-only template families, 11 source-only modes, 8 unvisited live modes and 17 untested save editors. All 62 registered source route spellings were mapped with zero unmapped. The full NOT-TESTED lists are in [coverage-register.md](coverage-register.md) and its JSON companion. Page visits, meaningful modes, save editors and workflow assertions are counted separately. Record IDs, filters, wizard steps and URL aliases do not inflate page totals. One live email family contains 29 independently saved definitions; the 30th source-expected Webhook Disabled email was absent.

Required gaps remain: exact release deployment; due-time automatic invoice/payment execution, failed-renewal retries and locks; completed bundle/box purchases and upload/freebie combinations; premium cancellation-offer acceptance and configuration save; several rule editor saves/enforcement paths; My Account custom pages and profile-field creation; checkout step creation; new payment-token save; protected downloads/PDF invoices; customer REST feature-type privacy; manual-payment completion, alternative gateways, language providers, external integrations and inactive AI generation. The register lists every remaining independent editor and source-only mode. No free-only run was requested.

## Restoration and retained evidence

All touched global settings, policies, modules, rule names, Checkout Builder radius, Feature Manager display setting and 29 live email subjects were restored through the UI with reload evidence. Customer 2595's original profile/address values and customer 3252's empty cart were restored. Credit balance is $0.00, reconciled against grant 109499 and debit 109515.

Subscriptions 109453, 109475 and 109511 are customer/admin verified Cancelled with no next payment. Pending subscription 109525 was deleted. Pending Scheduled Actions searches for all four IDs return no items. Products 109433 and 109466 are Draft. Page 109473 and isolated product copies 109500/109529/109542/109573 were trashed; variable parent 109542 was trashed, but child 109544/109546 disposition was not independently queried and remains a cleanup verification gap. Grouped draft 109521 remains as explicit issue 8 evidence. Customer 3252, four sandbox orders, cancelled subscriptions and the two credit ledger rows are retained for traceability; no refunds were issued. Session closure and final housekeeping are recorded in mutations.md.

No product fixes, builds, Composer installation, PHPCS, version bumps, changelog edits, license changes, new webhooks, automation or deployment occurred. A completed QA board records the work performed and its failures/gaps; it is not release approval.

Evidence entry points: [index](index.md), [plan](plan.md), [root coverage](coverage.md), [commerce](commerce-coverage.md), [account/access](access-coverage.md), [premium](premium-coverage.md), [emails](emails-coverage.md), [mutation ledger](mutations.md), [audit review](audit-review.md).

Final checks: all five fresh progress tasks complete with failures/gaps recorded; no active browser sessions; run-owned authentication states and temporary filled-card screenshots removed. Both product repositories have clean working trees. The artifact privacy review found no actual secrets in321 readable files, and mandatory fields/status preservation were checked for the issues. See completion.json and line-counts.txt.
