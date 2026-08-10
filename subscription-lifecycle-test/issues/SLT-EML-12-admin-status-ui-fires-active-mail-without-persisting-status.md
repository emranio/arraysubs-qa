# SLT-EML-12: admin subscription status UI fires active mail without persisting status, then subscriptions React UI collapses

- Status: **RETRACTED — concurrent QA transitions invalidated the observation; not a product finding**

## Retraction evidence

The apparent non-persistence was caused by two QA owners mutating H1 concurrently, not by the product:

1. The first owner's Active transition emitted customer/admin pair `6IU3cBwmT9dWpQxtFW8Hra` / `2G3O9lw9OovzM1ecZNohbb` at `14:19:52Z` / `14:19:53Z`.
2. The root watcher had independently captured `MP0=3g8Vwfvn45nsiIoOg0vwCH` at `14:19:20Z`, then selected and confirmed **Pending** on the same H1. Its post-check at `14:20:34Z` proved `arraysubs-pending`. That later Pending transition fully explains why the first owner read Pending after its Active mail.
3. The root watcher then confirmed **Active** in the browser; the second pair `1gpznceQ5LZsi6NK7FTZlp` / `65pK2vT5zGVU7UHiyphXFF` arrived at `14:21:28Z` / `14:21:29Z`, and H1 was active. This pair is not proof that the first UI transition failed to persist.
4. The `All(0)` screenshot was a transient, not a collapsed data set: after the normal SPA wait, the same browser route rendered `All(375)`, `Active(33)`, and the full subscription rows.

The root watcher stopped at `14:22:41Z`, with H1 `arraysubs-active` and the New Subscription option row absent. The overlap is documented in `/home/server-manager/slt-evidence/SLT-EML-12-root-concurrency-note.txt`. No product defect remains from this observation; the 21:00 override test may continue with one owner.

## QA context

- QA progress task: `#56` / `SLT-EML-12`
- Stage/day: subscription lifecycle test D03 (`2026-08-05`)
- Date found: `2026-08-05`
- QA plan: `qa/subscription-lifecycle-test/kanban/tasks/056-override-subject-heading-and-content-on-new.md`
- Severity: N/A (retracted QA-concurrency artifact; originally triaged high)

## Exact routes / browser context

- Browser context: `agent-browser --session admin-SLT-EML-12`
- Subscriptions index route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions`
- Emitted detail route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/12786`
- Acting WordPress user: `admin`

## Affected records

| Field | Value |
|---|---|
| Subscription IDs | `12786` (`H1`) |
| Order IDs | `12776` |
| Product IDs | `11938` (`SLT Lifetime One Time`) |
| WordPress user/customer IDs | `366`, login/email `slt-email` / `slt-email@example.test`, role `customer` |
| Acting WP user | ID `1`, login `admin`, email `admin@mirror-help.arrayhash.com`, role `administrator` |
| Gateway / checkout | Stripe / existing subscription admin flow |
| Exact routes | `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions`, emitted detail link `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/12786` |
| Browser/user context | `admin-SLT-EML-12`, WordPress administrator |
| Non-default settings | None; the D03 override bracket never opened and no email-setting row was saved |

## Expected result

On Wednesday, August 5, 2026:

1. Setting `H1` from `Pending` to `Active` in the admin subscription UI should persist `post_status=arraysubs-active`.
2. That one persisted transition should emit exactly one customer `new_subscription` email and one admin `admin_new_subscription` email.
3. The subscriptions list/detail React UI should remain usable so the task can continue into the 21:00-21:40 site override bracket.

## Actual result

No product defect remains. The original observation was invalidated by concurrent QA ownership on the same subscription and is now preserved only as a retracted evidence trail.

## Original observation (invalidated by the retraction timeline above)

The admin browser path split into two failures:

