# Stage 10 — Task 01: Custom Profile Fields (6 Field Types)

| Key | Value |
|---|---|
| Stage | 10 — Profile Builder & My Account Customization |
| Module | Profile Builder → Profile Form (Custom Fields) |
| Plugin Coverage | Free |
| Estimated Time | 35 min |
| Depends On | Stage 09 cleanup complete |

## Objective
Add six custom profile fields covering every supported field type — Text, Textarea, Select, Date, Checkbox, File Upload — with a mix of required/optional, labels, placeholders, help text, and select options. Verify the fields render correctly on **My Account → Account Details**, that required-field validation blocks empty submission, that values persist after save and across logout/login, and that admins see and can edit the same fields on **Users → Edit User**.

## Pre-conditions
- Logged in as `qa_admin` for the admin configuration sub-tasks.
- `pf_test@example.com` available for the customer-facing tests.
- File upload sandbox file `id-doc.pdf` ready (≤ 5 MB).
- ArraySubs → Profile Builder → Profile Form open in admin.

## Test Data
| Field | Type | Label | Key (auto) | Required | Placeholder / Options / Help |
|---|---|---|---|---|---|
| 1 | Text | Company Name | `company_name` | No | Placeholder: `Acme Corp` ; Help: `Your registered business name.` |
| 2 | Textarea | Bio | `bio` | No | Placeholder: `Tell us a bit about yourself...` |
| 3 | Select | Industry | `industry` | Yes | Options: `tech`/Technology, `retail`/Retail, `health`/Healthcare, `other`/Other |
| 4 | Date | Birthday | `birthday` | No | Help: `Used for birthday discounts only.` |
| 5 | Checkbox | Newsletter Opt-in | `newsletter_optin` | No | Description: `Yes, send me product updates.` |
| 6 | File Upload | ID Document | `id_document` | Yes | Allowed: `pdf, jpg, png` ; Max: `5` MB |

## Sub-Tasks

### Sub-Task 01.1 — Enable custom profile fields and add all 6 fields
**Steps:**
1. Go to **ArraySubs → Profile Builder → Profile Form**.
2. Toggle **Enable custom profile fields** to ON.
3. Click **Add Field**. Expand the new row.
4. For Field 1, enter Label `Company Name`. Confirm the Key auto-fills as `company_name`. Type = Text. Placeholder = `Acme Corp`. Help Text = `Your registered business name.` Required = Off.
5. Click **Add Field** again for Field 2: Label `Bio`, Type = Textarea, Placeholder = `Tell us a bit about yourself...`. Required = Off.
6. Add Field 3: Label `Industry`, Type = Select, Required = ON. Click **Add Option** four times and enter:
   - `tech` / `Technology`
   - `retail` / `Retail`
   - `health` / `Healthcare`
   - `other` / `Other`
7. Add Field 4: Label `Birthday`, Type = Date, Help Text = `Used for birthday discounts only.`. Required = Off.
8. Add Field 5: Label `Newsletter Opt-in`, Type = Checkbox, Placeholder/Description = `Yes, send me product updates.`. Required = Off.
9. Add Field 6: Label `ID Document`, Type = File Upload, Allowed File Types = `pdf, jpg, png`, Max File Size = `5`, Required = ON.
10. Click **Save Configuration**.

**Expected Result:**
- All 6 fields appear in the field list with their labels and type badges (Text, Textarea, Select, Date, Checkbox, File Upload).
- A success notice "Configuration saved." (or equivalent) appears.
- Reloading the page shows all 6 fields persisted.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.2 — Customer-facing render on My Account → Account Details
**Steps:**
1. Log out as admin. Log in as `pf_test@example.com`.
2. Navigate to **My Account → Account Details**.
3. Scroll below the standard WooCommerce fields (First name, Last name, Display name, Email, Password).
4. Visually inspect each of the 6 custom fields:
   - Each has the correct Label.
   - Text field shows placeholder `Acme Corp` when empty.
   - Textarea shows 4 rows and the placeholder.
   - Select shows the 4 options in dropdown order.
   - Date input renders as a native date picker.
   - Checkbox shows label `Yes, send me product updates.`.
   - File Upload shows a file input.
