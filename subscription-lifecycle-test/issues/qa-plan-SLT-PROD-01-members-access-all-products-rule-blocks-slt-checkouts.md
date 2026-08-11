# Existing all-products Members Access rule blocks every SLT product checkout

- **Severity:** critical QA blocker (the configured rule behaves as designed, but the lifecycle plan cannot place any purchase while it applies to SLT products)
- **Found:** 2026-08-02, D0
- **Status:** resolved and browser-verified 2026-08-02
- **Originating QA progress task:** board task `#5`, `SLT-PROD-01`, stage/window day D0 (`catalog`)
- **QA plan file:** `qa/subscription-lifecycle-test/kanban/tasks/005-slt-prod-01-create-slt-daily-core-the-day-1.md`

## Affected objects

| | |
|---|---|
| Subscription IDs | N/A — checkout cannot start |
| Order IDs | N/A — checkout cannot start |
| Product ID | `11927`, `SLT Daily Core`, slug `slt-daily-core`, published simple virtual subscription |
| WordPress users | Logged-out guest; ID `347`, login `slt-core`, email `slt-core@example.test`, role `customer`; by configuration the same applies to SLT customer IDs `348` through `355` |
| Gateway | Both Stripe and Paddle are unreachable for this product |
| Checkout type | Both block and classic are unreachable for this product |
| Browser/user contexts | `guest-SLT-PROD-01` and `customer-SLT-PROD-01`, Headless Chrome 149.0.0.0 |
| Exact test URL | `https://mirror-help.arrayhash.com/product/slt-daily-core/` and the equivalent `https://mirror-help.arrayhash.com/?p=11927` |
| Relevant configuration | `members_access.enabled=true`; enabled rule `rule_1784662676378_maa3te08s` named `Private member store`; condition `has_active_subscription >= 100`; scope `all`; no product/category exclusions; action `block_purchase` |

## Expected result

The task requires the published product page to show the recurring price/schedule and a subscription
add-to-cart button so later guest, registered-customer, classic-checkout, block-checkout, Stripe, and Paddle
flows can buy the control product.

## Actual result

The page renders `$10.00 / day`, but replaces the complete add-to-cart form with:

`This product is available to members only. Join now to unlock purchasing.`

The result is identical for a logged-out guest and authenticated customer `slt-core`. Neither page has a
`form.cart`, and no add-to-cart control is exposed. Browser errors are empty. The product itself is correctly
published with price `10.00`, `_is_subscription=yes`, period `day`, interval `1`, and in-stock status.

## Reproduction steps

1. Publish a normal in-stock WooCommerce product while the configuration above is active; product `11927`
   is the concrete fixture.
2. Open the exact product URL in a logged-out browser session.
3. Observe `$10.00 / day` followed by the members-only message and no add-to-cart form.
4. Log in as `slt-core`, who has no qualifying set of 100 active subscriptions, and reopen the same URL.
5. Observe the identical block and absence of the add-to-cart form.
6. Run `wp option get arraysubs_settings --format=json --allow-root | jq '.members_access'` and observe the
   enabled all-products rule with empty exclusions.

## Concrete proof

- Guest storefront screenshot: `/home/server-manager/slt-evidence/SLT-PROD-01-03-frontend.png`.
- Authenticated customer screenshot: `/home/server-manager/slt-evidence/SLT-PROD-01-04-customer-blocked.png`.
- Exact product metadata: `/home/server-manager/slt-evidence/SLT-PROD-01-meta.json`.
- Relevant product metadata: `/home/server-manager/slt-evidence/SLT-PROD-01-relevant-meta.txt`.
- Rule dump: `/home/server-manager/slt-evidence/SLT-PROD-01-members-access-rules.json`.
- Both browser sessions reported no JavaScript errors; the guest DOM contained no `.summary .price` or
  `form.cart` selector because the block-theme template and restriction hook rendered the price/message
  outside that classic wrapper.

## Scope notes and counterexamples

- The restriction service is honoring the stored configuration; this is not evidence that subscription
  product metadata or recurring-price rendering is broken.
- Related storefront products also display `MEMBERS ONLY`, confirming the rule is global rather than newly
  attached to product `11927`.
- Administrator product editing bypasses the restriction, so publish and metadata checks pass while every
  intended customer checkout remains blocked.
- The D0 settings baseline already contained this rule. The lifecycle plan never declared an exclusion or
  temporary restoration step, so this is an environment-isolation omission in the plan.
- No pre-existing rule or non-SLT product was changed while gathering this proof.

## Remediation verification and cache observation

The narrow product exclusion was then applied through **Member Access → Shop Access** so the lifecycle
window could continue. The stored rule immediately showed `exclusion_product_ids=[11927]`, and a fresh
WordPress request reported the product as purchasable. However, the canonical product URL continued to
serve the pre-change members-only HTML from Cloudflare (`cf-cache-status: HIT`, positive `age`). A unique
query-string request produced a Cloudflare `MISS` and correctly rendered `$10.00 / day`, quantity, and
**Subscribe Now** for both guest and `slt-core`.

- Pre-exclusion blocked copy: `/home/server-manager/slt-evidence/SLT-PROD-01-03-frontend-blocked-before-exclusion.png`.
- Post-exclusion successful copy: `/home/server-manager/slt-evidence/SLT-PROD-01-03-frontend.png`.
- Stored rule after exclusion: `/home/server-manager/slt-evidence/SLT-PROD-01-members-access-rule-after-exclusion.json`.

This stale edge response is an additional safety concern for access-rule changes: the inverse transition
could temporarily expose previously cached purchase/content markup after a rule becomes more restrictive,
even though server-side add-to-cart validation still runs on the later request.

## Suggested resolution

Before any SLT purchase, append each SLT product parent ID to the existing rule's
`exclusion_product_ids`, record the exact pre-change rule JSON, and restore it byte-for-byte in
`SLT-SETUP-99`. Product exclusions are narrower than disabling Members Access globally and still allow
later SLT-specific ecommerce rules to match after this first rule is skipped. Purge affected product-page
edge caches when Shop Access rules are saved; until that exists, QA must use a unique query string when
verifying the immediate post-save state and preserve both the cached and origin-fresh observations.

## Resolution and verification

- The exact pre-window rule snapshot is preserved at the documented evidence path. The four SLT parent
  products created so far are the only exclusions: `11927`, `11933`, `11938`, and `11943`.
- Added a binding isolation rule to the suite: every newly published SLT parent is excluded before any
  storefront/checkout step; no non-SLT product may be excluded; immediate checks use a unique query string.
- Added exact Shop Access restoration and a sorted JSON diff to `SLT-SETUP-99A`, plus a final assertion to
  `SLT-SETUP-99B`.
- Fixed the Member Access REST writer to merge sanitized partial payloads into the raw stored option. A real
  **Save Rules** click preserved the complete raw `arraysubs_settings` hash
  `d736a01170ec0326d5ab727dc51f2eae912294d510f2bf19f6ee6139ecbb993a` and left all six unrelated defaults absent.
- Browser counterexamples: guest access to excluded SLT Daily Core shows **Subscribe Now**; non-SLT product
  `233` remains blocked, has no cart form/button, and renders the rule message. Evidence:
  `/home/server-manager/slt-evidence/SLT-ISSUE-122-excluded-open.png`,
  `/home/server-manager/slt-evidence/SLT-ISSUE-122-nonslt-still-blocked.png`, and
  `/home/server-manager/slt-evidence/SLT-ISSUE-122-shop-access-save.png`.
