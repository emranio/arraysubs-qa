// Audit logs tour: free subscription history -> an admin edit and a customer edit ->
// the Pro Activities trail (filters, Changes) -> Portal Errors -> Jobs -> logging controls.
// Store changes and how to undo them: notes/audit-logs.md
import { wp } from '../lib/director.mjs';

const SUB = 31492; // Christopher Hamilton (user 608), Plus Membership, bank transfer
const CUSTOMER = 608;
const NOTE_BASE = 31509; // newest note on SUB before this video
const PORTAL_SUB = 27045; // Valentina Powell (user 565)
const ORIGINAL_BILLING = '{"first_name":"Christopher","last_name":"Hamilton","company":"","address_1":"98 Spring Court","address_2":"","city":"Minneapolis","state":"MN","postcode":"55401","country":"US","email":"christopher.hamilton@example.com","phone":"+1 202 555 0181"}';
const ORIGINAL_PHONE = '+1 202 555 0181';
const NEW_PHONE = '+1 612 555 0147';
// Christopher's account (My Account) billing address: original, and after the correction.
const ACCOUNT_OLD = { billing_address_1: '98 Spring Court', billing_address_2: '', billing_postcode: '55401' };
const ACCOUNT_NEW = { billing_address_1: '2150 Lakeview Drive', billing_address_2: 'Apt 4B', billing_postcode: '55408' };

const ADMIN = '/wp-admin/admin.php?page=arraysubs-mainadmin#';
const AUDITS = `${ADMIN}/audits`;

const php = (code) => wp(['eval', code]);

// Set user meta rows directly (no profile hooks, so no audit entry is written).
const setUserMetaPhp = (user, values) => `
    foreach (json_decode('${JSON.stringify(values)}', true) as $k => $val) {
      $has = $wpdb->get_var($wpdb->prepare("SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s", ${user}, $k));
      if ($has) { $wpdb->update($wpdb->usermeta, ['meta_value' => $val], ['umeta_id' => (int) $has]); }
      else { $wpdb->insert($wpdb->usermeta, ['user_id' => ${user}, 'meta_key' => $k, 'meta_value' => $val]); }
    }
    wp_cache_delete(${user}, 'user_meta');
`;
const clearAuditCache = () => php('if (class_exists("\\\\ArraySubs\\\\Supports\\\\AuditDataCache")) \\ArraySubs\\Supports\\AuditDataCache::invalidate();');

// Back to the state before the admin edit, the customer note and the customer's phone change.
function resetSubscriptionStory() {
  php(`
    global $wpdb;
    $ids = $wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_subscription_id' AND meta_value=%d AND post_id > %d", ${SUB}, ${NOTE_BASE}));
    foreach ($ids as $id) { wp_delete_post((int) $id, true); }
    $ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type='arraysubs_sub_note' AND ID > 32000 AND post_content LIKE 'Member #${CUSTOMER} %'");
    foreach ($ids as $id) { wp_delete_post((int) $id, true); }
    update_post_meta(${SUB}, '_billing_address', wp_slash('${ORIGINAL_BILLING}'));
    $wpdb->update($wpdb->usermeta, ['meta_value' => '${ORIGINAL_PHONE}'], ['user_id' => ${CUSTOMER}, 'meta_key' => 'billing_phone']);
    wp_cache_delete(${CUSTOMER}, 'user_meta');
    ${setUserMetaPhp(CUSTOMER, ACCOUNT_OLD)}
  `);
  clearAuditCache();
}

// Customer 608's phone back to the original, without an audit entry, and his member notes removed.
// His account billing address is aligned with the corrected subscription address (chapter 2),
// so My Account does not contradict the story (direct usermeta, no audit entry).
function resetCustomerPhone() {
  php(`
    global $wpdb;
    $ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type='arraysubs_sub_note' AND ID > 32000 AND post_content LIKE 'Member #${CUSTOMER} %'");
    foreach ($ids as $id) { wp_delete_post((int) $id, true); }
    $wpdb->update($wpdb->usermeta, ['meta_value' => '${ORIGINAL_PHONE}'], ['user_id' => ${CUSTOMER}, 'meta_key' => 'billing_phone']);
    wp_cache_delete(${CUSTOMER}, 'user_meta');
    ${setUserMetaPhp(CUSTOMER, ACCOUNT_NEW)}
  `);
  clearAuditCache();
}

