# Premium lane findings

Initial URL rule name save toast was followed by original name on reload during possible overlapping coordinator settings saves. Isolated repeat after coordinator paused writes persisted correctly. Not a confirmed reproducible defect; concurrency is a coverage concern. No eligibility changes.

## Suspected deployed recurrence of issue #127: Cancellation wizard Escape freezes renderer

- Active QA task: #2/#4; scheduled day 2026-10-31 Asia/Dhaka; plan qa/release-2026-10-31-0208/plan.md.
- Subscriptions/orders: N/A, admin configuration only. Customer: N/A. WordPress administrator admin, user 1.
- URL: https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/cancellation-flow
- Browser: rqa0208-premium-admin, desktop 1280×720, baseline profile A with editor-only unsaved toggles.
- Steps: Open Edit cancellation flow; Continue to Retention Offers; turn retention on in local draft; reveal/enable each offer while inspecting; invalid discount 101% rejected, restore 20% and advance to Contact Support; Escape opens shared Discard your changes modal; choose Keep editing; press Escape again. No Save click.
- Expected: Keep editing preserves draft, repeated Escape opens shared discard prompt; confirmed discard closes and resets draft.
- Actual: Initial shared prompt renders; after Keep editing and immediate repeated Escape, browser snapshot waits indefinitely/renderer unresponsive. CLI doctor reports Chrome+daemon healthy. Pending wait/snapshot processes interrupted after budget bound; no further repro.
- Proof: evidence/premium-cancel-shared-confirm.png (initial prompt), premium-cancel-contact.png (unsaved draft); browser tool pending process observations in chat. No console error before freeze; cannot query frozen renderer afterwards.
- Counterexample/scope: first Escape did open prompt. New isolated recovery session loaded saved overview with retention disabled and all 4 offers disabled, proving no draft saved. Initial 1280-wide full-page screenshots contain fixed-position capture artifacts, so these are not by themselves visual-layout bugs.
- Assessment: Suspected recurrence matching previously closed #127; live deployment is older/misaligned, so cannot claim current local fix regressed. Preserve existing closed status, append run evidence for investigation.
