// Feature Manager (ArraySubs Pro) product tour.
// Story: settings -> build a plan's list -> What's Included -> tiers -> My Features + usage
// -> admin Feature Log -> Combine mode -> content opened by a perk.
// Store changes and undo steps: notes/feature-manager.md
//
// Every request of this recording carries the cookie fv_clip=fv-feature-manager, so
// mu-plugins/zz-fv-clips.php applies clips/overrides/fv-feature-manager.json to it (and only
// to it): Plus Membership's Support Level reads "Email" instead of "Community", test-store
// nav items are removed, demo SKUs and Luna's example.com address are renamed, and a small
// display stylesheet hides editor noise. Nothing is written to the database.
import { wp } from '../lib/director.mjs';

const PRODUCT = 32093; // Creative Studio Membership (hidden demo product made for this video)
const TIER = 6722; // Membership Tier (variable): Basic 6723, Professional 6724
const LUNA = 842; // Luna Anderson: #29307 Membership Tier - Professional, #30240 Plus Membership
const STARTER = 805; // John Hamilton: Starter Membership #29079 (1 team seat)
const SUB_PRO = 29307;
const SUB_PLUS = 30240;
const WORKSPACE = 32765; // "Team Workspace" page made for this video: Team Seats, one plan at least 5
const CLIP = 'fv-feature-manager';

const BASE_ROWS = [
  { id: 'team-seats', name: 'Team Seats', type: 'number', value: '5', enabled: true, order: 0, reset_period: 'none' },
  { id: 'masterclasses', name: 'Masterclasses', type: 'toggle', value: 'Yes', enabled: true, order: 1, reset_period: 'none' },
  { id: 'private-coaching', name: 'Private Coaching', type: 'toggle', value: 'No', enabled: true, order: 2, reset_period: 'none' },
  { id: 'support-level', name: 'Support Level', type: 'text', value: 'Priority', enabled: true, order: 3, reset_period: 'none' },
];

// The list as chapter 2 leaves it (Monthly Downloads added and moved to row 2).
const FULL_ROWS = [
  BASE_ROWS[0],
  { id: 'monthly-downloads', name: 'Monthly Downloads', type: 'number', value: '20', enabled: true, order: 1, reset_period: 'month' },
  ...BASE_ROWS.slice(1),
].map((r, i) => ({ ...r, order: i }));
const fullProduct = () => wp(['post', 'meta', 'update', String(PRODUCT), '_arraysubs_features', JSON.stringify(FULL_ROWS)]);

const resetProduct = () => wp(['post', 'meta', 'update', String(PRODUCT), '_arraysubs_features', JSON.stringify(BASE_ROWS)]);

const setUsage = (sub, name, n) => wp(['eval',
  `$r = ArraySubs\\Developer\\Features::forSubscription(${sub})->set('${name}', ${n}, ['reason' => 'feature video demo usage']); echo is_wp_error($r) ? $r->get_error_message() : $r['used'];`]);

// Only writes when the mode differs; retried because WP-CLI can fail while the store is busy.
const setMode = (mode) => {
  for (let i = 0; i < 4; i++) {
    try {
      return wp(['eval',
        `$s = get_option('arraysubs_settings'); if (is_array($s) && ($s['feature_manager']['aggregation_mode'] ?? '') !== '${mode}') { $s['feature_manager']['aggregation_mode'] = '${mode}'; update_option('arraysubs_settings', $s); } echo arraysubs_get_feature_manager_aggregation_mode();`]);
    } catch (e) { if (i === 3) throw e; }
  }
};

const demoUsage = (downloads = 8) => {
  setUsage(SUB_PRO, 'Monthly Downloads', downloads);
  setUsage(SUB_PRO, 'Team Seats', 3);
  setUsage(SUB_PLUS, 'Monthly Downloads', 4);
  setUsage(SUB_PLUS, 'Team Seats', 1);
};

