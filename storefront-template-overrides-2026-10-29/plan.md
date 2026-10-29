# Storefront child-theme overrides QA

Scope confirmed by user on 2026-10-29 (Asia/Dhaka): support child-theme customization of view-subscription.php and every other bundled storefront PHP view in ArraySubs and ArraySubs Pro; add a developer guide in user-manual.

Environment: actual local WordPress http://localhost:10013, running this workspace's sources. Local site/access and admin credentials come from AGENTS.md Local Testing Context. The remote site does not run these unshipped files and is not deployed during this cycle. WP-CLI uses --allow-root with the existing Local MySQL socket passed as PHP ini configuration, without extra environment variables. Browser automation uses agent-browser CLI and isolated template-qa-customer/admin/guest sessions, with Edge executable if needed for screenshots.

Existing qa/issues board inspected before testing; preserve all earlier issues. No unrelated issue fixes, purchases, payment collection, external mail, release, push or publishing. Temporary theme overrides, fixture customer/content and exact changed option/plugin baselines are backed up outside the repository and restored at completion.

## Immediate schedule — 2026-10-29

1. Inventory and implement all 15 storefront templates: 10 core, 5 Pro. Preserve authorization/feature checks, caller scope, nested partial fallback, WordPress comments context and existing WooCommerce email override paths.
2. Run focused resolver regression checks for child/parent/plugin priority, plugin namespace isolation, valid/readable file requirements, invalid path rejection and caller scope. Verify source integration and syntax of touched PHP files.
3. Install disposable child theme overrides copied from the actual updated sources and show visible markers. Use real customer/guest browser routes for My Account list/cards/detail and nested notice, order summary, simple/variable product output, box builder, Pro Features/Store Credit/profile/avatar/fixed/flexible views, denied page/comments as feasible. Record exact IDs, URLs, active feature requirements, snapshots, screenshots and errors. Verify default fallback after removing overrides; parent fallback and child priority; core alone with Pro inactive; unauthorized detail access still denied.
4. Review developer manual paths and example against installed behavior. Log any new defect immediately in qa/issues with task/day/plan, IDs/roles/URLs/repro/expected/actual/evidence. Restore baseline themes/options/plugins, remove temporary override files and disposable fixtures, close owned browser sessions, inspect final diffs, check all touched line counts and read back both canonical changelog drafts. Complete report and board honestly.

No recurring automation is requested; this schedule is the immediate execution order.
