// Content gating (ArraySubs Free + Pro): default messages → whole-page gate → guest / member /
// lapsed member → block gate → site-wide Member Access rules → drip → member perks → downloads →
// Pro login limit.
// Store records used: page 32133 (created for this video), page 31220 (lesson with a
// Restricted Content block), page 31750 (/demo-url-drip/), product 6702 (flask), posts
// 32745/32746 (Members Journal, created for this video).
// Customers (block 739-799): 739 Sadie Graham (Plus since 2026-11-28 local, sub 32096),
// 742 Emma Myers (Plus cancelled in May, sub 28647), 748 Parker Baker (Quarterly Learning Pass),
// 749 Theo Laurent (Plus since April 5, sub 28695). See notes/content-gating.md.
//
// The recording browser also carries `fv_clip=content-gating-video`, so
// mu-plugins/zz-fv-clips.php applies clips/overrides/content-gating-video.json to its requests
// only (flask rule custom message, signed-in message, leftover download rule off, display renames).
// Nothing shared is changed.
import { SITE } from '../lib/director.mjs';

const ADMIN = '/wp-admin/admin.php?page=arraysubs-mainadmin#';
const PAGE_ID = 32133; // "Member Roast: Ethiopia Guji"
const FLASK = '/product/demo-%c2%b7-limited-edition-flask/';
const CLIP = 'content-gating-video';
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// Storefront only: hide the test store's extra menu items (classic checkout, second cart page)
// and the theme's placeholder footer links. Display only, for the recording browser.
const STORE_CSS = `
header .wp-block-navigation-item:has(> a[href$="/checkout/"]),
header .wp-block-navigation-item:has(> a[href$="/cart-2/"]) { display: none !important; }
footer nav.wp-block-navigation,
footer .wp-block-group:has(> p > a[href*="wordpress.org"]) { display: none !important; }
`;

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

// Every login switch also sets the fv_clip cookie (request-scoped overrides, see header).
function withOverrides(v) {
  const as = v.as.bind(v);
  v.as = async (user) => {
    await as(user);
    await v.context.addCookies([{ name: 'fv_clip', value: CLIP, domain: 'localhost', path: '/', expires: Math.floor(Date.now() / 1000) + 86400 }]);
  };
}

