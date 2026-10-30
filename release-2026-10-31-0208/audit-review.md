# Final-evidence audit

Read-only local review during the original timed run. This is an interim audit while coordinator/commerce work continues; final counts and restoration must be refreshed at handback. No browser operation, configuration write, issue mutation or product edit was performed by this audit.

## Corrections needed before the final report

1. **Do not equate early renewal with due-time billing continuation.** Early Renew and credit application have real browser proof. Automatic due invoice generation, scheduled payment processing, failed-renewal retry and alignment of both scheduler hooks remain unverified. A successful initial/manual payment would not close that gap by itself. Keep it explicit even on a FAIL verdict.
2. **Reconcile all pending cleanup entries.** Current ledger still lists Cancel Immediately false pending restoration to true and an email Subject draft/save pending restoration to its empty/default original. Active owned subscriptions 109453/109475/109511 and Pending custom subscription 109525 need their planned final state recorded; owned products/draft copies/variation children need trash/draft/retention accounting. Do not say “no scheduled debits remain” from “no financial request is pending”: inspect targeted future jobs if possible, otherwise report that limitation. Customer 3252 and sandbox orders may be retained as tagged evidence. Credit balance is already proven zero.
3. **Use the refreshed central register, not early lane snapshots.** coverage.md and commerce-coverage.md retain superseded “pending” rows alongside later successful results (wizard reload, payment, variations/box saves and pause/resume). plan.md still contains initial “discovery pending” text and baseline.private.md is only a placeholder. Reconcile or clearly mark these historical before delivery. Final numeric counts must come from the regenerated register after coordinator saves, module contrasts and source-only email discovery finish.
4. **Complete the later occurrence fields in issue #150.** The original customer 595 occurrence has the required fields. Its later run-owned Page 109473/A3252 occurrence should explicitly carry login rqa0208.customera, email rqa0208-a@example.invalid, customer role, product 109433, subscription 109453 and initial order 109452. Append that the page was actually trashed, replacing the outstanding cleanup impression. No authentication-loss or cache root cause is proven; shared caching remains an inference.
5. **Update issue #153's counterexample and severity narrative.** It correctly documents the required-reason failure, exact route, roles/IDs, processing/error state and bounded reproduction. The body says “high” while frontmatter is critical/release-blocker; use one classification in the report. Its “only RequireReason=true tested so far” sentence is now historical: the false-reason Pause→Resume contrast passed. Add that counterexample and the verified restored policy. Do not describe all Pause modes as broken.
6. **Keep role/fixture scope precise.** Three distinct customer identities were used: run-owned A3252, existing B2595 and existing entitled customer595, plus administrator1 and unauthenticated guests. Positive My Features rendering on the administrator-owned account does not prove ordinary-customer feature assignment/access or customer REST privacy. The latter remains a gap.
7. **Preserve the limited scope of matched issues.** #8 is a deliberately deferred historical issue with only grouped-draft checked-flag behavior reconfirmed. External products, direct meta/helper behavior and a grouped purchase were not retested; Plans correctly excluding that grouped draft is a counterexample. #19 is a confirmed fresh-guest HTTP200 for the known synthetic8×8 raw file, not proof of a newly regressed signed endpoint or exposure of actual personal documents. #115/#146 are observed live recurrences with locally documented fixes; source/deployment alignment remains unknown. #146's present fixture had no Invoice action, so it does not verify current PDF download/ownership.
8. **Keep #127 suspected and BLOCKED, not a confirmed current-source regression.** The first Escape did open a shared confirmation; subsequent Keep editing/repeated Escape produced an unresponsive session. There was no second reproduction or post-freeze console/CPU proof in this run. Saved cancellation settings were unchanged in recovery. Record the observation and deployment caveat without upgrading it into a new confirmed source defect.

## Central-index corrections already handed to the index owner

The index owner acknowledged and implemented these changes during this audit:

- Added workflow assertions for confirmed raw-upload #19 FAIL, required-reason Pause #153 FAIL, optional-reason Pause/Resume PASS, and suspected cancellation #127 BLOCKED. These are separate from template counts and from a settings editor's successful save.
- Marked Cancellation wizard interaction BLOCKED while retaining the counterexample that all seven step forms rendered.
- Counted the inline Store Credit adjustment as a mode of the credit-detail template family, rather than calling it a separate modal/family.
- Added the source-expected Webhook Endpoint Switched Off admin email (`arraysubs_admin_webhook_disabled`) as NOT-TESTED under deployment blocker #151. This is additional to the29 observed live email editors; no missing live URL was guessed.

At the reviewed intermediate snapshot, counts were96 families/82 visited,156 modes/133 visited,73 independent save editors/23 tested. These are **not final numbers**: the index owner is adding the source-only email and coordinator work continues.

## Evidence and field checks

