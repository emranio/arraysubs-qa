// Feature Manager (ArraySubs Pro) product tour.
// Story: settings -> build a plan's list -> What's Included -> tiers -> My Features + usage
// -> admin Feature Log -> Combine mode -> content opened by a perk.
// Store changes and undo steps: notes/feature-manager.md
import { wp } from '../lib/director.mjs';

const PRODUCT = 32093; // Creative Studio Membership (hidden demo product made for this video)
const TIER = 6722; // Membership Tier (variable): Basic 6723, Professional 6724
const LUNA = 842; // Luna Anderson: #29307 Membership Tier - Professional, #30240 Plus Membership
const STARTER = 805; // John Hamilton: Starter Membership (1 team seat)
const SUB_PRO = 29307;
const SUB_PLUS = 30240;

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

export default {
  slug: 'feature-manager',
  tier: 'Pro',
  intro: { title: 'Feature *Manager*', sub: 'One feature list per plan, shown before and after the sale.' },
  outro: { title: 'List your perks *once*.', sub: 'ArraySubs Pro shows them on the product page, in My Account and in your access rules.', url: 'subscriptionplugin.pro' },

  // Runs before every recording (also single-chapter re-records): keep it to the shared setting.
  setup: async () => {
    setMode('per_subscription');
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
        await v.focus('.arraysubs-fb-card >> nth=0', { label: 'Four display switches', dim: true, side: 'right' });
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
        await v.click('#arraysubs_features_data .arraysubspro-feature-edit-btn');
        await v.say('Each row is one perk: a **name** and a **value**.');
        await v.focus('.arraysubspro-feature-table th.arraysubspro-feature-col-type', { label: 'Types stay in your admin', side: 'bottom', pad: 6 });
        await v.unfocus();
        await v.click('.arraysubspro-feature-table-footer button');
        const row = 'tr.arraysubspro-feature-row--editing';
        await v.type(`${row} .arraysubspro-feature-name-input`, 'Monthly Downloads');
        await v.select(`${row} .arraysubspro-feature-type-select`, 'number');
        await v.type(`${row} .arraysubspro-feature-value-input`, '20');
        await v.say('Give a number a **refill period** and it becomes an allowance.');
        await v.select(`${row} .arraysubspro-feature-reset-period select`, 'month');
        await v.zoom(row, { scale: 1.6 });
        await v.focus(`${row} .arraysubspro-feature-reset-period`, { label: 'Refills every month', side: 'bottom' });
        await v.unfocus();
        await v.zoomOut();
        await v.click(`${row} .arraysubspro-feature-action-btn--save`);
        await v.say('Order the rows the way **shoppers** should read them.');
        for (const n of [4, 3, 2]) {
          await v.click(`tr.arraysubspro-feature-row >> nth=${n} >> button[title="Move up"]`, { hold: 0.15 });
        }
        await v.focus('tr.arraysubspro-feature-row >> nth=1', { label: 'Now second in the list', side: 'bottom', pad: 4 });
        await v.unfocus();
        await v.click('.arraysubspro-feature-modal-actions .button-primary');
        await v.focus('#arraysubs_features_data .arraysubspro-feature-summary', { label: '5 features', side: 'right' });
        await v.say('**Update** the product and the new list goes live.');
        await v.click('#publish', { nav: true, hold: 0 });
        await noFlyouts(v);
        await v.act(async (f) => {
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
        await v.focus('.arraysubspro-feature-item >> nth=1', { label: 'Limit and refill period', side: 'bottom', pad: 4 });
        await v.focus('.arraysubspro-feature-item >> nth=3', { label: 'A cross for No', side: 'bottom', pad: 4 });
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
        await v.goto(`/wp-admin/post.php?post=${TIER}&action=edit`);
        await noFlyouts(v);
        await v.scrollTo('#woocommerce-product-data', { block: 'start' });
        await v.click('li.variations_options a', { wait: 600 });
        await v.say('On a variable product, each **tier** can have its own list.');
        await v.click('.woocommerce_variation >> nth=1 >> a.edit_variation', { wait: 500 });
        await v.scrollTo('.arraysubspro-variation-features >> nth=1', { block: 'center' });
        await v.focus('.arraysubspro-variation-features >> nth=1', { label: "Basic tier's own list", side: 'right' });
        await v.say("A tier's own list **replaces** the default list. Lists never merge.");
        await v.unfocus();

        await v.as('guest');
        await v.goto('/product/demo-%c2%b7-membership-tier/');
        // Display-only: the seeded description is placeholder copy.
        await v.eval(() => {
          const st = document.createElement('style');
          st.textContent = '.woocommerce-Tabs-panel--description p { visibility: hidden !important; }';
          document.head.appendChild(st);
        });
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
        await v.zoom('.arraysubspro-features-product-section >> nth=0 >> tr.arraysubspro-feature-row >> nth=1', { scale: 1.7 });
        await v.say('Used, limit and the **next refill date**.');
        await v.zoomOut();
        await v.say('Your code or a FlowMattic workflow **records each use**.');
        await v.act(async (f) => {
          wp(['eval', `ArraySubs\\Developer\\Features::forSubscription(${SUB_PRO})->consume('Monthly Downloads', 1, ['reason' => 'download', 'operation_id' => 'fv-' . time()]);`]);
          await f.goto(f.url(), { waitUntil: 'domcontentloaded' });
          await wait(300);
          await f.evaluate(() => {
            const el = document.querySelectorAll('.arraysubspro-features-product-section')[0];
            const y = el.getBoundingClientRect().top + scrollY - 74 - (document.getElementById('wpadminbar')?.offsetHeight || 0);
            window.scrollTo(0, y);
          });
        });
        await v.zoom('.arraysubspro-features-product-section >> nth=0 >> tr.arraysubspro-feature-row >> nth=1', { scale: 1.7 });
        await v.focus('.arraysubspro-features-product-section >> nth=0 >> tr.arraysubspro-feature-row >> nth=1 >> td.feature-usage', { label: 'One more download counted', side: 'bottom' });
        await v.unfocus();
        await v.zoomOut();

        await v.say(null);
        await v.goto('/my-account/as-subscriptions/');
        await v.say('Each subscription card lists its **perks** too.');
        await v.scrollTo('.arraysubs-subscription-card__access >> nth=0', { block: 'center' });
        await v.zoom('.arraysubs-subscription-card >> nth=0', { scale: 1.7 });
        await v.focus('.arraysubs-subscription-card__access >> nth=0', { label: 'Access Scope', side: 'bottom' });
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
        await v.scrollTo('.arraysubs-member-insight__table >> nth=0', { block: 'start' });
        await v.focus('.arraysubs-member-insight__table >> nth=0', { label: 'Same numbers the member sees', side: 'bottom' });
        await v.unfocus();
        await v.zoom('.arraysubs-member-insight__table >> nth=0', { scale: 1.45 });
        await v.focus('.arraysubs-member-insight__table >> nth=0 >> th:has-text("Type")', { label: 'Admin only', side: 'bottom', pad: 6 });
        await v.say('The **Type** column helps your team. Customers never see it.');
        await v.unfocus();
        await v.zoomOut();
        await v.goto(`/wp-admin/admin.php?page=arraysubs-mainadmin#/manage-members/${LUNA}`);
        await v.focus('a.arraysubs-member-insight__profile-link:has-text("Show Features")', { label: 'Also on the member profile', side: 'bottom', pad: 6 });
        await v.say('The same log opens from **Manage Members**.');
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
          await v.say('When a member holds **two plans**, you choose how perks add up.');
          await v.focus('.arraysubs-fb-card >> nth=1', { label: 'One setting for every member', side: 'right' });
          await v.unfocus();
          await v.click('.arraysubs-fb-card >> nth=1 >> label:has-text("Combine")');
          await v.say('**Combine** adds the numbers. **Highest value** keeps the biggest.');
          await v.click('button.button-primary:has-text("Save Settings")', { wait: 500 });
          await v.hold(0.8);

          await v.as(LUNA);
          await v.goto('/my-account/as-features/');
          await v.scrollTo('.arraysubspro-features-combined-section', { block: 'start' });
          await v.say('Luna now sees **one combined table** for both plans.');
          await v.zoom('.arraysubspro-features-combined-section', { scale: 1.35 });
          await v.focus('.arraysubspro-features-combined-section tr.arraysubspro-feature-row:has-text("Team Seats")', { label: '5 seats + 1 seat = 6', side: 'bottom', pad: 4 });
          await v.focus('.arraysubspro-features-combined-section tr.arraysubspro-feature-row:has-text("Monthly Downloads")', { label: '15 + 10 downloads = 25', side: 'bottom', pad: 4 });
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
      sub: 'Team Workspace needs 5 team seats',
      before: async () => { setMode('per_subscription'); },
      run: async (v) => {
        await v.as(STARTER);
        await v.goto('/demo-team-workspace/');
        await v.say('Access rules can check a perk, like **Team Seats**.');
        await v.focus(await pageBody(v), { label: 'Starter plan: 1 seat', side: 'bottom', pad: 18 });
        await v.say('On a 1-seat plan, the Team Workspace stays **locked**.');
        await v.unfocus();
        await v.as(LUNA);
        await v.goto('/demo-team-workspace/');
        await v.focus(await pageBody(v), { label: 'Professional plan: 5 seats', side: 'bottom', pad: 18 });
        await v.say('With 5 seats on her plan, Luna gets **in**.');
        await v.unfocus();
      },
    },
  ],
};
