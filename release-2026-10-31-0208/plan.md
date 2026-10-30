# Release QA — 2026-10-31

Scope: ArraySubs + ArraySubsPro active together, required WooCommerce dependencies; live browser regression on https://mirror-help.arrayhash.com. Credential source: workspace AGENTS.md, Live QA access. No credentials copied here.

Start: 2026-10-31 02:08:13 Asia/Dhaka (2026-10-30 20:08:13 UTC). Hard finish: 03:08:13. Testing stops 03:01:13. One shared clock: clock.json.

| Phase | Local schedule | Work |
| --- | --- | --- |
| Preflight | 02:08:13–02:14:13 | Fresh board, baseline, inventory, fixtures |
| Sweep | 02:14:13–02:32:13 | Distinct templates and independent save editors |
| Journeys | 02:32:13–02:52:13 | Commerce, lifecycle, access and ownership |
| Contrasts | 02:52:13–03:01:13 | A/B/C targeted variations |
| Cleanup/report | 03:01:13–03:08:13 | Restore, reconcile, final report |

Coordinator owns all global settings/modules and issue-board writes. Workers own disjoint isolated records/sessions. Workers stop new mutations at02:59:13, hand back by03:00:13. No product fixes, builds, Composer, PHPCS, license mutation, external endpoint registration or real payments/messages.

## Baseline and prerequisites
Live versions/module/license/payment/mail status: discovery pending. Local revisions are supporting evidence only; deployment alignment unverified. Local WP-CLI fails database connection, so no local site writes will be attempted. Use browser for live assertions. Mail/payment safety must be established before transaction completion; otherwise record blocked path.

Roles: rqa0208-admin coordinator; worker-prefixed admin/customer-A/customer-B/guest sessions as assigned. Minimum fixtures: standard recurring, eligible lifecycle/renewal, entitled/unentitled customers and premium trial/shipping contrast. Prefer existing tagged fixtures, create only run-owned minimums. IDs recorded as discovered.

## Profiles
A: baseline current settings, both plugins active. Exact values pending discovery.
B: paid versus trial plus one premium commerce path; only existing safe manual/sandbox payment. Positive and rejected eligibility case.
C: one action permission allowed/disabled and one safe optional module off/on; coordinator only, after workers acknowledge pause. Exact values and restoration ledger recorded before changes.

## Exclusions and limits
License activation, secret changes, import/reset and service provisioning are inspect-only. External integrations/language providers/alternative gateways require existing safe setup. Missing fixtures and time-limited templates remain explicit gaps.
