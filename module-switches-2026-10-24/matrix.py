import importlib.util,subprocess
from pathlib import Path
root=Path(__file__).resolve().parent
spec=importlib.util.spec_from_file_location('module_browser',root/'browser-check.py');b=importlib.util.module_from_spec(spec);spec.loader.exec_module(b)
mode=__import__('sys').argv[1]
for member,toolkit in [(False,False),(False,True),(True,False),(True,True)]:
 label=mode+'-'+('on' if member else 'off')+'-'+('on' if toolkit else 'off')
 b.switches(member,toolkit,label+'-final')
 p=subprocess.run(['/tmp/arraysubs-module-wp','--skip-plugins=elementor','eval-file',str(root/'runtime-check.php'),'on' if member else 'off','on' if toolkit else 'off',mode],capture_output=True,text=True)
 (root/'evidence'/f'{label}-runtime.txt').write_text(p.stdout+p.stderr)
 print(p.stdout.strip(),flush=True)
 if p.returncode:raise RuntimeError(p.stderr)
 if not member and not toolkit:
  proof=b.run('module-admin','eval','''fetch(window.arraySubs.env.apiBaseUrl+"arraysubs/v1/settings/save",{method:"POST",headers:{"Content-Type":"application/json","X-WP-Nonce":window.arraySubs.env.nonce},body:JSON.stringify({settings:{toolkit:{hide_admin_bar:false,multi_login_max_sessions:99}}})}).then(async r=>{const j=await r.json();return JSON.stringify({status:r.status,toolkit:j.content?.values?.toolkit})})''')
  (root/'evidence'/f'{mode}-off-settings-write.txt').write_text(proof)
  import json
  data=json.loads(proof)
  if isinstance(data,str):data=json.loads(data)
  assert data['status']==200 and data['toolkit']['hide_admin_bar'] is True and data['toolkit']['multi_login_max_sessions']==2,proof
  print(mode,'dormant settings writes retained values',flush=True)
  b.surfaces(False,mode+'-off-final')
  if mode=='free':b.box('free-fixed')
 if member and not toolkit:b.surfaces(True,mode+'-on-final')
