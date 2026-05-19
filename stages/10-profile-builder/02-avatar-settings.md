# Stage 10 — Task 02: Avatar Upload Settings

| Key | Value |
|---|---|
| Stage | 10 — Profile Builder & My Account Customization |
| Module | Profile Builder → Profile Form (Avatar Settings) |
| Plugin Coverage | Free |
| Estimated Time | 25 min |
| Depends On | 01-custom-profile-fields.md (or independent — Profile Form page reachable) |

## Objective
Enable avatar uploads with a configured max file size and allowed file types. Verify drag-and-drop upload on **My Account → Account Details**, that oversize files are rejected with the correct error, that disallowed-type files are rejected, and that when no custom avatar is set the system falls back to the user's Gravatar (or default WordPress avatar).

## Pre-conditions
- Logged in as `qa_admin` for admin configuration.
- `pf_test@example.com` has a known email registered with Gravatar (record current Gravatar status — if no Gravatar, the test will use the WP default avatar fallback per Settings → Discussion).
- Test files ready in QA assets folder:
  - `avatar-ok.png` — valid PNG, ~500 KB (well under 2 MB).
  - `avatar-large.jpg` — valid JPG, ~5 MB (exceeds 2 MB default).
  - `avatar-bad.txt` — plain text file with `.txt` extension.
- ArraySubs → Profile Builder → Profile Form admin page open.

## Test Data
- Avatar feature: ON
- Max File Size (MB): `2` (default)
- Allowed File Types: `jpg, jpeg, png, gif, webp` (default)
- Storage path: `wp-uploads/arraysubs-avatars/`
- Filename pattern: `{user_id}-{random}.{ext}`

## Sub-Tasks

### Sub-Task 02.1 — Enable avatar upload and confirm settings
**Steps:**
1. Go to **ArraySubs → Profile Builder → Profile Form**.
2. Locate the **Avatar Settings** section.
3. Toggle **Enable avatar upload** ON (if not already).
4. Confirm Max File Size = `2` and Allowed File Types = `jpg, jpeg, png, gif, webp`.
5. Click **Save Configuration**.

**Expected Result:**
- Save success notice.
- Settings persist after page reload.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.2 — Customer sees Profile Photo section
**Steps:**
1. Log in as `pf_test@example.com`.
2. Navigate to **My Account → Account Details**.
3. Scroll to the top of the form.

**Expected Result:**
- A section titled **Profile Photo** appears at the top of the form (above the standard WooCommerce account fields).
- A preview image (Gravatar or default WP avatar) is shown.
- An **Upload Avatar** button is visible.
- A **Remove Avatar** button is NOT visible (no custom avatar uploaded yet).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.3 — Drag-and-drop upload of valid file
**Steps:**
1. Drag `avatar-ok.png` from your file manager onto the avatar upload area (or click **Upload Avatar** and select via file dialog).
2. Wait for the upload to complete.
3. Observe the avatar preview area.

**Expected Result:**
- The upload happens via REST API without a full page reload.
- The avatar preview updates to show `avatar-ok.png`.
- The button label changes from **Upload Avatar** to **Change Avatar**.
- A **Remove Avatar** button now appears.
- No inline error notices.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.4 — Verify file storage path and naming
**Steps:**
1. SSH/FTP into the server.
2. List the contents of `wp-content/uploads/arraysubs-avatars/`.
3. Confirm a file matching the pattern `<user_id>-<random>.png` exists for `pf_test@example.com`'s user ID.
4. Confirm the directory contains an `index.php` file and an `.htaccess` file (security protection).

**Expected Result:**
- File exists with correct naming pattern.
- Directory has `index.php` and `.htaccess` for protection.
- The file is not directory-listable when navigating to `wp-content/uploads/arraysubs-avatars/` in a browser.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.5 — Oversize file rejection
**Steps:**
1. Click **Change Avatar**.
2. Select `avatar-large.jpg` (~5 MB) — exceeds the 2 MB max.
3. Wait for the response.

**Expected Result:**
- The upload is rejected.
- An inline error message appears below the avatar preview indicating the file is too large (e.g., "File size exceeds 2 MB limit." or similar).
- The current avatar (`avatar-ok.png`) is unchanged.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.6 — Disallowed file type rejection
**Steps:**
1. Click **Change Avatar**.
2. Select `avatar-bad.txt`.

**Expected Result:**
- Upload rejected.
- Inline error: "Invalid file type" or equivalent (mentioning the allowed list `jpg, jpeg, png, gif, webp`).
- Current avatar unchanged.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.7 — Avatar appears site-wide via get_avatar()
**Steps:**
1. Leave a test comment on any blog post as `pf_test@example.com`.
2. View the post and scroll to the comments.
3. Confirm the avatar shown next to the comment matches the uploaded `avatar-ok.png`.
4. Also check the WP admin bar (top-right) when logged in as `pf_test@example.com` — the avatar should reflect the upload.

**Expected Result:**
- The custom avatar appears wherever WordPress calls `get_avatar()` (comments, admin bar, author bios, etc.) — confirming site-wide replacement.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.8 — Remove avatar falls back to Gravatar
**Steps:**
1. Back on **My Account → Account Details**, click **Remove Avatar**.
2. Wait for the removal.

**Expected Result:**
- The custom avatar is deleted from disk (verify via SSH: file no longer present in `wp-content/uploads/arraysubs-avatars/`).
- The preview now shows the user's Gravatar (or WP default avatar from Settings → Discussion if no Gravatar exists).
- The **Remove Avatar** button disappears.
- The button label is back to **Upload Avatar**.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.9 — Admin avatar UI
**Steps:**
1. Log in as `qa_admin`.
2. Go to **Users → All Users → pf_test@example.com → Edit**.
3. Scroll to **ArraySubs Profile Fields** section.
4. Inspect the avatar area.

**Expected Result:**
- A 96×96 preview shows the current avatar (Gravatar fallback after Sub-Task 02.8).
- A file input is visible to upload a new avatar.
- A help note shows the maximum file size (e.g., "Maximum file size: 2 MB.").
- A **Remove avatar** checkbox is NOT shown (no custom avatar exists).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 02.10 — Cleanup
**Steps:**
1. Confirm `pf_test@example.com` has no custom avatar (Gravatar/default fallback in place).
2. Leave **Enable avatar upload** = ON for downstream tests if any.

**Expected Result:**
- Test customer back to baseline avatar state.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm `pf_test2@example.com` (separate user) avatar state is unaffected by `pf_test`'s changes.
- Confirm avatar is stored as filename only in user meta `arraysubs_avatar` (not full URL) — verify via `wp user meta get <user_id> arraysubs_avatar`.
- Confirm WP-multisite environments (if applicable): avatars are site-specific, not network-shared.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Storage path verified at:
- Filename pattern observed:
- Notes:
