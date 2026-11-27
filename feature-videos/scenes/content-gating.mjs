// Content gating (ArraySubs Free + Pro): default message → whole-page gate → block gate →
// site-wide Member Access rules → drip → member perks → downloads → Pro login limit.
// Store records used: page 32133 (created for this video), page 31220 (lesson with a
// Restricted Content block), page 31750 (/demo-url-drip/), product 6702 (flask).
// Customers: 739 Sadie Graham (Plus since today, sub 32096), 748 Parker Baker
// (Quarterly Learning Pass since April), 749 Theo Laurent (Plus since April). See notes/content-gating.md.

const ADMIN = '/wp-admin/admin.php?page=arraysubs-mainadmin#';
const PAGE_ID = 32133; // "Member Roast: Ethiopia Guji"
const FLASK = '/product/demo-%c2%b7-limited-edition-flask/';

// Block-editor screens send Document-Isolation-Policy, which puts the editor in its own
// browsing context inside the stage iframe (it renders at the wrong size). Drop that header
// for the recording browser only.
async function stripIsolation(v) {
  await v.context.route(/\/wp-admin\/(post|post-new)\.php/, async (route) => {
    if (route.request().resourceType() !== 'document') return route.continue();
    const resp = await route.fetch();
    const headers = { ...resp.headers() };
    delete headers['document-isolation-policy'];
    await route.fulfill({ response: resp, headers });
  });
}

// The store is shared with other recordings and can be slow: retry a navigation once.
async function go(v, url, opts = {}) {
  try {
    await v.goto(url, opts);
  } catch (e) {
    if (!/Timeout/i.test(String(e))) throw e;
    await v.goto(url, opts);
  }
}

async function editorReady(f) {
  await f.waitForFunction(() => window.wp && wp.data && wp.data.select('core/block-editor') && wp.data.select('core/block-editor').getBlocks().length > 0, null, { timeout: 45000 });
  await f.evaluate(() => {
    // Hide the floating block toolbar: it covers the text we show.
    const s = document.createElement('style');
    s.textContent = '.block-editor-block-contextual-toolbar, .block-editor-block-list__block-popover, .block-editor-block-popover { display: none !important; }';
    document.head.appendChild(s);
  });
  await new Promise((r) => setTimeout(r, 600));
}

// Rect (store-frame coordinates) of an element inside the block editor's canvas iframe.
async function canvasRect(v, sel) {
  const canvas = v.frame.childFrames().find((f) => f.name() === 'editor-canvas');
  const off = await v.frame.evaluate(() => {
    const r = document.querySelector('iframe[name="editor-canvas"]').getBoundingClientRect();
    return { x: r.x, y: r.y, b: r.bottom };
  });
  const r = await canvas.locator(sel).first().evaluate((el) => {
    const b = el.getBoundingClientRect();
    return { x: b.x, y: b.y, w: b.width, h: b.height };
  });
  const y = r.y + off.y;
  return { x: r.x + off.x, y, w: r.w, h: Math.min(r.h, off.b - y - 6) };
}

function resetPageGate(wp) {
  wp(['eval', `foreach (['_arraysubs_restrict_config','_arraysubs_restrict_enabled','_arraysubs_restrict_conditions','_arraysubs_restrict_message'] as $k) { delete_post_meta(${PAGE_ID}, $k); } echo 'ok';`]);
}

// Union rect of the visible step cards in an open rule (the flow column itself is taller than the screen).
async function flowRect(v) {
  return v.eval(() => {
    const vh = innerHeight;
    const rs = [...document.querySelectorAll('button.arraysubs-rule-flow__node')].map((n) => n.getBoundingClientRect()).filter((r) => r.height && r.top < vh - 20 && r.bottom > 40);
    const x = Math.min(...rs.map((r) => r.left)), y = Math.min(...rs.map((r) => r.top));
    const r = Math.max(...rs.map((r) => r.right)), b = Math.min(vh - 12, Math.max(...rs.map((r) => r.bottom)));
    return { x, y, w: r - x, h: b - y };
  });
}

// Open rule N in a Member Access tab (cursor click), optionally a step of its flow.
async function openRule(v, n) {
  await v.click(`button.arraysubs-rule-list-item__select >> nth=${n}`);
}

