// Checkout Builder (ArraySubs Pro) feature tour.
// Story: build a dog-food subscription checkout in the builder -> a customer goes through it
// -> the answers on the order, the subscription and My Account -> the same questions on the
// Checkout block. The demo layout lives in the private option `fv_cb_overlay`, which only this
// scene's browser sees (cookie fv_cb=1, mu-plugins/zz-fv-checkout-builder.php). The shared
// arraysubs_settings['checkout_builder'] is never written. See notes/checkout-builder.md.
import { wp, wpJson } from '../lib/director.mjs';

const HERE = '/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/feature-videos';
const LAYOUT = `${HERE}/notes/checkout-builder-layout.php`;
const PHOTO = `${HERE}/assets/checkout-builder/biscuit.jpg`;
const BUILDER = '/wp-admin/admin.php?page=arraysubs-mainadmin#/checkout-builder';
const CUSTOMER = 703; // Willow James (assigned block 678-738)
const DOG_FOOD_MEDIUM = 31816; // Grain-Free Dog Food, Medium bag (6 kg), $45 / month

const st = { orderId: 0, subId: 0 };

const layout = (variant) => wp(['eval-file', LAYOUT, variant]);

/** Log in (v.as) and add the cookie that switches on the private demo layout. */
async function as(v, user) {
  await v.as(user);
  await v.context.addCookies([{ name: 'fv_cb', value: '1', domain: 'localhost', path: '/', expires: Math.floor(Date.now() / 1000) + 86400 }]);
}

function clearCart(id) {
  return wpJson(`global $wpdb; delete_user_meta(${id}, "_woocommerce_persistent_cart_" . get_current_blog_id()); $wpdb->delete($wpdb->prefix . "woocommerce_sessions", ["session_key" => "${id}"]); echo json_encode(["ok" => 1]);`);
}

/** Latest order of the demo customer that carries checkout answers. */
function latestOrder() {
  return wpJson(`$o = null; foreach (wc_get_orders(["customer_id" => ${CUSTOMER}, "limit" => 10, "orderby" => "date", "order" => "DESC", "status" => "any", "type" => "shop_order"]) as $x) { if ($x->get_meta("_arraysubs_cf_dog_size")) { $o = $x; break; } } $subs = $o ? (array) $o->get_meta("_subscription_ids") : []; echo json_encode(["order" => $o ? $o->get_id() : 0, "sub" => (int) ($subs[0] ?? 0)]);`);
}

/** Union rect (app coords) of every visible element matching a CSS selector. */
async function unionRect(v, css) {
  return v.eval((sel) => {
    const rs = [...document.querySelectorAll(sel)].map((e) => e.getBoundingClientRect()).filter((r) => r.width > 0 && r.height > 0);
    if (!rs.length) throw new Error(`no match for ${sel}`);
    const x = Math.min(...rs.map((r) => r.left)), y = Math.min(...rs.map((r) => r.top));
    return { x, y, w: Math.max(...rs.map((r) => r.right)) - x, h: Math.max(...rs.map((r) => r.bottom)) - y };
  }, css);
}

/** Rect of the bordered card that holds a heading/label text (builder Settings tab). */
async function cardRect(v, text) {
  return v.eval((t) => {
    const el = [...document.querySelectorAll('body *')].find((e) => e.children.length === 0 && e.textContent.trim().toLowerCase() === t.toLowerCase() && e.getBoundingClientRect().width > 0);
    if (!el) throw new Error(`no ${t}`);
    let p = el.parentElement;
    while (p && p !== document.body && getComputedStyle(p).borderTopWidth === '0px') p = p.parentElement;
    const r = p.getBoundingClientRect();
    return { x: r.x, y: r.y, w: r.width, h: r.height };
  }, text);
}

