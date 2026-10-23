import json,subprocess,sys
from pathlib import Path
ROOT=Path(__file__).resolve().parent;f=json.loads((ROOT/"fixtures.json").read_text());mode=sys.argv[1] if len(sys.argv)>1 else "pro"
WP=["php","-d","mysqli.default_socket=/Users/emran/Library/Application Support/Local/run/NPLEnP-EU/mysql/mysqld.sock","/opt/homebrew/bin/wp","--allow-root","--path=/Users/emran/Local Sites/wp-test2/app/public"]
def browser(role,*args):
 r=subprocess.run(["agent-browser","--session","legacy-"+role,*args],capture_output=True,text=True,timeout=40)
 if r.returncode:raise RuntimeError(role+" "+args[0]+": "+r.stdout+r.stderr)
 return r.stdout
checks=[]
def check(v,text):
 checks.append({"passed":bool(v),"check":text});(ROOT/f"evidence/browser-{mode}-checks.json").write_text(json.dumps(checks,indent=2))
 if not v:raise AssertionError(text)
states=["legacy_login","legacy_any","visibility_in","visibility_out","visibility_empty","visibility_shortcode","default","admin","transitional_custom","transitional_default","current_any_fail","current_any_pass"]
if mode=="core":states=["legacy_login","visibility_out","visibility_empty","current_any_fail","transitional_custom"]
for state in states:
 subprocess.run(WP+["eval-file",str(ROOT/"fixture.php"),"state",state],capture_output=True,text=True,check=True)
 for role in ["guest","customer","member","admin"]:
  allowed=(state=="legacy_login" and role!="guest") or (state in ["legacy_any","current_any_pass"] and role=="member") or (state=="visibility_in" and role!="guest") or (state in ["visibility_out","visibility_empty","visibility_shortcode"] and role=="guest") or (state=="admin" and role=="admin")
  for kind in ["post","gutenberg","elementor"]:
   marker="QA LEGACY PROTECTED "+kind.upper();expected=marker if allowed else "QA LEGACY CUSTOM"
   if not allowed:
    if state in ["default","transitional_default"]:expected="QA LEGACY DEFAULT"
    if state.startswith("visibility") and kind!="post":expected="QA LEGACY EXPANDED" if state=="visibility_shortcode" else ("" if state=="visibility_empty" else "QA LEGACY FALLBACK")
   browser(role,"open",f["urls"][kind]+"?qa-legacy-state="+state);tree=browser(role,"snapshot")
   label=f"{mode}/{state}/{role}/{kind}";path=ROOT/("evidence/"+label.replace("/","-"))
   path.with_suffix(".txt").write_text(tree)
   check((marker in tree)==allowed,label+" protected content visibility")
   check((expected in tree) if expected else not any(x in tree for x in ["QA LEGACY DEFAULT","QA LEGACY CUSTOM","QA LEGACY FALLBACK"]),label+" denial message/blank fallback")
   if role in ["guest","member"] and kind!="post" and state in ["legacy_login","visibility_out","visibility_empty","current_any_fail"]:browser(role,"screenshot","--full",str(path.with_suffix(".png")))
 print(mode,state,"passed all three surfaces/four roles",flush=True)
for role in ["guest","customer","member","admin"]:(ROOT/f"evidence/browser-{mode}-{role}-errors.txt").write_text(browser(role,"errors"))
print(json.dumps({"passed":len(checks),"total":len(checks)}),flush=True)
