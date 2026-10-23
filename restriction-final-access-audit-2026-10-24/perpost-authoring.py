import subprocess,json,re
from pathlib import Path
P=Path(__file__).resolve().parent
wp=['php','-d','mysqli.default_socket=/Users/emran/Library/Application Support/Local/run/NPLEnP-EU/mysql/mysqld.sock','/opt/homebrew/bin/wp','--allow-root','--path=/Users/emran/Local Sites/wp-test2/app/public']
def browser(*args):
 r=subprocess.run(['agent-browser','--session','final-access-admin',*args],capture_output=True,text=True,timeout=45,check=True);return r.stdout
def ref(snapshot,label):
 m=re.search(r'button "'+re.escape(label)+r'" \[ref=(e\d+)\]',snapshot)
 if not m:raise RuntimeError('Missing button '+label+' in '+snapshot)
 return '@'+m[1]
results=[]
for case in ['enabled-string','login-string','admin-null']:
 subprocess.run(wp+['eval-file',str(P/'seed-security-case.php'),case,'author_post'],capture_output=True,text=True,check=True)
 browser('open','http://localhost:10013/wp-admin/post.php?post=20190&action=edit&qa-import='+case)
 browser('wait','--load','networkidle');snap=browser('snapshot','-i');title=re.search(r'textbox \"Add title\" \[ref=(e\d+)\]',snap);browser('fill','@'+title[1],'QA imported '+case+' saved ');snap=browser('snapshot','-i');browser('click',ref(snap,'Edit restriction'))
 snap=browser('snapshot','-i');(P/'evidence'/f'perpost-{case}-dialog.txt').write_text(snap)
 browser('click',ref(snap,'Apply'))
 snap=browser('snapshot','-i');browser('click',ref(snap,'Save'));browser('wait','--text','Page updated.');browser('wait','--load','networkidle')
 r=subprocess.run(wp+['eval','echo "QA_RESULT:".wp_json_encode(["raw"=>get_post_meta(20190,"_arraysubs_restrict_config",true),"canonical"=>ArraySubs\\Features\\MembersAccess\\Services\\PostRestrictionConfig::get(20190)]);'],capture_output=True,text=True,check=True)
 saved=json.JSONDecoder().raw_decode(r.stdout.split('QA_RESULT:')[1])[0];c=saved['canonical']
 assert c['accessVersion']==1 and c['enabled'] is True and c['showToAdmins'] is False
 assert c['legacy']['loginOnly']==(case=='login-string')
 (P/'evidence'/f'perpost-{case}-saved.json').write_text(json.dumps(saved,indent=2))
 subprocess.run(['python3',str(P/'verify-audience.py'),'author_post',case+'-saved','customer,member,admin' if case=='login-string' else ''],check=True)
 results.append({'case':case,'saved':True,'audience_preserved':True})
(P/'evidence/perpost-imported-authoring.json').write_text(json.dumps(results,indent=2))
