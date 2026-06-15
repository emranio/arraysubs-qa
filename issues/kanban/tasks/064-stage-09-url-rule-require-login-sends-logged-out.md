---
id: 64
title: 'stage-09: URL rule require-login sends logged-out visitor to pricing instead of login'
status: closed
priority: high
created: 2026-05-20T15:29:19.99215255+02:00
updated: 2026-05-22T03:51:36.289053002+02:00
started: 2026-05-22T03:45:31.746497522+02:00
completed: 2026-05-22T03:51:36.28905174+02:00
tags:
    - qa
    - stage-09
    - members-access
    - url-rules
claimed_by: mold-glade
claimed_at: 2026-05-22T03:51:36.289052902+02:00
class: standard
---

Task 02 configured members_access.require_login=true and URL rule Prefix /premium-content -> Redirect /pricing with Pro Plan condition. Logged-out direct request to /premium-content redirected to /pricing instead of wp-login.php or My Account login. Expected logged-out visitor sent to login first, then returned/re-evaluated.

[[2026-05-22]] Fri 03:46
Plan: verify Stage 09 Task 02 and UrlRestrictor path. Patch URL rule handling so when members_access.require_login=true and a matched restricted URL is requested by a logged-out visitor, the visitor is sent to wp_login_url(current_url) before evaluating rule denial action. Recreate the Premium Content URL gate fixture if missing. Browser/curl verify logged-out /premium-content goes to login, logged-in nonmember goes to /pricing, active member stays on /premium-content.

[[2026-05-22]] Fri 03:51
Fix: UrlRestrictor now checks members_access.require_login immediately after a URL rule matches and exclusions pass. Logged-out visitors are redirected through wp_login_url(current_url) before the rule's denied action (redirect/message/403) runs. Restored Stage 09 Task 02 fixture: Premium Content page #694, URL rule prefix /premium-content, Pro Plan #233 condition, redirect /pricing, require_login=true. Verification: curl and agent-browser logged-out direct /premium-content/ now lands on wp-login.php with redirect_to=/premium-content/; logged-in nonmember redirects to /pricing and does not see premium content; member1 is not redirected by the rule and can view page_id=694 content. Note: site uses plain permalinks, so direct /premium-content/ after allowed access renders the theme blog route, matching prior progress note; the issue-specific guest/login redirect is fixed.
