# Stage 10 — Profile Builder & My Account Customization

**Goal:** Validate the ArraySubs Profile Builder admin pages: custom Profile Form fields (six field types with required/optional behavior, persistence, admin visibility), avatar upload settings (drag-and-drop, file size and type validation, Gravatar fallback), the My Account Editor (reorder, rename, hide, custom endpoints), and the four account/visibility shortcodes ([arraysubs_login], [arraysubs_logout], [arraysubs_user], plus the shortcodes-reference catalog page in admin). Every task is performed by a human tester clicking through wp-admin and the customer-facing My Account page in a real browser.

**Prerequisites:**
- Stages 00–09 complete. Stage 09 rules cleaned up (only the Role Mapping rule from Task 09.01 may remain enabled).
- ArraySubs and (for some sub-tasks) ArraySubs Pro active.
- WooCommerce My Account page set up at `/my-account/` with default endpoints (Dashboard, Orders, Downloads, Addresses, Account Details, Logout).
- A test customer `pf_test@example.com` registered with a known password and Customer role. This customer will be used as the primary frontend tester for profile fields and avatar.
- A second customer `pf_test2@example.com` for cross-checks.
- An administrator user `qa_admin` for editing settings and viewing the admin user-edit screen.
- A test image file `avatar-ok.png` (≤ 2 MB) ready in the QA assets folder for avatar upload.
- A test image file `avatar-large.jpg` (~5 MB, exceeds default 2 MB) ready for size-rejection test.
- A test file `avatar-bad.txt` (a `.txt` file) for type-rejection test.
- A test PDF `id-doc.pdf` (≤ 5 MB) ready for the file-upload field test.
- A WordPress page **Help Center** at slug `help-center`, published, with body content built using normal Gutenberg blocks (used in My Account Editor custom endpoint test).
- A test page **Account Shortcodes Sandbox** at slug `account-shortcodes-sandbox`, published, empty body (used in Task 04).
- All Stage 09 leftover test rules cleaned up before starting Stage 10.

**Run order:**
1. [01 — Custom profile fields](01-custom-profile-fields.md) — Add 6 custom profile fields (text, textarea, select, date, checkbox, file upload). Verify rendering, required validation, persistence, and admin visibility.
2. [02 — Avatar settings](02-avatar-settings.md) — Enable avatar upload with max file size and allowed types. Drag-and-drop upload, oversize/disallowed-type rejection, Gravatar fallback verification.
3. [03 — My Account editor](03-my-account-editor.md) — Reorder, rename, hide menu items. Add a custom endpoint linked to **Help Center** with Prevent direct access.
4. [04 — Shortcodes & page display](04-shortcodes-and-page-display.md) — Place [arraysubs_login], [arraysubs_logout], [arraysubs_user] on a sandbox page. Verify rendering by login state. Verify the admin shortcodes-reference page lists all available shortcodes.

**Exit criteria:**
- All 4 task files signed PASS, or failures recorded with screenshots and reproduction steps.
- The exact UI strings recorded verbatim during testing match the user manual:
  - Profile Form section title in admin: **ArraySubs Profile Fields**.
  - Customer-facing avatar section: **Profile Photo**, **Upload Avatar** / **Change Avatar** / **Remove Avatar** buttons.
  - My Account Editor toggle: **Enable My Account menu items customization**.
  - "Endpoint conflicts with reserved name" error appears when trying to use a reserved slug like `orders`.
  - Shortcode reference page lists `[arraysubs_login]`, `[arraysubs_logout]`, `[arraysubs_user]`, `[arraysubs_visibility]`, `[arraysubs_restrict]`, and (Pro) `[arraysubs_buy_credits]`.
- All custom profile fields persist across logout/login.
- File upload field stores files in `wp-uploads/arraysubs-profiles/{user_id}/` with `.htaccess` deny-all protection (verify by attempting direct URL access — should return 403/404).
- Avatar files stored in `wp-uploads/arraysubs-avatars/`.
- Custom endpoint URL `/my-account/help-center/` resolves and renders the Help Center content inside the My Account layout.
- All test profile fields, custom endpoints, and avatar uploads cleaned up at end of stage so the test customers' My Account state matches the baseline before Stage 11 (or whatever stage runs next).