/** Load the builder fresh, off camera (hash routes need a new query string to reload). */
async function loadBuilder(v) {
  await v.frame.goto(`http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin&fv=${Date.now()}#/checkout-builder`, { waitUntil: 'domcontentloaded' });
  await v.frame.locator('.arraysubs-cb-field-card').first().waitFor({ state: 'visible', timeout: 30000 });
  await v.settle();
}

/** Open the builder on camera and wait until its canvas is rendered (the SPA loads after settle). */
async function openBuilder(v) {
  await v.act(async (f) => {
    await f.goto(`http://localhost:10013${BUILDER}`, { waitUntil: 'domcontentloaded' });
    await f.locator('.arraysubs-cb-field-card').first().waitFor({ state: 'visible', timeout: 30000 });
    await new Promise((r) => setTimeout(r, 600));
  });
  await v.hold(0.5, { note: 'builder' });
}

/** Rect of the builder toolbar (Undo .. Save). */
async function toolbarRect(v) {
  return v.eval(() => {
    const save = [...document.querySelectorAll('button.button-primary')].find((b) => b.textContent.trim() === 'Save');
    const a = document.querySelector('.arraysubs-cb-fullscreen__history-button').getBoundingClientRect();
    const b = save.getBoundingClientRect();
    const top = Math.min(a.y, b.y);
    return { x: a.x, y: top, w: b.right - a.x, h: Math.max(a.bottom, b.bottom) - top };
  });
}

/** Drag a palette tile to a point (app coords) with a visible cursor; real pointer events. */
async function drag(v, from, to) {
  const a = await v.moveTo(from);
  await v.animate(0.12, (t) => { v.cursor.press = t; });
  await v.page.mouse.down();
  const b = v.toStage({ x: to.x, y: to.y, w: 0, h: 0 });
  const ax = a.x, ay = a.y;
  await v.animate(1.3, async (t) => {
    v.cursor.x = ax + (b.x - ax) * t;
    v.cursor.y = ay + (b.y - ay) * t - Math.sin(Math.PI * t) * 40;
    await v.page.mouse.move(v.cursor.x, v.cursor.y);
    await new Promise((r) => setTimeout(r, 12));
  });
  await new Promise((r) => setTimeout(r, 250));
  v.dirty = true;
  await v.hold(0.8, { note: 'drop-line' });
  await v.act(async () => { await v.page.mouse.up(); await new Promise((r) => setTimeout(r, 500)); });
  await v.animate(0.12, (t) => { v.cursor.press = 1 - t; });
}

/** Bring the classic checkout to "step 2 filled" off camera (for re-recording chapter 6 alone). */
async function ensureStepTwoFilled(v) {
  const f = v.frame;
  const ready = await f.evaluate(() => location.pathname === '/checkout/' && !!document.querySelector('.arraysubs-checkout-upload li')).catch(() => false);
  if (ready) return;
  clearCart(CUSTOMER);
  await as(v, CUSTOMER);
  await f.goto(`http://localhost:10013/?add-to-cart=${DOG_FOOD_MEDIUM}&quantity=1`, { waitUntil: 'domcontentloaded' });
  await v.settle();
  await f.goto('http://localhost:10013/checkout/', { waitUntil: 'domcontentloaded' });
  await v.settle();
  await f.locator('.arraysubs-step-buttons__next >> visible=true').first().click();
  await v.settle();
  for (const t of ['Medium', 'Yes', 'Chicken', 'Wheat']) {
    await f.locator(`label.arraysubs-grid-select__card:has-text("${t}")`).first().click();
    await v.settle();
  }
  await f.locator('tr.arraysubs-product-table__row:has-text("Peanut Butter Biscuits") button:has-text("Add to Cart")').click();
  await new Promise((r) => setTimeout(r, 1200));
  await v.settle();
  await f.setInputFiles('#_arraysubs_cf_photo_of_your_dog', PHOTO);
  await new Promise((r) => setTimeout(r, 1500));
  await v.settle();
}

