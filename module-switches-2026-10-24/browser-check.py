import subprocess, re, json, sys
from pathlib import Path
DIR=Path(__file__).resolve().parent

def run(session,*args):
 p=subprocess.run(['agent-browser','--session',session,*args],text=True,capture_output=True)
 if p.returncode: raise RuntimeError(p.stderr+p.stdout)
 return p.stdout

def snap(): return run('module-admin','snapshot','-i')
def ref(text,role,name):
 matches=[line for line in text.splitlines() if f'{role} "{name}"' in line]
 if not matches: raise AssertionError(f'Missing {role}: {name}')
 return '@'+re.search(r'ref=(e\d+)', matches[-1]).group(1)

def switches(member, toolkit, label):
 run('module-admin','open','http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/modules')
 run('module-admin','wait','--text','Member Access & Content Gate')
 run('module-admin','wait','--fn','!document.querySelector(".arraysubs-route-overlay.is-active")')
 run('module-admin','wait','--load','networkidle')
 s=snap()
 for name,wanted in [('Member Access & Content Gate',member),('Toolkit',toolkit)]:
  line=next(l for l in s.splitlines() if f'switch "{name}"' in l)
  if ('checked=true' in line)!=wanted:
   run('module-admin','click',ref(s,'switch',name))
   module_id='member_access' if name.startswith('Member Access') else 'toolkit'
   run('module-admin','wait','--fn',f"""document.querySelector('[aria-labelledby="arraysubs-module-{module_id}-title"][role=switch]')?.getAttribute('aria-checked') === '{str(wanted).lower()}'""")
   s=snap()
 if 'button "Save Settings" [disabled' not in s:
  run('module-admin','eval','window.__moduleQaBeforeReload = true')
  run('module-admin','click',ref(s,'button','Save Settings'))
  # Visible pending state is followed by a full document reload.
  run('module-admin','wait','--fn','window.__moduleQaBeforeReload !== true')
 run('module-admin','open','http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/modules')
 run('module-admin','wait','--text','Member Access & Content Gate')
 run('module-admin','wait','--fn','!document.querySelector(".arraysubs-route-overlay.is-active")')
 run('module-admin','wait','--load','networkidle')
 s=snap()
 for name,wanted in [('Member Access & Content Gate',member),('Toolkit',toolkit)]:
  line=next(l for l in s.splitlines() if f'switch "{name}"' in l)
  assert ('checked=true' in line)==wanted,(name,line,wanted)
  assert (f'link "Configure {name}"' in s)==wanted,(name,'Configure')
 (DIR/'evidence'/f'{label}-modules.txt').write_text(s)
 run('module-admin','screenshot','--full',str(DIR/'evidence'/f'{label}-modules.png'))
 print(label,'persisted and Configure visibility passed')

def surfaces(enabled,label):
 f=json.loads((DIR/'fixtures.json').read_text())
 for kind,page in f['pages'].items():
  run('module-guest','open',page['url'])
  run('module-guest','snapshot','-i')
  data=json.loads(run('module-guest','eval','JSON.stringify({body:document.body.innerText,bar:!!document.querySelector("#wpadminbar")})').strip())
  if isinstance(data,str):data=json.loads(data)
  body=data['body']; secret=page['marker'] in body;denied='QA MODULE ACCESS DENIED' in body
  (DIR/'evidence'/f'{label}-{kind}.txt').write_text(body)
  run('module-guest','screenshot','--full',str(DIR/'evidence'/f'{label}-{kind}.png'))
  print(label,kind,'secret',secret,'denied',denied)
  assert secret != enabled, (kind,enabled,body)
  if enabled: assert denied,(kind,body)

def gates(label):
 for route,module in [('members-access','Member Access & Content Gate'),('members-access/discount-rules','Member Access & Content Gate'),('members-access/ecommerce-rules','Member Access & Content Gate'),('members-access/url-rules','Member Access & Content Gate'),('members-access/cpt-rules','Member Access & Content Gate'),('members-access/downloads-rules','Member Access & Content Gate'),('members-access/comment-rules','Member Access & Content Gate'),('members-access/purchase-limit-rules','Member Access & Content Gate'),('members-access/login-limit','Member Access & Content Gate'),('member-styling','Member Access & Content Gate'),('settings/content-gate','Member Access & Content Gate'),('audits/access-rule-conflicts','Member Access & Content Gate'),('settings/toolkit','Toolkit')]:
  run('module-admin','open','http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/'+route)
  run('module-admin','wait','--text',module+' is switched off')
  print(label,route,'guarded')
  (DIR/'evidence'/f'{label}-route-{route.replace("/","-")}.txt').write_text(snap())

def box(label):
 run('module-admin','open','http://localhost:10013/wp-admin/post.php?post=5922&action=edit')
 run('module-admin','wait','--text','Edit Box Configuration')
 run('module-admin','wait','--load','networkidle')
 s=snap();run('module-admin','click',ref(s,'button','Edit Box Configuration'))
 run('module-admin','wait','--text','Configure Subscription Box')
 s=snap()
 label_line=next(l for l in s.splitlines() if 'textbox "LABEL Required' in l)
 run('module-admin','fill','@'+re.search(r'ref=(e\d+)',label_line).group(1),'QA Product choices')
 s=snap();run('module-admin','click',ref(s,'button','Continue to Discounts & Freebies'))
 run('module-admin','wait','--fn',"[...document.querySelectorAll('h3')].some(h => h.textContent === 'Discounts & Freebies')")
 s=snap();run('module-admin','click',ref(s,'button','Search freebie products…'))
 s=snap();run('module-admin','fill',ref(s,'textbox','Type at least 3 characters…'),'Demo')
 run('module-admin','wait','--load','networkidle')
 s=snap();(DIR/'evidence'/f'{label}-box-freebies.txt').write_text(s)
 assert 'Demo' in s and ('checkbox "Demo' in s or 'button "Demo' in s or 'generic "Demo' in s),s[-5000:]
 run('module-admin','screenshot','--full',str(DIR/'evidence'/f'{label}-box-freebies.png'))
 print(label,'Box freebie search returned products')
 s=snap();run('module-admin','click',ref(s,'button','Cancel'))

if __name__=='__main__':
 mode=sys.argv[1]
 if mode=='switch':switches(sys.argv[2]=='on',sys.argv[3]=='on',sys.argv[4])
 elif mode=='surfaces':surfaces(sys.argv[2]=='on',sys.argv[3])
 elif mode=='gates':gates(sys.argv[2])
 elif mode=='box':box(sys.argv[2])