const wait = (ms) => new Promise((r) => setTimeout(r, ms));

// Display-only: stop admin-menu hover flyouts from opening under the moving cursor.
const noFlyouts = (v) => v.eval(() => {
  const st = document.createElement('style');
  st.textContent = '#adminmenu li.menu-top:not(.wp-has-current-submenu):not(.current) .wp-submenu { display: none !important; }';
  document.head.appendChild(st);
});

// Rect around the page title and the visible post content (the content wrapper itself is full width).
const pageBody = (v) => v.eval(() => {
  const parts = [document.querySelector('h1.wp-block-post-title, h1'), ...document.querySelector('.entry-content').children].filter(Boolean);
  let x1 = 1e9, y1 = 1e9, x2 = 0, y2 = 0;
  for (const k of parts) { const r = k.getBoundingClientRect(); if (!r.width) continue; x1 = Math.min(x1, r.left); y1 = Math.min(y1, r.top); x2 = Math.max(x2, r.right); y2 = Math.max(y2, r.bottom); }
  return { x: x1, y: y1, w: x2 - x1, h: y2 - y1 };
});

// Union rect of every element matching `sel` (optionally only inside the n-th `root`).
// text: measure the text itself (narrow columns), not the full cell boxes.
const rectOf = (v, sel, { root = null, n = 0, text = false } = {}) => v.eval(({ sel, root, n, text }) => {
  const scope = root ? document.querySelectorAll(root)[n] : document;
  let x1 = 1e9, y1 = 1e9, x2 = -1e9, y2 = -1e9;
  for (const e of scope.querySelectorAll(sel)) {
    let rs = [e.getBoundingClientRect()];
    if (text) {
      rs = [];
      const tw = document.createTreeWalker(e, NodeFilter.SHOW_TEXT);
      for (let t = tw.nextNode(); t; t = tw.nextNode()) {
        if (!t.textContent.trim()) continue;
        const r = document.createRange(); r.selectNodeContents(t); rs.push(...r.getClientRects());
      }
    }
    for (const b of rs) {
      if (!b.width || !b.height) continue;
      x1 = Math.min(x1, b.left); y1 = Math.min(y1, b.top); x2 = Math.max(x2, b.right); y2 = Math.max(y2, b.bottom);
    }
  }
  return { x: x1, y: y1, w: x2 - x1, h: y2 - y1 };
}, { sel, root, n, text });

// Camera target for a 1.5x zoom on a My Account subscription card that keeps the page
// heading (cut off by the zoom) out of the frame.
const cardFocus = (v, sel, scale = 1.5) => v.eval(({ sel, scale }) => {
  const card = document.querySelector(sel).getBoundingClientRect();
  const h1 = document.querySelector('h1');
  const half = innerHeight / scale / 2;
  let cy = card.top + card.height / 2;
  if (h1) cy = Math.max(cy, h1.getBoundingClientRect().bottom + half + 8);
  return { x: card.left, y: cy - 1, w: card.width, h: 2 };
}, { sel, scale });

// Click with a hard cut instead of a crossfade (for clicks that resize or move a modal,
// where a crossfade would show a double image).
async function cutClick(v, target, ms = 450) {
  const p = await v.moveTo(target);
  await v.animate(0.1, (t) => { v.cursor.press = t; });
  await v.act(async (f, page) => { await page.mouse.click(p.x, p.y); await wait(ms); }, { fade: 0.03 });
  await v.animate(0.12, (t) => { v.cursor.press = 1 - t; });
  v.cursor.press = 0;
}

// Block-editor screens send Document-Isolation-Policy, which renders the editor at the wrong
// size inside the stage iframe. Drop that header for the recording browser only.
async function stripIsolation(v) {
  await v.context.route(/\/wp-admin\/(post|post-new)\.php/, async (route) => {
    if (route.request().resourceType() !== 'document') return route.continue();
    const resp = await route.fetch();
    const headers = { ...resp.headers() };
    delete headers['document-isolation-policy'];
    await route.fulfill({ response: resp, headers });
  });
}

