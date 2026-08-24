# D00 issue #2 afternoon revalidation — 2026-08-23

## Scope and safety

This is the approved non-duplicating revalidation for shared QA issue #2. It ran in the D00 afternoon phase (site UTC+6), after the initial out-of-phase fixture creation was contained. No product, user, setting, provider object, order, subscription, or scheduled action was created, saved, deleted, or replayed. The only browser-side state change was adding `SLT2 Flex Week Segments` to an isolated guest cart and removing it again before the session closed.

## Fresh browser evidence

- `SLT-ISS-02-02-registry-authenticated.png`: private registry page, authenticated as admin.
- `SLT-ISS-02-03-users-list-revalidation.png`: exactly the nine `slt2-*` customer accounts in the real Users UI.
- `SLT-ISS-02-04-daily-core-subscription-revalidation.png`: Daily Core is simple, virtual, subscription, Day/1, no flexible sync.
- `SLT-ISS-02-05-fixed-three-subscription-revalidation.png`: Fixed Three is simple, virtual, subscription, Day/2, length 3, no flexible sync.
- `SLT-ISS-02-06-lifetime-subscription-revalidation.png`: Lifetime One Time has Lifetime Deal, forced interval 1/length 0, and no flexible-sync control.
- `SLT-ISS-02-07-flex-week-subscription-revalidation.png`: Flex Week is Week/1, flexible sync enabled, handles 2/5, all three segments enabled.
- `SLT-ISS-02-08-daily-core-storefront-revalidation.png`: guest page shows `$10.00 / day` and `Subscribe Now`.
- `SLT-ISS-02-09-fixed-three-storefront-revalidation.png`: guest page shows `$7.00 / 2 days` and `3 billing cycles`.
- `SLT-ISS-02-10-lifetime-storefront-revalidation.png`: guest page shows `$49.00` with no recurring period wording.
- `SLT-ISS-02-11-flex-week-storefront-revalidation.png`: guest product page rendered without access interception.
- `SLT-ISS-02-12-flex-week-cart-revalidation.png`: D00/day-2 cart displays `$14.00 full first charge` and next charge `29 August, 2026 (UTC+6)` for `$14.00`.
- `SLT-ISS-02-13-cart-emptied-revalidation.png`: the isolated guest cart is empty after the assertion.

Browser errors were empty. The only console warning was WooCommerce's existing dependency-detection warning for an inline/unknown script accessing `wc.wcBlocksData`; it is the same known non-functional warning already recorded by SLT-PROD-13 and did not affect any product, cart, or checkout assertion.

## Data and isolation reconciliation

- Registry TSV `fixture-registry.tsv` has the four products, nine customers, two reserved absent guests, and six provider binding rows; every row is `cleanup_approved=no`.
- Private page `31301` contains the same nine user IDs, two reserved guest emails, four product IDs, and weekly segment handoff.
- Users `474`–`482` are each role `customer` with the expected `billing_*` fields: `SLT`, purpose-specific last name, `1 SLT2 Way`, Dhaka, `BD`, `1207`, shared test phone, and matching email. `slt2-guest-d0` and `slt2-guest-d5` remain absent.
- Read-only controls `cust1` (5), `customer1` (32), and `sync-stripe` (319) match their recorded login, email, registration timestamp, and roles.
- Persisted subscription metas match the cards: Daily `day/1/0/$10`, Fixed `day/2/3/$7`, Lifetime `lifetime/1/0/$49`, and Flex Week `week/1/$14` with flex `yes`, ends `2/5`, and all segments `yes`.
- The Shop Access full-store rule retains only `[31340,31347,31357,31363]` in its exclusions. No global setting was changed.
- Live `start_of_week=6`; `arraysubs_calculate_renewal_sync_cycle_dates('2026-08-23 03:00:00', 1, 'week')` returned cycle start `2026-08-21 18:00:00` and next payment `2026-08-28 18:00:00` UTC, matching the cart's 29 August UTC+6 boundary.
- Current Paddle local bindings exactly match the registry: Daily Core `pro_01m0nh1pxqymawg7yc6j3krmsx` / `pri_01m0nh1qw5barwpaeaa8s0jdsf`; Fixed Three `pro_01m0nhqad1kxs86czedp5gmyfy` / `pri_01m0nhqbcjykweg9hv4qj9gmjy`; Flex Week `pro_01m0njkv2n85sm0j5kxegen19t` / `pri_01m0njkw5yy2r9bz83wc2crr6w`; Lifetime has no Paddle binding.
- There are zero subscriptions and zero HPOS orders for the nine SLT2 customers. Therefore no SLT2 renewal actions are pending. Mailpit baseline and final latest ID both remain `72KRGoCQ1KMjnd32006pMg`; the revalidation produced no mail.

## Verdict

All mandatory, non-mutating assertions of lifecycle cards 12, 5, 6, 7, and 8 now pass in the correct phase. The original early-mutation timestamp cannot be erased, but the registry and current fixtures are complete, exact, and safe for the D00 checkout spine. Shared issue #2 is resolved by this fresh revalidation; no fixture was duplicated.