export default {
  slug: 'content-gating',
  tier: 'Free + Pro',
  intro: { title: 'Content *gating*', sub: 'Lock pages, sections and products for paying members.' },
  outro: { title: 'Stop giving it *away*.', sub: 'Lock what members pay for. Access follows the subscription.', url: 'subscriptionplugin.pro' },
  setup: async (v) => {
    await stripIsolation(v);
  },
  chapters: [
    // 1 ─────────────────────────────────────────────────────────────── default message
    {
      kicker: 'Settings',
      title: 'Write the message *once*',
      run: async (v) => {
        await v.as(1);
        await go(v, `${ADMIN}/settings/content-gate`);
        await v.say('First, decide what visitors see on a **locked** page.');
        const guest = '#arraysubs-content-gate-default-message .arraysubs-fb-richtext-wrapper >> nth=0';
        await v.focus(guest, { label: 'Message for guests', side: 'right' });
        await v.unfocus();
        await v.zoom({ x: 200, y: 330, w: 810, h: 150 }, { scale: 1.75 });
        await v.say('Add **Sign in** and plan links. Gates without their own message use it.');
        await v.zoomOut();
        await v.zoom({ x: 200, y: 230, w: 810, h: 90 }, { scale: 1.6 });
        await v.say('Placeholders like **{login_link}** fill in the right link.');
        await v.zoomOut();
        await v.say('Lock a page, a block, an **Elementor** container, a shortcode or PHP.', { hold: 0.4 });
        await v.focus('aside.arraysubs-content-gate__overview', { label: 'Five ways to lock content', side: 'left', dim: true, hold: 3 });
        await v.unfocus();
      },
    },

    // 2 ─────────────────────────────────────────────────────────────── whole page gate
    {
      kicker: 'Editor',
      title: 'Lock a whole *page*',
      sub: 'From the page editor, no code.',
      before: async (v, { wp }) => { resetPageGate(wp); },
      run: async (v) => {
        await v.as(1);
        await go(v, `/wp-admin/post.php?post=${PAGE_ID}&action=edit`, { hold: 0 });
        await v.act(async (f) => { await editorReady(f); }, { settle: false });
        await v.say('This member roast page is still **public**.');
        await v.scrollTo('.arraysubs-restriction-panel', { block: 'center' });
        await v.focus('.components-panel__body:has(.arraysubs-restriction-panel)', { label: 'Access Restriction', side: 'left' });
        await v.click('.arraysubs-restriction-panel__configure', { wait: 400 });
        await v.click('.components-modal__frame label:has-text("Restrict access to this content")', { wait: 300 });
        await v.say('Choose the plans that **unlock** the page.', { hold: 1.2 });
        const products = '.components-modal__frame input.components-form-token-field__input >> nth=1';
        await v.scrollTo(products, { block: 'center' });
        await v.type(products, 'Plus', { clear: false, cps: 12 });
        await v.frame.locator('.components-form-token-field__suggestion:has-text("Plus Membership")').first().waitFor({ state: 'visible', timeout: 90000 });
        await v.click('.components-form-token-field__suggestion:has-text("Plus Membership")', { settle: false, wait: 300 });
        await v.type(products, 'Prem', { clear: false, cps: 12 });
        await v.frame.locator('.components-form-token-field__suggestion:has-text("Premium Membership")').first().waitFor({ state: 'visible', timeout: 90000 });
        await v.click('.components-form-token-field__suggestion:has-text("Premium Membership")', { settle: false, wait: 300 });
        // close the suggestion list
        await v.act(async (f) => { await f.evaluate(() => document.activeElement && document.activeElement.blur()); await new Promise((r) => setTimeout(r, 250)); }, { settle: false });
        await v.focus('.components-modal__frame .components-form-token-field >> nth=1', { label: 'Plus or Premium members' });
        await v.say('Status, purchases, roles and lifetime spend work the same way.');
        await v.unfocus();
        const feature = '.components-modal__frame .components-base-control:has(label:has-text("Plan feature"))';
        await v.scrollTo(feature, { block: 'center' });
        await v.say('With **Pro**, gate by plan perks from Feature Manager.', { hold: 0.3 });
        await v.focus(feature, { label: 'Pro: gate by plan feature', side: 'top', hold: 2.4 });
        await v.unfocus();
        const denied = '.components-modal__frame select:has(option:has-text("Show a message instead"))';
        await v.scrollTo(denied, { block: 'center' });
        await v.say('Others see your **message**, or go to another page.', { hold: 0.3 });
        await v.focus(denied, { label: 'Message or redirect', side: 'top', hold: 2.4 });
        await v.unfocus();
        await v.click('.components-modal__frame button:has-text("Apply")', { wait: 300 });
        await v.click('button.editor-post-publish-button', { wait: 1800 });
        await v.scrollTo('.arraysubs-restriction-panel', { block: 'center' });
        await v.say('Saved. The page is now **members-only**.', { hold: 0.3 });
        await v.focus('.arraysubs-restriction-panel', { label: 'Login + 2 plans', side: 'left', hold: 2.4 });
      },
    },

    // 3 ─────────────────────────────────────────────────────────────── guest vs member
    {
      kicker: 'Storefront',
      title: 'Same page. *Two* visitors.',
      run: async (v) => {
        await v.as('guest');
        await go(v, '/member-roast-ethiopia-guji/');
        await v.say('A **guest** opens the page.', { hold: 0.8 });
        const msg = 'p:has-text("Members can open this resource")';
        await v.focus(msg, { label: 'Your default message', dim: true });
        await v.zoom(msg, { scale: 1.8 });
        await v.say('The content is gone. Your message shows the way in.');
        await v.zoomOut();
        await v.as(739);
        await go(v, '/member-roast-ethiopia-guji/');
        await v.say('Sadie has an active **Plus** plan, so she sees it all.', { hold: 0.4 });
        await v.focus('.wp-block-media-text', { label: 'Unlocked for members', side: 'right', hold: 2.6 });
      },
    },

    // 4 ─────────────────────────────────────────────────────────────── block gate
    {
      kicker: 'Editor',
      title: 'Lock *part* of a page',
      sub: 'Keep the intro public. Lock the rest.',
      run: async (v) => {
        await v.as(1);
        await go(v, '/wp-admin/post.php?post=31220&action=edit', { hold: 0 });
        await v.act(async (f) => {
          await editorReady(f);
          await f.evaluate(() => {
            const be = wp.data.select('core/block-editor');
            const find = (blocks) => { for (const b of blocks) { if (b.name === 'arraysubs/content-restricted') return b; const x = find(b.innerBlocks); if (x) return x; } return null; };
            const b = find(be.getBlocks());
            if (b) wp.data.dispatch('core/block-editor').selectBlock(b.clientId);
            wp.data.dispatch('core/edit-post').openGeneralSidebar('edit-post/block');
          });
          // wait until the product tokens show names, not IDs
          await f.waitForFunction(() => [...document.querySelectorAll('.interface-complementary-area .components-form-token-field__token')].some((t) => /Pass/.test(t.textContent)), null, { timeout: 30000 }).catch(() => {});
          await new Promise((r) => setTimeout(r, 400));
        }, { settle: false });
        await v.say('The **Restricted Content** block locks only what sits inside it.', { hold: 0.3 });
        const block = await canvasRect(v, '[data-type="arraysubs/content-restricted"]');
        await v.focus(block, { label: 'Locked blocks', side: 'right', hold: 2.6 });
        await v.unfocus();
        const note = await canvasRect(v, '[data-type="arraysubs/content-restricted"] .components-notice, [data-type="arraysubs/content-restricted"] [class*="notice"]');
        await v.zoom(note, { scale: 1.6 });
        await v.say('Editors always see the locked blocks while they work.');
        await v.zoomOut();
        const passes = '.interface-complementary-area .components-form-token-field >> nth=1';
        await v.scrollTo(passes, { block: 'center' });
        await v.say('Here, Quarterly and Annual pass holders get in.', { hold: 0.3 });
        await v.focus(passes, { label: 'Learning pass holders', side: 'left', hold: 2.4 });
        await v.unfocus();

        await v.as('guest');
        await go(v, '/dial-in-your-grind/');
        await v.say('Guests read the intro, then see the message.', { hold: 0.3, kicker: 'Storefront' });
        await v.focus('p:has-text("This lesson is for members")', { label: 'Block replaced by message', dim: true, hold: 2.6 });
        await v.as(748);
        await go(v, '/dial-in-your-grind/');
        await v.say('Parker has a **Quarterly Learning Pass**. The lesson opens.', { hold: 0.3, kicker: 'Storefront' });
        await v.focus('.wp-block-media-text', { label: 'Visible to pass holders', side: 'right', hold: 2.6 });
      },
    },

    // 5 ─────────────────────────────────────────────────────────────── site-wide rules
    {
      kicker: 'Member Access',
      title: 'Rules for whole *sections*',
      sub: 'Who gets in, what opens, what others see.',
      run: async (v) => {
        await v.as(1);
        await go(v, `${ADMIN}/members-access/cpt-rules`, { hold: 0 });
        await openRule(v, 2);
        await v.zoom({ x: 165, y: 34, w: 990, h: 44 }, { scale: 1.4 });
        await v.say('**Member Access** keeps site-wide rules in ten tabs.');
        await v.zoomOut();
        await v.say('Each rule reads top to bottom: who, what, and what others see.', { hold: 0.3 });
        await v.focus(await flowRect(v), { label: 'Who → what → how', side: 'left', hold: 2.6 });
        await v.click('button.arraysubs-rule-flow__node >> nth=0', { wait: 300 });
        await v.say('Plus or Premium members, **or** anyone who spent $250.', { hold: 0.3 });
        await v.focus('.arraysubs-condition-builder__group', { label: 'Any one of these', side: 'top', hold: 2.2 });
        await v.zoom('.arraysubs-condition-builder__group', { scale: 1.45 });
        v.poster();
        await v.hold(1.6, { note: 'conditions' });
        await v.zoomOut();
        await v.click('.arraysubs-modal button:has-text("Done")');
        await v.click('button.arraysubs-rule-flow__node >> nth=1', { wait: 300 });
        await v.say('It locks the **Members Journal** category.', { hold: 0.3 });
        await v.focus('.arraysubs-modal div:has(> label:has-text("Terms"))', { label: 'One category', side: 'bottom', hold: 2 });
        await v.say('Locked posts can show a lock icon or leave listings.', { hold: 0.3 });
        await v.focus('.arraysubs-modal select:has(option:has-text("Show with lock icon"))', { label: 'Lock icon in listings', side: 'bottom', hold: 2.4 });
        await v.click('.arraysubs-modal button:has-text("Done")');
      },
    },

    // 6 ─────────────────────────────────────────────────────────────── drip
    {
      kicker: 'Drip',
      title: 'Unlock on each member\'s *clock*',
      sub: 'Days, weeks or months after they join.',
      run: async (v) => {
        await v.as(1);
        await go(v, `${ADMIN}/members-access/url-rules`, { hold: 0 });
        await openRule(v, 2);
        await v.say('This URL rule guards the **week-two** lesson.', { hold: 0.3 });
        await v.focus('button.arraysubs-rule-flow__node >> nth=1', { label: 'The week-two lesson', side: 'left', hold: 2.4 });
        await v.click('button.arraysubs-rule-flow__node >> nth=3', { wait: 300 });
        await v.say('It opens **14 days** after each member\'s subscription starts.', { hold: 0.3 });
        await v.focus('.arraysubs-schedule-fields', { label: '14 days after joining', side: 'bottom', hold: 1.4 });
        await v.unfocus();
        await v.zoom('.arraysubs-schedule-fields', { scale: 1.5, hold: 1.8 });
        await v.zoomOut();
        await v.click('.arraysubs-modal button:has-text("Done")');

        await v.as(749);
        await go(v, '/demo-url-drip/');
        await v.say('Theo joined in April, so the lesson is already open.', { hold: 0.3, kicker: 'Storefront' });
        await v.focus('.wp-block-media-text', { label: 'Open for Theo', side: 'right', hold: 2.4 });
        await v.unfocus();

        await v.as(1);
        await v.act(async (f) => {
          await f.goto(`http://localhost:10013${ADMIN}/manage-members/739`, { waitUntil: 'domcontentloaded', timeout: 90000 });
          await f.waitForSelector('.arraysubs-member-insight__role-badge', { timeout: 60000 });
          await f.evaluate(() => {
            const names = { demo_member: 'Member', demo_plus: 'Plus Member', demo_premium: 'Premium Member' };
            document.querySelectorAll('.arraysubs-member-insight__role-badge').forEach((b) => {
              const k = b.textContent.trim().toLowerCase();
              if (names[k]) b.textContent = names[k];
            });
          });
        });
        await v.say('Sadie joined **today** on the Plus plan.', { hold: 0.3, kicker: 'Manage Members' });
        await v.focus('.arraysubs-member-insight__profile-roles', { label: 'Roles from Role Mapping', side: 'bottom', hold: 2.2 });
        await v.focus('a:has-text("Login as Customer")', { label: 'See what she sees', side: 'bottom' });
        await v.click('a:has-text("Login as Customer")', { nav: true, wait: 500 });
        await go(v, '/demo-url-drip/');
        const unlock = 'p:has-text("unlocks for you")';
        await v.say('Same page: Sadie sees the date it **unlocks for her**.', { hold: 0.3, kicker: 'Storefront' });
        await v.focus(unlock, { label: 'Her own unlock date', dim: true, hold: 1.6 });
        await v.zoom(unlock, { scale: 1.8, hold: 1.6 });
        await v.zoomOut();
        await v.say('Her clock counts from her own subscription start date.', { hold: 2.6, kicker: 'Storefront' });
      },
    },

    // 7 ─────────────────────────────────────────────────────────────── member perks
    {
      kicker: 'Shop',
      title: 'Member *prices* and products',
      run: async (v) => {
        await v.as(1);
        await go(v, `${ADMIN}/members-access/discount-rules`, { hold: 0 });
        await openRule(v, 2);
        await v.click('button.arraysubs-rule-flow__node >> nth=2', { wait: 300 });
        await v.say('Loyal members get **15% off** the flask and free shipping.', { hold: 0.3, kicker: 'Member Access' });
        await v.focus('.arraysubs-modal .arraysubs-rule-drawer__body', { label: '15% off + free shipping', side: 'bottom', hold: 2.6 });
        await v.click('.arraysubs-modal button:has-text("Done")');

        await go(v, `${ADMIN}/members-access/ecommerce-rules`, { hold: 0 });
        await openRule(v, 0);
        await v.click('button.arraysubs-rule-flow__node >> nth=2', { wait: 300 });
        await v.say('Others can see the limited flask, but cannot **buy** it.', { hold: 0.3, kicker: 'Member Access' });
        await v.focus('.arraysubs-modal select', { label: 'Show it, block purchase', side: 'bottom', hold: 2.6 });
        await v.click('.arraysubs-modal button:has-text("Done")');

        await v.as('guest');
        await go(v, FLASK);
        await v.say('A guest sees the price and your message, no buy button.', { hold: 0.3, kicker: 'Storefront' });
        await v.focus('.arraysubs-ecommerce-restricted-notice', { label: 'Purchase blocked', side: 'bottom', hold: 2.6 });
        await v.unfocus();

        await v.as(749);
        await go(v, FLASK);
        await v.say('Theo sees his **member price** and can buy one.', { hold: 0.3, kicker: 'Storefront' });
        await v.zoom({ x: 600, y: 205, w: 560, h: 80 }, { scale: 1.9, hold: 2.4 });
        await v.zoomOut();
        await v.focus('text=Purchase limit', { label: 'One per order', side: 'right', hold: 2 });
        await v.unfocus();
      },
    },

    // 8 ─────────────────────────────────────────────────────────────── downloads
    {
      kicker: 'My Account',
      title: 'Member *downloads*',
      run: async (v) => {
        await v.as(1);
        await go(v, `${ADMIN}/members-access/downloads-rules`, { hold: 0 });
        await openRule(v, 1);
        await v.click('button.arraysubs-rule-flow__node >> nth=1', { wait: 300 });
        await v.say('Share files with a **download limit** and an expiry.', { hold: 0.3, kicker: 'Member Access' });
        await v.focus('.arraysubs-modal .arraysubs-rule-drawer__body', { label: 'Limit 10, expires in 90 days', side: 'bottom', hold: 2.6 });
        await v.click('.arraysubs-modal button:has-text("Done")');

        await v.as(749);
        await go(v, '/my-account/downloads/');
        await v.say('Members find them in **My Account**, next to their other files.', { hold: 0.3 });
        await v.focus('tr:has-text("Member Guide")', { label: '10 downloads left', side: 'bottom', dim: true, hold: 2.2 });
        await v.unfocus();
        await v.zoom('table', { scale: 1.5, hold: 2 });
        await v.zoomOut();
      },
    },

    // 9 ─────────────────────────────────────────────────────────────── Pro login limit
    {
      kicker: 'Pro',
      title: 'Stop shared *logins*',
      sub: 'Cap the devices per account.',
      run: async (v) => {
        await v.as(1);
        await v.act(async (f) => {
          await f.goto(`http://localhost:10013${ADMIN}/members-access/login-limit`, { waitUntil: 'domcontentloaded', timeout: 90000 });
          await f.waitForSelector('button.arraysubs-rule-list-item__select', { timeout: 60000 });
          await f.locator('button.arraysubs-rule-list-item__select').first().click();
          await new Promise((r) => setTimeout(r, 600));
          await f.evaluate(() => window.scrollTo(0, 0));
        });
        await v.say('**Pro** limits how many devices one account can use.', { hold: 0.3 });
        await v.focus('.arraysubs-login-limit-global-settings', { label: 'Default: 2 sessions', side: 'right', hold: 2.4 });
        await v.say('Different members can get different limits.', { hold: 0.3 });
        await v.focus(await flowRect(v), { label: 'Members: 3 devices', side: 'left', hold: 2.4 });
        await v.unfocus();
        await v.zoom('.arraysubs-login-limit-global-settings .arraysubs-alert-info >> nth=-1', { scale: 1.9 });
        await v.say('A new login signs out the **oldest** session.');
        await v.zoomOut();
      },
    },
  ],
};
