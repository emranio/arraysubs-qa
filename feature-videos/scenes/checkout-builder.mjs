// Checkout Builder (ArraySubs Pro) feature tour.
// Story: build a dog-food subscription checkout in the builder (steps, a dragged-in upload,
// Show When, a trimmed address form, design) -> a customer goes through it -> the answers on
// the order, the subscription and My Account -> the same questions on the Checkout block.
// The demo layout lives in the private option `fv_cb_overlay`, which only this scene's browser
// sees (cookie fv_cb=1, mu-plugins/zz-fv-checkout-builder.php). The shared
// arraysubs_settings['checkout_builder'] is never written. See notes/checkout-builder.md.
import { wp, wpJson } from '../lib/director.mjs';

const HERE = '/Users/emran/Local Sites/wp-test2/app/public/wp-content/plugins/qa/feature-videos';
const LAYOUT = `${HERE}/notes/checkout-builder-layout.php`;
const PHOTO = `${HERE}/assets/checkout-builder/biscuit.jpg`;
const BUILDER = '/wp-admin/admin.php?page=arraysubs-mainadmin#/checkout-builder';
const CUSTOMER = 703; // Willow James (assigned block 678-738)
const DOG_FOOD_MEDIUM = 31816; // Grain-Free Dog Food, Medium bag (6 kg), $45 / month
// 12/12/2026 (a Saturday) reads the same day-first or month-first, so the recording browser's
// en-BD date format cannot be misread by US viewers.
const FIRST_DELIVERY = '2026-12-12';

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
async function unionRect(v, css, pad = 0) {
  return v.eval(({ sel, pad }) => {
    const rs = [...document.querySelectorAll(sel)].map((e) => e.getBoundingClientRect()).filter((r) => r.width > 0 && r.height > 0);
    if (!rs.length) throw new Error(`no match for ${sel}`);
    const x = Math.min(...rs.map((r) => r.left)), y = Math.min(...rs.map((r) => r.top));
    return { x: x - pad, y: y - pad, w: Math.max(...rs.map((r) => r.right)) - x + pad * 2, h: Math.max(...rs.map((r) => r.bottom)) - y + pad * 2 };
  }, { sel: css, pad });
}

/** Rect of the bordered card that holds a heading/label text (builder Settings tab). */
async function cardRect(v, text) {
  return v.eval((t) => {
    const el = [...document.querySelectorAll('body *')].find((e) => e.children.length === 0 && e.textContent.trim().toLowerCase() === t.toLowerCase() && e.getBoundingClientRect().width > 0);
    if (!el) throw new Error(`no ${t}`);
    let p = el.parentElement;
    while (p && p !== document.body && getComputedStyle(p).borderTopWidth === '0px') p = p.parentElement;
    const r = p.getBoundingClientRect();
    // keep the ring below the sticky Settings / Elements tab bar
    const tabs = document.querySelector('.arraysubs-cb-builder-sidebar__tab');
    const top = Math.max(r.y, tabs ? tabs.getBoundingClientRect().bottom + 8 : 0);
    return { x: r.x, y: top, w: r.width, h: Math.min(r.bottom, 800) - top };
  }, text);
}

