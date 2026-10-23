# Member Access custom-message switches

Completed 2026-10-24 (Asia/Dhaka) against the real local WordPress site at `http://localhost:10013`, using the changed checkout and its production build. No remote deployment was performed.

## Result

Shop Access, URL, Post Types, Comments, and Purchase Limit rules now provide a **Use custom message** switch before the rich-text editor. The editor appears when enabled. Disabling it retains its text. Disabled switches and empty custom messages use Settings → Content Gate's shared default; empty rich-editor markup also falls back correctly. Editing the shared default updates these rules without resaving each one.

The change belongs entirely to ArraySubs core. Per-post configuration, Elementor, and Gutenberg editors are deferred. Existing per-post message behavior is preserved by the internal shop/comment adapters and covered by runtime regression checks.

## Executed checks

| Verification | Result | Evidence |
| --- | --- | --- |
| Runtime and REST with Pro | 69/69 passed | [Log](evidence/runtime-pro.txt) |
| Runtime and REST without Pro | 69/69 passed | [Log](evidence/runtime-core.txt) |
| Admin save/reload, switch, retained HTML, Discard across all five tabs | 40/40 passed | [Assertions](evidence/admin-checks.json), [run](evidence/admin-run.txt) |
| Guest storefront with Pro | 50/50 passed | [Assertions](evidence/storefront-pro-checks.json), [run](evidence/storefront-pro-run.txt) |
| Guest storefront without Pro | 50/50 passed | [Assertions](evidence/storefront-core-checks.json), [run](evidence/storefront-core-run.txt) |
| Active member access | Shop purchase controls, protected URL/content, and comments remain available | [Member checks](evidence/member-checks.json), [URL content](evidence/member-url.txt), [shop screenshot](evidence/qualifying-member-shop.png) |
| Real editor input and saved storefront output without Pro | Passed; typed text, existing formatting and absolute link survive save and reload | [Reloaded editor](evidence/core-typed-reload.txt), [storefront screenshot](evidence/core-custom-typed-saved-storefront.png) |
| Keyboard, mobile, and accent | Space toggles the switch; 390px viewport has no horizontal overflow; checked switch matches WordPress accent `#3858e9` | [Mobile](evidence/shop-custom-mobile.png), [computed colors](evidence/admin-switch-style.txt) |
| Production assets | Build passed | [Build log](evidence/build.txt) |
| PHP syntax, whitespace, source sizes | Passed; every changed plugin source is below 3000 lines | [Validation](evidence/source-validation.json) |

Both storefront matrices exercised every rule type with custom text disabled, enabled, empty, and blank rich-editor markup, followed by a changed shared default. Purchase Limit checks submitted a real quantity of four against the guest rule's maximum of three and inspected the server-rendered WooCommerce error. No checkout/payment was submitted. Admin interactions used the actual controls, isolated browser sessions, fresh snapshots/refs, and screenshots; runtime checks supplemented browser verification.

## Fixtures and restoration

Plan and same-day execution schedule: [plan.md](plan.md). Tracking: [board/kanban/](board/kanban/). Existing `qa/issues/` records were preserved; no unresolved feature regression was found.

Existing targets: products 6702 and 6698, content post 9237, comment post 9239, and the URL demo post at `/2026/09/27/demo-url-members/`. Active member: WordPress customer 224, with existing active/trial subscriptions. Anonymous guest validation has no subscription or order ID. The URL fixture used the site's actual canonical permalink instead of its pre-existing demo rule's short exact path, so the test genuinely exercised URL gating after WordPress canonical redirects.

Original ArraySubs settings, active plugins, saved-at timestamp, WooCommerce coming-soon setting, and administrator color preference were restored exactly: [restoration checks](evidence/restoration-check.txt). Three temporary WordPress authentication tokens were revoked, the private backup files were removed, and the four test browser sessions were closed: [cleanup](evidence/session-cleanup.txt). Existing account passwords were unchanged.

Browser error captures were empty for the admin and both storefront matrices. WP-CLI logs include an existing Elementor nullable-parameter deprecation; it did not fail the checks. No Composer install, phpcs, version bump, release, push, or external messaging was performed. Existing unrelated version/package/changelog edits were preserved.
