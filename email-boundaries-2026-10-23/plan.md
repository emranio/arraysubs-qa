# Email Free/Pro boundary verification

User-requested scope 2026-10-23, task91, immediate local manual execution; no old QA cycle or watcher reused. Site http://localhost:10013, isolated agent-browser admin context via documented Local auto-login if email settings/preview supports a real browser check.

1. Preserve current activation/settings and existing issue-board state.
2. Audit core and Pro registration, trigger hooks, shared templates, placeholders and links. Check module-off and Pro-absent behavior, missing/deleted related objects and customer-facing feature-type privacy.
3. Exercise actual WordPress/Woo email classes with disposable fixtures. Capture rendered HTML/plaintext and intercept every mail before external transport. Do not send external mail, submit test-email delivery, mutate an existing subscription/payment, or deploy.
4. Inspect supported email preview/configuration in the real browser. Record concrete source/runtime/browser evidence and any limits, separately from assertions about actual SMTP delivery.
5. Log and fix confirmed defects in isolated worktrees; verify Free and Pro, append canonical draft entries for completed product changes, remove own fixtures and restore exact state.
