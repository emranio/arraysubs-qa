#!/usr/bin/env node
/**
 * run.mjs — minimal test runner for the agent-browser QA suite.
 *
 * agent-browser has no test runner, so this discovers ./tests/*.test.mjs, runs each
 * module's exported `run()`, and reports PASS / FAIL / SKIP. Exit code = failure count.
 *
 *   node run.mjs                 # run every test
 *   node run.mjs admin-login     # run tests whose filename matches a substring
 */
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";
import { closeAll } from "./lib/ab.mjs";

const HERE = path.dirname(fileURLToPath(import.meta.url));
const TESTS_DIR = path.join(HERE, "tests");
const filter = process.argv[2] || "";

function discover() {
  if (!fs.existsSync(TESTS_DIR)) return [];
  return fs
    .readdirSync(TESTS_DIR)
    .filter((f) => f.endsWith(".test.mjs"))
    .filter((f) => !filter || f.includes(filter))
    .sort()
    .map((f) => path.join(TESTS_DIR, f));
}

async function main() {
  const files = discover();
  if (!files.length) {
    console.error(`No tests found in ${TESTS_DIR}${filter ? ` matching "${filter}"` : ""}`);
    process.exit(1);
  }

  let pass = 0;
  let fail = 0;
  let skip = 0;

  for (const file of files) {
    const mod = await import(pathToFileURL(file).href);
    const name = mod.name || path.basename(file);
    const started = Date.now();
    try {
      await mod.run();
      pass++;
      console.log(`PASS  ${name}  (${Date.now() - started}ms)`);
    } catch (e) {
      if (e && e.__qaSkip) {
        skip++;
        console.log(`SKIP  ${name}  — ${e.message}`);
      } else {
        fail++;
        console.error(`FAIL  ${name}  — ${e?.message || e}`);
        if (e?.stack) console.error(e.stack.split("\n").slice(1, 4).join("\n"));
      }
    }
  }

  closeAll();
  console.log(`\n${pass} passed, ${fail} failed, ${skip} skipped (${files.length} total)`);
  process.exit(fail);
}

main().catch((e) => {
  closeAll();
  console.error(e);
  process.exit(1);
});
