# Stage 00 — Task 04: Test Accounts and Mail

| Key | Value |
|---|---|
| Stage | Pre-flight & Environment Verification |
| Module | Users / Mail Delivery |
| Plugin Coverage | Both |
| Estimated Time | 20 min |
| Depends On | 00-preflight/01-fresh-install-check.md, 00-preflight/02-admin-menu-and-capabilities.md |

## Objective
Create the six test users defined in `../README.md`, send a WordPress test email and a WooCommerce test order email, and confirm both arrive in the inbox with intact templates. Mail delivery must be verified before any lifecycle test that depends on renewal reminders, trial-ending alerts, or cancellation confirmations.

## Pre-conditions
- Stages 00 Tasks 01–03 passed.
- An SMTP plugin (WP Mail SMTP, FluentSMTP, or similar) is configured on the site, OR the server's `mail()` function is verified to deliver to the test inbox domain.
- Inboxes for `admin@test.local`, `shopmgr@test.local`, `cust1-3@test.local`, and `guest@test.local` are reachable (Mailpit, MailHog, or a real catch-all inbox is fine).

## Test Data
- The six test accounts as listed in `../README.md`:
  - `admin` / `admin@test.local` (Administrator)
  - `shopmgr` / `shopmgr@test.local` (Shop manager)
  - `cust1` / `cust1@test.local` (Customer)
  - `cust2` / `cust2@test.local` (Customer)
  - `cust3` / `cust3@test.local` (Customer)
  - `guest@test.local` (no account — used for guest checkout scenarios)

## Sub-Tasks

### Sub-Task 4.1 — Create the five WordPress test users
**Steps:**
1. Log in as Administrator.
2. Go to **Users → Add New**.
3. Create each of `shopmgr`, `cust1`, `cust2`, `cust3` with the role specified above. Use a strong password and untick "Send the new user an email about their account" unless you want to verify the welcome email here too.
4. Confirm `admin` already exists.
5. Save passwords to your password manager.

**Expected Result:**
- All five users appear under **Users → All Users** with the correct role.
- No "Username already exists" or "Email already in use" errors.
- The account count under **Users → All Users** matches expectations (5 + any pre-existing).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.2 — Send a WordPress test email
**Steps:**
1. Install and activate **WP Mail SMTP** (or use a similar diagnostic plugin) if it is not already active.
2. Go to its **Email Test** screen (e.g., **WP Mail SMTP → Tools → Email Test**).
3. Enter `admin@test.local` as the recipient.
4. Click **Send Email**.
5. Open the inbox for `admin@test.local`.

**Expected Result:**
- The diagnostic page reports "Test email sent successfully".
- The email arrives in `admin@test.local` within 1 minute.
- The email body is the standard WP Mail SMTP test message.
- No SMTP error message is shown on the diagnostic page.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.3 — Send a WooCommerce email-template test
**Steps:**
1. Go to **WooCommerce → Settings → Emails**.
2. Locate the **Customer processing order** template and click **Manage**.
3. Confirm "Enable this email notification" is ticked.
4. Return to the email list. Many WooCommerce versions expose a **Send test** link in the email row — click it and address the test to `cust1@test.local`. If your WooCommerce build does not expose that link, place a tiny test order in Sub-Task 4.5 instead and skip to that sub-task.

**Expected Result:**
- The test email arrives in `cust1@test.local`.
- The HTML template renders with the WooCommerce header / footer (logo placeholder, "Thanks for shopping with us" body).
- No `{site_title}` or `{order_number}` placeholders remain unrendered in the body.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.4 — Confirm reply-to and from addresses
**Steps:**
1. In the inbox, open the message from Sub-Task 4.3.
2. Click "Show original" / "View source" / equivalent to inspect the email headers.
3. Read the `From:` and `Reply-To:` headers.

**Expected Result:**
- `From:` matches the address configured in **WooCommerce → Settings → Emails → Email sender options → "From" address**.
- `Reply-To:` is either the same address or a deliberately configured support address.
- Neither header is `wordpress@` followed by a non-resolving domain (a common sign that SMTP is mis-configured).

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.5 — Place a $0 test order to trigger a real WooCommerce email
**Steps:**
1. In wp-admin, go to **WooCommerce → Settings → Payments** and enable **Cash on delivery**.
2. Create a free product: **Products → Add New**, name `QA Mail Probe`, regular price `0`, virtual + downloadable optional. Publish.
3. Open an incognito window, go to the product page, click **Add to cart**, then complete checkout as `cust1@test.local`.
4. After the order is placed, return to the admin window and check **WooCommerce → Orders**.
5. Open the inbox for `cust1@test.local`.

**Expected Result:**
- An order is created in WooCommerce.
- The customer receives a "Thank you for your order" / "Your order is on-hold" email within 1 minute.
- The email renders with the correct subject, header, and footer.
- Order details (item name, total $0.00) are populated in the email body.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

### Sub-Task 4.6 — Confirm admin "new order" email arrives
**Steps:**
1. Open the inbox for `admin@test.local`.
2. Look for the WooCommerce **New order** email triggered by Sub-Task 4.5.

**Expected Result:**
- A "New order" email arrives at `admin@test.local`.
- Subject begins with `[Your Site] New order #` (or matches the configured subject template).
- Body includes the order number, item list, and total.

**Pass Criteria:** [ ] PASS [ ] FAIL
**Fail Notes:**

## Regression / Cross-checks
- Re-check `wp-content/debug.log` for any `wp_mail` or PHPMailer error lines added after sending the test emails.
- If using a catch-all inbox (Mailpit / MailHog), confirm the connection is still alive at the end of the task — closed connections silently drop later notifications.
- Note in Sign-off the SMTP provider in use; later email-stage tests will reference it.

## Sign-off
- Tester:
- Date:
- Browser & version:
- SMTP provider:
- Test email round-trip time:
- Notes:
