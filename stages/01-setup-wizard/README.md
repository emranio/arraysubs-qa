# Stage 01 — Easy Setup Wizard

**Goal:** Exercise the 9-step Easy Setup Wizard end to end. Confirm it launches from **ArraySubs → Easy Setup**, that navigation (Next / Back / Skip with defaults / close confirmation) behaves as documented, that each of the 7 business-type profiles preloads its documented defaults, that conditional questions only show when their parent answer is set, that Pro-only options are hidden when ArraySubsPro is inactive, and that a completed run actually writes the chosen answers into General Settings and Toolkit Settings.

**Prerequisites:** Stage 00 complete. ArraySubs and ArraySubsPro both active. No prior wizard run since fresh install (or accept that a re-run is overwriting prior wizard answers).

**Run order:**
1. [01 — Launch and navigation](01-launch-and-navigation.md) — Open the wizard, validate Next / Back / Skip with defaults, and the close-confirmation dialog.
2. [02 — Business type defaults](02-business-type-defaults.md) — Confirm each of the 7 business types preloads its documented defaults.
3. [03 — Conditional questions](03-conditional-questions.md) — Verify conditional show/hide for trial, skip, pause, and plan-switching questions.
4. [04 — Pro vs Free options](04-pro-vs-free-options.md) — With Pro inactive verify Pro-only options are hidden and the Pro-not-active note appears; with Pro active verify they appear.
5. [05 — Apply and verify settings](05-apply-and-verify-settings.md) — Complete a SaaS run, click Apply Settings, verify success and that at least 6 settings now match in General Settings and Toolkit Settings; re-run to confirm idempotency.

**Exit criteria:**
- Wizard launches and closes correctly with the documented confirmation dialog text.
- Each of the 7 business types loads the documented default profile.
- All conditional questions hide and show correctly when parent answers change.
- Pro-only options are hidden with the documented note when Pro is inactive and visible when Pro is active.
- A completed SaaS run writes recognisable values into General Settings and Toolkit Settings; re-running the wizard with identical answers does not corrupt the configuration.
