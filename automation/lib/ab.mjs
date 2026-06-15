/**
 * ab.mjs — agent-browser test helper for ArraySubs QA automation.
 *
 * Drives Chrome through Vercel's agent-browser CLI (CDP). agent-browser has no
 * test runner, so this module provides the thin pieces a runner needs: an
 * isolated-session Page wrapper, a tiny `expect`, and console/network readers.
 *
 * Install once: `npm i -g agent-browser && agent-browser install`.
 */
import { spawnSync } from "node:child_process";
import process from "node:process";

const BIN = process.env.AGENT_BROWSER_BIN || "agent-browser";
export const BASE_URL = (process.env.QA_BASE_URL || "https://mirror-help.arrayhash.com").replace(/\/+$/, "");
let SEQ = 0;

function ab(session, args, { input } = {}) {
  const res = spawnSync(BIN, ["--session", session, ...args], {
    encoding: "utf8",
    input,
    maxBuffer: 64 * 1024 * 1024,
    env: { ...process.env },
  });
  if (res.error && res.error.code === "ENOENT") {
    throw new Error("agent-browser CLI not found. Install: npm i -g agent-browser && agent-browser install");
  }
  return {
    status: res.status,
    stdout: (res.stdout || "").trim(),
    stderr: (res.stderr || "").trim(),
    error: res.error,
  };
}

function tryJson(s) {
  try {
    return JSON.parse(s);
  } catch {
    return null;
  }
}

export class Page {
  constructor(session) {
    this.session = session;
  }
  run(args, opts) {
    return ab(this.session, args, opts);
  }

  launch() {
    return this.run(["open"]);
  }
  goto(url) {
    const u = /^https?:\/\//i.test(url) ? url : `${BASE_URL}${url.startsWith("/") ? "" : "/"}${url}`;
    const r = this.run(["open", u]);
    if (r.error) throw new Error(`open ${u} failed: ${r.stderr || r.error.message}`);
    return r;
  }
  setViewport(w, h, scale = 1) {
    return this.run(["set", "viewport", String(w), String(h), String(scale)]);
  }
  url() {
    return this.run(["get", "url"]).stdout;
  }

  fill(sel, value) {
    return this.run(["fill", sel, String(value)]);
  }
  click(sel) {
    return this.run(["click", sel]);
  }
  clickText(text, { exact = false } = {}) {
    return this.run(["find", "text", text, ...(exact ? ["--exact"] : []), "click"]);
  }
  clickRole(role, name) {
    return this.run(["find", "role", role, ...(name ? ["--name", name] : []), "click"]);
  }
  press(key) {
    return this.run(["press", key]);
  }

  count(sel) {
    const n = parseInt(this.run(["get", "count", sel]).stdout, 10);
    return Number.isFinite(n) ? n : 0;
  }
  exists(sel) {
    return this.count(sel) > 0;
  }
  isVisible(sel) {
    return /^true$/i.test(this.run(["is", "visible", sel]).stdout);
  }
  textContent(sel) {
    const r = this.run(["get", "text", sel]);
    return r.status === 0 ? r.stdout : null;
  }
  getValue(sel) {
    const r = this.run(["get", "value", sel]);
    return r.status === 0 ? r.stdout : null;
  }

  waitSelector(sel) {
    return this.run(["wait", sel]);
  }
  waitText(text) {
    return this.run(["wait", "--text", text]);
  }
  waitTimeout(ms) {
    return this.run(["wait", String(Math.max(0, Math.round(ms)))]);
  }
  waitNetworkIdle() {
    return this.run(["wait", "--load", "networkidle"]);
  }
  waitUrl(glob) {
    return this.run(["wait", "--url", glob]);
  }
  scrollIntoView(sel) {
    return this.run(["scrollintoview", sel]);
  }