Issues150–153 and matched19/8/115/127/146 contain reproduction, expected/actual observations, evidence, route, browser context and scope/counterexamples. Aside from issue150's later-fixture identity/cleanup clarification and issue153's updated contrast noted above, no critical new-issue field omission was found.

All exact `evidence/...` file references extracted from coverage.md, commerce-coverage.md, access-coverage.md, premium-coverage.md, emails-coverage.md and the reviewed central surface register existed. Some prose uses shorthand filenames or txt/png suffixes; final report links should target actual files. For profile render, the worker image is evidence/premium-profile.png (not premium-profile-form.png). For the raw-file screenshot use evidence/security-box-raw-known.png; the network proof is evidence/security-box-raw-network.txt.

No confirmed email defect remains from this lane: Store Credit Used's initially empty subject resolved after the explicit rendered-subject wait. All29 live email forms and embedded previews were inspected; persistence/delivery/attachments are separate tests. Do not open a blank-subject issue from the early screenshot.

The completed worker mutation ledger shows URL/Role/Discount names restored; coordinator evidence shows Shop name restored exactly with its checkbox false; credit grant109499/debit109515 reconciled to0.00. Cancellation changes were local unsaved drafts only. All premium/email/security guest browser sessions were closed before this audit.

## Verdict implication

Required-reason Pause #153 is a confirmed release-blocking customer workflow failure on the live target, so FAIL takes precedence over the substantial coverage/deployment gaps. The final report should still distinguish browser-verified deployed behavior from untested local release code and retain every unresolved save/workflow gap. No unconditional release pass is supported.

## Additional index metadata and contrast correction

The index owner was sent two further corrections: Activity/Portal Errors/Jobs and the portal failure detail use the Pro Audits backend with core UI; the feature-log backend is Pro FeatureManager (`FeatureController::getAdminFeatureLog`). Default core-only ownership labels would be misleading, although worker ownership is correctly kept separate.

Cart Info Editor is already visible within General (evidence/settings-general.txt, lines145–151): all three hide switches are false. Its render belongs under the existing General family/save editor; do not add a new page or editor count. The hide/show cart/email downstream contrast was not exercised and should be an explicit gap.

After the source-only Webhooks alert email addition, the acknowledged interim totals are82/96 families,133/157 modes (133/147 live,10 source-expected),23/74 independent save editors. These remain provisional. No additional concrete counting error was found after the inline-credit-family correction.

## Later commerce reconciliation follow-up

At approximately minute46, a second read-only review found the commerce lane had reconciled earlier pending rows: product creation persistence, variation/box saves, private notes, ordinary-customer feature display, Redirect-panel persistence, and optional Pause/Resume now have final evidence. Features and billing company were restored; Redirect changed only an unpublished owned draft that was trashed. Four intended TEST orders total25.93, with three Completed and one Processing physical trial order.

**Future scheduler alignment is no longer wholly unverified:** scoped Scheduled Actions UI shows one invoice/payment pair for each of109453/109475/109511, six hours apart and within the configured payment spread. Exact deterministic offsets are source-derived; deployed helper/stored seconds remain unverified. This supersedes the alignment portion of correction1 above. Due-time execution, retry and scheduler locking/idempotency remain gaps.

Commerce acknowledged normalizing Box builder/cart depth to interaction, retaining the unknown disposition of variation children109544/109546 after parent trash, and rechecking pending argument searches after cancellation. Three active/trial subscriptions, Pending109525 and two published products still awaited the coordinator's cleanup handoff when reviewed. No further counting or functional overclaim was identified in the updated commerce shards.

## Final text privacy and issue-field audit

At approximately minute51, scanned317 Markdown/JSON/JSONL/TXT/YAML artifacts, then expanded the check to321 readable non-media files including Python and extensionless metadata. Actual credential, auth-header/cookie, transient nonce/order-key, Stripe client-secret/API-secret and JWT findings:0. Redactions required:0; artifact bodies changed by this privacy scan:0. One broad keyword candidate was ordinary authorization prose, not a secret. Binary screenshots/video were outside this text-only scan.

Mandatory fields present in new issues150–153:4/4. Status checks:150–153 open; matched8 open,19 blocked,115/127/146 closed. No duplicate issue or status change by this audit. Later identity/cleanup fields in150 and the optional-reason counterexample/restored policy in153 are appended.

Run-owned temporary authentication paths inventoried, without reading or deleting their contents: /tmp/rqa0208-admin-state.json, /tmp/rqa0208-customer-a-state.json, /tmp/rqa0208-customer-b-state.json, /tmp/rqa0208-entitled-state.json. Coordinator subsequently confirmed all sessions closed and took deletion ownership. Two additional run-owned temporary card-entry images were inventoried: /tmp/rqa0208-card-filled.png and /tmp/rqa0208-card-filled2.png. No temp file deletion was performed by this worker.