// Portal error row for Valentina: reopen it if a previous take resolved it.
function reopenPortalError() {
  php(`
    global $wpdb;
    $id = (int) $wpdb->get_var($wpdb->prepare("SELECT p.post_id FROM {$wpdb->postmeta} p JOIN {$wpdb->postmeta} s ON s.post_id=p.post_id AND s.meta_key='_subscription_id' AND s.meta_value=%d WHERE p.meta_key='_note_type' AND p.meta_value='portal_action_failure' ORDER BY p.post_id DESC LIMIT 1", ${PORTAL_SUB}));
    if (!$id) {
      wp_set_current_user(565);
      $r = new WP_REST_Request('POST', '/arraysubs/v1/my-subscriptions/${PORTAL_SUB}/skip');
      $r->set_header('content-type', 'application/json');
      $r->set_body(wp_json_encode(['cycles' => 1, 'reason' => '']));
      rest_do_request($r);
      wp_set_current_user(0);
    } else {
      update_post_meta($id, '_portal_action_status', 'open');
      delete_post_meta($id, '_portal_action_resolved_at');
      delete_post_meta($id, '_portal_action_resolved_by');
      $done = $wpdb->get_col($wpdb->prepare("SELECT m.post_id FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} n ON n.ID=m.post_id WHERE m.meta_key='_subscription_id' AND m.meta_value=%d AND n.post_content LIKE 'Portal action failure marked resolved%%' AND n.ID > %d", ${PORTAL_SUB}, $id));
      foreach ($done as $d) { wp_delete_post((int) $d, true); }
    }
  `);
  clearAuditCache();
}

// A renewal-invoice leg run before its time is logged as Skipped (nothing is created,
// the leg is put back at its due time). Run SUB's pending leg once, unless our Skipped
// row is already the newest job row.
function ensureSkippedJob() {
  php(`
    global $wpdb;
    $newest = (int) $wpdb->get_var("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_job_status' ORDER BY post_id DESC LIMIT 1");
    $ours = (int) $wpdb->get_var($wpdb->prepare("SELECT s.post_id FROM {$wpdb->postmeta} s JOIN {$wpdb->postmeta} j ON j.post_id=s.post_id AND j.meta_key='_job_status' AND j.meta_value='skipped' WHERE s.meta_key='_subscription_id' AND s.meta_value=%d ORDER BY s.post_id DESC LIMIT 1", ${SUB}));
    if ($ours && $ours === $newest) { return; }
    $ids = as_get_scheduled_actions(['hook' => 'arraysubs_generate_renewal_invoice', 'args' => [${SUB}], 'status' => 'pending', 'per_page' => 1], 'ids');
    if ($ids) { ActionScheduler::runner()->process_action((int) reset($ids), 'WP CLI'); }
  `);
  clearAuditCache();
}

// Activities filter bar controls (no ids on them).
const AUTHOR = 'select.arraysubs-audit-filters__select >> nth=0';
const ENTITY = 'select.arraysubs-audit-filters__select >> nth=1';
const SEARCH = '.arraysubs-audit-filters__search input';
const FIRST_ROW = '.arraysubs-audit-table tbody tr >> nth=0';
const TIMELINE = '.arraysubs-sd__side .arraysubs-sd__panel:has(h3:has-text("Payment Timeline"))';
const NOTES_HEAD = 'h3:has-text("Subscription Notes")';
const CHANGES_TABLE = '.arraysubs-audit-changes-table';

// A unique query string forces a full page load: going to the same SPA URL again is a
// same-document navigation and would keep the previous chapter's filters.
const activities = (tag) => `/wp-admin/admin.php?page=arraysubs-mainadmin&fv=${tag}#/audits/activity-audits`;

// Type a search, press Enter and wait until the newest row is the one we expect.
async function search(v, text, expectInFirstRow) {
  await v.type(SEARCH, text);
  await v.act(async (f, page) => {
    await page.keyboard.press('Enter');
    await f.waitForFunction((t) => {
      const row = document.querySelector('.arraysubs-audit-table tbody tr');
      return !!row && row.innerText.includes(t);
    }, expectInFirstRow, { timeout: 90000, polling: 200 });
  });
}