1. Step 3 behaved correctly: starting from Mailpit cursor `3g8Vwfvn45nsiIoOg0vwCH`, the UI `Active -> Pending` transition sent no mail, and `wp post get 12786 --field=post_status --allow-root` returned `arraysubs-pending`.
2. Step 4 misfired: the UI `Pending -> Active` modal emitted the default customer/admin mail pair, but the persisted status stayed `arraysubs-pending`.
3. After that transition attempt, the subscriptions React UI became unusable: fresh admin sessions rendered the index with `All(0)`, `Active(0)`, `Pending(0)` and no rows, while direct `#/subscriptions/detail/12786` and `#/subscriptions/edit/12786` routes rendered only the outer shell.
4. Cleanup via `wp post update 12786 --post_status=arraysubs-active --allow-root` restored the DB status, but it emitted a second identical customer/admin mail pair. That proves the first UI mail pair was not the successful persisted transition.

## Reproduction steps

These were the original overlapping steps that produced the now-retracted observation. They are preserved for auditability but are not a valid single-owner reproduction of a product defect.

## Original reproduction steps (not reproducible under single ownership)

1. Confirm `H1` is `arraysubs-active` and note `mailpit-agent latest-id`.
2. In browser session `admin-SLT-EML-12`, open the subscriptions UI and edit subscription `#12786`.
3. Change status to `Pending` and confirm the modal.
4. Verify the Mailpit cursor is unchanged and `wp post get 12786 --field=post_status --allow-root` returns `arraysubs-pending`.
5. Change status back to `Active` in the same UI flow and confirm the modal.
6. Observe one customer `is active` mail and one admin `New subscription` mail.
7. Immediately run `wp post get 12786 --field=post_status --allow-root`.
8. Observe the status is still `arraysubs-pending`.
9. Re-open the subscriptions index or direct detail route in a fresh browser session.
10. Observe the React index shows zero counts and the direct detail/edit routes render only the shell.

## Concrete proof

The following evidence now proves the overlap/retraction rather than a product defect.

## Original evidence record (reclassified as concurrency evidence)

- Silent `Active -> Pending` proof:
  - pre-cursor `MP0 = 3g8Vwfvn45nsiIoOg0vwCH`
  - post-transition `mailpit-agent latest-id` stayed `3g8Vwfvn45nsiIoOg0vwCH`
  - `wp post get 12786 --field=post_status --allow-root` returned `arraysubs-pending`
- Broken `Pending -> Active` UI pair on `2026-08-05`:
  - customer mail `6IU3cBwmT9dWpQxtFW8Hra` at `2026-08-05T14:19:52Z`
    - subject: `[mirror-help.arrayhash.com] Your subscription #12786 is active`
    - heading text: `Your subscription is now active!`
  - admin mail `2G3O9lw9OovzM1ecZNohbb` at `2026-08-05T14:19:53Z`
    - subject: `[mirror-help.arrayhash.com] New subscription #12786 from SLT Email`
- Immediately after that UI pair:
  - `wp post get 12786 --field=post_status --allow-root` still returned `arraysubs-pending`
- CLI cleanup pair on `2026-08-05`:
  - customer mail `1gpznceQ5LZsi6NK7FTZlp` at `2026-08-05T14:21:28Z`
  - admin mail `65pK2vT5zGVU7UHiyphXFF` at `2026-08-05T14:21:29Z`
  - `wp post get 12786 --field=post_status --allow-root` then returned `arraysubs-active`
- Evidence files:
  - `/home/server-manager/slt-evidence/SLT-EML-12-prior.txt`
  - `/home/server-manager/slt-evidence/SLT-EML-12-reference-no-consumer.txt`
  - `/home/server-manager/slt-evidence/SLT-EML-12-detail-before.png`
  - `/home/server-manager/slt-evidence/SLT-EML-12-00-react-empty-list.png`

## Scope notes and counterexamples

- The bug is in the admin status/UI path, not in the task's global override bracket:
  - no WooCommerce email setting was changed
  - the 21:00-21:40 site override bracket never opened
- The lifetime-sub fixture itself remained otherwise valid throughout:
  - `_next_payment_date` stayed empty
  - `_start_date` remained `2026-08-05 13:26:24`
  - `_payment_method_title` remained `Stripe`
- The passing counterexample is the `Active -> Pending` leg on the same subscription and same day: it persisted and sent no mail as expected.
- No `arraysubs/` or `arraysubspro/` file was opened or modified while recording this issue.