async function editorReady(f) {
  await f.waitForFunction(() => window.wp && wp.data && wp.data.select('core/block-editor') && wp.data.select('core/block-editor').getBlocks().length > 0, null, { timeout: 60000 });
  await f.evaluate(() => {
    const s = document.createElement('style');
    s.textContent = '.block-editor-block-contextual-toolbar, .block-editor-block-list__block-popover, .block-editor-block-popover { display: none !important; }';
    document.head.appendChild(s);
  });
  await wait(600);
}

// The PHP call a developer's download handler makes (Developer API), shown as a card while
// the same call runs off camera.
const CODE_HTML = `
<div class="h">PHP &middot; your download handler</div>
<pre><span class="v">$features</span> = <span class="t">Features</span>::<span class="f">forSubscription</span>( <span class="v">$subscription_id</span> );
<span class="v">$features</span>-&gt;<span class="f">consume</span>( <span class="s">'Monthly Downloads'</span>, <span class="n">1</span>, [
    <span class="s">'reason'</span> =&gt; <span class="s">'Member download'</span>,
] );</pre>`;
const showCode = (v) => v.act(async (f) => {
  await f.evaluate((html) => {
    const st = document.createElement('style');
    st.id = 'fv-code-style';
    st.textContent = `#fv-code{position:fixed;z-index:99999;left:50%;bottom:118px;transform:translateX(-50%);width:760px;padding:24px 30px 26px;border-radius:18px;background:#2B0A33;color:#FDF4EB;box-shadow:0 24px 60px rgba(43,10,51,.35);font:500 13px/1.2 ui-sans-serif,system-ui,sans-serif}
#fv-code .h{font-size:14px;letter-spacing:.12em;text-transform:uppercase;color:#FE6C0D;font-weight:800;margin-bottom:14px}
#fv-code pre{margin:0;font:500 19px/1.6 ui-monospace,SFMono-Regular,Menlo,monospace;white-space:pre;color:#FDF4EB;background:none;padding:0;border:0}
#fv-code .v{color:#FDD9CD}#fv-code .t{color:#EDDBFD}#fv-code .f{color:#C9A6FF}#fv-code .s{color:#9EE6A8}#fv-code .n{color:#FE6C0D}`;
    document.head.appendChild(st);
    const d = document.createElement('div');
    d.id = 'fv-code';
    d.innerHTML = html;
    // centre the card on the My Features tables, not on the whole page
    const sec = document.querySelector('.arraysubspro-features-product-section');
    if (sec) { const r = sec.getBoundingClientRect(); d.style.left = `${Math.round(r.left + r.width / 2)}px`; }
    document.body.appendChild(d);
  }, CODE_HTML);
}, { settle: false });

