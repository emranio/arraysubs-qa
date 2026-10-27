---
id: 133
title: Add Translations screen to ArraySubs settings
status: closed
priority: high
created: 2026-10-28T02:47:32.333897+06:00
updated: 2026-10-28T03:18:35.780017+06:00
tags:
    - i18n
    - settings
parent: 128
class: standard
---

Parent128 userapproveddirectsettingsUI. OwncoreReact/menu/routes/page only. Reuse sharedSettingsnavigation, DataList/FormBuilder/SpinnerButton/toasts andWPadminaccent. PickTPconfiguredtargetlanguage, search/filter/paginate safecatalog, read-onlysource andeditabletranslation, saving/loading/errors. TPnotactivehelp; do notalterstoredsourceoraddAIcost. Coordinatebackendcontract withdynamic_core. Worktree /tmp/arraysubs-multilingual/arraysubs. RootownsWP/browserQA.

Implemented Settings > Translations navigation/lazy route and admin page using existing DataList search/group/status pagination and optional on-demand FormBuilder product selector (no preload, twenty results/search). Row SquarePen opens shared Modal/FormBuilder with read-only source, editable target, SpinnerButton save, visible errors/toasts and shared dirty/discard confirmation. Native TP unavailable/language-setup help uses correct wp-admin links. Source IDs/internal paths hidden; matching-original shared-translation note shown. Language/product changes reset catalog context; saving only changes DataList refreshKey prop and preserves current search/filter/page state. Product scope included in GET/POST. Nine focused React/API tests pass: read-only source, save/loading, retry draft preservation, discard, empty reset, Unicode/nonce/product scope, plain REST permalink support, stale/malformed errors, provider help and no-preload real AJAX select behavior. Babel parsed new page/editor/Main; parent owns app builds/browser QA and backend owns draft. No code committed yet.

UI committed as e35926f after parent acceptance and coordinated Git index window with backend agent. Only 10 owned UI/menu/router/test/config files included; backend/draft untouched. All 9 focused UI/API tests pass, Babel parses page/editor/Main, git diff check clean, wc counts under limit (Main818, Menu451, page193, editor106, API36, stylesheet39). Pending parent merge/build/actual browser verification before task closure.

Live parent QA passed custom quoted Subscribe button editing/saving/native Spanish storefront output, original DB source unchanged and list search preserved. Addressed the discovered validation UX gap in follow-up commit709d86d: persistent inline role=alert below translation, connected aria-invalid/aria-describedby, existing toast retained, draft/modal preserved after errors, message clears on correction/new save. Ten UI/API tests pass including persistence/retry/correction; source117/style40/test97 lines, diff check clean. PHP required-variable message follow-up d676694 is its parent; shared branch head709d86d ready for parent merge/rebuild and final product-picker/stale-source browser checks.

Parent accepted final live browser QA on http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/translations after source merge and successful build: Spanish language selection; search and selection preserved after save; read-only source; quoted/ampersand Subscribe label saved and verified on the actual storefront with original English DB option unchanged; on-demand product lookup selected product5900 and translated its conditional Subscription Box step through this editor; missing-placeholder save returned400 and retained draft/modal with persistent inline correction guidance; corrected save succeeded; Escape/Keep editing preserved draft and explicit Discard closed; actual inactive-provider help verified and TranslatePress restored active. UI/API suite10/10 passing. Task implementation and verification complete; parent128 owns fixture cleanup, final POT regeneration/report and combined board commit. No further code changes.
