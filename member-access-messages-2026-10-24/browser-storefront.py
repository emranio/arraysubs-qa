import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent
BASE = "http://localhost:10013"
SESSION = "msg-storefront"
WP = ["php", "-d", "mysqli.default_socket=/Users/emran/Library/Application Support/Local/run/NPLEnP-EU/mysql/mysqld.sock", "/opt/homebrew/bin/wp", "--allow-root", "--path=/Users/emran/Local Sites/wp-test2/app/public"]
checks = []
mode = sys.argv[1] if len(sys.argv) > 1 else "pro"


def run(*args):
    result = subprocess.run(["agent-browser", "--session", SESSION, *args], capture_output=True, text=True, timeout=40)
    if result.returncode:
        raise RuntimeError(f"{args[0]} failed: {result.stdout} {result.stderr}")
    return result.stdout


def fixture(*args):
    result = subprocess.run(WP + ["eval-file", str(ROOT / "fixture.php"), *args], capture_output=True, text=True, check=True)
    return result.stdout


def check(value, name):
    checks.append({"passed": bool(value), "check": name})
    (ROOT / f"evidence/storefront-{mode}-checks.json").write_text(json.dumps(checks, indent=2))
    assert value, name


targets = [
    ("ecommerce_rules", "/product/demo-%c2%b7-limited-edition-flask/"),
    ("url_rules", "/2026/09/27/demo-url-members/"),
    ("cpt_rules", "/2026/09/27/demo-cpt-lesson/"),
    ("comment_rules", "/2026/09/27/demo-public-discussion/"),
    ("purchase_limit_rules", "/product/demo-%c2%b7-ceramic-member-mug/"),
]


def visit(key, path, expected):
    run("open", BASE + path)
    run("reload")
    if key == "purchase_limit_rules":
        tree = run("snapshot", "-i")
        quantity = re.findall(r'spinbutton "Product quantity"[^\n]*\bref=(e\d+)', tree)[-1]
        run("fill", "@" + quantity, "4")
        tree = run("snapshot", "-i")
        purchase = re.findall(r'button "Purchase now"[^\n]*\bref=(e\d+)', tree)[-1]
        run("click", "@" + purchase)
    for attempt in range(8):
        tree = run("snapshot")
        if expected in tree:
            return tree
    raise AssertionError(f"Expected message {expected} at {path}: {tree[-3000:]}")


for state in ["off", "on", "empty", "blank"]:
    fixture("seed")
    fixture("state", state)
    for key, path in targets:
        expected = f"QA custom {key}" if state == "on" else "QA shared default"
        tree = visit(key, path, expected)
        check(expected in tree, f"{key}: {state} renders expected message")
        if state != "on":
            check(f"QA custom {key}" not in tree, f"{key}: {state} suppresses stored custom text")
        else:
            check("Join now" in tree, f"{key}: custom link displayed")
            check("members only" in tree, f"{key}: custom formatting content displayed")
        (ROOT / f"evidence/{mode}-{key}-{state}.txt").write_text(tree)
        run("screenshot", "--full", str(ROOT / f"evidence/{mode}-{key}-{state}.png"))
    print(mode, state, "passed for all five rule types", flush=True)

fixture("state", "off")
fixture("global")
for key, path in targets:
    tree = visit(key, path, "QA revised shared default")
    check("QA revised shared default" in tree, key + ": later global edit used without resaving rule")
print(mode, "global update passed for all five rule types", flush=True)

errors = run("errors")
(ROOT / f"evidence/storefront-{mode}-errors.txt").write_text(errors)
print(json.dumps({"passed": len(checks), "total": len(checks)}), flush=True)
