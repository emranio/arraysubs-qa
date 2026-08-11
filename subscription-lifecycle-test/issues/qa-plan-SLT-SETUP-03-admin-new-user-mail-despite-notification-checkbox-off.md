# User-notification checkbox does not suppress WordPress admin registration mail

- **Severity:** low (QA plan expectation defect; customer notification suppression works)
- **Found:** 2026-08-02, D0
- **Status:** resolved 2026-08-02 — task contract corrected
- **Originating QA progress task:** board task `#12`, `SLT-SETUP-03`, stage/window day D0 (`foundation`)
- **QA plan file:** `qa/subscription-lifecycle-test/kanban/tasks/012-slt-setup-03-create-the-slt-account-matrix-7-slt.md`

## Affected objects

| | |
|---|---|
| Subscription IDs | N/A |
| Order IDs | N/A |
| Product IDs | N/A |
| Created WP user IDs | `347` through `355`: `slt-core`, `slt-trial`, `slt-switch`, `slt-flex`, `slt-fail`, `slt-paddle`, `slt-admincreated`, `slt-flex2`, `slt-flex3`; all role `customer` |
| Acting WP user | ID `1`, login `admin`, email `admin@mirror-help.arrayhash.com`, role `administrator` |
| Gateway / checkout | N/A |
| Exact route | `https://mirror-help.arrayhash.com/wp-admin/user-new.php` |
| Browser/user context | `admin-SLT-SETUP-03`, Headless Chrome 149.0.0.0, WordPress administrator |
| Non-default settings | None relevant |

## Affected user / customer context

- Acting WordPress user ID(s): `1`
- Acting login / email: `admin` / `admin@mirror-help.arrayhash.com`
- Acting role(s): `administrator`
- Created WordPress user ID(s): `347` through `355`
- Created login / email set:
  - `slt-core` / `slt-core@example.test`
  - `slt-trial` / `slt-trial@example.test`
  - `slt-switch` / `slt-switch@example.test`
  - `slt-flex` / `slt-flex@example.test`
  - `slt-fail` / `slt-fail@example.test`
  - `slt-paddle` / `slt-paddle@example.test`
  - `slt-admincreated` / `slt-admincreated@example.test`
  - `slt-flex2` / `slt-flex2@example.test`
  - `slt-flex3` / `slt-flex3@example.test`
- Created role(s): `customer`

## Expected result

The task says that unchecking **Send the new user an email about their account** must keep
`mailpit-agent latest-id` unchanged across all user creations, with no mail sent to anyone.

## Actual result

The checkbox suppressed customer-facing account mail, but WordPress still sent one
`[mirror-help.arrayhash.com] New User Registration` notification to
`admin@mirror-help.arrayhash.com` for every created account. Nine planned users therefore produced nine
admin messages. No message was addressed to any `slt-*@example.test` customer.

| User | Mailpit ID |
|---|---|
| `slt-core` | `4JzvL6XchRrGvispuldoFY` |
| `slt-trial` | `1DRCYLnCvmcSBarzx5H494` |
| `slt-switch` | `4J8qoNUMLHCkAO9FSKDCzE` |
| `slt-flex` | `39kaOe09lMuxgQjSyYoIW0` |
| `slt-fail` | `0QR9UQpvLEwgQetk7ONl8r` |
| `slt-paddle` | `0L1Pn4CfF9GmeehR3YurK6` |
| `slt-admincreated` | `0B0Wp9y9Ndj5pTv5KMi3ia` |
| `slt-flex2` | `5aCbQEoFV0bi6ITrEKAloN` |
| `slt-flex3` | `56AX8SAvP293phU1MmHzWK` |

## Reproduction steps

1. Save `mailpit-agent latest-id`.
2. Open the Add User route above as administrator.
3. Fill a unique username/email, names, password, and role `Customer`.
4. Uncheck **Send the new user an email about their account**.
5. Click **Add User** and observe `New user created.`.
6. Run `mailpit-agent list 10`.
7. Observe a new admin registration notification naming the created user, while no customer account email exists.

## Concrete proof

- Mailpit baseline: `43I8xZYyWDz2sQZ3c31o0x`.
- Captured message list: `/home/server-manager/slt-evidence/SLT-SETUP-03-mailpit-after-users.json`.
- Example latest message `56AX8SAvP293phU1MmHzWK`: recipient `admin@mirror-help.arrayhash.com`, subject
  `[mirror-help.arrayhash.com] New User Registration`, body names `slt-flex3` and its email.
- User matrix: `/home/server-manager/slt-evidence/SLT-SETUP-03-user-matrix.tsv`.
- Browser list screenshot: `/home/server-manager/slt-evidence/SLT-SETUP-03-01-users-list-slt.png`.

## Scope notes and counterexample

- This is a QA-plan expectation defect, not evidence that ArraySubs sent an unexpected customer email.
- The visible checkbox wording is specifically about emailing the new user. That promise held: zero messages
  went to the nine customer addresses. Only the WordPress administrator notification remained.
- A separate temporary `slt-console-probe` user was later created to retry an intermittent console observation;
  it produced the same admin-only mail and was immediately deleted after confirming it owned no posts. It is
  not part of the nine-message matrix above.

## Suggested resolution

Change `SLT-SETUP-03` to expect exactly one WordPress admin registration message per created account and zero
customer account messages when the checkbox is off. Future mail reconciliation should map those admin messages
instead of treating an unchanged Mailpit ID as the pass condition.

## Resolution and verification

- Corrected `SLT-SETUP-03` to expect one WordPress administrator registration notification per account and
  no customer-facing account email when **Send User Notification** is off.
- Brought the same task's stale account count and guest-checkout description into line with its binding
  conflict resolutions: nine registered accounts, guest checkout enabled site-wide, and registration forced
  specifically for subscription carts.
- Re-reviewed the captured Mailpit set: the nine planned account creations map one-to-one to nine messages
  addressed to `admin@mirror-help.arrayhash.com`; none is addressed to an `slt-*@example.test` customer.
