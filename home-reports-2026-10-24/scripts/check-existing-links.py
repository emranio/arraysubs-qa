import json, re, subprocess
from pathlib import Path
base='http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/overview'
out=Path('qa/home-reports-2026-10-24/evidence')
def browser(*args):
 return subprocess.run(['agent-browser','--session','report-home-links',*args],check=True,capture_output=True,text=True).stdout
results=[]
for title in ['Subscription performance →','Retention analytics →','AI churn analysis →','AI forecast →','All products →','View all →']:
 browser('open',base)
 browser('wait','--fn','document.querySelectorAll("[data-report-card]").length === 11 && !document.querySelector(".arraysubs-route-overlay.is-active")')
 snap=browser('snapshot','-i','-s','.arraysubs-overview')
 ref=re.search(r'link "'+re.escape(title)+r'" \[ref=(e\d+)\]',snap).group(1)
 browser('click','@'+ref)
 browser('wait','--load','networkidle')
 browser('snapshot','-i','-s','#wpbody-content')
 page=json.loads(browser('eval','JSON.stringify({url:location.href,title:document.title,text:document.querySelector("#wpbody-content")?.innerText.slice(0,2000)})'))
 if isinstance(page,str): page=json.loads(page)
 results.append({'link':title,**page})
 print(title,page['url'],flush=True)
 (out/'existing-link-results.json').write_text(json.dumps(results,indent=2))
browser('open',base)
browser('wait','--fn','document.querySelectorAll("[data-report-card]").length === 11 && !document.querySelector(".arraysubs-route-overlay.is-active")')
