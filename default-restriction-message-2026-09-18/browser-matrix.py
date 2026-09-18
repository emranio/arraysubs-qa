import json, subprocess, sys, pathlib
phase=sys.argv[1]; session=sys.argv[2] if len(sys.argv)>2 else 'default-message-guest'
root=pathlib.Path('/Users/emran/Public/Projects/local-wp/woocommerce/app/public/wp-content/plugins/qa/default-restriction-message-2026-09-18/evidence')
fixtures=json.load(open('/tmp/dm-fixtures.json')); results={}
def run(*args):
 p=subprocess.run(['agent-browser','--session',session,*args],capture_output=True,text=True,timeout=30)
 if p.returncode: raise RuntimeError(p.stderr+p.stdout)
 return p.stdout
script='''(() => {const root=document.querySelector('main')||document.body; return {text:root.innerText,notices:Array.from(document.querySelectorAll('.arraysubs-restricted-content,.arraysubs-comment-restricted,.arraysubs-access-denied__message')).map(n=>({text:n.innerText,html:n.innerHTML,strong:n.querySelectorAll('strong').length,links:Array.from(n.querySelectorAll('a')).map(a=>a.getAttribute('href'))})),status:performance.getEntriesByType('navigation')[0]?.responseStatus};})()'''
for name,item in fixtures.items():
 run('open',item['url']); run('snapshot','-i')
 r=json.loads(run('--json','eval',script))['data']['result']; results[name]=r
 if phase=='authorized': ok='DM_PROTECTED' in r['text'] and not r['notices']
 else:
  expected='DM Global '+phase if phase in ['A','B'] else 'This content is restricted. Please subscribe to access.'
  joined='\n'.join(n['text'] for n in r['notices'])
  mixed=name in ['gutenberg','elementor','shortcode']; custom=name.endswith('custom')
  ok=bool(r['notices']) and (('DM Custom' in joined) if custom else (expected in joined)) and (not mixed or 'DM Custom' in joined) and ('DM_PROTECTED' not in r['text'] or name.startswith('comment-')) and 'DM Wrong' not in joined
  if custom: ok=ok and expected not in joined
  if name.startswith('url-403'): ok=ok and r['status']==403
 r['passed']=ok
 print(phase,name,'PASS' if ok else 'FAIL',json.dumps(r['notices']),flush=True)
 if name in ['gutenberg','elementor','cpt-custom','url-403-default','comment-default'] and phase=='A': run('screenshot',str(root/(phase+'-'+name+'.png')))
(root/('matrix-'+phase+'.json')).write_text(json.dumps(results,indent=2))
if not all(x['passed'] for x in results.values()): sys.exit(1)
