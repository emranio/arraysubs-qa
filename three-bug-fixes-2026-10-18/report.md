# Three product bug fixes — 2026-10-18

Scope: issues #44, #45, and #6, handled in that order. This is focused fix verification, not a revival of the removed subscription-lifecycle QA plan. Scheduled day: 2026-10-18, immediate local execution. Site: http://localhost:10013. Administrator: user 1, `admin`; no new customer accounts or payment orders were required.

## Changes and results

### #44 — admin renewal-price toggle

Registered the missing `_enable_different_renewal_price` metadata using the existing native WordPress REST contract, a yes/no sanitizer, and administrator/WooCommerce-management capabilities. No form, edit-endpoint, pricing, or Pro implementation change.

- Browser baseline: new pending subscription 12091 saved price 29 and threshold 3 but had no enabled flag.
- Browser after fix: new pending subscription 12094 stored `yes`, 29, 3. Its detail screen showed “Different Renewal Price: $29.00” and “Applies After: 3 payments.”
- Supplemental actual custom REST updates returned 200 and stored `no` / `yes` when toggled off / on. The current edit page does not expose the switch; these were endpoint checks, not an edit-UI claim.
- Core-only runtime load retained the registration and saved value.
- Evidence: [before creation](screenshots/44-before-create.png), [after detail](screenshots/44-after-detail.png).

### #45 — external member downloads

Validated the saved external target before quota consumption, temporarily allowed its exact configured host for WordPress validation, removed that filter, then redirected to the validated URL. Existing HMAC, login, condition/drip checks, quota locks, and local streaming are retained.

- Temporary administrator-only rule `fix45-20261018`, user 1; subscription/order IDs: N/A. Original download rules were backed up and restored, with an equality check.
- Browser baseline: a valid protected external link fell back to `/wp-admin/` and used one allowance.
- Browser after fix: same link reached `http://127.0.0.1:10013/wp-content/uploads/arraysubs-bug-fix-20261018.txt`, displaying the actual file. The site host is `localhost`, so this exercised a distinct external host without a global allowlist.
- Browser denial cases: FTP target, embedded credentials, missing local file, modified signature, guest, and exhausted allowance. Invalid/missing/credential files remained at zero used; the exhausted external record remained capped at three, including the baseline attempt.
- Local file navigation initiated a download (`ERR_ABORTED`, expected for a download navigation) and counted once. The prior error document remained visible; it was not mistaken for the downloaded file.
- Remote-server success after a valid redirect is outside quota accounting. No remote requests or rollback system were added.
- Evidence: [browser cases](45-browser-cases.json), [counters](45-counts-after.jsonl), [external file](screenshots/45-after-external-file.png).

### #6 — expiring-soon subscription email

Added a focused service under the existing Email provider. It schedules one reminder from the actual final payment boundary or eligible Pro fixed end, reconciles completed metadata/status changes, and processes settings/initial/daily recovery in bounded batches through the shared ActionScheduler. It does not change billing dates, payment processing, or expiration rules.

- Existing-subscription initialization queues a bounded scan once; its separate, non-autoloaded feature marker records successful queueing, not a plugin version or completed migration.
- A claimed stale job rechecks current eligibility and lead time before sending. Inactive, unlimited, lifetime, mirrored-child, pending-cancellation, and annually auto-renewing fixed-period memberships are excluded.
- Shared locks and the existing observed-delivery helper protect the target-specific sent guard. Disabled and failed transport attempts do not consume it.
- Email content uses the calculated final date and actual remaining days, including subscriptions already inside the configured lead window.
- Baseline fixture 12104: manual day/2, length 3, completed payments 1, next payment October 20; its final boundary was October 22. Browser scheduler initially contained only renewal invoice and payment actions. After the fix, action 10898 ran through the actual asynchronous queue and Mailpit received the subscription email.
- Final message fixture 12318 exercised the corrected inside-window copy through the real queue and local Mailpit sink. Invoice/customer email was `qa-bugfix20261018@example.test`; customer/admin 1; orders N/A.
- Runtime matrix: **37 passed with Pro loaded, 37 passed with Pro skipped**. Coverage includes target math, unchanged action identity, preserved billing metadata, stale-job requeue, prompt signup scheduling, duplicate delivery, actual date/day count, transport failure, both email enable controls, revised lead time, all inactive states, unlimited/lifetime/completed exclusions, cancellation, box/bundle children, invalid dates, fixed dates without renewals, annual auto-renew exclusion, free trials, month-end rollover, DST, provider autoload, bounded recovery and teardown.
- Runtime tests intercept email transport only for their temporary fixtures and delete those fixtures. Browser queue/Mailpit proof uses the real transport separately.
- Evidence: [test source](email-regression.php), [Pro results](email-regression-results.json), [core-only results](email-regression-core-only-results.json), [final Mailpit browser text](6-mailpit-final.txt), [fixture records](fixture-summary.json).

## Verification limits and cleanup

No PHPCS/lint or Composer installation was run. No real gateway charges, provider API calls, releases, or pushes were performed. Pro was skipped per WP-CLI request for the core-only matrix; its persistent activation state was not changed. The current site has both plugins active. Product source files remain below 3,000 lines. Only core needs a changelog entry; Pro source and its draft are unchanged.

All four browser/setup subscriptions (12091, 12094, 12104, 12318), the temporary upload, and the five fixture-only download-access records were removed after verification. Original download rules were restored and both plugins remain active. Runtime-matrix fixtures were already deleted by their test cleanup.
