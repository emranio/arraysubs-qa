import json,re,subprocess
from pathlib import Path
out=Path('qa/home-reports-2026-10-24/evidence')
def browser(*args):
 return subprocess.run(['agent-browser','--session','report-home-admin',*args],check=True,capture_output=True,text=True).stdout
results=[]
for label,expected in [('7 days','8'),('90 days','1,249'),('This month','24'),('30 days','91')]:
 snap=browser('snapshot','-i','-s','.arraysubs-overview__toolbar')
 ref=re.search(r'button "'+re.escape(label)+r'" \[ref=(e\d+)\]',snap).group(1)
 browser('click','@'+ref)
 browser('wait','--fn','document.querySelector("[data-report-card=orders-analytics] dd")?.innerText === '+json.dumps(expected)+' && !document.querySelector(".arraysubs-overview__body.is-busy")')
 result=json.loads(browser('eval','JSON.stringify({orders:document.querySelector("[data-report-card=orders-analytics] dd").innerText,url:document.querySelector("[data-report-card=orders-analytics] a").href})'))
 if isinstance(result,str):result=json.loads(result)
 results.append({'range':label,'expected':expected,**result,'pass':result['orders']==expected})
 print(label,result['orders'],flush=True)
(out/'browser-range-checks.json').write_text(json.dumps(results,indent=2))
snap=browser('snapshot','-i','-s','.arraysubs-overview__toolbar')
ref=re.search(r'button "Refresh" \[ref=(e\d+)\]',snap).group(1)
browser('click','@'+ref)
browser('wait','--fn','!document.querySelector(".arraysubs-overview__body.is-busy") && document.querySelectorAll("[data-report-card]").length===11')
print('Refresh completed; 11 cards retained.')