export default {
  slug: 'checkout-builder',
  tier: 'Pro',
  intro: { title: 'Checkout *Builder*', sub: 'Ask your own questions at checkout, in short steps, and keep every answer on the subscription.' },
  outro: { title: 'Ask at checkout, not *after*.', sub: 'Every answer stays with the subscription.', url: 'subscriptionplugin.pro' },
  setup: async () => {
    clearCart(CUSTOMER);
  },
  teardown: async () => {
    clearCart(CUSTOMER);
  },
  chapters: [
    // 1 ------------------------------------------------------------------ the builder
    {
      kicker: 'Admin',
      title: 'Your checkout on one *canvas*',
      sub: 'ArraySubs > Checkout Builder',
      before: async () => { layout('pre'); },
      run: async (v) => {
        await as(v, 1);
        v.captionPos('br');
        await openBuilder(v);
        await v.say('The builder opens **full screen**, with your checkout on the canvas.');
        await v.focus(await unionRect(v, '.arraysubs-cb-step-tab, .arraysubs-cb-step-editor__add-step'), { label: 'Four steps', side: 'bottom' });
        await v.say('Each tab is one **step** shoppers go through.');
        await v.click('button.arraysubs-cb-step-tab__label:has-text("Your dog")');
        await v.say('This step asks about the customer\'s **dog**.');
        await v.zoom({ x: 372, y: 132, w: 760, h: 300 }, { scale: 1.6 });
        await v.focus('.arraysubs-cb-field-card:has-text("Which ingredients")', { label: 'Required, shown only on a match', side: 'bottom' });
        await v.unfocus();
        await v.zoomOut();
        await v.say('Pick from **28 elements**: layouts, 18 inputs and checkout parts.', { hold: 0.6 });
        await v.focus('.arraysubs-cb-builder-sidebar', { label: '28 elements', side: 'right', dim: true });
        await v.scrollTo('button.arraysubs-cb-palette-item:has-text("Date Range")', { dur: 1.4 });
        await v.hold(0.8);
        await v.scrollTo('button.arraysubs-cb-palette-item:has-text("Order Notes")', { dur: 1.0 });
        await v.hold(1.2, { note: 'others' });
        await v.say('Nothing reaches shoppers until you press **Save**.', { hold: 0.6 });
        await v.focus(await toolbarRect(v), { label: 'Undo, Reset, Discard, Save', side: 'left' });
        await v.unfocus();
      },
    },
    // 2 ------------------------------------------------------------------ drag in a field
    {
      kicker: 'Admin',
      title: 'Drag in a *question*',
      sub: 'Here: a photo upload for the box label.',
      before: async (v) => {
        layout('pre');
        await as(v, 1);
        await loadBuilder(v);
        await v.frame.locator('button.arraysubs-cb-step-tab__label:has-text("Your dog")').click();
        await v.settle();
        await v.frame.locator('button.arraysubs-cb-palette-item:has-text("File Upload")').evaluate((e) => e.scrollIntoView({ block: 'center' }));
        await v.settle();
      },
      run: async (v) => {
        v.captionPos('br');
        await v.cardOut();
        await v.hold(0.4);
        await v.focus('button.arraysubs-cb-palette-item:has-text("File Upload")', { label: 'File Upload', side: 'right' });
        await v.say('Want a photo of the dog? Drag in a **File Upload**.');
        const last = await v.eval(() => {
          const c = [...document.querySelectorAll('.arraysubs-cb-field-card')].pop().getBoundingClientRect();
          return { x: c.x + c.width / 2, y: c.bottom + 36 };
        });
        await drag(v, 'button.arraysubs-cb-palette-item:has-text("File Upload")', last);
        await v.say('Its settings open **right away**.');
        await v.type('.arraysubs-cb-settings-panel__body input >> nth=0', 'Photo of your dog', { cps: 16 });
        await v.type('input[placeholder="Enter Help Text"]', 'Optional. We print it on the box label.', { cps: 22 });
        await new Promise((r) => setTimeout(r, 900));
        await v.settle();
        await v.zoom({ x: 22, y: 128, w: 312, h: 312 }, { scale: 1.6 });
        await v.hold(1.4, { note: 'label-help' });
        await v.zoomOut();
        await v.focus('.arraysubs-cb-field-card:has-text("Photo of your dog")', { label: 'Canvas updates as you type', side: 'top' });
        await v.unfocus();
        await v.scrollTo('text=Allowed Types', { block: 'end' });
        const ts = await v.eval(() => {
          const leaf = (re) => [...document.querySelectorAll('.arraysubs-cb-settings-panel__body *')].find((e) => e.children.length === 0 && re.test(e.textContent.trim()));
          const a = leaf(/^type settings$/i).getBoundingClientRect();
          const inputs = [...document.querySelectorAll('.arraysubs-cb-settings-panel__body input')].map((e) => e.getBoundingClientRect()).filter((r) => r.top > a.top);
          const bottom = Math.max(...inputs.slice(0, 3).map((r) => r.bottom));
          return { x: 30, y: Math.max(a.top - 6, 122), w: 300, h: Math.min(bottom + 8, 785) - Math.max(a.top - 6, 122) };
        });
        await v.focus(ts, { label: 'Size, count and file types', side: 'right' });
        await v.say('Set the largest size, how many files and which **types**.');
        await v.unfocus();
        await v.click('button.button-primary:has-text("Save")', { wait: 600 });
        await v.say('**Saved.** The next checkout visit gets the new field.');
        await v.hold(0.6);
      },
    },
    // 3 ------------------------------------------------------------------ Show When
    {
      kicker: 'Admin',
      title: 'Ask only what *applies*',
      sub: 'Show When rules on any question.',
      before: async (v) => {
        layout('final');
        await as(v, 1);
        await loadBuilder(v);
        await v.frame.locator('button.arraysubs-cb-step-tab__label:has-text("Your dog")').click();
        await v.settle();
      },
      run: async (v) => {
        v.captionPos('br');
        await v.cardOut();
        await v.hold(0.5);
        await v.click('.arraysubs-cb-field-card:has-text("Which ingredients")');
        await v.say('This follow-up should only show for dogs with **allergies**.');
        await v.scrollTo('text=Show When', { block: 'start', dur: 1.2 });
        const rule = await v.eval(() => {
          const h = [...document.querySelectorAll('body *')].find((e) => e.children.length === 0 && e.textContent.trim() === 'Show When');
          const items = [...document.querySelectorAll('.arraysubs-cb-settings-panel__body .arraysubs-fb-select')].map((e) => e.getBoundingClientRect());
          const hr = h.getBoundingClientRect();
          const bottom = Math.max(...items.map((r) => r.bottom), hr.bottom + 300);
          return { x: hr.x - 6, y: hr.y - 4, w: 290, h: Math.min(bottom - hr.y + 10, 780 - hr.y) };
        });
        await v.zoom(rule, { scale: 1.55 });
        await v.focus(rule, { label: 'Allergies Is "yes"', side: 'right' });
        await v.say('Show it when **Any food allergies?** is **Yes**.');
        await v.unfocus();
        await v.zoomOut();
        const opSel = await v.eval(() => {
          const lab = [...document.querySelectorAll('.arraysubs-cb-settings-panel__body label')].find((e) => e.textContent.trim().startsWith('Operator'));
          const sel = lab && lab.parentElement.parentElement.querySelector('.arraysubs-fb-select');
          const r = (sel || lab).getBoundingClientRect();
          return { x: r.x, y: r.y, w: r.width, h: r.height };
        });
        await v.click(opSel, { dx: 0.4 });
        await v.hold(0.4);
        await v.say('Match with Is, Is Not, Contains, Is Empty or Is Not Empty.');
        await v.press('Escape');
        await v.say('Several rules can join with **AND** or **OR**.', { hold: 2.2 });
        await v.click('button:text-is("Back")');
        await v.focus('.arraysubs-cb-field-card:has-text("Which ingredients")', { label: 'Eye icon = conditional', side: 'bottom' });
        await v.unfocus();
      },
    },
    // 4 ------------------------------------------------------------------ settings
    {
      kicker: 'Settings',
      title: 'Match your *store*',
      sub: 'Colors, width and the step bar.',
      before: async (v) => {
        layout('final');
        await as(v, 1);
        await loadBuilder(v);
      },
      run: async (v) => {
        v.captionPos('br');
        await v.cardOut();
        await v.hold(0.5);
        await v.click('button.arraysubs-cb-builder-sidebar__tab:has-text("Settings")');
        await v.focus(await cardRect(v, 'Copy Custom Fields to Renewal Orders'), { label: 'Answers on renewals too', side: 'right' });
        await v.say('One switch copies the answers to every **renewal order**.');
        await v.focus(await cardRect(v, 'Color Scheme'), { label: 'Light, Dark or Custom', side: 'right' });
        await v.say('Pick Light, Dark or **your own colors**.');
        await v.unfocus();
        await v.scrollTo('text=Container Width', { block: 'start', dur: 1.1 });
        await v.focus(await cardRect(v, 'Container Width'), { label: 'Width, corners, spacing', side: 'right' });
        await v.say('Set the form **width**, corner roundness and spacing.');
        await v.unfocus();
        await v.scrollTo('text=Step Indicator', { block: 'center', dur: 1.0 });
        await v.focus(await cardRect(v, 'Navigation Position'), { label: 'Step bar', side: 'right' });
        await v.say('The **step bar** sits on top, down the left, or stays hidden.');
        await v.unfocus();
      },
    },
    // 5 ------------------------------------------------------------------ checkout steps 1-2
    {
      kicker: 'Checkout',
      title: 'Short steps, *checked*',
      sub: 'Willow orders dog food for her terrier.',
      before: async (v) => {
        layout('final');
        clearCart(CUSTOMER);
        await as(v, CUSTOMER);
        await v.frame.goto(`http://localhost:10013/?add-to-cart=${DOG_FOOD_MEDIUM}&quantity=1`, { waitUntil: 'domcontentloaded' });
        await v.settle();
      },
      run: async (v) => {
        v.captionPos('br');
        await v.goto('/checkout/', { hold: 0.5 });
        await v.say('The shopper gets **four short steps** instead of one long form.');
        await v.zoom('.arraysubs-step-nav', { scale: 1.45 });
        await v.focus('.arraysubs-step-nav', { label: 'Your details, Your dog, Delivery, Payment', side: 'bottom' });
        await v.unfocus();
        await v.zoomOut();
        await v.say('Step 1 holds the usual **billing details**.');
        await v.scrollBy(380, { hold: 0.6 });
        await v.click('.arraysubs-step-buttons__next >> visible=true');
        await v.scrollTo(0);
        await v.say('Step 2 asks the store\'s **own questions**.');
        await v.click('.arraysubs-step-buttons__next >> visible=true');
        await v.scrollTo('.arraysubs-step-validation-notice', { block: 'center' });
        await v.focus('.arraysubs-step-validation-notice', { label: 'Required answers first', side: 'top' });
        await v.say('**Next** checks the step, and names what is missing.');
        await v.unfocus();
        await v.scrollTo(0);
        await v.click('label.arraysubs-grid-select__card:has-text("Medium")');
        await v.click('label.arraysubs-grid-select__card:has-text("Yes")');
        await v.focus('#_arraysubs_cf_ingredients_to_avoid_field', { label: 'Appeared after "Yes"', side: 'top' });
        await v.say('Answer **Yes**, and the follow-up question appears.');
        v.poster();
        await v.unfocus();
        await v.click('label.arraysubs-grid-select__card:has-text("Chicken")');
        await v.click('label.arraysubs-grid-select__card:has-text("Wheat")');
        await v.scrollTo('#_arraysubs_cf_add_a_treat_field', { block: 'center' });
        await v.focus('#_arraysubs_cf_add_a_treat_field', { label: 'Product table', side: 'right' });
        await v.say('An **add-on** sits right inside checkout.');
        await v.click('tr.arraysubs-product-table__row:has-text("Peanut Butter Biscuits") button:has-text("Add to Cart")', { wait: 900 });
        await v.focus('tr.arraysubs-product-table__row:has-text("Peanut Butter Biscuits")', { label: 'Added to the order', side: 'top' });
        await v.unfocus();
        await v.scrollTo('#_arraysubs_cf_photo_of_your_dog_field', { block: 'center' });
        await v.say('And the new **photo upload**, before they pay.');
        v.page.once('filechooser', (fc) => fc.setFiles(PHOTO).catch(() => {}));
        await v.click('#_arraysubs_cf_photo_of_your_dog', { dx: 0.06, wait: 1500 });
        await v.focus('#_arraysubs_cf_photo_of_your_dog_field', { label: 'Stored privately', side: 'top' });
        await v.say('The file uploads now and only staff and the customer can open it.');
        await v.unfocus();
      },
    },
    // 6 ------------------------------------------------------------------ steps 3-4, order
    {
      kicker: 'Checkout',
      title: 'Delivery, then *payment*',
      sub: 'Placing the order.',
      before: async (v) => { await ensureStepTwoFilled(v); },
      run: async (v) => {
        v.captionPos('br');
        await v.cardOut();
        await v.say('Step 2 is done: size, allergies, a treat and a photo.', { hold: 1.8 });
        await v.click('.arraysubs-step-buttons__next >> visible=true');
        await v.scrollTo(0);
        await v.say('Step 3 is about **delivery**.');
        await v.click('label.arraysubs-grid-select__card:has-text("Wednesday")');
        await v.act(async (f) => { await f.locator('#_arraysubs_cf_first_delivery').fill('2026-12-09'); }, { settle: false });
        await v.focus('#_arraysubs_cf_first_delivery_field', { label: 'Dec 1 to Jan 31 only', side: 'bottom' });
        await v.unfocus();
        await v.type('#order_comments', 'Please leave it by the side gate.', { cps: 24 });
        await v.click('.arraysubs-step-buttons__next >> visible=true');
        await v.scrollTo(0);
        await v.say('Payment comes last, with the **coupon box** moved here.');
        await v.focus('#order_review table.shop_table', { label: 'Plan and add-on', side: 'right' });
        await v.unfocus();
        await v.scrollTo('#place_order', { block: 'center' });
        await v.say('The shopper places the order.');
        await v.click('#place_order', { nav: true, wait: 800 });
        const m = /order-received\/(\d+)/.exec(v.frame.url());
        st.orderId = m ? Number(m[1]) : 0;
        await v.hold(0.6);
        await v.scrollTo('.arraysubspro-checkout-builder-order-fields', { block: 'center' });
        await v.focus('.arraysubspro-checkout-builder-order-fields', { label: 'Answers on the order', side: 'right' });
        await v.say('Their answers show right on the **order confirmation**.');
        await v.unfocus();
      },
    },
    // 7 ------------------------------------------------------------------ admin outcome
    {
      kicker: 'Admin',
      title: 'Every answer, *kept*',
      sub: 'On the order and the subscription.',
      before: async () => {
        const r = latestOrder();
        st.orderId = r.order;
        st.subId = r.sub;
        if (!st.orderId || !st.subId) throw new Error(`no demo order for customer ${CUSTOMER}: ${JSON.stringify(r)}`);
      },
      run: async (v) => {
        await as(v, 1);
        v.captionPos('br');
        await v.goto(`/wp-admin/admin.php?page=wc-orders&action=edit&id=${st.orderId}`, { hold: 0.5 });
        await v.say(`Order #${st.orderId}, in WooCommerce.`);
        await v.scrollTo('.arraysubspro-checkout-builder-order-fields', { block: 'center' });
        await v.zoom('.arraysubspro-checkout-builder-order-fields', { scale: 1.4 });
        await v.focus('.arraysubspro-checkout-builder-order-fields', { label: 'Checkout Builder Fields', side: 'right' });
        await v.say('Every answer sits in a **Checkout Builder Fields** box.');
        await v.unfocus();
        await v.zoomOut();
        await v.goto(`/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/${st.subId}`, { hold: 0.5 });
        await v.say(`The answers are copied to **subscription #${st.subId}**.`);
        await v.scrollTo('text=Custom Fields', { block: 'start' });
        const panel = await v.eval(() => {
          const h = [...document.querySelectorAll('h2,h3,h4')].find((e) => e.textContent.trim() === 'Custom Fields');
          let p = h.parentElement;
          while (p && p.getBoundingClientRect().height < 300) p = p.parentElement;
          const r = p.getBoundingClientRect();
          return { x: r.x, y: Math.max(r.y, 60), w: r.width, h: Math.min(r.height, 760 - Math.max(r.y, 60)) };
        });
        await v.focus(panel, { label: 'Selections, dates and files', side: 'right' });
        await v.say('Choices, dates and the **photo** stay with every renewal.');
        await v.unfocus();
        await v.goto(`/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/edit/${st.subId}`, { hold: 0.5 });
        await v.scrollTo('text=Dog size', { block: 'center' }).catch(() => {});
        await v.say('Staff can **fix an answer** or swap the file here.');
        await v.hold(1.2, { note: 'edit-answers' });
      },
    },
    // 8 ------------------------------------------------------------------ My Account
    {
      kicker: 'My Account',
      title: 'Customers see their *answers*',
      before: async () => {
        if (!st.subId) { const r = latestOrder(); st.orderId = r.order; st.subId = r.sub; }
      },
      run: async (v) => {
        await as(v, CUSTOMER);
        v.captionPos('br');
        await v.goto(`/my-account/as-view-subscription/${st.subId}/`, { hold: 0.5 });
        await v.say('In **My Account**, the subscription page lists the answers too.');
        await v.scrollTo('.arraysubspro-checkout-builder-subscription-field', { block: 'start', offset: -260 });
        await v.focus(await unionRect(v, '.arraysubspro-checkout-builder-subscription-field'), { label: 'Saved at checkout', side: 'right' });
        await v.unfocus();
      },
    },
    // 9 ------------------------------------------------------------------ block checkout
    {
      kicker: 'Checkout',
      title: 'On the Checkout *block* too',
      sub: 'Same questions, block checkout.',
      before: async (v) => {
        layout('final');
        clearCart(CUSTOMER);
        await as(v, CUSTOMER);
        await v.frame.goto(`http://localhost:10013/?add-to-cart=${DOG_FOOD_MEDIUM}&quantity=1`, { waitUntil: 'domcontentloaded' });
        await v.settle();
      },
      run: async (v) => {
        v.captionPos('br');
        await v.goto('/checkout-2/', { hold: 0.6 });
        await v.say('Stores on the **Checkout block** get the same questions.');
        await v.scrollTo('.wp-block-woocommerce-checkout-additional-information-block', { block: 'start' });
        await v.focus('.wp-block-woocommerce-checkout-additional-information-block', { label: 'Your fields, one section', side: 'right' });
        await v.unfocus();
        await v.click('.wp-block-woocommerce-checkout-additional-information-block label:has-text("Yes")');
        await v.say('**Show When** rules work here as well.');
        await v.say('Steps, columns and design need the classic checkout.', { hold: 2.4 });
      },
    },
  ],
};