async function storefrontCss(v) {
  await v.context.addInitScript((css) => {
    if (window.top === window || /^\/wp-admin/.test(location.pathname)) return;
    const add = () => {
      if (document.getElementById('fv-cg-css')) return;
      const s = document.createElement('style');
      s.id = 'fv-cg-css';
      s.textContent = css;
      const host = document.head || document.documentElement;
      if (host) host.appendChild(s);
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', add);
    add();
  }, STORE_CSS);
}

// The store is shared with other recordings and can be slow: retry a navigation once.
// The caption is hidden first, so it never sits on a screen it does not describe.
async function go(v, url, opts = {}) {
  await v.say(null);
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
  await sleep(600);
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

// Navigate to a Member Access tab with rule N already open (no empty "Select a rule" panel on camera).
async function goRule(v, path, n) {
  await v.say(null);
  await v.act(async (f) => {
    await f.goto(`${SITE}${ADMIN}${path}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await f.waitForSelector('button.arraysubs-rule-list-item__select', { timeout: 60000 });
    await f.locator('button.arraysubs-rule-list-item__select').nth(n).click();
    await f.waitForSelector('button.arraysubs-rule-flow__node', { timeout: 30000 });
    await sleep(700);
  });
}

// Visible cursor press on a point, the real work off camera, then one crossfade.
async function pressThen(v, target, fn) {
  const p = await v.moveTo(target);
  await v.animate(0.1, (t) => { v.cursor.press = t; });
  await v.act(async (f) => { await v.page.mouse.click(p.x, p.y); await fn(f); }, { settle: false });
  await v.animate(0.14, (t) => { v.cursor.press = 1 - t; });
  v.cursor.press = 0;
}

// Token field: type the term off camera, land on the open suggestion, pick it, close the list.
async function pickToken(v, field, term, name) {
  const sugg = `.components-form-token-field__suggestion:has-text("${name}")`;
  await pressThen(v, field, async (f) => {
    await v.page.keyboard.type(term, { delay: 60 });
    // The search can take >12 s while other recordings load the store.
    await f.locator(sugg).first().waitFor({ state: 'visible', timeout: 90000 });
    await sleep(350);
  });
  await v.hold(0.5, { still: false });
  await pressThen(v, sugg, async (f) => {
    await sleep(350);
    await f.evaluate(() => document.activeElement && document.activeElement.blur());
    await sleep(350);
  });
  await v.hold(0.3, { still: false });
}

// Manage Members profile, already scrolled to its Subscriptions table. The profile's role badges
// print raw role slugs (product bug, see notes), so they are hidden for these shots.
async function memberSubs(v, userId) {
  await v.say(null);
  await v.act(async (f) => {
    await f.goto(`${SITE}${ADMIN}/manage-members/${userId}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await f.waitForSelector('button.arraysubs-member-insight__section-header', { timeout: 60000 });
    await f.addStyleTag({ content: '.arraysubs-member-insight__profile-roles { display: none !important; }' });
    await f.waitForSelector('.arraysubs-member-insight__table-action', { timeout: 60000 });
    await sleep(500);
    await f.evaluate(() => {
      const h = [...document.querySelectorAll('button.arraysubs-member-insight__section-header')].find((b) => /Subscriptions/.test(b.textContent));
      window.scrollTo(0, h.getBoundingClientRect().top + window.scrollY - 300);
    });
    await sleep(300);
  });
}

export default {
  slug: 'content-gating',
  tier: 'Free + Pro',
  intro: { title: 'Content *gating*', sub: 'Lock pages, sections and products for paying members.' },
  outro: { title: 'Stop giving it *away*.', sub: 'Lock what members pay for. Access follows the subscription.', url: 'subscriptionplugin.pro' },
  setup: async (v) => {
    await stripIsolation(v);
    withOverrides(v);
    await storefrontCss(v);
  },
  chapters: [
    // 1 ─────────────────────────────────────────────────────────────── default messages
    {
      kicker: 'Settings',
      title: 'Write the message *once*',
      run: async (v) => {
        await v.as(1);
        await go(v, `${ADMIN}/settings/content-gate`);
        await v.say('First, decide what visitors see on a **locked** page.');
        const editors = '#arraysubs-content-gate-default-message .arraysubs-fb-richtext-wrapper';
        const g = await v.appRect(`${editors} >> nth=0`);
        await v.focus({ x: g.x, y: g.y, w: g.w, h: 148 }, { label: 'Message for guests', side: 'bottom' });
        await v.unfocus();
        await v.zoom({ x: 200, y: 232, w: 810, h: g.y + 148 - 232 });
        await v.say('Add **Sign in** and plan links. Gates without their own message use it.');
        await v.say('**{login_link}** brings visitors back to the same page after sign-in.');
        await v.zoomOut();
        const member = `${editors} >> nth=1`;
        await v.scrollTo(member, { block: 'center' });
        const m = await v.appRect(member);
        await v.say('Signed-in customers **without access** get their own message.', { hold: 0.3 });
        await v.focus({ x: m.x, y: m.y, w: m.w, h: 148 }, { label: 'Signed in, no access', side: 'bottom', hold: 2.4 });
        await v.unfocus();
        await v.scrollTo('aside.arraysubs-content-gate__overview', { block: 'start' });
        // The five methods (Shortcode → PHP), without the card header under the sticky page header.
        const ways = await v.eval(() => {
          const a = document.querySelector('aside.arraysubs-content-gate__overview');
          const ar = a.getBoundingClientRect();
          const hs = [...a.querySelectorAll('h4')];
          const links = [...a.querySelectorAll('a')].filter((x) => /How-to/.test(x.textContent));
          const top = hs[0].getBoundingClientRect().top - 14;
          const bottom = links[links.length - 1].getBoundingClientRect().bottom + 8;
          return { x: ar.x + 14, y: top, w: ar.width - 28, h: bottom - top };
        });
        await v.say('Lock a page, a block, an **Elementor** container, a shortcode or PHP.', { hold: 0.4 });
        await v.focus(ways, { label: 'Five ways to lock content', side: 'left', dim: true, hold: 3 });
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
        await pickToken(v, products, 'Plus', 'Plus Membership');
        await pickToken(v, products, 'Prem', 'Premium Membership');
        await v.say(null);
        await v.hideCursor();
        await v.focus('.components-modal__frame .components-form-token-field >> nth=1', { label: 'Plus or Premium members', hold: 0.8 });
        v.poster();
        await v.hold(1.2, { still: false });
        await v.say('Status, purchases, roles and lifetime spend work the same way.');
        await v.unfocus();

        const feature = '.components-modal__frame .components-base-control:has(label:has-text("Plan feature"))';
        await v.scrollTo(feature, { block: 'start', offset: -40 });
        await v.say('With **Pro**, gate by plan perks from Feature Manager.', { hold: 0.3 });
        await pressThen(v, `${feature} input`, async (f) => {
          await f.locator('.components-modal__frame .components-form-token-field__suggestion:has-text("Team Seats")').first().waitFor({ state: 'visible', timeout: 30000 });
          await sleep(300);
        });
        await v.focus('.components-modal__frame .components-form-token-field__suggestions-list', { label: 'Pro: plan features', side: 'bottom', hold: 2.4 });
        await v.act(async (f) => { await f.evaluate(() => document.activeElement && document.activeElement.blur()); await sleep(350); }, { settle: false });

        const denied = '.components-modal__frame select:has(option:has-text("Show a message instead"))';
        await v.scrollTo(denied, { block: 'center' });
        await v.say('Others see your **message**…', { hold: 0.3 });
        await v.focus(denied, { label: 'Show a message', side: 'top', hold: 1.6 });
        await v.select(denied, 'redirect');
        await v.say('…or go to another page instead.', { hold: 0.3 });
        await v.focus('.components-modal__frame select:has(option:has-text("A page on this site"))', { label: 'A page or a custom URL', side: 'bottom', hold: 2 });
        await v.select(denied, 'message');
        await v.hold(0.4, { still: false });
        await v.say(null);
        await v.click('.components-modal__frame button:has-text("Apply")', { wait: 300 });
        await v.click('button.editor-post-publish-button', { wait: 1800 });
        await v.scrollTo('.arraysubs-restriction-panel', { block: 'center' });
        await v.say('Saved. The page is now **members-only**.', { hold: 0.3 });
        await v.focus('.arraysubs-restriction-panel', { label: 'Login + 2 plans', side: 'left', hold: 2.4 });
      },
    },

    // 3 ─────────────────────────────────────────────────────────────── guest vs member vs lapsed
    {
      kicker: 'Storefront',
      title: 'Same page. *Three* visitors.',
      sub: 'A guest, a member, a lapsed member.',
      run: async (v) => {
        await v.as('guest');
        await go(v, '/member-roast-ethiopia-guji/');
        await v.say('A **guest** opens the page.', { hold: 0.8 });
        const msg = 'p:has-text("Members can open this resource")';
        await v.focus(msg, { label: 'Your default message', side: 'bottom', dim: true });
        await v.zoom(msg, { scale: 1.8 });
        await v.say('The content is gone. Your message shows the way in.');
        await v.zoomOut();

        await v.as(739);
        await go(v, '/member-roast-ethiopia-guji/');
        await v.say('Sadie has an active **Plus** plan, so she sees it all.', { hold: 0.4 });
        await v.focus('.wp-block-media-text', { label: 'Unlocked for members', side: 'right', hold: 2.6 });

        await v.as(742);
        await go(v, '/my-account/as-subscriptions/');
        await v.say('Emma cancelled her **Plus** plan in May.', { kicker: 'My Account', hold: 0.4 });
        await v.focus('article.arraysubs-subscription-card >> nth=0', { label: 'Cancelled, access ended', side: 'bottom', dim: true, hold: 2.4 });
        await go(v, '/member-roast-ethiopia-guji/');
        await v.say('No active plan, so the page stays **locked** for her.', { kicker: 'Storefront', hold: 0.4 });
        await v.focus('p:has-text("Your plan does not include")', { label: 'Message for signed-in customers', side: 'bottom', dim: true, hold: 2.6 });
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
          await sleep(400);
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
        await v.focus('p:has-text("Grind size is the biggest lever")', { label: 'Public intro', side: 'right', hold: 1.6 });
        await v.focus('p:has-text("This lesson is for members")', { label: 'Locked part → message', side: 'bottom', hold: 2.2 });
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
        await goRule(v, '/members-access/cpt-rules', 2);
        await v.zoom({ x: 165, y: 34, w: 990, h: 44 }, { scale: 1.4 });
        await v.say('**Member Access** keeps site-wide rules in ten tabs.');
        await v.zoomOut();
        await v.say('Each rule reads top to bottom: who, what, and what others see.', { hold: 0.3 });
        await v.focus(await flowRect(v), { label: 'Who → what → how', side: 'left', hold: 2.6 });
        await v.click('button.arraysubs-rule-flow__node >> nth=0', { wait: 300 });
        await v.say('Plus or Premium members, **or** anyone who spent $250.', { hold: 0.3 });
        await v.focus('.arraysubs-condition-builder__group', { label: 'Any one of these', side: 'bottom', hold: 2.2 });
        await v.zoom('.arraysubs-condition-builder__group', { scale: 1.45, hold: 1.6 });
        await v.zoomOut();
        await v.click('.arraysubs-modal button:has-text("Done")');
        await v.click('button.arraysubs-rule-flow__node >> nth=1', { wait: 300 });
        await v.say('It locks the **Members Journal** category.', { hold: 0.3 });
        await v.focus('.arraysubs-modal div:has(> label:has-text("Terms"))', { label: 'One category', side: 'bottom', hold: 2 });
        await v.say('Locked posts can show a lock icon or leave listings.', { hold: 0.3 });
        await v.focus('.arraysubs-modal select:has(option:has-text("Show with lock icon"))', { label: 'Lock icon in listings', side: 'bottom', hold: 2.4 });
        await v.click('.arraysubs-modal button:has-text("Done")');

        await v.as('guest');
        await go(v, '/category/demo-members-journal/');
        await v.say('Guests see the journal posts with a **lock**.', { kicker: 'Storefront', hold: 0.3 });
        await v.focus('h2.wp-block-post-title >> nth=0', { label: 'Locked for guests', side: 'right', hold: 2.4 });
        await v.as(749);
        await go(v, '/category/demo-members-journal/');
        await v.say('Theo is a **Plus** member: no locks, full previews.', { kicker: 'Storefront', hold: 0.3 });
        await v.focus('h2.wp-block-post-title >> nth=0', { label: 'Open for members', side: 'right', hold: 2.2 });
      },
    },

    // 6 ─────────────────────────────────────────────────────────────── drip
    {
      kicker: 'Drip',
      title: 'Unlock on each member\'s *clock*',
      sub: 'Days, weeks or months after each plan starts.',
      run: async (v) => {
        await v.as(1);
        await goRule(v, '/members-access/url-rules', 2);
        await v.say('This URL rule guards the **week-two** lesson.', { hold: 0.3 });
        await v.focus('button.arraysubs-rule-flow__node >> nth=1', { label: 'The week-two lesson', side: 'left', hold: 2.4 });
        await v.click('button.arraysubs-rule-flow__node >> nth=3', { wait: 300 });
        await v.say('It opens **14 days** after each member\'s subscription starts.', { hold: 0.3 });
        await v.focus('.arraysubs-schedule-fields', { label: '14 days after the plan starts', side: 'bottom', hold: 1.4 });
        await v.unfocus();
        await v.zoom('.arraysubs-schedule-fields', { scale: 1.5, hold: 1.8 });
        await v.zoomOut();
        await v.click('.arraysubs-modal button:has-text("Done")');

        await memberSubs(v, 749);
        await v.say('Theo\'s **Plus** plan started on April 5.', { kicker: 'Manage Members', hold: 0.3 });
        await v.focus('tr:has-text("#28695")', { label: 'Started April 5', side: 'bottom', dim: true, hold: 2.2 });
        await v.as(749);
        await go(v, '/demo-url-drip/');
        await v.say('So the week-two lesson is already **open** for him.', { hold: 0.3, kicker: 'Storefront' });
        await v.focus('.wp-block-media-text', { label: 'Open for Theo', side: 'right', hold: 2.4 });
        await v.unfocus();

        await v.as(1);
        await memberSubs(v, 739);
        await v.say('Sadie\'s **Plus** plan started today.', { kicker: 'Manage Members', hold: 0.3 });
        await v.focus('tr:has-text("#32096")', { label: 'Started today', side: 'bottom', dim: true, hold: 2.2 });
        await v.unfocus();
        await v.scrollTo(0);
        await v.say('Log in as Sadie to see what she sees.', { kicker: 'Manage Members', hold: 0.3 });
        await v.focus('a:has-text("Login as Customer")', { label: 'See what she sees', side: 'bottom', hold: 1.4 });
        await v.say(null);
        await pressThen(v, 'a:has-text("Login as Customer")', async (f) => {
          await f.waitForURL((u) => !/wp-admin/.test(String(u)), { timeout: 60000 }).catch(() => {});
          await f.waitForLoadState('domcontentloaded').catch(() => {});
          await f.goto(`${SITE}/demo-url-drip/`, { waitUntil: 'domcontentloaded', timeout: 90000 });
          await v.settle();
        });
        const unlock = 'p:has-text("unlocks for you")';
        await v.say('Same page: Sadie sees the date it **unlocks for her**.', { hold: 0.3, kicker: 'Storefront' });
        await v.focus(unlock, { label: 'Her own unlock date', dim: true, hold: 1.6 });
        await v.zoom(unlock, { scale: 1.8, hold: 1.6 });
        await v.zoomOut();
        await v.say('Two weeks after her plan started: December 12.', { hold: 2.4, kicker: 'Storefront' });
      },
    },

    // 7 ─────────────────────────────────────────────────────────────── member perks
    {
      kicker: 'Shop',
      title: 'Member *prices* and products',
      sub: 'Member discounts and members-only products.',
      run: async (v) => {
        await v.as(1);
        await goRule(v, '/members-access/discount-rules', 2);
        await v.click('button.arraysubs-rule-flow__node >> nth=2', { wait: 300 });
        await v.say('Loyal members get **15% off** the flask and free shipping.', { hold: 0.3, kicker: 'Member Access' });
        await v.focus('.arraysubs-modal .arraysubs-rule-drawer__body', { label: '15% off + free shipping', side: 'bottom', hold: 2.6 });
        await v.click('.arraysubs-modal button:has-text("Done")');

        await goRule(v, '/members-access/ecommerce-rules', 0);
        await v.say('Others can see the limited flask, but cannot **buy** it.', { hold: 0.3, kicker: 'Member Access' });
        await v.click('button.arraysubs-rule-flow__node >> nth=2', { wait: 300 });
        await v.focus('.arraysubs-modal select', { label: 'Show it, block purchase', side: 'bottom', hold: 2.4 });
        await v.say('This rule also has its **own message**.', { hold: 0.3, kicker: 'Member Access' });
        await v.focus('.arraysubs-modal .arraysubs-rule-message', { label: 'Custom message', side: 'bottom', hold: 2.2 });
        await v.click('.arraysubs-modal button:has-text("Done")');

        await v.as('guest');
        await go(v, FLASK);
        await v.say('A guest sees the price and your message, no buy button.', { hold: 0.3, kicker: 'Storefront' });
        await v.focus('.arraysubs-ecommerce-restricted-notice', { label: 'Purchase blocked', side: 'top', hold: 2.6 });
        await v.unfocus();

        await v.as(749);
        await go(v, FLASK);
        await v.say('Theo sees his **member price** and can buy one.', { hold: 0.3, kicker: 'Storefront' });
        await v.zoom({ x: 600, y: 150, w: 600, h: 310 }, { scale: 1.6, hold: 2.4 });
        await v.zoomOut();
        await v.focus('text=Purchase limit', { label: 'One per order', side: 'right', hold: 2 });
        await v.unfocus();
      },
    },

    // 8 ─────────────────────────────────────────────────────────────── downloads
    {
      kicker: 'My Account',
      title: 'Member *downloads*',
      sub: 'Files with a limit and an expiry.',
      run: async (v) => {
        await v.as(1);
        await goRule(v, '/members-access/downloads-rules', 1);
        await v.click('button.arraysubs-rule-flow__node >> nth=1', { wait: 300 });
        await v.say('Share files with a **download limit** and an expiry.', { hold: 0.3, kicker: 'Member Access' });
        await v.focus('.arraysubs-modal .arraysubs-rule-drawer__body', { label: 'Limit 10, expires in 90 days', side: 'bottom', hold: 2.6 });
        await v.say('Copy a file\'s protected link for your own button.', { hold: 0.3, kicker: 'Member Access' });
        await v.focus('.arraysubs-modal .arraysubs-download-files__copy-btn', { label: 'Copy protected link', side: 'right', hold: 2 });
        await v.click('.arraysubs-modal button:has-text("Done")');

        await v.as(749);
        await go(v, '/my-account/downloads/');
        await v.say('Members find them in **My Account** → Downloads.', { hold: 0.3 });
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
          await f.goto(`${SITE}${ADMIN}/members-access/login-limit`, { waitUntil: 'domcontentloaded', timeout: 90000 });
          await f.waitForSelector('button.arraysubs-rule-list-item__select', { timeout: 60000 });
          await f.locator('button.arraysubs-rule-list-item__select').first().click();
          await sleep(600);
          await f.evaluate(() => window.scrollTo(0, 0));
        });
        await v.say('**Pro** limits how many devices one account can use.', { hold: 0.3 });
        await v.focus('.arraysubs-login-limit-global-settings', { label: 'Default: 2 sessions', side: 'right', hold: 2.4 });
        await v.say('Different members can get different limits.', { hold: 0.3 });
        await v.focus(await flowRect(v), { label: 'Members: 3 devices', side: 'left', hold: 2.4 });
        await v.unfocus();
        await v.scrollBy(220, { hold: 0.2 });
        v.captionPos('tr');
        await v.zoom('.arraysubs-login-limit-global-settings .arraysubs-alert-info >> nth=-1', { scale: 1.6, pad: 40 });
        await v.say('A new login signs out the **oldest** session.');
        await v.zoomOut();
      },
    },
  ],
};
