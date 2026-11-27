# Translation download verification — 2026-11-17

Result: PASS. The Modules-page link downloads the current translation pack. All downloaded catalogs match the latest generated translation files available in this workspace.

Plan: [plan.md](plan.md). Tasks TD-01 through TD-04; scheduled day 2026-11-17, Asia/Dhaka.

## Download verification

- Actual admin route: https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/modules
- Browser context: isolated browser session `translation-download-admin`, signed in as `admin`, administrator. No customer/order/subscription data changed; affected IDs N/A.
- The Multilingual card exposes **Download Translations**, pointing to https://subscriptionplugin.pro/docs/downloads/arraysubs-translations.zip.
- Activated the actual anchor using browser's download command. Browser download completed successfully; the page remained on Modules. Browser errors output was empty.
- Independent public HTTP request returned 200, `application/zip`, 36,699,829 bytes. Cloudflare reported MISS for that request.
- Browser-downloaded ZIP, public HTTP ZIP, and repository ZIP have identical bytes and SHA-256:
  `13e7c23a0c1c0e4ed43dd779954710edf8801fd04239f5aa9a87c8c731d23a40`.
- ZIP integrity/CRC validation passed for every entry.

## Freshness and coverage

The archive contains 241 files: one README and 240 catalogs, comprising 30 locales for both plugins in `.po`, `.mo`, `.json`, and `.l10n.php` formats.

Every one of the 240 catalog files matches the latest generated staged source bytes. All 60 PO catalogs cover the current plugin POT message identities exactly. There are no missing, obsolete, empty, or fuzzy translations. JavaScript catalog identities also match current templates without missing, obsolete, or empty entries.

| Plugin | Locales | Messages per locale | JavaScript messages per locale |
| --- | ---: | ---: | ---: |
| ArraySubs | 30 | 7,712 | 4,187 |
| ArraySubs Pro | 30 | 1,996 | 635 |

The catalogs were translated on November 16. The source templates have newer generation timestamps, but their message identities are identical; the timestamp difference does not indicate missing translations.

## Deployment detail

Both the ordinary and cache-busted public deployment markers report guide commit `1ceb97b0a2d295c10d31ee00d17b48e186b97338`, built at 2026-11-17 14:36:47 Asia/Dhaka. Local guide HEAD is `e5e0cb7598f3c9a1346f2ffb57cfb11139c0d451`.

The later commit changes only deployment workflow timeouts and README text. Both commits contain the same translation ZIP Git blob `c6a118ab962c9702aac88e63a7c0e60b54013bc0`. This difference does not affect translation freshness.

## Evidence and scope

- [Modules screenshot](evidence/modules.png), visually inspected for the actual download control.
- [Modules snapshot](evidence/modules-snapshot.txt) and [post-download snapshot](evidence/modules-after-download-snapshot.txt).
- [Browser download comparison](evidence/browser-download.json).
- [Public HTTP headers](evidence/download-headers.txt).
- [Per-file hashes and all-locale coverage](evidence/translation-freshness.json).
- [Deployment marker](evidence/deployment.json) and [cache-busted marker](evidence/deployment-fresh.json).

This validates download delivery, archive integrity, source freshness, and catalog coverage. It does not certify linguistic quality or install translations to test every translated screen. No plugin settings, plugin code, catalogs, or deployments were changed. Existing QA issues were preserved; no download/freshness issue was found.