export default {
  slug: 'feature-manager',
  tier: 'Pro',
  intro: { title: 'Feature *Manager*', sub: 'One feature list per plan, shown before and after the sale.' },
  outro: { title: 'List your perks *once*.', sub: 'ArraySubs Pro shows them on the product page, in My Account and in your access rules.', url: 'subscriptionplugin.pro' },

  // Runs before every recording (also single-chapter re-records).
  setup: async (v) => {
    setMode('per_subscription');
    await stripIsolation(v);
    const as = v.as.bind(v);
    v.as = async (user) => {
      await as(user);
      await v.context.addCookies([{ name: 'fv_clip', value: CLIP, domain: 'localhost', path: '/', expires: Math.floor(Date.now() / 1000) + 86400 }]);
    };
  },

  teardown: async () => {
    setMode('per_subscription');
  },

  chapters: [
    // 1 ------------------------------------------------------------------ settings
    {
      kicker: 'Settings',
      title: 'Choose where perks *show*',
      run: async (v) => {
        await v.as(1);
        await v.goto('/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/feature-manager');
        await v.say('Feature Manager has its own **settings tab** in ArraySubs Pro.');
        await v.focus(await rectOf(v, '.field-warper', { root: '.arraysubs-fb-card', n: 0, text: true }), { label: 'Four display switches', dim: true, side: 'right', pad: 14 });
        await v.unfocus();
        await v.zoom('.arraysubs-fb-card >> nth=0', { scale: 1.55 });
        await v.say('Show perks on the **product page** and in **My Account**.');
        await v.say('Show **usage counts** to members, to your team, or both.');
        await v.zoomOut();
        await v.focus('.arraysubs-feature-guide__header', { label: 'Built-in setup guide', side: 'left', pad: 14 });
        await v.say('A short guide on the same page walks you through the **first plan**.');
        await v.unfocus();
      },
    },

    // 2 ------------------------------------------------------------------ product editor
    {
      kicker: 'Admin',
      title: "List each plan's *perks*",
      sub: 'Creative Studio Membership, $29 a month',
      before: async () => { resetProduct(); },
      run: async (v) => {
        await v.as(1);
        await v.goto(`/wp-admin/post.php?post=${PRODUCT}&action=edit`);
        await noFlyouts(v);
        await v.say('Each plan keeps its perks in the **product editor**.');
        await v.scrollTo('#woocommerce-product-data', { block: 'start' });
        await v.click('li.arraysubs_features_options a');
        await v.focus('#arraysubs_features_data .arraysubspro-feature-preview', { label: '4 perks so far', side: 'right' });
        await cutClick(v, '#arraysubs_features_data .arraysubspro-feature-edit-btn', 600);
        await v.say('Each row is one perk: a **name** and a **value**.');
        await v.focus(await rectOf(v, '.arraysubspro-feature-table .arraysubspro-feature-col-type'), { label: 'Types stay in your admin', side: 'top', pad: 6 });
        await v.unfocus();
        await cutClick(v, '.arraysubspro-feature-table-footer button', 600);
        const row = 'tr.arraysubspro-feature-row--editing';
        await v.type(`${row} .arraysubspro-feature-name-input`, 'Monthly Downloads');
        await v.select(`${row} .arraysubspro-feature-type-select`, 'number');
        await v.type(`${row} .arraysubspro-feature-value-input`, '20');
        await v.say('A **translate** icon sits next to each name and value.', { hold: 0.6 });
        await v.focus(`${row} .arraysubspro-feature-col-name .arraysubs-tf-btn`, { label: 'Translate icon', side: 'bottom', pad: 6, hold: 2 });
        await v.unfocus();
        await v.say('Give a number a **refill period** and it becomes an allowance.');
        await v.zoom(row, { scale: 1.5 });
        // Display-only: draw the native refill dropdown open so its choices are visible.
        const sel = `${row} .arraysubspro-feature-reset-period select`;
        await v.act(async (f) => {
          await f.evaluate((s) => {
            const el = document.querySelector(s);
            el.dataset.fvOpen = '1';
            el.size = el.options.length;
            Object.assign(el.style, { position: 'absolute', zIndex: 50, height: 'auto', overflow: 'hidden', background: '#fff', boxShadow: '0 10px 24px rgba(0,0,0,.18)', padding: '4px 0' });
            el.parentElement.style.position = 'relative';
          }, sel);
        }, { settle: false });
        await v.focus(sel, { label: 'Six refill choices', side: 'left', pad: 4 });
        await v.unfocus();
        await v.act(async (f) => {
          await f.locator(sel).first().selectOption('month');
          await f.evaluate((s) => {
            const el = document.querySelector(s);
            el.size = 0;
            el.removeAttribute('size');
            el.removeAttribute('style');
            el.parentElement.style.position = '';
          }, sel);
          await wait(200);
        }, { settle: false });
        await v.focus(`${row} .arraysubspro-feature-reset-period`, { label: 'Refills every month', side: 'bottom' });
        await v.unfocus();
        await v.say('Switch a row **off** to hide it without deleting it.', { hold: 0.6 });
        await v.focus(`${row} .arraysubspro-feature-toggle-switch`, { label: 'Off hides the row, keeps it', side: 'bottom', pad: 6, hold: 2.4 });
        await v.unfocus();
        await v.zoomOut();
        await cutClick(v, `${row} .arraysubspro-feature-action-btn--save`, 500);
        await v.say('Order the rows the way **shoppers** should read them.');
        for (const n of [4, 3, 2]) {
          await v.click(`tr.arraysubspro-feature-row >> nth=${n} >> button[title="Move up"]`, { hold: 0.15 });
        }
        const second = await v.eval(() => {
          const tr = document.querySelectorAll('tr.arraysubspro-feature-row')[1];
          const a = tr.querySelector('.arraysubspro-feature-col-drag').getBoundingClientRect();
          const b = tr.querySelector('.arraysubspro-feature-text').getBoundingClientRect();
          return { x: a.left, y: a.top, w: b.right + 16 - a.left, h: a.height };
        });
        await v.focus(second, { label: 'Now second in the list', side: 'right', pad: 4 });
        await v.unfocus();
        await cutClick(v, '.arraysubspro-feature-modal-actions .button-primary', 600);
        await v.focus('#arraysubs_features_data .arraysubspro-feature-summary', { label: '5 features', side: 'right' });
        await v.say('**Update** the product and the new list goes live.');
        await v.click('#publish', { nav: true, hold: 0 });
        await noFlyouts(v);
        await v.hideCursor();
        await v.act(async (f, page) => {
          // park the real mouse away from the metabox handles (their tooltips)
          await page.mouse.move(700, 160);
          v.cursor.x = 700; v.cursor.y = 160;
          await f.evaluate(() => { document.querySelector('li.arraysubs_features_options a').click(); });
          await wait(300);
          await f.evaluate(() => {
            const el = document.querySelector('#woocommerce-product-data');
            window.scrollTo(0, el.getBoundingClientRect().top + scrollY - 50);
          });
        });
        await v.focus('#arraysubs_features_data .arraysubspro-feature-preview', { label: 'Saved with the product', side: 'right' });
        await v.unfocus();
      },
    },

    // 3 ------------------------------------------------------------------ product page
    {
      kicker: 'Storefront',
      title: "Shoppers see what's *included*",
      before: async () => { fullProduct(); },
      run: async (v) => {
        await v.as('guest');
        await v.goto('/product/creative-studio-membership/');
        await v.say("A **What's Included** box sits right under the price.");
        await v.focus('.arraysubspro-product-features', { label: 'Read live from the plan', dim: true, side: 'left' });
        v.poster();
        await v.unfocus();
        await v.zoom('.arraysubspro-product-features', { scale: 1.45 });
        await v.focus('.arraysubspro-feature-item >> nth=1 >> .arraysubspro-feature-value', { label: 'Limit and refill period', side: 'left', pad: 6 });
        await v.focus('.arraysubspro-feature-item >> nth=3 >> .arraysubspro-feature-value', { label: 'A cross for No', side: 'left', pad: 6 });
        await v.say('Shoppers see names and values only, never the **types**.');
        await v.unfocus();
        await v.zoomOut();
      },
    },

    // 4 ------------------------------------------------------------------ tiers
    {
      kicker: 'Tiers',
      title: 'One page, every *tier*',
      sub: 'Membership Tier: Basic and Professional',
      run: async (v) => {
        await v.as(1);
        // Open the editor straight on the Variations tab (behind the chapter card).
        await v.act(async (f) => {
          await f.goto(`http://localhost:10013/wp-admin/post.php?post=${TIER}&action=edit`, { waitUntil: 'domcontentloaded', timeout: 90000 });
          await f.waitForSelector('li.variations_options a', { timeout: 60000 });
          await f.evaluate(() => {
            const st = document.createElement('style');
            st.textContent = '#adminmenu li.menu-top:not(.wp-has-current-submenu):not(.current) .wp-submenu { display: none !important; }';
            document.head.appendChild(st);
            document.querySelector('li.variations_options a').click();
          });
          await f.waitForSelector('.woocommerce_variation', { timeout: 60000 });
          await wait(500);
          await f.evaluate(() => {
            const el = document.querySelector('#woocommerce-product-data');
            window.scrollTo(0, el.getBoundingClientRect().top + scrollY - 50);
          });
        });
        await v.say('On a variable product, each **tier** can have its own list.');
        await v.click('.woocommerce_variation >> nth=1 >> a.edit_variation', { wait: 500 });
        await v.scrollTo('.arraysubspro-variation-features >> nth=1', { block: 'center' });
        await v.focus('.arraysubspro-variation-features >> nth=1', { label: "Basic tier's own list", side: 'right' });
        await v.say("A tier's own list **replaces** the default list. Lists never merge.");
        await v.unfocus();

        await v.say(null);
        await v.as('guest');
        await v.goto('/product/demo-%c2%b7-membership-tier/');
        // Display-only: the seeded description is placeholder copy.
        await v.eval(() => {
          const st = document.createElement('style');
          st.textContent = '.woocommerce-Tabs-panel--description p { visibility: hidden !important; } .woocommerce-variation-description { display: none !important; }';
          document.head.appendChild(st);
        });
        await v.scrollBy(150);
        await v.say('Shoppers pick a tier, and the box **swaps** to match.');
        await v.select('#tier', 'Basic');
        await v.focus('.arraysubspro-product-features', { label: 'Basic', side: 'left' });
        await v.unfocus();
        await v.select('#tier', 'Professional');
        await v.focus('.arraysubspro-product-features', { label: 'Professional', side: 'left' });
        await v.say('They see what each **step up** adds before they pay.');
        await v.unfocus();
      },
    },

    // 5 ------------------------------------------------------------------ My Account
    {
      kicker: 'My Account',
      title: 'Members track their *usage*',
      sub: 'Luna Anderson holds two plans',
      before: async () => { demoUsage(8); setMode('per_subscription'); },
      run: async (v) => {
        await v.as(LUNA);
        await v.goto('/my-account/as-features/');
        await v.say('Members get a **My Features** page in My Account.');
        await v.scrollTo('.arraysubspro-features-product-section >> nth=0', { block: 'start', offset: -50 });
        await v.focus('.arraysubspro-features-product-section >> nth=0', { label: 'One table per plan', side: 'right' });
        await v.unfocus();
        const dl = '.arraysubspro-features-product-section >> nth=0 >> tr.arraysubspro-feature-row >> nth=1';
        await v.zoom(dl, { scale: 1.7 });
        await v.say('Used, limit and the **next refill date**.');
        await v.zoomOut();
        await v.say(null);
        await showCode(v);
        await v.say('Your code or a FlowMattic workflow **records each use**.', { hold: 0.6 });
        await v.focus('#fv-code', { pad: 6, hold: 2.8 });
        await v.unfocus();
        await v.act(async (f) => {
          wp(['eval', `ArraySubs\\Developer\\Features::forSubscription(${SUB_PRO})->consume('Monthly Downloads', 1, ['reason' => 'Member download', 'operation_id' => 'fv-' . time()]);`]);
          await f.goto(f.url(), { waitUntil: 'domcontentloaded' });
          await wait(300);
          await f.evaluate(() => {
            const el = document.querySelectorAll('.arraysubspro-features-product-section')[0];
            const y = el.getBoundingClientRect().top + scrollY - 74 - (document.getElementById('wpadminbar')?.offsetHeight || 0);
            window.scrollTo(0, y);
          });
        });
        await v.zoom(dl, { scale: 1.7 });
        await v.focus(`${dl} >> td.feature-usage`, { label: 'One more download counted', side: 'bottom' });
        await v.unfocus();
        await v.zoomOut();

        await v.say(null);
        await v.goto('/my-account/as-subscriptions/');
        await v.say('Each subscription card lists its **perks** too.');
        await v.scrollTo('.arraysubs-subscription-card__access >> nth=0', { block: 'center' });
        await v.zoom('.arraysubs-subscription-card__access >> nth=0', { scale: 1.7 });
        await v.focus('.arraysubs-subscription-card__access >> nth=0', { label: 'Access Scope', side: 'top' });
        await v.unfocus();
        await v.zoomOut();
      },
    },

    // 6 ------------------------------------------------------------------ Feature Log
    {
      kicker: 'Admin',
      title: "Check any member's *perks*",
      before: async () => { demoUsage(9); },
      run: async (v) => {
        await v.as(1);
        await v.goto(`/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/${SUB_PRO}`);
        await v.say('Support questions start from the **subscription**.');
        await v.focus('a.arraysubs-sd__link:has-text("Feature Log")', { label: 'One click away', side: 'left', pad: 6 });
        await v.click('a.arraysubs-sd__link:has-text("Feature Log")', { wait: 400 });
        await v.say('The **Feature Log** shows every live plan, with usage.');
        const t0 = { root: '.arraysubs-member-insight__table', n: 0, text: true };
        await v.focus(await rectOf(v, 'th:nth-child(4), td:nth-child(4)', t0), { label: 'Matches My Features', side: 'right', pad: 10 });
        await v.unfocus();
        await v.say('The **Type** column helps your team. Customers never see it.', { hold: 0.6 });
        await v.focus(await rectOf(v, 'th:nth-child(2), td:nth-child(2)', t0), { label: 'Admin only', side: 'top', pad: 10, hold: 2.6 });
        await v.unfocus();

        await v.say(null);
        await v.moveTo({ x: 1000, y: 110, w: 4, h: 4 });
        await v.goto(`/wp-admin/admin.php?page=arraysubs-mainadmin#/manage-members/${LUNA}`);
        await v.say('The same log opens from **Manage Members**.');
        await v.focus('a.arraysubs-member-insight__profile-link:has-text("Show Features")', { pad: 6, hold: 1.4 });
        await v.click('a.arraysubs-member-insight__profile-link:has-text("Show Features")', { wait: 500 });
        await v.focus('.arraysubs-member-insight__table >> nth=0', { label: 'Same plans, same usage', side: 'top', pad: 8 });
        await v.unfocus();
      },
    },

    // 7 ------------------------------------------------------------------ combine
    {
      kicker: 'Settings',
      title: 'Two plans? *Stack* them',
      before: async () => { demoUsage(9); setMode('per_subscription'); },
      run: async (v) => {
        try {
          await v.as(1);
          await v.goto('/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/feature-manager');
          await v.scrollTo('.arraysubs-fb-card >> nth=1', { block: 'center' });
          v.captionPos('tr'); // keep the bottom Save bar clear
          await v.say('When a member holds **two plans**, you choose how perks add up.');
          await v.focus('.arraysubs-fb-card >> nth=1', { label: 'One setting for every member', side: 'right' });
          await v.unfocus();
          await v.click('.arraysubs-fb-card >> nth=1 >> label:has-text("Combine")');
          await v.say('**Combine** adds the numbers. **Highest value** keeps the biggest.');
          await v.click('button.button-primary:has-text("Save Settings")', { wait: 500 });
          await v.hold(0.8);

          await v.say(null);
          v.captionPos('bl');
          await v.as(LUNA);
          await v.goto('/my-account/as-features/');
          await v.scrollTo('.arraysubspro-features-combined-section', { block: 'start', offset: -50 });
          await v.say('Luna now sees **one combined table** for both plans.');
          await v.zoom('.arraysubspro-features-combined-section', { scale: 1.35 });
          await v.focus('.arraysubspro-features-combined-section tr.arraysubspro-feature-row:has-text("Team Seats")', { label: '5 seats + 1 seat = 6', side: 'right', pad: 4 });
          await v.focus('.arraysubspro-features-combined-section tr.arraysubspro-feature-row:has-text("Monthly Downloads")', { label: '15 + 10 downloads = 25', side: 'right', pad: 4 });
          await v.unfocus();
          await v.zoomOut();
        } finally {
          setMode('per_subscription');
        }
      },
    },

    // 8 ------------------------------------------------------------------ gating
    {
      kicker: 'Access',
      title: 'Open pages by *perk*',
      sub: 'Team Workspace needs a plan with 5 team seats',
      before: async () => { setMode('per_subscription'); },
      run: async (v) => {
        await v.as(1);
        await v.act(async (f) => {
          await f.goto(`http://localhost:10013/wp-admin/post.php?post=${WORKSPACE}&action=edit`, { waitUntil: 'domcontentloaded', timeout: 90000 });
          await editorReady(f);
        });
        await v.say('Access rules can check a perk, like **Team Seats**.');
        await v.scrollTo('.arraysubs-restriction-panel', { block: 'center' });
        await v.focus('.components-panel__body:has(.arraysubs-restriction-panel)', { label: 'Rule on this page', side: 'left' });
        await v.click('.arraysubs-restriction-panel__configure', { wait: 500 });
        const feat = '.components-modal__frame .arraysubs-access-conditions__feature';
        await v.scrollTo(feat, { block: 'center' });
        await v.moveTo('.components-modal__frame button:has-text("Cancel")');
        await v.focus(feat, { dim: true, pad: 10 });
        await v.say('Pick the perk and the amount **one plan** must give.');
        await v.unfocus();
        await v.click('.components-modal__frame button:has-text("Cancel")', { wait: 300 });

        await v.say(null);
        await v.as(STARTER);
        await v.goto('/my-account/as-subscriptions/');
        const seats = '.arraysubs-subscription-card__access li:has-text("Team Seats")';
        await v.scrollTo(seats, { block: 'center' });
        await v.say("John Hamilton's Starter plan has **1 team seat**.", { hold: 0.6 });
        await v.zoom(await cardFocus(v, '.arraysubs-subscription-card'), { scale: 1.5 });
        await v.focus(seats, { label: 'Team Seats: 1', side: 'top', pad: 6, hold: 2.2 });
        await v.unfocus();
        await v.say(null);
        await v.goto('/team-workspace/');
        await v.say('So the Team Workspace stays **locked** for John.');
        await v.zoom(await pageBody(v), { scale: 1.6 });
        await v.focus(await pageBody(v), { label: 'Your message, not the page', side: 'bottom', pad: 18 });
        await v.unfocus();
        await v.zoomOut();

        await v.say(null);
        await v.as(LUNA);
        await v.goto('/my-account/as-subscriptions/');
        const lunaSeats = '.arraysubs-subscription-card:has-text("Professional") .arraysubs-subscription-card__access li:has-text("Team Seats")';
        await v.scrollTo(lunaSeats, { block: 'center' });
        await v.say("Luna's Professional plan has **5 team seats**.", { hold: 0.6 });
        await v.zoom('.arraysubs-subscription-card:has-text("Professional")', { scale: 1.5 });
        await v.focus(lunaSeats, { label: 'Team Seats: 5', side: 'top', pad: 6, hold: 2.2 });
        await v.unfocus();
        await v.say(null);
        await v.goto('/team-workspace/');
        await v.say('With 5 seats on her plan, Luna gets **in**.');
        await v.zoom(await pageBody(v), { scale: 1.6 });
        await v.focus(await pageBody(v), { label: 'Unlocked for Luna', side: 'bottom', pad: 18 });
        await v.unfocus();
        await v.zoomOut();
      },
    },
  ],
};
