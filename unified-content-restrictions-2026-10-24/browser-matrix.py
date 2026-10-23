import json
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent
FIXTURES = json.loads((ROOT / "fixtures.json").read_text())
WP = ["php", "-d", "mysqli.default_socket=/Users/emran/Library/Application Support/Local/run/NPLEnP-EU/mysql/mysqld.sock", "/opt/homebrew/bin/wp", "--allow-root", "--path=/Users/emran/Local Sites/wp-test2/app/public"]
argument = sys.argv[1] if len(sys.argv) > 1 else "pro"
resume = argument.endswith("_resume")
MODE = argument.removesuffix("_resume")
checks = json.loads((ROOT / f"evidence/browser-{MODE}-checks.json").read_text()) if resume else []
states = ["public", "login_only", "any_fail", "any_pass", "all_fail", "admin_bypass", "custom", "empty", "blank"]
if MODE == "core":
    states = ["login_only", "any_fail", "any_pass", "admin_bypass", "custom", "empty"]
    if resume:
        states = ["custom", "empty"]


def browser(role, *args):
    result = subprocess.run(["agent-browser", "--session", "unified-" + role, *args], capture_output=True, text=True, timeout=40)
    if result.returncode:
        raise RuntimeError(f"{role} {args[0]}: {result.stdout} {result.stderr}")
    return result.stdout


def check(value, description):
    checks.append({"passed": bool(value), "check": description})
    (ROOT / f"evidence/browser-{MODE}-checks.json").write_text(json.dumps(checks, indent=2))
    if not value:
        raise AssertionError(description)


for state in states:
    subprocess.run(WP + ["eval-file", str(ROOT / "fixture.php"), "state", state], capture_output=True, text=True, check=True)
    for role in ["guest", "customer", "member", "admin"]:
        allowed = state == "public" or (state == "login_only" and role != "guest") or (state == "any_pass" and role == "member") or (state == "admin_bypass" and role == "admin")
        for surface in ["post", "gutenberg", "elementor"]:
            marker = "QA PROTECTED " + surface.upper() + " CONTENT"
            expected = marker if allowed else ("QA UNIFIED CUSTOM" if state == "custom" else "QA UNIFIED DEFAULT")
            browser(role, "open", FIXTURES["urls"][surface])
            for attempt in range(8):
                tree = browser(role, "snapshot")
                if expected in tree:
                    break
            evidence = ROOT / f"evidence/{MODE}-{state}-{role}-{surface}"
            evidence.with_suffix(".txt").write_text(tree)
            check(expected in tree, f"{surface}/{role}/{state}: expected {'protected content' if allowed else 'denial message'}")
            check((marker in tree) == allowed, f"{surface}/{role}/{state}: protected content visibility")
            if role in ["guest", "member"] or state == "admin_bypass":
                browser(role, "screenshot", "--full", str(evidence.with_suffix(".png")))
    print(MODE, state, "passed for all three surfaces and four roles", flush=True)

for role in ["guest", "customer", "member", "admin"]:
    (ROOT / f"evidence/browser-{MODE}-{role}-errors.txt").write_text(browser(role, "errors"))
print(json.dumps({"passed": len(checks), "total": len(checks)}), flush=True)
