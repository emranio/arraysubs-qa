# Member Access and Toolkit switches — verification

Completed 2026-10-24 (Asia/Dhaka) against the real local WordPress site http://localhost:10013. Scope and execution schedule: [plan.md](plan.md); progress board: [board/kanban](board/kanban).

Both modules now use the existing registry, persisted switches, dormant providers, menu visibility, ModuleGate routes and setup-wizard rules. Saved configuration survives switching off and back on. Member Access owns content restrictions, member benefits/styling and Pro login limits; Toolkit owns toolbar, WordPress dashboard/login access and new impersonation sessions. Existing impersonated sessions can still return to admin.

## Matrix

Every row was saved through the real Modules UI and checked again after reload. Runtime checks booted actual WordPress/WooCommerce in a new process for each saved state. Pro was genuinely deactivated for the free-only rows.

| Member Access | Toolkit | Free + Pro checks | Free-only checks |
| --- | --- | ---: | ---: |
| Off | Off | 90 passed | 79 passed |
| Off | On | 93 passed | 82 passed |
| On | Off | 89 passed | 78 passed |
| On | On | 92 passed | 81 passed |
| Total | | 364 passed | 320 passed |

684 runtime checks passed. Coverage includes live/dormant provider ownership, core subscription lifecycle and both renewal scheduler handlers, independent Toolkit controls, generic account shortcodes, read-only developer policy, admin REST availability/permissions, saved content fallbacks, module exports and wizard selections/writes. These lifecycle checks confirm registration remains active; no live renewal payment or full purchase was triggered.

## Real browser results

- Guests were denied on per-page, Gutenberg, restriction-shortcode and Elementor fixture pages with Member Access on. Switching it off showed their original inner content without exposing shortcode syntax. Switching back on reinstated the saved restrictions. An authenticated editor passed the same four login-only gates in free-only mode.
- All 13 Member Access/Styling/Content Gate/conflict/Toolkit direct routes displayed the shared switched-off guard when disabled, in both plugin setups. Menus and Configure links followed saved state. Keyboard activation, unsaved Configure visibility, Discard, Save and reload passed.
- Toolkit role options loaded with Member Access off, in both setups. Toolkit on hid the editor toolbar and redirected editor wp-admin access and guest wp-login access to My Account; Toolkit off restored the toolbar and native profile/login pages.
- New impersonation was refused while Toolkit was off. A session started while on retained its return control after switching off and returned to the original administrator. An earlier interrupted-navigation attempt invalidated its authentication context; a clean repeat passed without changing nonce/security behavior.
- Protected member-download URLs were refused while Member Access was off. A real authenticated settings POST could not overwrite disabled Toolkit or login-limit controls. Saved post restriction and Toolkit settings remained intact.
- Browser JavaScript error checks were empty on the final admin/editor sessions. Screenshots were visually inspected.

## Fixed regression

[Issue #111](../issues/tasks/111-box-freebie-product-picker-depends-on-member.md): Subscription Box freebie search used a Member Access REST route, causing 404s after disabling Member Access. The picker now uses a Box-owned endpoint and shared product-option queries. Browser searches returned real products with HTTP 200 in free + Pro, and passed again with free only. Product 5922 was opened for this check; temporary modal edits were discarded and no product configuration was saved. Issue closed.

## Validation and cleanup

Production core webpack build passed. All 50 touched PHP files passed syntax checks; scoped whitespace checks passed. All touched source files are under 3000 lines (largest: SetupController.php, 2895). No phpcs or Composer installation ran. Both canonical changelog drafts and module architecture documentation were updated.

Original ArraySubs settings, plugin activation list (including Pro), save timestamp and coming-soon setting were restored exactly; endpoint rewrites were regenerated with the restored providers. Four disposable pages (21042–21045) and editor user 471 (`qa_module_editor_20261024`, editor) were removed. Administrator 1 (`admin`) was used for admin actions; subscription/order IDs are N/A. Own temporary authentication was removed. Existing issue entries and unrelated source edits were preserved.

Evidence is in [evidence/](evidence/), including per-state runtime logs, route snapshots, screenshots, settings-write results, PHP/line-count records and restoration verification. PHP CLI runtime checks skipped Elementor's bootstrap to avoid its existing nullable-parameter deprecation; all four Elementor frontend tests used the actual active Elementor plugin in the real browser. No remote deployment, full billing cycle or external payment was performed.
