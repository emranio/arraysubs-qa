# SLT-SYN-02 — variable parent persists hidden flexible-sync defaults

- Status: Open
- Severity: High
- Date found: 2026-08-05
- Watch day: D03; the originating card was scheduled D02 and executed late
- QA progress task: `#44 SLT-SYN-02` / stage `subscription-lifecycle-test D02`
- QA plan: `kanban/tasks/044-slt-syn-02-audit-variation-level-flexible-renewal.md`
- Related fixture task: `#40 SLT-PROD-15`
- Affected product: variable parent `12385` (`SLT Flex Variable Daily`)
- Affected variations: `12386` Full, `12388` Next Cycle, `12390` No Sync
- Affected subscription IDs: N/A — this was detected before purchase
- Affected order IDs: N/A — order lookup count for the fixture is zero
- Affected WordPress user/customer: user `1`, login `admin`, role `administrator`
- Gateway: N/A — configuration-only product-editor probe
- Checkout type: N/A — no cart, checkout, order, or subscription was created
- Non-default settings in play: the frozen QA-window global-sync value was OFF; the three variation-level flexible-sync plans named below were the only fixture configuration under test
- Browser/user context: `admin-SLT-SYN-02`, WordPress product editor
- Exact route: `https://mirror-help.arrayhash.com/wp-admin/post.php?post=12385&action=edit`

## Reproduction

1. Create and publish a variable product through the real WooCommerce admin UI, enable the parent header checkbox **Subscription [ArraySubs]**, and configure subscription/flex settings only inside its variations.
2. Reload the product editor with the product still typed as **Variable product**.
3. Observe that the parent **General** and **Subscription [ArraySubs]** tabs are hidden (`display: none`) and no parent flex-sync controls are available to the operator.
4. Inspect the DOM and stored parent metadata.

## Expected

The variable parent should carry no `_arraysubs_flex_sync_*` metadata. Only the three purchasable variation IDs should own their independent plans.

## Actual

The hidden parent tab still contributes unindexed default controls to the DOM and the published parent stores a default month/1 flex plan:

```text
_arraysubs_flex_sync_enabled=yes
_arraysubs_flex_sync_seg1_active=yes
_arraysubs_flex_sync_seg2_active=yes
_arraysubs_flex_sync_seg3_active=yes
_arraysubs_flex_sync_seg1_end=10
_arraysubs_flex_sync_seg2_end=20
```

The same DOM contains the correct indexed variation fields (`[0]`, `[1]`, `[2]`) plus a separate unindexed hidden parent set. The parent `_subscription_period=month` and `_subscription_interval=1` defaults are also stored.

## Proof

- `/home/server-manager/slt-evidence/SLT-SYN-02-parent-precondition-leak.csv`
- Browser DOM at the exact route reported hidden parent tabs and six unindexed `_arraysubs_flex_sync_*` inputs alongside the 18 indexed variation inputs.
- Fresh WP-CLI metadata read before any SLT-SYN-02 mutation produced the values above.
- Parent `12385` was created by the real SLT-PROD-15 admin flow; no direct product-meta write created these values.

## Scope and counterexamples

- The three variation plans themselves were correct at detection: Full `yes/yes/yes` with boundaries `1/2`, Next Cycle `no/no/yes`, and No Sync with no enabled/active keys.
- The parent Shop Access exclusion is correct and variation IDs are not separately excluded.
- No order or subscription exists for this variable fixture yet, so purchase impact is not claimed here.
- Containment for continued QA is limited to deleting the six parent flex keys, preserving the defect evidence, and avoiding the parent **Update** action. No product code is changed.
