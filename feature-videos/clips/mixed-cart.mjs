// Mixed cart: two subscriptions on different schedules plus a one-time product, one checkout,
// one order, two subscriptions. Request-scoped overrides only (zz-fv-clips.php): currency left,
// One Click Checkout back to Default so the cart page shows.
// Store changes that stay: one bacs order for demo customer 1221 (Priya Raman) and its two
// subscriptions (logged in notes/clips.md after the run). The cart is emptied before and after.
import { clearCart, addToCart } from './_lib.mjs';

const CUSTOMER = 1221;
const ITEMS = [6713, 6714, 6698]; // Weekly Coffee Delivery, Fortnightly Tea Delivery, Ceramic Member Mug
// Order placed by an earlier take of this clip (same three items, $55.00). Retakes show its
// order-received page instead of placing another order.
const EXISTING_ORDER = process.env.FV_MC_ORDER || '32172';

export default {
  overrides: {
    options: { woocommerce_currency_pos: 'left' },
    settings: { 'checkout.one_click_mode': 'default' },
  },
  setup: async (v, { wp }) => {
    clearCart(wp, CUSTOMER);
    if (EXISTING_ORDER) v.wpOrderUrl = wp(['eval', `echo wc_get_order(${Number(EXISTING_ORDER)})->get_checkout_order_received_url();`]).trim().replace('http://localhost:10013', '');
  },
  teardown: async (v, { wp }) => clearCart(wp, CUSTOMER),
  items: [
    {
      key: 'checkout/mixed-cart',
      type: 'video',
      before: async (v) => {
        await v.as(CUSTOMER);
        await v.goto('/cart-2/');
        await addToCart(v, ITEMS);
        await v.goto('/cart-2/');
        await v.act(async (f) => {
          await f.locator('.wc-block-cart-items__row .wc-block-components-product-name').nth(2).waitFor({ timeout: 20000 });
          await f.waitForTimeout(700);
        });
        await v.scrollTo('.wc-block-cart-items__row >> nth=1', { block: 'center' });
      },
      run: async (v) => {
        await v.hold(0.8);
        const row = (i) => `.wc-block-cart-items__row >> nth=${i}`;
        await v.focus(row(0), { label: 'Renews every week', side: 'right', hold: 1.4 });
        await v.focus(row(1), { label: 'Renews every 2 weeks', side: 'right', hold: 1.4 });
        await v.focus(row(2), { label: 'One-time product', side: 'right', hold: 1.4 });
        await v.unfocus();
        await v.click('.wc-block-cart__submit-button', { nav: true, wait: 600 });
        await v.act(async (f) => {
          await f.locator('.wc-block-components-checkout-place-order-button').waitFor({ timeout: 20000 });
          await f.waitForTimeout(800);
        });
        await v.scrollTo('.wp-block-woocommerce-checkout-order-summary-block', { block: 'center' });
        await v.focus('.wp-block-woocommerce-checkout-order-summary-block', { label: 'One checkout, one payment', side: 'top', hold: 2.0 });
        await v.unfocus();
        await v.scrollTo('.wc-block-components-checkout-place-order-button', { block: 'center' });
        if (EXISTING_ORDER) {
          // Swallow the real submit: the click animates, then we open the earlier take's order.
          await v.frame.evaluate(() => {
            const btn = document.querySelector('.wc-block-components-checkout-place-order-button');
            btn.addEventListener('click', (e) => { e.preventDefault(); e.stopImmediatePropagation(); }, true);
          });
          await v.click('.wc-block-components-checkout-place-order-button', { settle: false, wait: 200 });
          const url = v.wpOrderUrl;
          await v.goto(url);
        } else {
          await v.click('.wc-block-components-checkout-place-order-button', { nav: true, wait: 800 });
        }
        await v.act(async (f) => {
          await f.locator('section.arraysubs-order-subscriptions').waitFor({ timeout: 30000 });
          await f.waitForTimeout(500);
        });
        await v.scrollTo('section.arraysubs-order-subscriptions', { block: 'center' });
        await v.focus('section.arraysubs-order-subscriptions', { label: 'Two subscriptions from one order', side: 'top', hold: 2.6 });
        v.poster();
        await v.hold(0.5);
      },
    },
  ],
};
