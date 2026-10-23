# Plan type priority and renewal sync notices

Verified October 24, 2026 on `http://localhost:10013` using the isolated `plan-types` agent-browser session. This is targeted follow-up verification of the creation wizard, not a new lifecycle QA cycle. Existing marked issues in `qa/issues/`, including26 and27, were preserved.

## Browser results

- Entered interval2 and length9, then enabled a fixed membership cutoff. Returning to Recurring billing left both flexible choices enabled. Selecting Flexible Length cleared the cutoff and restored maximum length9. Switching to Full Flexible and back restored interval2.
- The membership step showed an unchecked, disabled cutoff for Flexible Length and Full Flexible, with instructions identifying the earlier controlling setting. Returning to Fixed made the cutoff available again without silently re-enabling it.
- Configured a two-day cycle, different renewal price39, and a seven-day trial. The sync notice listed all three actual blockers and named the relevant step and setting to change. [Screenshot](evidence/plan-types/plan-types-sync-reasons.jpg).
- Lifetime billing kept both flexible choices enabled. Its sync notice explained that lifetime plans do not renew. Selecting Full Flexible changed billing back to a recurring monthly period; trial and renewal-price blockers cleared, and the custom-sync builder became available.
- Created one real plan through confirmation and the success screen: product20675, Full Flexible, price29/month, maximum length9, interval1, trial0, custom sync enabled, and no fixed membership cutoff. WP-CLI verified those saved values. [Review](evidence/plan-types/plan-types-full-flexible-review.jpg), [success](evidence/plan-types/plan-types-created.jpg).
- Opened the fixture's native billing editor, selected a one-day cycle without saving, and verified the new short-cycle notice and clarified requirement text. [Screenshot](evidence/plan-types/plan-types-native-sync-notice.jpg). Box and bundle use the shared core short-cycle notice; both production bundles compiled successfully.
- With Pro inactive, the creator retained the existing premium availability gates and showed the module-specific sync instruction. At390×844, the modal stayed within x20–370 with its footer visible. [Mobile screenshot](evidence/plan-types/plan-types-core-notice-mobile.jpg).
- Agent-browser reported no uncaught errors.

## Preparation checks and source validation

`scripts/plan-type-priority.php` exercises the actual controller preparation without inserting products. **18 checks passed with Pro**: flexible modes and maximum lengths survive stale membership flags; inactive invalid dates are ignored; Full Flexible normalizes interval/period; Fixed still saves and validates active cutoffs; invalid modes remain rejected. **4 checks passed without Pro**: core creation retains finite length and does not enable unavailable premium settings.

Both plugin production builds passed. All five touched PHP product files passed syntax checks. Core, Pro and documentation diffs passed whitespace checks; all touched source files remain below3000 lines. The old vague sync message and fixed-date instruction that blocked flexible choices are absent from active source. PHPCS was not run.

## Cleanup

Product20675 was permanently removed after verifying its title. The second core-only draft was discarded in the browser before creation. Existing products, subscriptions, orders and customers were unchanged. The catalog returned to35 plans. Pro started inactive, was temporarily activated for premium tests, and was restored to inactive. No module switches were changed. The dedicated browser session was closed.
