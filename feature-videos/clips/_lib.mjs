// Shared helpers for clip scenes: temporary store changes with exact restore.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = path.dirname(fileURLToPath(import.meta.url));

/** Set arraysubs_settings[a][b] = value (JSON value). Returns the previous value (JSON). */
export function setSetting(wp, a, b, value) {
  const php = `$s=get_option('arraysubs_settings'); $old=$s['${a}']['${b}'] ?? null; $s['${a}']['${b}']=json_decode('${JSON.stringify(value).replace(/'/g, "\\'")}', true); update_option('arraysubs_settings',$s); echo json_encode($old);`;
  const out = wp(['eval', php]);
  return JSON.parse(out.slice(out.search(/[\[{"\dtfn-]/)));
}

export function settingsMd5(wp) {
  return wp(['eval', "echo md5(serialize(get_option('arraysubs_settings')));"]).trim();
}

/** Display-only renames for the recording browser (fv_clean cookie). */
export function cleanMap(name, map) {
  const file = path.join(HERE, '..', 'clean-map.d', `${name}.json`);
  if (map) fs.writeFileSync(file, JSON.stringify({ map }, null, 2));
  else fs.rmSync(file, { force: true });
}

/**
 * Selector for the card/panel that holds a heading (tagged with data-fv-card), after scrolling it into view.
 * With `until` (another heading's text), the card is the child of both headings' common
 * ancestor that holds the first one: robust when card wrappers have no telling class.
 */
export async function cardRect(v, tag, text, { scroll = true, block = 'center', until = null } = {}) {
  const head = v.frame.locator(tag, { hasText: text }).first();
  if (scroll) await v.scrollTo(head, { block });
  const sel = await head.evaluate((h, untilText) => {
    let card = null;
    if (untilText) {
      const other = [...document.querySelectorAll('h1,h2,h3,h4')].find((e) => e.innerText.trim().startsWith(untilText));
      if (other) {
        let a = h;
        while (a && !a.contains(other)) a = a.parentElement;
        if (a) card = [...a.children].find((c) => c.contains(h));
      }
    }
    if (!card) card = h.closest('[class*="__hero"], [class*="card"]:not([class*="card-title"]), [class*="__panel"]:not([class*="panel-title"]), .postbox, section') || h.parentElement;
    document.querySelectorAll('[data-fv-card]').forEach((e) => e.removeAttribute('data-fv-card'));
    card.setAttribute('data-fv-card', '1');
    return '[data-fv-card="1"]';
  }, until);
  await new Promise((r) => setTimeout(r, 600));
  return sel;
}

/** Union rect of every element matching a CSS selector (iframe viewport px). */
export async function unionRect(v, css) {
  return v.eval((sel) => {
    const rs = [...document.querySelectorAll(sel)].map((e) => e.getBoundingClientRect()).filter((r) => r.width && r.height);
    const l = Math.min(...rs.map((r) => r.left)), t = Math.min(...rs.map((r) => r.top));
    const r = Math.max(...rs.map((x) => x.right)), b = Math.max(...rs.map((x) => x.bottom));
    return { x: l, y: t, w: r - l, h: b - t };
  }, css);
}

/** Inject CSS into the store frame (display only, gone on next navigation). */
export async function frameCss(v, css) {
  await v.frame.addStyleTag({ content: css });
}

/** Empty a customer's saved cart and WooCommerce session (so a clip starts clean). */
export function clearCart(wp, userId) {
  wp(['eval', `global $wpdb; $u=(int)${Number(userId)}; delete_user_meta($u, '_woocommerce_persistent_cart_' . get_current_blog_id()); $wpdb->delete($wpdb->prefix . 'woocommerce_sessions', ['session_key' => (string) $u]); echo 'ok';`]);
}

/** AJAX add-to-cart from inside the store frame (no page navigation). */
export async function addToCart(v, ids) {
  for (const id of ids) {
    await v.frame.evaluate(async (pid) => {
      await fetch('/?wc-ajax=add_to_cart', { method: 'POST', body: new URLSearchParams({ product_id: String(pid), quantity: '1' }) });
    }, id);
  }
}
