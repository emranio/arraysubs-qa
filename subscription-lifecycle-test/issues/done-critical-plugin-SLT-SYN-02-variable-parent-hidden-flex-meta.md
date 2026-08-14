# SLT-SYN-02 — variable parent persists hidden flexible-sync defaults

- Status: Resolved
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

## Resolution — 2026-08-14

Status: **Resolved**.

### Confirmed root cause

The report was a true positive. The Pro simple-product flex block was rendered inside WooCommerce's hidden simple-product panel even while the stored product type was `variable`. Its six successful, unindexed controls remained enabled, so a parent **Update** submitted them alongside the indexed variation controls. The Pro parent saver had no exact product-type or payload-shape boundary and interpreted the hidden controls as a parent plan.

A before-fix live no-op **Update** on parent `12385` reproduced the defect from a clean six-key state: the unchecked hidden master plus its initialized endpoints recreated `_arraysubs_flex_sync_seg1_end=10` and `_arraysubs_flex_sync_seg2_end=20`. This ruled out the historical report being stale or a direct-data false positive.

### Implemented fix

- Added one canonical `SegmentPlan::META_KEYS` list containing exactly the six flex-plan keys.
- The authenticated/capability-checked parent saver now resolves both WooCommerce product-type field names, deletes every row for those six keys on an exact variable parent, and only accepts a complete scalar simple-product payload for an exact simple product.
- Variation saves now require complete scalar schedule/boundary anchors for the exact loop. Missing-loop, partial, scalar-collision, nested, invalid-nonce, wrong-post-type, and unauthorized payloads fail closed without changing an existing plan.
- Billing intervals are accepted only within the editor contract `1..12`; nominal-cycle math independently caps the interval to prevent integer-to-float overflow from crafted or corrupt data.
- The admin script removes the six unindexed simple-control names and disables those controls for every non-simple product. It restores them when the editor changes back to simple and reconciles again immediately before form submission. Indexed variation controls are untouched.
- `SegmentPlan::isEnabled()` rejects variable containers, so legacy, duplicate, import, REST, or direct-write stale parent rows remain runtime-inert even before a classic editor save physically normalizes them.

Only the six `_arraysubs_flex_sync_*` keys are cleanup-owned. The parent's broader subscription defaults such as `_subscription_period=month`, `_subscription_interval=1`, fixed-period, shipping, and other unrelated metadata are intentionally preserved. Core ArraySubs required no change; the implementation is isolated to ArraySubsPro and preserves the separate variation, Subscription Box, and Subscription Bundle owners.

### Verification

- Deterministic WordPress/WooCommerce regression: **28/28 assertions passed**, with all three disposable products and the capability-control user deleted. Coverage included a literal exact/count/unique six-key oracle, two duplicate rows per key, unrelated-meta preservation, variation independence, runtime rejection, nonce/capability/post-type gates, complete and partial simple saves, complete and missing-loop variation saves, scalar/nested collisions, both product-type field names and transitions, custom product ownership, idempotency, and oversized intervals.
- Static/build checks passed: PHP syntax for both changed services, JavaScript syntax, targeted `git diff --check`, and the production ArraySubsPro webpack build. The served build contains both DOM-protection markers.
- Final live parent browser regression at the exact report route: the variable editor exposed **zero** live unindexed flex names, retained six backed-up names, disabled the simple controls, and contributed zero unindexed flex keys to `FormData`. A real no-op **Update** returned **Product updated.** and all six parent flex rows remained absent.
- Dynamic live editor regression: variable -> simple restored exactly the six names; simple -> variable removed exactly the six names again and left zero unindexed flex keys in `FormData`.
- Final live variation regression: the expanded UI contained exactly 18 indexed flex fields across loops `0`, `1`, and `2`; the AJAX save produced HTTP 200 responses with no browser page error. Full remained enabled with actives `[1,2,3]` and boundaries `[1,2]`; Next Cycle remained enabled with actives `[3]`; No Sync remained disabled; menu order remained `1/2/3`.
- Isolation remained clean throughout the final live bracket: parent runtime config `null`; order-item and subscription references to IDs `12385/12386/12388/12390` both zero; `arraysubs_settings` serialized hash stayed `1de46b4b09c69bf6cd24a0c8ae2cd9788055f7131038acd3ed49088bca80bdef`; the absent `arraysubspro_settings` serialized state stayed `d7558fd9aaedfc894dc306ac51a78789346aa9bc93e6a7d3505decf02cedd0d7`; Mailpit latest ID stayed `3fSVVzMmDFtiJU2RGRmFWU`.
- Browser console contained only the normal JQMIGRATE startup log. The WordPress debug log received no new entry during the final build, deterministic suite, parent save, or variation AJAX save.
- Three independent final read-only reviews returned PASS with no remaining critical, high, or medium blocker.

Evidence:

- `/home/server-manager/slt-evidence/SYN02-fix/01-before-variable-parent.png`
- `/home/server-manager/slt-evidence/SYN02-fix/02-before-fix-noop-update-recreated-meta.png`
- `/home/server-manager/slt-evidence/SYN02-fix/03-after-fix-noop-update-clean.png`
- `/home/server-manager/slt-evidence/SYN02-fix/04-after-fix-variations-expanded.png`
- `/home/server-manager/slt-evidence/SYN02-fix/05-final-variations-expanded.png`
