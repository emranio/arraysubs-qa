# Disabled flexible-renewal segments remain visible instead of collapsing the legend

- Severity: low
- Date found: 2026-08-03
- Watch day: D01
- Originating task: `SLT-SYN-01`
- Plan file: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban/tasks/013-slt-syn-01-audit-simple-product-flexible-renewal.md`

## Task / stage / plan

- QA progress task: `#13` / `SLT-SYN-01`
- Stage: `D01`
- Plan path: `qa/subscription-lifecycle-test/kanban/tasks/013-slt-syn-01-audit-simple-product-flexible-renewal.md`

## Affected records

- Subscription IDs: N/A
- Order IDs: N/A
- Product IDs: `12099` (`SLT Flex Daily Two Seg`), `12102` (`SLT Flex Daily Next Cycle`)
- WP user: ID `1`, login `admin`, email `admin@mirror-help.arrayhash.com`, role `administrator`
- Gateway: N/A (admin-only product configuration check)
- Checkout type: N/A
- Non-default settings: no global setting bracket; only the authored per-product flex configurations were present (`12099` active flags `no/yes/yes`; `12102` active flags `no/no/yes`).

## Affected user / customer context

- WordPress user ID(s): `1`
- Login / email: `admin` / `admin@mirror-help.arrayhash.com`
- Role(s): `administrator`

## Exact routes / browser context

- `https://mirror-help.arrayhash.com/wp-admin/post.php?post=12099&action=edit`
- `https://mirror-help.arrayhash.com/wp-admin/post.php?post=12102&action=edit`
- Browser context: authenticated administrator, isolated session `admin-SLT-SYN-01`

## Reproduction

1. Sign in to wp-admin as an administrator.
2. Open product `12099`, `SLT Flex Daily Two Seg`, at the first route above.
3. In Product data, open `Subscription [ArraySubs]` and scroll to `Flexible Renewal Sync to Next Billing Cycle`.
4. Confirm the stored configuration has Full amount off and Prorate/Next-cycle on.
5. Observe the visible legend rows.
6. Open product `12102`, `SLT Flex Daily Next Cycle`, at the second route.
7. Open the same panel and confirm Full amount and Prorate are off while Next-cycle is on.
8. Observe the visible legend rows.

## Expected result

- Product `12099` collapses to exactly two schedule rows: `1 / Prorate amount` and `2 - 3 / Charge full for next billing cycle`.
- Product `12102` collapses to exactly one schedule row: `1 - 3 / Charge full for next billing cycle`, with no draggable boundary handle.

## Actual result

- Product `12099` renders three visible rows: `Off / Full amount`, `1 / Prorate amount`, and `2 - 3 / Charge full for next billing cycle`.
- Product `12102` renders three visible rows: `Off / Full amount`, `Off / Prorate amount`, and `1 - 3 / Charge full for next billing cycle`.
- The disabled rows are grey/struck through but remain visible, so the authored two-row and one-row collapse behaviours do not occur.

## Proof

- Screenshot: `/home/server-manager/slt-evidence/SLT-SYN-01-04-two-active-positional.png` visibly shows the extra `Off / Full amount` row on product `12099`.
- Screenshot: `/home/server-manager/slt-evidence/SLT-SYN-01-05-one-active.png` visibly shows the extra `Off / Full amount` and `Off / Prorate amount` rows on product `12102`.
- Browser DOM readback for `12099`: three visible `.arraysubs-flex-sync-legend-row` elements, each `display:flex`, visibility `visible`, height `18.1875`; texts were `Off\nFull amount`, `1\nProrate amount`, and `2 - 3\nCharge full for next billing cycle`.
- Browser DOM readback for `12102`: three visible rows with the two `Off` texts above. Both range inputs were `display:none` with zero-height boxes, so the no-handle part passed.
- WP-CLI meta readback for `12099`: enabled `yes`, active flags `no/yes/yes`, positional boundaries `1/3`.
- WP-CLI `SegmentPlan::getConfig(12102)` returned `actives => [3]` and an empty `boundaries` array.
- Browser console contained only JQMIGRATE 3.4.1 logs; the browser error collection was empty. No failed network response or JavaScript exception explained the rendering.
- Consolidated evidence: `/home/server-manager/slt-evidence/SLT-SYN-01B-facts.txt`.

## Scope and counterexamples

- The defect reproduced on both reduced-active fixtures, so it is not isolated to a particular disabled segment position.
- Product `12093`, with all three segments active, correctly rendered the three meaningful ranges `1 - 2`, `3 - 6`, and `7 - 30`.
- Stored metadata and server-side segment math remained correct: product `12099` retained positional boundary `1`; product `12102` returned an empty boundary array and displayed no handle.
- This is therefore a presentation/collapse defect, not evidence of an incorrect renewal schedule or persisted product state.
