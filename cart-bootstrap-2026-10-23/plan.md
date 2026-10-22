# Cart bootstrap fix and verification

User-authorized task90, 2026-10-23 Asia/Dhaka. New targeted manual plan; no previous QA execution board/watchers reused. Actual local site http://localhost:10013/cart-2/.

1. Snapshot activation, site visibility, relevant Woo/plugin/theme/cart-page configuration. Preserve existing issues and concurrent source changes.
2. Reproduce empty/populated cart with real agent-browser guest/customer sessions. Collect screenshots, DOM, script/config/interactivity state, failed requests and console errors. Separate the malformed Mini-Cart request from main Cart hydration until causality is proven.
3. Audit ArraySubs/Pro and installed Woo/WordPress initialization order. Identify owner and smallest durable fix; develop product changes in an isolated worktree. Do not patch compiled/vendor files as the solution.
4. Browser-test empty and populated carts, quantity/totals/removal, header mini-cart and navigation to checkout without submission. Include representative ordinary/subscription products with Pro active and Free-only; supplemental request-local module cases are labelled separately.
5. Remove own fixtures/sessions, restore exact activation and Coming Soon settings, update affected plugin draft and task90, and save concrete evidence.

Only local reversible QA configuration and isolated cart mutations are authorized. Do not place/pay an order, submit customer account forms, send external messages, clear another customer's cart, or deploy. No Composer install or PHPCS/lint. Use agent-browser CLI with version-matched core/dogfood guide, snapshot/ref loop and screenshots for UI judgments. Root coordinates all plugin activation changes; one browser owner.
