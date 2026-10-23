# Final restriction access-leak audit

User-confirmed scope: final security regression of Gutenberg, Elementor, and per-post/CPT restrictions. Old settings and Match Any are priority. Opening or ordinarily saving old content must preserve exactly who can access it. Explicit conversion is outside ordinary-save invariance.

Schedule: 2026-10-24 Asia/Dhaka, tasks1–4 in this session. Fresh progress board board/kanban; preserve existing qa/issues. Target actual localWordPress http://localhost:10013 serving this checkout. Disposable fixtures only; admin1/member224 plus a new nonmember. Separate agent-browser sessions and scoped temporary authentication. Do not touch original content.

1. Independent shared backend/per-post differential audit against actual pre-change HEAD logic. Cover oldrawformats, MatchAny, empty/nonempty conditions, nestedtrees, cachedoldclient/REST/classic saves, malformed/imported booleans and fail-open edges. Actual WP runtime, core/Pro; create/deleteownruntimefixtures.
2. Independent Gutenberg parsing/default/serialization audit and real oldordinaryANY editor open/body/title/save/reload tests. Compare audience guest/nonmember/member/admin before/after. Missing defaultattrs and no explicit mode/version are primary cases.
3. Independent Elementor raw/default pruning and actual-render audit. Real unrelated heading save without opening restrictioncontrols, oldordinaryANY and transitionalflags, nestedcontainers/cache/defaults. Compare audience before/after; preserve exactrule contract.
4. Root per-post editor/classic round-trip and storefront verification, focused regressions for any confirmedfix, build/syntax/linecounts ifchanged, cleanup and report. Runtimechecks supplement realbrowser tests. No phpcs/Composer/release/push.

Log confirmed defects in qa/issues with active task/date/plan, subscription/orderIDs or N/A, users/roles, fixtureIDs/URLs, reproduction, expected/actual andproof. Fix/retest beforeclosing. Scope ends after requiredcasespass andcleanupreadback.

## Outcome

Completed this session. Issues104–108 fixed and closed; all four work items finished. Real browser tests, original-behavior comparisons, core/Pro regressions and production build passed. Disposable fixtures, account and authentication state were removed and original shared settings verified. See [report.md](report.md) for results, evidence and browser-tool limitations.
