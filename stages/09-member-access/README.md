# Stage 09 — Member Access & Restriction Rules

**Goal:** Validate every Member Access rule type — Role Mapping, URL Rules, Post Type Rules, Discount Rules, Ecommerce Rules, Download Rules — plus drip scheduling, content gating messages, restriction shortcodes, paused-subscription access behavior, and Pro-only multi-login prevention. Every task is performed by a human tester clicking through `wp-admin` and the storefront in a real browser. Each task ends with a verified, falsifiable result on screen.

**Prerequisites:**
- Stages 00–02 complete (plugins active, settings configured, environment verified).
- Stage 03 catalog available — at least the following published products:
  - `Pro Plan` — simple subscription, $29.99 / month.
  - `Basic Plan` — simple subscription, $9.99 / month.
  - `Premium Widget` — simple non-subscription product, $25.00 (used for ecommerce gating).
  - `Members Tee` — simple non-subscription product, $20.00 (used for discount tests).
- Stages 05/06 used to seed the following test customers (passwords stored in the QA password vault):
  - `member1@example.com` — has 1 Active subscription to **Pro Plan**.
  - `member2@example.com` — has 1 Paused subscription to **Pro Plan**.
  - `nonmember@example.com` — registered customer, no subscriptions.
- ArraySubs Pro active for tasks marked *(Pro)* — specifically `11-multi-login-prevention-pro.md`.
- WordPress test pages already created and published:
  - `/premium-content` (page slug `premium-content`) — empty body, used for URL rule.
  - `/pricing` — placeholder pricing page used as default redirect URL.
  - `/become-a-member` — placeholder signup page.
- Custom post type `kb_article` registered (or use `page` if CPT plugin not available — note substitution in test sign-off).
- Default browsers: Chrome (primary), Firefox (secondary). Use private/incognito windows when testing logged-out behavior.

**Run order:**
1. [01 — Role mapping](01-role-mapping.md) — Map "Pro Plan" subscription to a custom "Pro Member" WordPress role; verify auto-assign on subscribe and auto-remove on cancel.
2. [02 — URL rules](02-url-rules.md) — Restrict `/premium-content` URL with a Prefix rule; verify logged-out, non-subscriber, and active-subscriber outcomes.
3. [03 — Post type rules](03-post-type-rules.md) — Restrict a custom post type by subscription; verify per-post override via post-meta UI.
4. [04 — Discount rules](04-discount-rules.md) — Apply 15% member discount to all products; verify cart shows discount for active subscribers only.
5. [05 — Ecommerce rules](05-ecommerce-rules.md) — Block guests/non-members from purchasing **Premium Widget**; verify member can purchase normally.
6. [06 — Download rules](06-download-rules.md) — Configure rate-limited downloads (5/day); verify counter increments and limit-reached message.
7. [07 — Drip / scheduled access](07-drip-scheduled-access.md) — 7-day drip lock on a post; verify before-7-days locked, after time-travel + 7 days unlocked. Document the time-travel approach used.
8. [08 — Content gating messages](08-content-gating-messages.md) — Default global message + default redirect URL + per-post message override.
9. [09 — Restriction shortcodes](09-restriction-shortcodes.md) — `[arraysubs_restrict]` and `[arraysubs_visibility]` rendering on test pages.
10. [10 — Pause-state access behavior](10-pause-state-access-behavior.md) — Configure paused-subscription access to None / Limited / Full and confirm content access matches each setting.
11. [11 — Multi-login prevention](11-multi-login-prevention-pro.md) — *(Pro)* Max 2 sessions; log in from 3 browsers, verify oldest session evicted; verify cooldown.

**Exit criteria:**
- All 11 task files signed PASS, or failures recorded with screenshots, browser version, and reproduction steps.
- The exact UI strings recorded verbatim during testing match the user manual:
  - Default restriction message: "This content is restricted. Please subscribe to access."
  - Restriction page elements: lock icon, message, **View Plans & Pricing** button, **Log In** button (logged-out only), **Return to Home** link.
  - Login Limit tab visible only when **ArraySubs → Settings → Toolkit → Multi-Login Prevention** is enabled.
- Role auto-assign and auto-remove correctly tracked through subscription status changes (active → cancelled).
- Drip schedule unlock time-travel approach documented in `07-drip-scheduled-access.md` Sign-off (system clock, plugin filter, or manual subscription start-date edit).
- Multi-login eviction confirmed via heartbeat tick (15–120 seconds maximum delay tolerated).
- All test rules cleaned up at end of stage (rules deleted or disabled) so they do not interfere with Stage 10.