// Bounding box of several elements, in store-frame page pixels (for zoom/focus on a group).
async function groupRect(v, rootSel, childSel) {
  return v.eval(([r, c]) => {
    const root = document.querySelector(r);
    const rs = [...root.querySelectorAll(c)].map((e) => e.getBoundingClientRect()).filter((b) => b.width > 0);
    const x = Math.min(...rs.map((b) => b.x)), y = Math.min(...rs.map((b) => b.y));
    return { x, y, w: Math.max(...rs.map((b) => b.right)) - x, h: Math.max(...rs.map((b) => b.bottom)) - y };
  }, [rootSel, childSel]);
}

// Storefront header: the test site's extra "Checkout Classic" and "Cart Page" menu items stay
// off camera (recording browser only, CSS).
const NAV_CSS = '.wp-block-navigation-item:has(> a[href$="/checkout/"]), .wp-block-navigation-item:has(> a[href$="/cart-2/"]) { display: none !important; }';

export default {
  slug: 'audit-logs',
  tier: 'Free + Pro',
  intro: { title: 'Audit *logs*', sub: 'Who changed what, when, and what it was before.', hold: 3.2 },
  outro: { title: 'Stop guessing. Start *knowing*.', sub: 'History is free from day one. Pro makes it searchable.', url: 'subscriptionplugin.pro', hold: 2.8 },

  setup: async (v) => {
    await v.context.addInitScript((css) => {
      if (window.top === window) return;
      const add = () => {
        if (document.getElementById('fv-audit-nav-css')) return;
        const s = document.createElement('style');
        s.id = 'fv-audit-nav-css';
        s.textContent = css;
        const host = document.head || document.documentElement;
        if (host) host.appendChild(s);
      };
      if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', add);
      add();
    }, NAV_CSS);
  },

  chapters: [
    // 1 ------------------------------------------------------------------ history (free)
    {
      kicker: 'Admin',
      title: 'Every subscription keeps its *story*',
      before: async () => { resetSubscriptionStory(); },
      run: async (v) => {
        await v.as(1);
        await v.goto(`${ADMIN}/subscriptions/detail/${SUB}`);
        await v.say('Christopher\'s Plus Membership, opened in **ArraySubs**.');
        await v.focus('.arraysubs-sd__hero-title', { label: 'Subscription #31492', side: 'bottom' });
        await v.unfocus();
        await v.scrollTo('h3:has-text("Subscription Notes")', { block: 'start', offset: -90 });
        await v.say('Status changes, payments and plan switches are **noted on their own**.');
        await v.zoom('.arraysubs-note-item >> nth=0', { scale: 1.6 });
        await v.focus('.arraysubs-note-item >> nth=0', { label: 'Who did it, when, and what' });
        await v.zoomOut();
        await v.focus('.arraysubs-note-item__type--customer >> nth=0', { label: 'Shown to the customer', side: 'left' });
        await v.focus('.arraysubs-note-item__type--private >> nth=0', { label: 'Private: admins only', side: 'left' });
        await v.unfocus();
        await v.scrollTo(TIMELINE, { block: 'start', offset: -90 });
        await v.say('The **Payment Timeline** shows payments, renewals and status changes.');
        await v.focus(TIMELINE, { label: 'Payment Timeline', side: 'left', hold: 1 });
        await v.unfocus();
        await v.hold(1.2);
      },
    },

    // 2 ------------------------------------------------------------------ admin edit (free)
    {
      kicker: 'Admin',
      title: 'Admin edits are *written down*',
      sub: '**Free.** Every edit leaves a note with the old and new value.',
      before: async () => { resetSubscriptionStory(); },
      run: async (v) => {
        await v.as(1);
        await v.goto(`${ADMIN}/subscriptions/detail/${SUB}`);
        await v.say('An admin corrects Christopher\'s **billing address**.');
        await v.click('a:has-text("Edit Subscription")');
        await v.scrollTo('h3:has-text("Billing Address")', { block: 'start' });
        await v.type('input[placeholder="Enter Address Line 1"] >> nth=0', '2150 Lakeview Drive');
        await v.type('input[placeholder="Enter Address Line 2 (optional)"] >> nth=0', 'Apt 4B');
        await v.type('input[placeholder="Enter Postcode / ZIP"] >> nth=0', '55408');
        await v.say(null);
        await v.focus('button:has-text("Save Changes")', { label: 'Save', side: 'top', hold: 0.8 });
        await v.click('button:has-text("Save Changes")', { wait: 800 });
        await v.act(async (f) => {
          await f.waitForSelector('.arraysubs-note-item:has-text("Billing Address line 1")', { timeout: 90000 });
        });
        await v.hold(0.6);
        await v.scrollTo(NOTES_HEAD, { block: 'start', offset: -90 });
        await v.say('The edit is noted with the **admin\'s name** and every changed field.');
        await v.zoom('.arraysubs-note-item >> nth=0', { scale: 1.55 });
        await v.focus('.arraysubs-note-item >> nth=0', { label: 'Old value → new value', side: 'bottom' });
        await v.hold(1.2);
        await v.zoomOut();
      },
    },

    // 3 ------------------------------------------------------------------ notes (free)
    {
      kicker: 'Admin',
      title: 'Notes for the team, or the *customer*',
      sub: '**Free.** Private for the team, or shown in My Account.',
      run: async (v) => {
        await v.as(1);
        await v.goto(`${ADMIN}/subscriptions/detail/${SUB}`);
        await v.scrollTo('.arraysubs-add-note-form', { block: 'center' });
        v.captionPos('tr');
        await v.say('Add your own note. Pick **Private** or **Note to customer**.');
        await v.type('.arraysubs-add-note-form iframe, .arraysubs-add-note-form [contenteditable="true"]', 'We updated your billing address to 2150 Lakeview Drive. Thanks!');
        await v.select('select.arraysubs-add-note-form__type', 'customer');
        await v.focus('select.arraysubs-add-note-form__type', { label: 'Note to customer', side: 'top', hold: 1 });
        await v.click('button.button-primary:has-text("Add note")', { wait: 500 });
        await v.act(async (f) => {
          await f.waitForSelector('.arraysubs-note-item:has-text("2150 Lakeview Drive. Thanks")', { timeout: 90000 });
        });
        await v.scrollTo(NOTES_HEAD, { block: 'start', offset: -90 });
        v.captionPos('bl');
        await v.say('Saved at the top, marked **Customer**.');
        await v.zoom('.arraysubs-note-item >> nth=0', { scale: 1.55 });
        await v.focus('.arraysubs-note-item__type--customer >> nth=0', { label: 'Visible to Christopher', side: 'left' });
        await v.zoomOut();
        await v.hold(0.6);
      },
    },

    // 4 ------------------------------------------------------------------ My Account (free)
    {
      kicker: 'My Account',
      title: 'The customer sees *their* side',
      sub: '**Free.** Customer notes show. Private notes never do.',
      before: async () => { resetCustomerPhone(); },
      run: async (v) => {
        await v.as(CUSTOMER);
        await v.goto(`/my-account/as-view-subscription/${SUB}/`);
        await v.say('Christopher opens his membership in **My Account**.');
        // Scroll the third newest note to the bottom of the window, then zoom (2x, left aligned)
        // with the camera's bottom edge right under it: the newest notes stay in the shot and the
        // older system notes (raw "Gateway (Bacs)" wording) stay out of it.
        await v.scrollTo('.subscription-note >> nth=2', { block: 'end' });
        const notes = await v.eval(() => {
          const box = document.querySelector('.subscription-notes');
          const items = box.querySelectorAll('.subscription-note');
          const a = box.getBoundingClientRect();
          const b = items[Math.min(2, items.length - 1)].getBoundingClientRect();
          const w = 1440 / 2, h = 810 / 2;
          return { x: a.x - 12, y: b.bottom + 6 - h, w, h };
        });
        await v.zoom(notes, { scale: 2 });
        await v.say('He sees notes meant for him. **Private notes stay hidden.**', { pos: 'tr' });
        await v.focus('.subscription-note >> nth=0', { label: 'The note from the store', side: 'top' });
        await v.unfocus();
        await v.zoomOut();
        await v.goto('/my-account/edit-address/billing/');
        await v.say('Then he updates his **phone number** himself.', { pos: 'br' });
        await v.type('#billing_phone', NEW_PHONE);
        await v.click('button[name="save_address"]', { nav: true });
        await v.focus('.wc-block-components-notice-banner, .woocommerce-message', { label: 'Saved', side: 'bottom', hold: 1 });
        await v.unfocus();
        await v.hold(0.6);
      },
    },

    // 5 ------------------------------------------------------------------ Activities (pro)
    {
      kicker: 'Audit Logs',
      title: 'One trail for the *whole store*',
      sub: '**Pro.** Every entry, every subscription, one list.',
      before: async () => { clearAuditCache(); },
      run: async (v) => {
        await v.as(1);
        await v.goto(activities('ch5'));
        await v.say('**Activities**: every recorded change, newest first.');
        await v.focus('.arraysubs-audit-filters__row', { label: 'Author, record type, dates, words', side: 'bottom' });
        await v.unfocus();
        await v.say('Show only what **customers** changed.');
        await v.focus(AUTHOR, { label: 'System, Admin, Customer or Gateway', side: 'bottom', hold: 1.6 });
        await v.select(AUTHOR, 'customer');
        await v.say('Then search the **new number**.', { hold: 0.6 });
        await search(v, '612 555 0147', '612 555 0147');
        await v.focus(`${FIRST_ROW} >> .arraysubs-audit-meta`, { label: 'Who, what, and who can see it', side: 'bottom' });
        await v.zoom(FIRST_ROW, { scale: 1.45 });
        await v.hold(1.4);
        await v.zoomOut();
        await v.click(`${FIRST_ROW} >> button:has-text("Changes")`);
        await v.say('**Changes** shows the old and new value.', { pos: 'bc' });
        await v.focus(CHANGES_TABLE, { label: 'Before → after', side: 'bottom', hold: 2 });
        await v.press('Escape');
      },
    },

    // 6 ------------------------------------------------------------------ Changes (pro)
    {
      kicker: 'Audit Logs',
      title: 'Before and *after*',
      sub: '**Pro.** Filters combine. Settings saves are logged too.',
      before: async () => { clearAuditCache(); },
      run: async (v) => {
        await v.as(1);
        await v.goto(activities('ch6'));
        await v.say('Now the admin\'s edit. Filters work **together**.');
        await v.select(AUTHOR, 'admin');
        await v.focus(ENTITY, { label: '8 record types', side: 'bottom', hold: 1.3 });
        await v.select(ENTITY, 'subscription');
        await search(v, '98 Spring Court', '98 Spring Court');
        await v.focus(FIRST_ROW, { label: 'The billing address edit', side: 'bottom' });
        await v.click(`${FIRST_ROW} >> button:has-text("Changes")`);
        await v.say('Each field before and after. **Nothing to guess.**', { pos: 'bc' });
        await v.focus(CHANGES_TABLE, { label: 'Three fields, old and new', side: 'bottom', hold: 2 });
        v.poster();
        await v.press('Escape');
        await v.say('Settings saves are logged too, with **who** and **when**.', { pos: 'bl', hold: 1.2 });
        await v.click('button:has-text("Reset")');
        await v.select(ENTITY, 'settings');
        await search(v, 'allow_cancellation', 'allow_cancellation');
        // Date + author cells of the newest settings save, labelled with what they show.
        const meta = await v.eval(() => {
          const tr = document.querySelector('.arraysubs-audit-table tbody tr');
          const tds = tr.querySelectorAll('td');
          const a = tds[0].getBoundingClientRect(), b = tds[1].getBoundingClientRect();
          const who = (tr.querySelector('.arraysubs-audit-badge')?.innerText || '').trim();
          return { label: `${who}, ${tds[0].innerText.trim()}`, rect: { x: a.x, y: a.y, w: b.right - a.x, h: Math.max(a.height, b.height) } };
        });
        await v.focus(meta.rect, { label: meta.label, side: 'right', hold: 2 });
        await v.click(`${FIRST_ROW} >> button:has-text("Changes")`);
        const vals = await v.eval(() => {
          const cells = [...document.querySelectorAll('.arraysubs-audit-changes-table tbody tr:first-child > *')].map((c) => c.innerText.trim());
          return cells.length >= 3 ? [cells[1], cells[2]] : ['', ''];
        });
        await v.say(vals[0] && vals[1]
          ? `Customer cancellations: **${vals[0]}** before, **${vals[1]}** after.`
          : 'Customer cancellations, **before and after**.', { pos: 'bc' });
        await v.focus(CHANGES_TABLE, { label: 'The setting, before and after', side: 'bottom', hold: 1.8 });
        await v.press('Escape');
      },
    },

    // 7 ------------------------------------------------------------------ Portal Errors (pro)
    {
      kicker: 'Audit Logs',
      title: 'Which button *failed*, and why',
      sub: '**Pro.** Portal Errors records failed My Account actions.',
      before: async () => { reopenPortalError(); },
      run: async (v) => {
        await v.as(1);
        // Old QA rows for a logged-out request on subscription #1 stay off camera: load the page
        // and hide them before the first frame is shown.
        await v.act(async (f) => {
          await f.goto(`http://localhost:10013${AUDITS}/portal-action-failures`, { waitUntil: 'domcontentloaded' });
          await f.waitForSelector('table tbody tr', { timeout: 90000 });
          await f.addStyleTag({ content: 'tr:has(a[href$="/subscriptions/detail/1"]) { display: none !important; }' });
        });
        await v.hold(0.4);
        await v.say('Valentina tried to skip a renewal **too close to its due date**.');
        await v.focus('table tbody tr >> nth=0', { label: 'Action, customer, error message', side: 'bottom' });
        await v.click('table tbody tr >> nth=0 >> button:has-text("View")');
        await v.say('Open a row for the request and a **suggested next step**.', { pos: 'br' });
        await v.focus('.arraysubs-portal-failure-details', { label: 'What failed, and what to check', side: 'top', hold: 2.2 });
        await v.press('Escape');
        await v.say('Once it is handled, mark it **resolved**.');
        await v.click('table tbody tr >> nth=0 >> button:has-text("Resolve")');
        await v.focus('.arraysubs-confirm-dialog', { label: 'Resolving is logged too', side: 'bottom', hold: 1 });
        await v.click('.arraysubs-confirm-dialog__actions button.button-primary', { wait: 700 });
        await v.say('The row leaves the queue. **Activities** shows who resolved it.');
        await v.click('a.arraysubs-tab-link:has-text("Activities")');
        await search(v, 'resolved by admin: Skip Renewal', 'Skip Renewal');
        await v.focus(FIRST_ROW, { label: 'Resolved by admin', side: 'bottom', hold: 1.6 });
        await v.say('Each row opens its record in **one click**.', { hold: 1.2 });
        await v.click(`${FIRST_ROW} >> a.arraysubs-audit-ref-pill:has-text("Subscription #${PORTAL_SUB}")`);
        await v.scrollTo(NOTES_HEAD, { block: 'start', offset: -90 });
        await v.say('The subscription keeps the error, the fix and **each email sent**.');
        await v.focus('.arraysubs-note-item:has-text("Email sent") >> nth=0', { label: 'Proof the invoice email went out', side: 'top', hold: 1.8 });
        await v.unfocus();
      },
    },

    // 8 ------------------------------------------------------------------ Jobs (pro)
    {
      kicker: 'Audit Logs',
      title: 'Proof the renewals *ran*',
      sub: '**Pro.** Each background run gets a status and a plain sentence.',
      before: async () => { ensureSkippedJob(); },
      run: async (v) => {
        await v.as(1);
        await v.goto(`${AUDITS}/scheduled-job-logs`);
        await v.say('**Jobs** lists background runs, with a plain sentence for each.');
        const skipRow = 'table tbody tr:has-text("Skipped") >> nth=0';
        await v.focus(skipRow, { label: 'Ran too early, so nothing was created', side: 'bottom' });
        const r = await v.eval(() => {
          const tr = [...document.querySelectorAll('table tbody tr')].find((t) => t.innerText.includes('Skipped'));
          const b = tr.getBoundingClientRect();
          return { x: 362, y: b.y, w: 1062, h: b.height };
        });
        await v.zoom(r, { scale: 1.3 });
        await v.hold(1.5);
        await v.zoomOut();
        await v.unfocus();
        await v.say('Pick a date to check **that day\'s renewals**.');
        const filters = await groupRect(v, '.arraysubs-audit-filters__row', 'label, input, button');
        await v.zoom(filters, { scale: 1.7 });
        // The table reloads under the zoomed date fields; keep its brief loader out of the shot.
        await v.eval(() => {
          const s = document.createElement('style');
          s.id = 'fv-hide-loader';
          s.textContent = '.arraysubs-audit-table-wrap .arraysubs-page-loader { opacity: 0 !important; }';
          document.head.appendChild(s);
        });
        for (const nth of [0, 1]) {
          const sel = `input.arraysubs-audit-filters__date >> nth=${nth}`;
          await v.click(sel, { dx: 0.14, settle: false, hold: 0.1 });
          await v.type(sel, '16112026', { click: false, clear: false, cps: 14, hold: 0.3 });
        }
        await v.act(async (f) => {
          const dates = f.locator('input.arraysubs-audit-filters__date');
          for (const i of [0, 1]) {
            if ((await dates.nth(i).inputValue()) !== '2026-11-16') await dates.nth(i).fill('2026-11-16');
          }
          await f.waitForFunction(() => [...document.querySelectorAll('table tbody tr')].some((t) => t.innerText.includes('Process Renewal')), null, { timeout: 90000 });
          await f.evaluate(() => { document.activeElement && document.activeElement.blur(); document.getElementById('fv-hide-loader')?.remove(); });
        });
        await v.hideCursor();
        await v.focus(filters, { label: 'Only 16 Nov 2026', side: 'bottom', hold: 1.4 });
        await v.unfocus();
        await v.zoomOut();
        await v.say('That day\'s renewals and reminders, **each with its result**.', { hold: 1.0 });
        await v.scrollTo('table tbody tr:has-text("Process Renewal") >> nth=0', { block: 'center' });
        await v.focus('table tbody tr:has-text("Process Renewal") >> nth=0', { label: 'Renewal processed', side: 'bottom' });
        await v.scrollTo('table tbody tr:has-text("Send Renewal Reminder") >> nth=0', { block: 'center' });
        await v.focus('table tbody tr:has-text("Send Renewal Reminder") >> nth=0', { label: 'Reminder sent 3 days before', side: 'bottom' });
        await v.unfocus();
        await v.hold(0.6);
      },
    },

    // 9 ------------------------------------------------------------------ logging controls (pro)
    {
      kicker: 'Settings',
      title: 'Keep the log *useful*',
      sub: '**Pro.** Choose what is recorded and how long job logs stay.',
      before: async () => { clearAuditCache(); },
      run: async (v) => {
        await v.as(1);
        await v.goto(activities('ch9'));
        await v.click('button[aria-label="Activity logging options"]');
        await v.say('Turn product, coupon, email or settings entries **on or off**.', { pos: 'bl' });
        await v.focus('.arraysubs-audit-logging-settings__popover', { label: 'Activity logging options', side: 'left' });
        await v.unfocus();
        await v.click('a:has-text("Logging Settings")');
        await v.say('For Jobs: log every run, **only failures**, or nothing.');
        await v.click('button.arraysubs-fb-select-control >> nth=0');
        const menu = await v.eval(() => {
          const a = document.querySelector('button.arraysubs-fb-select-control').getBoundingClientRect();
          const b = document.querySelector('.arraysubs-fb-select-dropdown').getBoundingClientRect();
          return { x: a.x, y: a.y, w: Math.max(a.right, b.right) - a.x, h: b.bottom - a.y };
        });
        await v.zoom(menu, { scale: 1.5 });
        await v.hover('.arraysubs-fb-select-option >> nth=1', { hold: 0.3 });
        await v.focus('.arraysubs-fb-select-option >> nth=1', { label: 'Fewer rows on busy days', side: 'right', hold: 1.8 });
        await v.unfocus();
        await v.zoomOut();
        await v.click('h3.arraysubs-title-text:has-text("Audit Logs")', { settle: false });
        await v.say('Job logs are cleared after the days you pick. **Notes and activity stay.**');
        await v.focus('input.arraysubs-fb-input >> nth=0', { label: '0 to 365 days, 0 = forever', side: 'left', hold: 2 });
        await v.unfocus();
        await v.hold(0.6);
      },
    },
  ],
};

export { resetSubscriptionStory, resetCustomerPhone, reopenPortalError, ensureSkippedJob };
