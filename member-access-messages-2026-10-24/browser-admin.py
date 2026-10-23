import json
import re
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parent
SESSION = "msg-admin"
checks = []


def run(*args):
    result = subprocess.run(["agent-browser", "--session", SESSION, *args], capture_output=True, text=True, timeout=40)
    if result.returncode:
        raise RuntimeError(f"{args[0]} failed: {result.stdout} {result.stderr}")
    return result.stdout


def snap():
    run("wait", "--fn", 'Array.from(document.querySelectorAll(".arraysubs-modal-backdrop")).every(e => getComputedStyle(e).opacity === "1" && e.getAnimations({subtree:true}).every(a => a.playState !== "running"))')
    return run("snapshot", "-i")


def click_name(name, kind="button"):
    tree = snap()
    matches = re.findall(rf'{kind} "{re.escape(name)}"[^\n]*\[ref=(e\d+)\]', tree)
    if not matches:
        raise AssertionError(f"Missing {kind}: {name}\n{tree[-3000:]}")
    run("click", "@" + matches[-1])


def check(value, name):
    checks.append({"passed": bool(value), "check": name})
    (ROOT / "evidence/admin-checks.json").write_text(json.dumps(checks, indent=2))
    assert value, name


def wait_switch(value):
    run("wait", "--fn", f'document.querySelector("input[role=switch]")?.checked === {str(value).lower()}')


def toggle(value, keyboard=False):
    tree = snap()
    ref = re.findall(r'switch "Use custom message"[^\n]*\bref=(e\d+)\]', tree)[-1]
    if keyboard:
        run("focus", "@" + ref)
        run("press", "Space")
    else:
        run("click", "@" + ref)
    wait_switch(value)


def open_rule(route, name):
    run("open", f"http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/members-access/{route}")
    run("reload")
    run("wait", "--load", "networkidle")
    run("wait", "--fn", f'Array.from(document.querySelectorAll("button")).some(b=>b.textContent!==null && b.getAttribute("aria-label") === {json.dumps("Edit " + name)})')
    click_name("Edit " + name)
    run("wait", "--fn", 'Array.from(document.querySelectorAll("button")).some(b=>b.getAttribute("aria-label")?.startsWith("Edit How "))')
    tree = snap()
    actions = re.findall(r'button "(Edit [^"]+)"[^\n]*\[ref=(e\d+)\]', tree)
    action = next((item for item in actions if item[0].startswith("Edit How ")), None)
    if action is None:
        raise AssertionError("No protection step: " + tree[-2500:])
    run("click", "@" + action[1])
    run("wait", "input[role=switch]")


cases = [
    ("ecommerce-rules", "Demo · Members-only limited flask", "ecommerce_rules"),
    ("url-rules", "Demo · Member URL access", "url_rules"),
    ("cpt-rules", "Specific member lesson", "cpt_rules"),
    ("comment-rules", "Demo · Members can join the discussion", "comment_rules"),
    ("purchase-limit-rules", "Demo · Guest gift quantity limit", "purchase_limit_rules"),
]

for route, name, key in cases:
    open_rule(route, name)
    wait_switch(False)
    check("Rich Text Area" not in snap(), key + ": off hides editor")
    toggle(True, keyboard=key == "ecommerce_rules")
    run("wait", "--fn", 'window.tinymce?.activeEditor?.initialized === true')
    check(key in run("eval", "tinymce.activeEditor.getContent()"), key + ": on restores retained custom text")
    check("<strong>members only</strong>" in run("eval", "tinymce.activeEditor.getContent()"), key + ": formatting retained")
    toggle(False)
    check("Rich Text Area" not in snap(), key + ": clicking off hides editor")
    toggle(True)
    run("wait", "--fn", 'window.tinymce?.activeEditor?.initialized === true')
    check(key in run("eval", "tinymce.activeEditor.getContent()"), key + ": off/on preserves text")
    run("screenshot", str(ROOT / f"evidence/{key}-on.png"))
    click_name("Done")
    run("wait", "--fn", '!document.querySelector(".arraysubs-modal-backdrop")')
    click_name("Save Rules")
    run("wait", "--text", "saved successfully!")
    open_rule(route, name)
    wait_switch(True)
    run("wait", "--fn", 'window.tinymce?.activeEditor?.initialized === true')
    check(key in run("eval", "tinymce.activeEditor.getContent()"), key + ": save/reload preserves enabled flag and HTML")
    toggle(False)
    click_name("Done")
    run("wait", "--fn", '!document.querySelector(".arraysubs-modal-backdrop")')
    click_name("Discard Changes")
    open_rule(route, name)
    wait_switch(True)
    check(True, key + ": Discard restores saved switch")
    toggle(False)
    click_name("Done")
    run("wait", "--fn", '!document.querySelector(".arraysubs-modal-backdrop")')
    click_name("Save Rules")
    run("wait", "--text", "saved successfully!")
    open_rule(route, name)
    wait_switch(False)
    check(True, key + ": save/reload preserves disabled flag")
    run("screenshot", str(ROOT / f"evidence/{key}-off.png"))
    click_name("Done")
    print(key, "passed", flush=True)

print(json.dumps({"passed": len(checks), "total": len(checks)}), flush=True)