5. Confirm Required-marked fields show an asterisk or "required" indicator (Industry, ID Document).

**Expected Result:**
- All 6 fields rendered with the correct labels, placeholders, help text, and required markers.
- Help text appears below the Birthday and Company Name fields.
- The form has `enctype="multipart/form-data"` (inspect Element on the form).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.3 — Required-field validation
**Steps:**
1. As `pf_test@example.com`, leave Industry empty (no selection / first placeholder option).
2. Leave ID Document empty.
3. Fill in unrelated WooCommerce fields normally.
4. Click **Save changes**.

**Expected Result:**
- Form is NOT saved.
- An inline error appears next to **Industry** (e.g., "This field is required.").
- An inline error appears next to **ID Document**.
- The page does not redirect away or show a success notice.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.4 — Fill in all fields and save
**Steps:**
1. Fill in:
   - Company Name: `Acme Corp QA`
   - Bio: `QA tester for ArraySubs.`
   - Industry: `Technology`
   - Birthday: `1990-05-15`
   - Newsletter Opt-in: checked
   - ID Document: select `id-doc.pdf` from the file dialog
2. Click **Save changes**.

**Expected Result:**
- Success notice "Account details changed successfully." (WooCommerce default) or equivalent.
- Page reloads showing all field values populated:
  - Company Name shows `Acme Corp QA`.
  - Bio shows the bio text.
  - Industry shows `Technology` selected.
  - Birthday shows `05/15/1990` (browser locale).
  - Newsletter Opt-in is still checked.
  - ID Document shows the filename `id-doc.pdf` and a remove option.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.5 — Persistence across logout/login
**Steps:**
1. Log out.
2. Log back in as `pf_test@example.com`.
3. Navigate to **My Account → Account Details**.

**Expected Result:**
- All 6 field values persist exactly as entered in Sub-Task 01.4.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.6 — Admin sees and can edit the same fields
**Steps:**
1. Log out, log in as `qa_admin`.
2. Go to **Users → All Users → pf_test@example.com → Edit**.
3. Scroll to the section titled **ArraySubs Profile Fields**.
4. Confirm all 6 fields render with the same values populated by the customer.
5. Change Company Name to `Acme Corp QA — admin edit`.
6. Click **Update User**.
7. Log in as `pf_test@example.com` (in another browser) and view My Account → Account Details.

**Expected Result:**
- Admin section heading is exactly **ArraySubs Profile Fields**.
- All 6 field values match what was saved by the customer.
- Admin's edit to Company Name is persisted.
- Customer view reflects the admin's change (`Acme Corp QA — admin edit`).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.7 — Verify file storage and direct-access protection
**Steps:**
1. Note the path used for file uploads: `wp-uploads/arraysubs-profiles/{user_id}/`.
2. SSH/FTP into the server and confirm the file `id-doc.pdf` (or auto-renamed equivalent) exists in that directory for `pf_test@example.com`'s user ID.
3. Try to load the file directly via its public URL (e.g., `https://<test-site>/wp-content/uploads/arraysubs-profiles/{user_id}/id-doc.pdf`).
4. Inspect the response.

**Expected Result:**
- File exists on disk in the correct directory.
- Direct URL access returns `403 Forbidden` (or `404`) due to `.htaccess deny all`.
- The download link inside the wp-admin user profile screen still works (admin nonce-signed).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 01.8 — Cleanup
**Steps:**
1. As admin, optionally disable individual fields via the toggle (do NOT delete them — leave the configuration in place for Stage 10 Task 04 if needed).
2. Note final field state in Sign-off.

**Expected Result:**
- Profile field configuration recorded for downstream tasks.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Confirm fields also appear at **Users → Add New** when creating a brand-new user (no avatar section, but custom fields visible).
- Confirm the user meta key format is `arraysubs_pf_<key>` (verify via `wp user meta list <user_id> | grep arraysubs_pf_`).
- Confirm disabling the **Enable custom profile fields** toggle hides the fields from My Account but keeps user meta intact (re-enable to restore).
- Confirm activity log records each change with old/new values.

## Sign-off
- Tester:
- Date:
- Browser & version:
- Final field configuration (enabled/disabled per field):
- Notes:
