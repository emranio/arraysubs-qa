// Cancellation feedback: a customer gives a reason and their own words, the subscription
// keeps both for the team. Request-scoped overrides only (zz-fv-clips.php): customer
// cancellation allowed, cancel at the end of the paid period (so it can be undone), currency left.
// Store changes: subscription 31605 (Emma Lawson, Annual Library Pass) gets a scheduled
// cancellation with reason Other + details, then teardown undoes the cancellation. Its notes,
// cancellation emails (local mail catcher) and Pro retention log rows stay.
import { cardRect } from './_lib.mjs';

const SUB = 31605;
const CUSTOMER = 1213;
const WORDS = 'Moving abroad for six months. I will be back in the spring.';

const undo = async (v) => {
  await v.as(CUSTOMER);
  await v.goto(`/my-account/as-view-subscription/${SUB}/`);
  const btn = v.frame.locator('button:has-text("Undo Scheduled Cancellation") >> visible=true').first();
  if (await btn.count()) {
    await btn.click();
    await v.frame.locator('button:has-text("Undo Cancellation") >> visible=true').last().click();
    await v.frame.waitForTimeout(2500);
  }
};

export default {
  overrides: {
    options: { woocommerce_currency_pos: 'left' },
    settings: { 'customer_actions.allow_cancellation': true, 'cancellation.cancel_immediately': false },
  },
  teardown: async (v, { wp }) => {
    await undo(v);
    console.log('status after undo:', wp(['eval', `$id=${SUB}; echo get_post_status($id), ' | ', json_encode(array_filter(get_post_meta($id), fn($k) => (bool) preg_match('/cancel/i', $k), ARRAY_FILTER_USE_KEY));`]));
  },
  items: [
    {
      key: 'cancellation/feedback',
      type: 'video',
      before: async (v) => {
        await v.as(CUSTOMER);
        await v.goto(`/my-account/as-view-subscription/${SUB}/`);
        await v.scrollTo('button.arraysubs-cancel-subscription', { block: 'center' });
      },
      run: async (v) => {
        await v.hold(0.7);
        await v.click('button.arraysubs-cancel-subscription', { wait: 300 });
        const modal = '#arraysubs-cancel-modal .arraysubs-modal__content';
        await v.zoom(modal, { pad: 36, hold: 0.3 });
        await v.select('#cancel-reason', 'other');
        await v.type('#cancel-other-reason', WORDS, { cps: 30 });
        await v.focus('#arraysubs-cancel-modal .arraysubs-modal__form', { label: 'A reason, in their own words', side: 'top', hold: 1.6 });
        await v.unfocus();
        await v.click('#arraysubs-cancel-modal .arraysubs-modal__confirm-btn', { wait: 900 });
        await v.zoomOut();
        await v.hold(0.8, { note: 'confirm-step' });
        await v.click('button:has-text("No thanks, continue to cancel") >> visible=true', { wait: 2500 });
        await v.act(async (f) => { await f.waitForLoadState('load').catch(() => {}); await f.waitForTimeout(800); });
        await v.hold(1.0, { note: 'after-cancel' });
        await v.as(1);
        await v.goto(`/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/${SUB}`);
        await v.act(async (f) => { await f.locator('h3', { hasText: 'Scheduled Cancellation' }).first().waitFor({ timeout: 90000 }); await f.waitForTimeout(600); });
        const panel = await cardRect(v, 'h3', 'Scheduled Cancellation', { until: 'Custom Fields' });
        await v.zoom(panel, { pad: 40, hold: 0.3 });
        await v.focus(panel, { label: 'Reason and their words, saved', side: 'top', hold: 2.8 });
        v.poster();
        await v.hold(0.4);
      },
    },
    {
      key: 'cancellation/feedback-details',
      type: 'image',
      pad: 24,
      before: async (v) => {
        await v.as(1);
        await v.goto(`/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/${SUB}`);
        await v.frame.locator('h3', { hasText: 'Scheduled Cancellation' }).first().waitFor({ timeout: 90000 });
      },
      run: async (v) => cardRect(v, 'h3', 'Scheduled Cancellation', { until: 'Custom Fields' }),
    },
  ],
};
