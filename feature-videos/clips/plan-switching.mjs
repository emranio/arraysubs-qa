// Plan switching clips: upgrade / downgrade / crossgrade from My Account, plus admin stills.
// All changes are request-scoped overrides for this recorder only (zz-fv-clips.php):
//  - plan_switching.allow_crossgrades -> true
//  - Premium Membership (6712): upgrade path to Team Membership (26179), crossgrade path to #5908
//  - QA product #5908 shown as "Creator Membership" at $69.00 every month
//  - currency position left ($69.00); SKU codes hidden in the admin product picker
import { cardRect, unionRect, frameCss } from './_lib.mjs';

const SUB = 31629; // Hannah Whitaker, Premium Membership, bought through checkout (paid cycle verifiable)
const CUSTOMER = 1214;
const PENDING_SUB = 31662; // Owen Fletcher, booked switch to Plus at the next renewal

export default {
  overrides: {
    options: { woocommerce_currency_pos: 'left' },
    settings: { 'plan_switching.allow_crossgrades': true },
    meta: {
      6712: { _arraysubs_upgrade_products: [26179], _arraysubs_downgrade_products: [6711], _arraysubs_crossgrade_products: [5908] },
      5908: { _subscription_interval: '1' },
    },
    prices: { 5908: { price: '69.00', regular_price: '69.00', sale_price: '' } },
    text: {
      'QPC QA 2026-09-10 Advanced Annual': 'Creator Membership',
      ' (MEDIA-TEAM-MEMBERSHIP-20261125)': '',
      ' (DEMO-260927-PLUS)': '',
      ' (DEMO-260927-STARTER)': '',
      ' (#5908)': '',
    },
  },
  items: [
    {
      key: 'my-account/plan-switch',
      type: 'video',
      before: async (v) => {
        await v.as(CUSTOMER);
        await v.goto(`/my-account/as-view-subscription/${SUB}/`);
        await v.scrollTo('button.arraysubs-switch-plan', { block: 'center' });
      },
      run: async (v) => {
        await v.hold(0.8);
        await v.click('button.arraysubs-switch-plan', { wait: 400 });
        const modal = '#arraysubs-switch-modal .arraysubs-modal__content';
        await v.zoom(modal, { pad: 40, hold: 0.3 });
        await v.focus('.arraysubs-switch-option[data-switch-type="upgrade"]', { label: 'Upgrade', side: 'top', hold: 1.3 });
        await v.focus('.arraysubs-switch-option[data-switch-type="downgrade"]', { label: 'Downgrade', side: 'bottom', hold: 1.3 });
        await v.unfocus();
        await v.click('button.arraysubs-tabs__nav[data-target="tab-others"]');
        await v.focus('#tab-others .arraysubs-switch-option', { label: 'Crossgrade: same price, another plan', side: 'bottom', hold: 1.8 });
        await v.unfocus();
        await v.click('button.arraysubs-tabs__nav[data-target="tab-main"]');
        await v.act(async (f) => {
          await f.locator('.arraysubs-switch-option[data-switch-type="upgrade"] button').click();
          await f.locator('.arraysubs-switch-preview__table').waitFor({ state: 'visible', timeout: 15000 });
          await f.waitForTimeout(300);
        });
        await v.zoom(modal, { pad: 30, hold: 0.3 });
        await v.focus('.arraysubs-switch-preview__table', { label: 'Credit, charge and amount due', side: 'top', hold: 2.6 });
        v.poster();
        await v.hold(0.6);
      },
    },
    {
      key: 'plan-switching/paths',
      type: 'image',
      before: async (v) => {
        await v.as(1);
        await v.goto('/wp-admin/post.php?post=6712&action=edit');
        await v.act(async (f) => { await f.locator('li.linked_product_options a').first().click(); });
      },
      run: async (v) => {
        await frameCss(v, '.form-field[class*="_arraysubs_"] .select2-container { width: 420px !important; max-width: none !important; }');
        await v.scrollTo('._arraysubs_downgrade_products_field', { block: 'center' });
        return unionRect(v, '#linked_product_data .form-field[class*="_arraysubs_"]');
      },
    },
    {
      key: 'plan-switching/settings',
      type: 'image',
      before: async (v) => {
        await v.as(1);
        await v.goto('/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/plan-switching');
      },
      run: async (v) => {
        await v.eval(() => {
          const save = [...document.querySelectorAll('button')].find((b) => b.innerText.trim() === 'Save Settings');
          let el = save;
          while (el && el !== document.body && getComputedStyle(el).position !== 'fixed' && getComputedStyle(el).position !== 'sticky') el = el.parentElement;
          if (el && el !== document.body) el.style.visibility = 'hidden';
        });
        return cardRect(v, 'h3', 'Proration Settings', { until: 'Switch Fees' });
      },
    },
    {
      key: 'plan-switching/pending-switch',
      type: 'image',
      before: async (v) => {
        await v.as(1);
        await v.goto(`/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/${PENDING_SUB}`);
      },
      run: async (v) => {
        return cardRect(v, 'h2', `#${PENDING_SUB}`);
      },
    },
  ],
};