/** Park the cursor in an empty part of the screen (stage coords), whatever the zoom. */
async function park(v, sx = 1330, sy = 470) {
  const { s, x, y } = v.cam;
  await v.moveTo({ x: (sx - x) / s, y: (sy - y) / s, w: 1, h: 1 }, { dur: 0.45 });
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

/** Field-settings rect from a label (Field, Operator, Value, Logic) down to the bottom of `toSel`. */
async function panelRect(v, fromLabel, toSel) {
  return v.eval(({ fromLabel, toSel }) => {
    const body = document.querySelector('.arraysubs-cb-settings-panel__body');
    const lab = [...body.querySelectorAll('label')].find((e) => e.textContent.trim().replace(/\*$/, '').trim() === fromLabel);
    const lr = lab.getBoundingClientRect();
    const to = [...body.querySelectorAll(toSel)].map((e) => e.getBoundingClientRect()).filter((r) => r.top >= lr.top - 4).sort((a, b) => a.top - b.top)[0];
    return { x: lr.x - 8, y: lr.y - 8, w: lr.width + 16, h: to.bottom - lr.y + 16 };
  }, { fromLabel, toSel });
}

/** Rect of the field-settings select that follows a label (Field, Operator, Logic). */
async function selRect(v, label) {
  return v.eval((t) => {
    const lab = [...document.querySelectorAll('.arraysubs-cb-settings-panel__body label')].find((e) => e.textContent.trim().replace(/\*$/, '').trim() === t);
    const lr = lab.getBoundingClientRect();
    const sel = [...document.querySelectorAll('.arraysubs-cb-settings-panel__body .arraysubs-fb-select')]
      .map((e) => e.getBoundingClientRect()).filter((r) => r.top >= lr.bottom - 4).sort((a, b) => a.top - b.top)[0];
    return { x: sel.x, y: sel.y, w: sel.width, h: sel.height };
  }, label);
}

/** Close an open custom select by clicking the "Show When" heading (a harmless spot). */
async function closeSelect(v) {
  const r = await v.eval(() => {
    const e = [...document.querySelectorAll('.arraysubs-cb-settings-panel__body *')].find((x) => x.children.length === 0 && x.textContent.trim() === 'Show When');
    const b = e.getBoundingClientRect();
    return { x: b.x, y: b.y, w: b.width, h: b.height };
  });
  await v.click(r, { dx: 0.9, hold: 0.2 });
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
  if (!ready) {
    layout('final');
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
  // Open on the answered follow-up and the added treat (the earlier "required field" notice
  // further down stays on screen until the next Next, so keep it out of this shot).
  await f.locator('#_arraysubs_cf_ingredients_to_avoid_field').evaluate((e) => {
    const r = e.getBoundingClientRect();
    window.scrollBy(0, r.top - 110);
  });
  await new Promise((r) => setTimeout(r, 300));
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
    // 1 ------------------------------------------------------------------ the builder, steps
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
        await v.hover('.arraysubs-cb-step-tab:has-text("Delivery")', { hold: 0.3, dx: 0.97, dy: 0.8 });
        await v.say('Each tab is one **step**. Rename, reorder or add more.', { hold: 0.4 });
        await v.focus(await unionRect(v, '.arraysubs-cb-step-tab, .arraysubs-cb-step-editor__add-step'), { label: 'Four named steps', side: 'bottom', hold: 1.8 });
        await v.unfocus();
        await v.click('button.arraysubs-cb-step-tab__label:has-text("Payment")');
        await park(v, 1300, 560);
        await v.say('Shoppers pay on the step that holds **Order Info and Payment**.', { hold: 0.5 });
        await v.focus('.arraysubs-cb-field-card:has-text("Order Info and Payment")', { label: 'Place order shows here', side: 'bottom' });
        await v.unfocus();
        await v.say('Pick from **28 elements**: layouts, 18 inputs and checkout parts.', { hold: 0.5 });
        await v.focus('.arraysubs-cb-builder-sidebar', { label: '28 elements', side: 'right', dim: true, hold: 1.6 });
        await v.scrollTo('button.arraysubs-cb-palette-item:has-text("Date Range")', { dur: 1.2 });
        await v.hold(0.5);
        await v.scrollTo('button.arraysubs-cb-palette-item:has-text("Order Notes")', { dur: 0.9 });
        await v.hold(0.9, { note: 'others' });
        await v.say('Nothing reaches shoppers until you press **Save**.', { hold: 0.5 });
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
        await v.say('Want a photo of the dog? Drag in a **File Upload**.', { hold: 0.5 });
        await v.focus('button.arraysubs-cb-palette-item:has-text("File Upload")', { label: 'File Upload', side: 'right' });
        const last = await v.eval(() => {
          const c = [...document.querySelectorAll('.arraysubs-cb-field-card')].pop().getBoundingClientRect();
          return { x: c.x + c.width / 2, y: c.bottom + 36 };
        });
        await drag(v, 'button.arraysubs-cb-palette-item:has-text("File Upload")', last);
        await v.say('Its settings open **right away**.', { hold: 0.9 });
        await v.type('.arraysubs-cb-settings-panel__body input >> nth=0', 'Photo of your dog', { cps: 16 });
        await v.say('Name it and add **help text** for the shopper.', { hold: 0.3 });
        await v.type('input[placeholder="Enter Help Text"]', 'Optional. We print it on the box label.', { cps: 22 });
        await new Promise((r) => setTimeout(r, 900));
        await v.settle();
        await v.focus('.arraysubs-cb-field-card:has-text("Photo of your dog")', { label: 'Canvas updates as you type', side: 'top' });
        await v.unfocus();
        await v.scrollTo('text=Allowed Types', { block: 'end' });
        await v.say('Set the largest size, how many files and which **types**.', { hold: 0.3 });
        await v.type('input[placeholder="Enter Max Files"]', '2', { cps: 8, hold: 0.2 });
        await v.type('input[placeholder="Enter Allowed Types"]', 'jpg,png', { cps: 14, hold: 0.2 });
        const ts = await v.eval(() => {
          const leaf = (re) => [...document.querySelectorAll('.arraysubs-cb-settings-panel__body *')].find((e) => e.children.length === 0 && re.test(e.textContent.trim()));
          const a = leaf(/^type settings$/i).getBoundingClientRect();
          const inputs = [...document.querySelectorAll('.arraysubs-cb-settings-panel__body input')].map((e) => e.getBoundingClientRect()).filter((r) => r.top > a.top);
          const help = leaf(/^comma-separated, e\.g\./i);
          const bottom = help ? help.getBoundingClientRect().bottom : Math.max(...inputs.slice(0, 3).map((r) => r.bottom));
          return { x: 30, y: Math.max(a.top - 6, 122), w: 300, h: Math.min(bottom + 6, 785) - Math.max(a.top - 6, 122) };
        });
        await v.focus(ts, { label: 'Up to 2 files, jpg or png', side: 'bottom' });
        await v.unfocus();
        await v.say(null);
        v.captionPos('bl');
        await v.say('Press **Save**, and the next checkout visit gets the new field.', { hold: 1.0 });
        await v.click('button.button-primary:has-text("Save")', { settle: false, wait: 450, hold: 0 });
        await v.hold(2.0, { note: 'saved-toast' });
        await v.settle();
      },
    },
    // 3 ------------------------------------------------------------------ Show When
    {
      kicker: 'Admin',
      title: 'Ask only what *applies*',
      sub: 'Show When rules on any question.',
      before: async (v) => {
        layout('noaddr');
        await as(v, 1);
        await loadBuilder(v);
        await v.frame.locator('button.arraysubs-cb-step-tab__label:has-text("Your dog")').click();
        await v.settle();
      },
      run: async (v) => {
        v.captionPos('br');
        await v.cardOut();
        await v.hold(0.4);
        await v.click('.arraysubs-cb-field-card:has-text("Ingredients to avoid")');
        await park(v, 1300, 600);
        await v.say('This follow-up should only show for dogs with **allergies**.');
        await v.scrollTo('text=Show When', { block: 'start', dur: 1.1 });
        const rule = await panelRect(v, 'Field', 'input');
        // No zoom here: it would magnify the repeater's raw "Item 1 - <field key>" header.
        await park(v, 1300, 600);
        await v.say('Show it when **Food allergies** is **Yes**.', { hold: 0.4 });
        await v.focus(rule, { label: 'Food allergies Is "yes"', side: 'right', dim: true, hold: 2.2 });
        await v.unfocus();
        await v.click(await selRect(v, 'Field'), { dx: 0.4 });
        await v.say('A rule can watch **any of your own questions**.');
        await closeSelect(v);
        await v.click(await selRect(v, 'Operator'), { dx: 0.4 });
        await v.say('Match with Is, Is Not, Contains, Is Empty or Is Not Empty.');
        await closeSelect(v);
        await park(v, 1300, 600);
        await v.say('Add more rules and join them with **AND** or **OR**.', { hold: 0.5 });
        await v.focus(await panelRect(v, 'Logic', 'button.arraysubs-fb-button-primary'), { label: 'AND / OR, more rules', side: 'right' });
        await v.unfocus();
        await v.click('button:text-is("Back")');
        await park(v, 1300, 640);
        await v.say('On the canvas, an **eye icon** marks a conditional question.', { hold: 0.3 });
        await v.focus('.arraysubs-cb-field-card:has-text("Ingredients to avoid")', { label: 'Eye icon = conditional', side: 'bottom' });
        await v.unfocus();
      },
    },
    // 4 ------------------------------------------------------------------ address + design
    {
      kicker: 'Admin',
      title: 'Match your *store*',
      sub: 'Trim the address form, then set colors, width and the step bar.',
      before: async (v) => {
        layout('noaddr');
        await as(v, 1);
        await loadBuilder(v);
      },
      run: async (v) => {
        v.captionPos('br');
        await v.cardOut();
        await v.hold(0.4);
        await v.click('.arraysubs-cb-field-card:has-text("Billing Address")');
        await park(v, 1300, 560);
        await v.say('Trim the WooCommerce **address form** right here.', { hold: 0.8 });
        await v.type('.arraysubs-cb-settings-panel__body textarea', 'US,CA', { cps: 9, hold: 0.2 });
        await v.say('Sell only where you deliver: **US and Canada**.', { hold: 0.4 });
        const countries = await v.eval(() => {
          const lab = [...document.querySelectorAll('.arraysubs-cb-settings-panel__body label')].find((e) => e.textContent.trim() === 'Limit Countries').getBoundingClientRect();
          const ta = document.querySelector('.arraysubs-cb-settings-panel__body textarea').getBoundingClientRect();
          const help = [...document.querySelectorAll('.arraysubs-cb-settings-panel__body *')].find((e) => e.children.length === 0 && /^Comma-separated ISO/.test(e.textContent.trim()));
          const bottom = help ? help.getBoundingClientRect().bottom : ta.bottom;
          return { x: ta.x - 6, y: lab.y - 6, w: ta.width + 12, h: bottom - lab.y + 10 };
        });
        await v.focus(countries, { label: 'Allowed countries', side: 'right' });
        await v.unfocus();
        await v.scrollTo('.arraysubs-cb-group-fields__item:has-text("billing_phone")', { block: 'center' });
        await v.say('Switch off a field you do not need, or **rename** one.', { hold: 0.3 });
        await v.click('button.arraysubs-cb-group-fields__switch[aria-label="Show Phone"]', { settle: false, wait: 250 });
        await v.type('input[aria-label="Label for billing_email"]', 'Email for delivery updates', { cps: 24, hold: 0.2 });
        await park(v, 1300, 560);
        await v.focus(await unionRect(v, '.arraysubs-cb-group-fields__item:has(input[aria-label="Label for billing_phone"]), .arraysubs-cb-group-fields__item:has(input[aria-label="Label for billing_email"])', 2), { label: 'Phone off, email renamed', side: 'right' });
        await v.unfocus();
        await v.click('button.arraysubs-cb-settings-panel__back');
        await v.click('button.arraysubs-cb-builder-sidebar__tab:has-text("Settings")');
        await park(v, 1300, 600);
        await v.say('One switch copies the answers to every **renewal order**.', { hold: 0.5 });
        const gen = await cardRect(v, 'Copy Custom Fields to Renewal Orders');
        await v.zoom(gen, { scale: 1.7 });
        await v.focus(gen, { label: 'Answers on renewals too', side: 'bottom' });
        await v.unfocus();
        await v.zoomOut({ dur: 0.6 });
        await v.say('Pick Light, Dark or **your own colors**.', { hold: 0.4 });
        const col = await cardRect(v, 'Color Scheme');
        const colTop = { x: col.x, y: col.y, w: col.w, h: Math.min(col.h, 300) };
        await v.focus(colTop, { label: 'Light, Dark or Custom', side: 'right' });
        await v.unfocus();
        await v.scrollTo('text=Container Width', { block: 'start', offset: -60, dur: 1.0 });
        await v.say('Set the **width**, corners and spacing, then the step bar.', { hold: 0.4 });
        await v.focus(await cardRect(v, 'Container Width'), { label: 'Width, corners, spacing', side: 'right', hold: 1.5 });
        await v.scrollTo('text=Navigation Position', { block: 'start', offset: -60, dur: 0.9 });
        await v.focus(await cardRect(v, 'Navigation Position'), { label: 'Step bar: top, left or hidden', side: 'right', hold: 1.7 });
        await v.unfocus();
        await v.say(null);
        v.captionPos('bl');
        await v.say('**Save**, and the next checkout visit gets it all.', { hold: 0.9 });
        await v.click('button.button-primary:has-text("Save")', { settle: false, wait: 450, hold: 0 });
        await v.hold(1.8, { note: 'saved-toast' });
        await v.settle();
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
        // The step script scrolls the step bar to the top edge on load; start at the page top
        // instead, so the store header and the whole step bar are in view.
        await v.act(async (f) => {
          await f.goto('http://localhost:10013/checkout/', { waitUntil: 'domcontentloaded' });
          await f.locator('.arraysubs-step-nav').waitFor({ state: 'visible', timeout: 30000 });
          await f.waitForLoadState('networkidle').catch(() => {});
          await new Promise((r) => setTimeout(r, 700));
          await f.evaluate(() => window.scrollTo(0, 0));
        });
        await v.act(async (f) => { await f.evaluate(() => window.scrollTo(0, 0)); }, { settle: false });
        await v.hold(0.3);
        await v.say('The shopper gets **four short steps** instead of one long form.', { hold: 0.6 });
        const nav = await unionRect(v, '.arraysubs-step-nav');
        const navPad = { x: nav.x - 30, y: nav.y - 40, w: nav.w + 60, h: nav.h + 80 };
        await v.zoom(navPad, { scale: 1.35 });
        await v.focus({ x: nav.x - 6, y: nav.y - 10, w: nav.w + 12, h: nav.h + 20 }, { label: 'Your details, Your dog, Delivery, Payment', side: 'bottom' });
        await v.unfocus();
        await v.zoomOut();
        await v.say('Step 1 holds the **trimmed** billing form.', { hold: 0.4 });
        await v.scrollTo(240, { dur: 0.8 });
        await v.click('#billing_country_field .select2-selection', { dx: 0.3 });
        await park(v, 1300, 620);
        await v.focus(await unionRect(v, '#billing_country_field .select2-selection, .select2-dropdown'), { label: 'Only US and Canada', side: 'right' });
        await v.press('Escape');
        await v.scrollTo('#billing_email_field', { block: 'center' });
        await v.say('No phone field, and the email label is **renamed**.', { hold: 0.4 });
        await v.focus('#billing_email_field', { label: 'Renamed in the builder', side: 'right' });
        await v.unfocus();
        await v.say(null);
        await v.click('.arraysubs-step-buttons__next >> visible=true');
        await v.scrollTo(0);
        await v.say('Step 2 asks the store\'s **own questions**.', { hold: 0.5 });
        const qa = await v.eval(() => { const a = document.querySelector('#_arraysubs_cf_dog_size_field').getBoundingClientRect(); const b = document.querySelector('#_arraysubs_cf_food_allergies_field').getBoundingClientRect(); return { x: a.x - 12, y: a.y - 12, w: a.width + 24, h: b.bottom - a.y + 24 }; });
        await v.zoom(qa, { scale: 1.3 });
        await v.click('label.arraysubs-grid-select__card:has-text("Medium")');
        await v.click('label.arraysubs-grid-select__card:has-text("Yes")');
        const qb = await v.eval(() => { const a = document.querySelector('#_arraysubs_cf_food_allergies_field').getBoundingClientRect(); const b = document.querySelector('#_arraysubs_cf_ingredients_to_avoid_field').getBoundingClientRect(); return { x: a.x - 12, y: a.y - 12, w: a.width + 24, h: b.bottom - a.y + 24 }; });
        await v.zoom(qb, { scale: 1.3, dur: 0.7 });
        await park(v, 1350, 330);
        await v.say('Answer **Yes**, and the follow-up question appears.', { hold: 0.5 });
        await v.focus('#_arraysubs_cf_ingredients_to_avoid_field', { label: 'Appeared after "Yes"', side: 'top' });
        v.poster();
        await v.unfocus();
        await v.zoomOut();
        await v.scrollTo('#_arraysubs_cf_add_a_treat_field', { block: 'center' });
        await v.say('An **add-on** product table sits right inside checkout.', { hold: 0.6 });
        await v.click('tr.arraysubs-product-table__row:has-text("Peanut Butter Biscuits") button:has-text("Add to Cart")', { wait: 900 });
        await park(v, 1330, 560);
        await v.focus('tr.arraysubs-product-table__row:has-text("Peanut Butter Biscuits")', { label: 'Added to the order', side: 'right' });
        await v.unfocus();
        await v.scrollTo('#_arraysubs_cf_photo_of_your_dog_field', { block: 'center' });
        await v.say('And the new **photo upload**, before they pay.', { hold: 0.5 });
        v.page.once('filechooser', (fc) => fc.setFiles(PHOTO).catch(() => {}));
        await v.click('#_arraysubs_cf_photo_of_your_dog', { dx: 0.06, wait: 1500 });
        await park(v, 1330, 560);
        await v.say('Uploaded files stay **private** to staff and the customer.', { hold: 0.4 });
        await v.focus('#_arraysubs_cf_photo_of_your_dog_field', { label: 'biscuit.jpg uploaded', side: 'right' });
        await v.unfocus();
        await v.say(null);
        await v.click('.arraysubs-step-buttons__next >> visible=true');
        await v.scrollTo('.arraysubs-step-validation-notice', { block: 'center' });
        await park(v, 1330, 560);
        await v.say('**Next** checks the step first, and names what is missing.', { hold: 0.4 });
        await v.focus('.arraysubs-step-validation-notice', { label: 'Follow-up still empty', side: 'right' });
        await v.unfocus();
        await v.scrollTo('#_arraysubs_cf_ingredients_to_avoid_field', { block: 'center' });
        await v.click('label.arraysubs-grid-select__card:has-text("Chicken")', { hold: 0.1 });
        await v.click('label.arraysubs-grid-select__card:has-text("Wheat")', { hold: 0.1 });
        await park(v, 1330, 560);
        await v.hold(0.3, { note: 'answered' });
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
        await v.say('Step 2 is done: size, allergies, a treat and a photo.', { hold: 1.0 });
        await v.say(null);
        await v.click('.arraysubs-step-buttons__next >> visible=true');
        await v.scrollTo(0);
        await v.say('Step 3 is about **delivery**.', { hold: 0.6 });
        await v.click('label.arraysubs-grid-select__card:has-text("Saturday")');
        await v.act(async (f) => {
          await f.locator('#_arraysubs_cf_first_delivery').fill(FIRST_DELIVERY);
          await f.evaluate(() => document.activeElement && document.activeElement.blur());
        }, { settle: false });
        await park(v, 1330, 470);
        await v.say('Dates can have an earliest and latest day.', { hold: 0.4 });
        await v.focus('#_arraysubs_cf_first_delivery_field', { label: 'Dec 1 to Jan 31 only', side: 'right' });
        await v.unfocus();
        await v.type('#order_comments', 'Please leave it by the side gate.', { cps: 24 });
        await v.say(null);
        await v.click('.arraysubs-step-buttons__next >> visible=true');
        await v.scrollTo(0);
        await park(v, 1330, 470);
        await v.say('Payment comes last, with the **coupon box** moved here.', { hold: 0.4 });
        await v.focus('.woocommerce-form-coupon-toggle', { label: 'Coupon box', side: 'right' });
        await v.scrollTo('#order_review table.shop_table tbody tr.cart_item', { block: 'start', offset: -40, dur: 0.6, hold: 0.1 });
        await v.focus(await unionRect(v, '#order_review table.shop_table tbody tr.cart_item', 4), { label: 'Plan and add-on', side: 'right' });
        await v.unfocus();
        await v.scrollTo('#place_order', { block: 'center' });
        await v.say('The shopper places the order.', { hold: 0.8 });
        await v.click('#place_order', { nav: true, wait: 800 });
        const m = /order-received\/(\d+)/.exec(v.frame.url());
        st.orderId = m ? Number(m[1]) : 0;
        await v.hold(0.5);
        await v.scrollTo('.arraysubspro-checkout-builder-order-fields', { block: 'center' });
        await park(v, 1330, 300);
        await v.say('Their answers show right on the **order confirmation**.', { hold: 0.4 });
        await v.focus('.arraysubspro-checkout-builder-order-fields', { label: 'Answers on the order', side: 'right' });
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
        // The local store records the localhost IP "::1"; clear it so the order header stays clean.
        wpJson(`$o = wc_get_order(${st.orderId}); $o->set_customer_ip_address(""); $o->save(); echo json_encode(["ok" => 1]);`);
      },
      run: async (v) => {
        await as(v, 1);
        v.captionPos('br');
        await v.goto(`/wp-admin/admin.php?page=wc-orders&action=edit&id=${st.orderId}`, { hold: 0.4 });
        await v.say('Each order keeps the shopper\'s **answers**.', { hold: 0.7 });
        await v.scrollTo('.arraysubspro-checkout-builder-order-fields', { block: 'center' });
        await v.zoom('.arraysubspro-checkout-builder-order-fields', { pad: 40 });
        await v.say('They sit in a **Checkout Builder Fields** box.', { hold: 0.4 });
        await v.focus('.arraysubspro-checkout-builder-order-fields', { label: 'Checkout Builder Fields', side: 'right' });
        await v.unfocus();
        await v.zoomOut();
        await v.goto(`/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/${st.subId}`, { hold: 0.4 });
        await v.say(`The answers are copied to **subscription #${st.subId}**.`, { hold: 1.0 });
        await v.scrollTo('text=Custom Fields', { block: 'start', offset: -110 });
        const panel = await v.eval(() => {
          const h = [...document.querySelectorAll('h2,h3,h4')].find((e) => e.textContent.trim() === 'Custom Fields');
          let p = h.parentElement;
          while (p && p.getBoundingClientRect().height < 300) p = p.parentElement;
          const r = p.getBoundingClientRect();
          const top = Math.max(r.y, 122);
          return { x: r.x, y: top, w: r.width, h: Math.min(r.bottom, 790) - top };
        });
        await v.say('Choices, dates and the **photo**, grouped by kind.', { hold: 0.4 });
        await v.focus(panel, { label: 'Selections, dates and files', side: 'right' });
        await v.unfocus();
        // Bottom-centre caption from the first frame: it also keeps the sticky save bar's
        // "Updated at: Not recorded yet" text off camera.
        await v.say(null);
        v.captionPos('bc');
        await v.goto(`/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/edit/${st.subId}`, { hold: 0 });
        await v.say('Staff can **fix an answer** or swap the photo here.', { hold: 0.3 });
        await v.scrollTo('text=Custom Fields', { block: 'start', offset: -110 }).catch(() => {});
        const daySel = v.frame.locator('select.arraysubs-fb-input').filter({ has: v.frame.locator('option', { hasText: 'Saturday' }) }).first();
        await v.select(daySel, { label: 'Friday' });
        await park(v, 1380, 140);
        const dayRect = await v.eval(() => {
          const lab = [...document.querySelectorAll('label')].find((e) => e.textContent.trim().replace(/\*$/, '').trim() === 'Delivery day');
          const sel = [...document.querySelectorAll('select')].find((e) => [...e.options].some((o) => o.textContent.trim() === 'Saturday'));
          const a = lab.getBoundingClientRect(), b = sel.getBoundingClientRect();
          return { x: b.x - 6, y: a.y - 6, w: b.width + 12, h: b.bottom - a.y + 12 };
        });
        await v.focus(dayRect, { label: 'Answer changed', side: 'bottom', hold: 1.5 });
        await v.scrollBy(230, { dur: 0.7 });
        const files = await v.eval(() => {
          const h = [...document.querySelectorAll('h4')].find((e) => e.textContent.trim() === 'Files');
          const btn = [...document.querySelectorAll('button')].find((b) => /^(choose files|replace file)$/i.test(b.textContent.trim()));
          const help = [...document.querySelectorAll('p, span, div')].find((e) => e.children.length === 0 && /^Save changes to apply your file selection/.test(e.textContent.trim()));
          const a = h.getBoundingClientRect();
          const bottom = (help || btn).getBoundingClientRect().bottom;
          return { x: a.x - 10, y: a.y - 8, w: 640, h: bottom - a.y + 14 };
        });
        await v.focus(files, { label: 'Remove or add a photo', side: 'right', hold: 1.6 });
        await v.unfocus();
        await v.hold(0.4, { note: 'edit-answers' });
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
        await v.goto(`/my-account/view-order/${st.orderId}/`, { hold: 0.4 });
        await v.say('In **My Account**, the order lists the answers.', { hold: 0.4 });
        await v.scrollTo('.arraysubspro-checkout-builder-order-fields', { block: 'center' });
        await v.focus('.arraysubspro-checkout-builder-order-fields', { label: 'On the order', side: 'right' });
        await v.unfocus();
        await v.goto(`/my-account/as-view-subscription/${st.subId}/`, { hold: 0.4 });
        await v.say('The **subscription page** shows them too.', { hold: 0.4 });
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
        await v.goto('/checkout-2/', { hold: 0.5 });
        await v.say('Stores on the **Checkout block** get the same questions.', { hold: 0.7 });
        await v.scrollTo('.wp-block-woocommerce-checkout-additional-information-block', { block: 'start' });
        v.captionPos('tr');
        await v.zoom('.wp-block-woocommerce-checkout-additional-information-block', { scale: 1.3 });
        await v.focus('.wp-block-woocommerce-checkout-additional-information-block', { label: 'Your fields, one section', side: 'left' });
        await v.unfocus();
        await v.click('.wp-block-woocommerce-checkout-additional-information-block label:has-text("Yes")');
        await park(v, 1000, 480);
        await v.say('**Show When** rules work here as well.', { hold: 0.4 });
        const follow = await v.eval(() => {
          const blk = document.querySelector('.wp-block-woocommerce-checkout-additional-information-block');
          const lab = [...blk.querySelectorAll('*')].find((e) => e.children.length === 0 && e.textContent.trim().startsWith('Ingredients to avoid'));
          if (!lab) return null;
          let p = lab.parentElement;
          while (p && p !== blk && p.getBoundingClientRect().height < 60) p = p.parentElement;
          const r = p.getBoundingClientRect();
          return r.height < 220 ? { x: r.x, y: r.y, w: r.width, h: r.height } : null;
        });
        if (follow) {
          await v.focus(follow, { label: 'Appeared after "Yes"', side: 'right' });
          await v.unfocus();
        }
        await v.zoomOut();
        await v.say(null);
        v.captionPos('br');
        await v.scrollBy(140, { dur: 0.6 });
        await v.say('Steps, columns and design need the classic checkout.', { hold: 1.7 });
      },
    },
  ],
};
