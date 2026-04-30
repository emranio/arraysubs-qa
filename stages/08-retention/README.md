# Stage 08 — Retention Flow

**Goal:** Validate the entire admin Retention Flow configuration page and the customer-facing retention modal "Before You Go..." that ArraySubs presents during a cancellation attempt. Each retention offer type — Discount, Pause, Downgrade, Contact Support — is configured, triggered, accepted, and verified end-to-end. Eligibility logic and (Pro) Retention Analytics dashboard are also exercised.

**Prerequisites:**
- Stage 07 — Customer Portal Self-Service complete (cancellation flows and self-service actions all PASS).
- ArraySubs core active and **Retention Flow → Enable Retention Offers** ON.
- Stage 03 catalog: Basic Monthly with linked downgrade products configured (used by Task 04).
- Stage 02 settings include **Require Cancellation Reason** enabled.
- A working public URL for the support hub (e.g. `https://example.com/support`) for Task 05.
- Subscriptions seeded for `cust1@example.com`:
  - Subscription R1 — Active Basic Monthly (used for discount offer, Task 02).
  - Subscription R2 — Active Basic Monthly (used for pause offer, Task 03).
  - Subscription R3 — Active Basic Monthly with linked downgrade products (Task 04).
  - Subscription R4 — Active Basic Monthly (Task 05 contact support).
  - Subscription R5 — Active Basic Monthly already has an accepted retention discount in history (Task 06 eligibility).

**Run order:**
1. [01 — Cancellation reasons setup](01-cancellation-reasons-setup.md) — Configure Require Reason and edit the predefined reasons list including "Other".
2. [02 — Discount offer](02-discount-offer.md) — 20% off for 3 cycles for "Too expensive". Verify "Before You Go..." modal and post-acceptance UI.
3. [03 — Pause offer](03-pause-offer.md) — 30-day pause offer for "Just need a temporary break". Verify auto-resume scheduling.
4. [04 — Downgrade offer](04-downgrade-offer.md) — Downgrade card redirects into plan switching flow with downgrade options.
5. [05 — Contact Support offer](05-contact-support-offer.md) — Verify URL opens in new tab, acceptance logged, subscription remains active.
6. [06 — Eligibility conditions](06-eligibility-conditions.md) — Already-used check, reason targeting, "leave reasons empty = show for all".
7. [07 — Retention Analytics](07-retention-analytics.md) — *(Pro)* Verify summary cards, charts, activity log filters.

**Exit criteria:**
- All 7 task files signed PASS, or failures recorded with screenshots.
- The exact UI strings recorded verbatim during testing match the manual:
  - Heading: **"Before You Go..."**
  - Body: **"We'd hate to see you leave! Here are some options that might help:"**
  - **"Keep Subscription"** button on retention modal.
  - **"No thanks, continue to cancel"** dismissal link.
  - Default discount headline: **"Stay and Save!"**
  - Default discount description: **"We'll give you [percent]% off for the next [cycles] billing cycles."**
  - Default pause headline: **"Need a Break?"**
  - Default pause description: **"Take a break for up to [days] days. Your subscription will automatically resume."**
- Retention Analytics dashboard reflects the cancellations and offer events triggered during this stage with non-zero values.
