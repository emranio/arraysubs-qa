# Account and access findings

## ACCESS-001 — Canonical protected content denies an entitled customer; a fresh URL grants access

Severity: high access/environment regression. Browser behavior is consistent with frontend page caching; that cause is an inference. The coordinator recorded issue #150.

- QA task: progress #4, Customer access and premium workflows; scheduled 2026-10-31, Asia/Dhaka.
- Plan: qa/release-2026-10-31-0208/plan.md.
- Original fixture: customer 595, login sqa-fix56-account, test email sqa-fix56-account@example.com, Customer role; active Daily Stripe subscription 69245. Orders: N/A for this read-only access assertion.
- Additional run fixture: customer A 3252, login rqa0208.customera, test email rqa0208-a@example.invalid, Customer role; active subscription 109453 to product 109433, initial order 109452. Page 109473 was created through the native editor with an active-product-109433 restriction.
- Negative fixture: customer B 2595, login sqa-fix81-c1, test email sqa-fix81-c1@example.com, Customer role; physical-box subscription 97430. Guest used a separate isolated session.
- Browser sessions: rqa0208-entitled, rqa0208-owner, rqa0208-customer-b and rqa0208-guest. Profile A, with both plugins active.
- Original URL: https://mirror-help.arrayhash.com/sqa-members-daily-content/.
- Fresh comparison: https://mirror-help.arrayhash.com/sqa-members-daily-content/?rqa0208=entitled.
- Additional URL: https://mirror-help.arrayhash.com/rqa0208-access/; fresh comparison ?rqa0208=owner.

Reproduction:
1. Open customer 595 in Manage Members and verify active Daily Stripe subscription 69245.
2. Use Login as Customer. My Account explicitly identifies sqa-fix56-account and shows the return-to-admin bar.
3. Open the canonical protected post URL. It renders guest navigation, no identity bar, and SQA-DENIED-7731 instead of protected content.
4. Open the same page with the harmless rqa0208 query. It displays the authenticated identity and SQA-ENTITLED-CONTENT-7731.
5. The new Page 109473, saved and reloaded with a restriction to A's product 109433, reproduces the same canonical denial. Its fresh URL correctly displays the tagged RQA0208 ENTITLED BODY for A.

Expected: an eligible signed-in member sees protected content on the ordinary URL. Unentitled customers and guests remain denied.

Actual: eligible members receive a guest-like denial on both canonical URLs. Fresh URLs allow the correct members. B and guest remain denied on fresh URLs, including a guest request to the exact URL that just allowed a member. No protected-body leak was observed.

Proof: evidence/access-entitled-content.png, access-entitled-fresh.png, access-guest-after-entitled.png, access-unentitled-fresh.png, access-runpage-owner.png, access-runpage-owner-fresh.png, access-runpage-b-fresh.png and access-runpage-guest-fresh.png.

Scope: canonical storefront pages also appeared as guest while account pages retained the correct session identity. No authentication-loss conclusion, global cache purge or extended cache debugging. Existing post/subscription conditions were preserved. Run-owned Page 109473 was trashed after testing; the Pages UI confirmed the change.

## ACCESS-002 — Related Orders exceeds the mobile viewport on the live deployment

Severity: medium responsive regression; matches existing closed issue #146. Preserve that issue's status and append this run's evidence. This may indicate deployment drift: #146 records a local fix and verification on 2026-10-29.

- QA task: progress #4, Customer access and premium workflows; scheduled 2026-10-31, Asia/Dhaka.
- Plan: qa/release-2026-10-31-0208/plan.md.
- Customer A: 3252, login rqa0208.customera, test email rqa0208-a@example.invalid, Customer role.
- Subscription: 109453; product 109433; orders 109452 and 109485. The latter was created by the commerce worker's early-renewal workflow, not by this lane.
- URL: https://mirror-help.arrayhash.com/my-account/as-view-subscription/109453/.
- Context: rqa0208-owner, viewport 390 × 844, profile A. Read-only inspection.

Reproduction:
1. Open the owner's subscription detail.
2. Set a 390 × 844 viewport.
3. Scroll to Related Orders and inspect its right columns.

Expected: the table and actions fit a usable mobile layout or a clearly contained scroll surface, without expanding the whole page.

Actual: the table is 509.8125 px wide at x=30. The document scroll width is 540 px for a 390 px viewport. Total and View actions are outside the initial viewport, and the viewport screenshot visibly clips the right columns.

Proof: evidence/access-related-orders-mobile-viewport.png and access-subscription-mobile.png. Browser DOM geometry corroborates viewport=390, document scroll width=540, table width=509.8125, table left=30.

Counterexamples and limits: Account details fits at 390 px; desktop subscription detail shows all columns. This fixture has no Invoice action, but it uses the same table described in #146. The existing issue records fix 23c9df3 and local verification; this run does not prove that current local source is broken. No record or setting changed.
