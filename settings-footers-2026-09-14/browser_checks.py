"""Real-browser checks through agent-browser's snapshot/ref CLI."""
import json, subprocess, sys, time
from pathlib import Path
OUT=Path(__file__).parent/'evidence'
BASE='http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin&footer-test=20260914'
LOG=OUT/'checks.jsonl'
def ab(*args):
 p=subprocess.run(['agent-browser','--session','footer-admin',*args],capture_output=True,text=True,timeout=40)
 if p.returncode: raise RuntimeError(p.stdout+p.stderr)
 return p.stdout.strip()
def snap():
 return json.loads(ab('snapshot','-i','--json','-s','#arraysubs-main-root'))['data']
def ref(name,role='button',index=0):
 d=snap(); visible=set(__import__('re').findall(r'ref=(e\d+)',d['snapshot']))
 found=[k for k,v in d['refs'].items() if k in visible and v['role']==role and v.get('name','')==name]
 assert len(found)>index,(name,d['snapshot'])
 return '@'+found[index]
def settle():
 ab('eval','new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(() => resolve(true))))')
def click(name,role='button',index=0):
 target=ref(name,role,index)
 try: ab('click',target)
 except RuntimeError as error:
  if 'covered' not in str(error): raise
  ab('scrollintoview',target);ab('scroll','down','300');ab('click',ref(name,role,index))
 settle()
def fill(name,value,role='textbox',index=0):
 ab('fill',ref(name,role,index),str(value));settle()
def js(expr):
 return json.loads(ab('eval',expr))
def check(condition,message):
 assert condition,message
 with LOG.open('a') as f:f.write(json.dumps({'check':message,'result':'PASS'})+'\n')
 print('PASS '+message,flush=True)
def screenshot(name):
 ab('screenshot','--full',str(OUT/(name+'.png')))
 (OUT/(name+'.txt')).write_text(ab('snapshot','-s','#arraysubs-main-root'))
def open_page(route):
 ab('errors','--clear');ab('open',BASE+'&check='+str(time.time_ns())+'#'+route);ab('wait','--text','Discard Changes');ab('wait','--load','networkidle')
 snap()
def footer(page):
 data=js('(() => { const bar=document.querySelector(".arraysubs-settings-actions > div"); return {text:bar.innerText,links:[...bar.querySelectorAll("a")].map(x=>x.getAttribute("href")),display:getComputedStyle(bar).display,buttons:[...bar.querySelectorAll("button")].map(b=>({label:b.innerText,disabled:b.disabled})),stamp:bar.querySelector(".arraysubs-settings-last-saved").innerText}; })()')
 check('Easy Setup & Import/Export' in data['text'] and '/easy-setup' in data['links'][0] and data['display']=='flex',page+' shared footer and Easy Setup destination')
 check('Settings last saved:' in data['stamp'] and 'Not recorded' not in data['stamp'],page+' persisted timestamp displayed')
 return data['stamp']
def save(label='Save Settings'):
 # Observe real UI loading, without stubbing the request or changing app state.
 ab('eval','window.footerSavingObserved=false; window.footerSaveObserver?.disconnect(); window.footerSaveObserver=new MutationObserver(()=>{if(document.querySelector(".arraysubs-settings-actions .arraysubs-spin") && [...document.querySelectorAll(".arraysubs-settings-actions button")].every(b=>b.disabled)) window.footerSavingObserved=true;}); window.footerSaveObserver.observe(document.querySelector(".arraysubs-settings-actions"),{subtree:true,childList:true,attributes:true}); true')
 click(label)
 ab('wait','--fn','!document.querySelector(".arraysubs-settings-actions .arraysubs-spin")')
 ab('wait','--load','networkidle')
 check(js('window.footerSavingObserved'),'save displays spinner and disables save/discard')
def form_state():
 return js('[...document.querySelectorAll("#arraysubs-main-root input,#arraysubs-main-root select,#arraysubs-main-root textarea,#arraysubs-main-root [role=switch]")].map(e=>({tag:e.tagName,type:e.type,name:e.name,placeholder:e.placeholder,value:e.value,checked:e.checked,ariaChecked:e.getAttribute("aria-checked")}))')
def easy_link(page,route):
 click('Easy Setup & Import/Export','link');ab('wait','--fn','location.hash === "#/easy-setup"');ab('wait','--text','Easy Setup')
 check('/easy-setup' in ab('get','url'),page+' Easy Setup link opens successfully')
 open_page(route)
def profile_fields():
 route='/profile-builder/profile-form'
 open_page(route);initial=form_state();stamp=footer('Profile Form')
 click('Add Field');click('Save Configuration');ab('wait','--text','A field label is required');
 check('A field label is required' in ab('get','text','#arraysubs-main-root'),'incomplete profile field validation remains visible')
 click('Discard Changes');check(form_state()==initial,'Profile Form discard clears added field and validation')
 click('Enable avatar upload','checkbox');fill('Maximum file size (MB)',4,role='spinbutton') if False else None
 click('Discard Changes');check(form_state()==initial,'Profile Form discard restores avatar toggle')
 click('Enable avatar upload','checkbox');save('Save Configuration');saved=form_state()
 click('Enable avatar upload','checkbox');click('Discard Changes');check(form_state()==saved,'Profile Form discard restores latest save')
 screenshot('profile-fields-saved');open_page(route);check(form_state()==saved,'Profile Form saved avatar survives reload');check(footer('Profile Form')!=stamp,'Profile Form saved timestamp advances')
 easy_link('Profile Form',route)