  evaluate(fnOrSrc, arg) {
    const src =
      typeof fnOrSrc === "function"
        ? `(${fnOrSrc.toString()})(${arg === undefined ? "" : JSON.stringify(arg)})`
        : String(fnOrSrc);
    const b64 = Buffer.from(src, "utf8").toString("base64");
    return this.run(["eval", "-b", b64]);
  }
  // Evaluate and parse the JSON result agent-browser prints for `eval`.
  evalJson(fnOrSrc, arg) {
    return tryJson(this.evaluate(fnOrSrc, arg).stdout);
  }

  screenshot(out, { full = false } = {}) {
    return this.run(["screenshot", ...(full ? ["--full"] : []), out]);
  }

  // Console messages recorded for this session (array of {type,text,...}).
  consoleMessages() {
    const j = tryJson(this.run(["console", "--json"]).stdout);
    return Array.isArray(j) ? j : Array.isArray(j?.messages) ? j.messages : [];
  }
  consoleErrors() {
    return this.consoleMessages()
      .filter((m) => (m.type || m.level) === "error")
      .map((m) => m.text || m.message || String(m));
  }
  // Tracked network requests (array of {url,method,status,...}).
  networkRequests(filter) {
    const args = ["network", "requests", "--json", ...(filter ? ["--filter", filter] : [])];
    const j = tryJson(this.run(args).stdout);
    return Array.isArray(j) ? j : Array.isArray(j?.requests) ? j.requests : [];
  }

  close() {
    return this.run(["close"]);
  }
}

export function newSession(name, { viewport = { width: 1440, height: 900 }, scale = 1 } = {}) {
  const session = `qa_${name}_${process.pid}_${++SEQ}`;
  const page = new Page(session);
  page.launch();
  page.setViewport(viewport.width, viewport.height, scale);
  return page;
}

// WordPress admin login. Throws if the form is still present afterwards.
export function loginAdmin(page, { user, pass } = {}) {
  const username = user || process.env.QA_ADMIN_USER || "admin";
  const password = pass || process.env.QA_ADMIN_PASS || "";
  page.goto("/wp-login.php");
  if (page.exists("#user_login")) {
    page.fill("#user_login", username);
    page.fill("#user_pass", password);
    page.click("#wp-submit");
    page.waitNetworkIdle();
    page.waitTimeout(600);
  }
  // Land on a clean, fully-attached admin document — the post-submit redirect can
  // briefly leave a detached document where DOM queries return nothing.
  page.goto("/wp-admin/");
  page.waitNetworkIdle();
  if (page.exists("#user_login") || !page.exists("#adminmenuwrap")) {
    const err = page.exists("#login_error") ? page.textContent("#login_error") : null;
    throw new Error(`admin login failed${err ? `: ${err.trim()}` : ""}`);
  }
}

export function closeAll() {
  spawnSync(BIN, ["close", "--all"], { encoding: "utf8" });
}

/* ----------------------------------------------------------------- expect -- */
// Minimal assertion shim (agent-browser has no test framework).
export function expect(actual) {
  return {
    toBe(exp) {
      if (actual !== exp) throw new Error(`expected ${JSON.stringify(actual)} to be ${JSON.stringify(exp)}`);
    },
    toEqual(exp) {
      if (JSON.stringify(actual) !== JSON.stringify(exp))
        throw new Error(`expected ${JSON.stringify(actual)} to equal ${JSON.stringify(exp)}`);
    },
    toBeTruthy() {
      if (!actual) throw new Error(`expected ${JSON.stringify(actual)} to be truthy`);
    },
    toContain(sub) {
      if (!String(actual).includes(sub)) throw new Error(`expected ${JSON.stringify(actual)} to contain ${JSON.stringify(sub)}`);
    },
    toMatch(re) {
      if (!re.test(String(actual))) throw new Error(`expected ${JSON.stringify(actual)} to match ${re}`);
    },
  };
}

export function skip(reason) {
  const e = new Error(reason);
  e.__qaSkip = true;
  return e;
}