def cart():
 route='/cart-info-editor';open_page(route);initial=form_state();footer('Cart Info Editor')
 data=snap()
 # All three switches are visible, named form controls.
 refs=[v['name'] for v in data['refs'].values() if v['role']=='switch']
 if not refs: refs=[v['name'] for v in data['refs'].values() if v['role']=='checkbox']
 role='switch' if any(v['role']=='switch' for v in data['refs'].values()) else 'checkbox'
 for name in refs:click(name,role)
 check(form_state()!=initial,'Cart Info changed all three switches')
 click('Discard Changes');check(form_state()==initial,'Cart Info discard restores all switches')
 click(refs[0],role);save();saved=form_state();click(refs[1],role);click('Discard Changes');check(form_state()==saved,'Cart Info discard restores latest save')
 open_page(route);check(form_state()==saved,'Cart Info save survives reload');screenshot('cart-saved');easy_link('Cart Info Editor',route)
def myaccount():
 route='/profile-builder/my-account';open_page(route);initial=form_state();footer('My Account')
 click('Add Custom Item');click('Save Configuration');ab('wait','--text','A menu label is required')
 check('A menu label is required' in ab('get','text','#arraysubs-main-root'),'My Account validates incomplete added item')
 click('Discard Changes');check(form_state()==initial,'My Account discard removes added item and validation')
 click('Expand details');fill('Dashboard','Footer QA Dashboard');
 click('Discard Changes');check('Footer QA Dashboard' not in ab('get','text','#arraysubs-main-root'),'My Account discard restores label')
 # The first default row remains expanded after discard.
 fill('Dashboard','Footer QA Dashboard');save('Save Configuration')
 check('Footer QA Dashboard' in ab('get','text','#arraysubs-main-root'),'My Account label saved')
 fill('Dashboard','Discard this label');click('Discard Changes')
 check('Footer QA Dashboard' in ab('get','text','#arraysubs-main-root') and 'Discard this label' not in ab('get','text','#arraysubs-main-root'),'My Account discard restores latest saved label')
 open_page(route);check('Footer QA Dashboard' in ab('get','text','#arraysubs-main-root'),'My Account save survives reload');screenshot('myaccount-saved');easy_link('My Account',route)

def inspect_rules():
 open_page('/members-access/url-rules');footer('URL rules');print(snap()['snapshot'],flush=True)
 click('Add URL Rule');print(snap()['snapshot'],flush=True)

RULE_ROUTES = [
 ('/members-access','Role Mapping'),
 ('/members-access/discount-rules','Discount'),
 ('/members-access/ecommerce-rules','Shop Access'),
 ('/members-access/url-rules','URL'),
 ('/members-access/cpt-rules','Post Types'),
 ('/members-access/downloads-rules','Downloads'),
 ('/members-access/comment-rules','Comments'),
 ('/members-access/purchase-limit-rules','Purchase Limit'),
 ('/members-access/login-limit','Login Limit'),
]
def rule_names():
 return js('[...document.querySelectorAll("#arraysubs-main-root input")].filter(e=>e.placeholder==="Rule name").map(e=>e.value)')
def rules(route,page):
 open_page(route);initial=form_state();names=rule_names();footer(page);errors_before=json.loads(ab('errors','--json'))['data']['errors']
 click('Add New Rule');fill('Rule name','Discard temporary rule',index=len(names));click('Add Condition')
 click('Discard Changes');check(form_state()==initial,page+' discard removes added rule and nested condition')
 click('Add New Rule');fill('Rule name','Footer QA '+page,index=len(names));ab('uncheck',ref('','checkbox',len(names)));settle()
 save('Save Login Limits' if page=='Login Limit' else 'Save Settings' if page=='Downloads' else 'Save Rules')
 saved_names=rule_names();check('Footer QA '+page in saved_names,page+' new disabled rule saved')
 fill('Rule name','Discard renamed rule',index=len(names));click('Duplicate',index=len(names));click('Discard Changes')
 check(rule_names()==saved_names,page+' discard restores latest saved rule name and removes duplicate')
 open_page(route);check(rule_names()==saved_names,page+' rule survives reload')
 check(json.loads(ab('errors','--json'))['data']['errors']==errors_before,page+' no new browser runtime errors')
 screenshot('rules-'+page.lower().replace(' ','-'));easy_link(page,route)
def all_rules():
 for route,page in RULE_ROUTES[int(sys.argv[2]) if len(sys.argv)>2 else 0:]: rules(route,page)

if __name__=='__main__':
 ab('set','viewport','1440','1000')
 {'profile':profile_fields,'cart':cart,'myaccount':myaccount,'inspect-rules':inspect_rules,'rules':all_rules}[sys.argv[1]]()
