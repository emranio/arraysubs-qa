import json,subprocess,sys
from pathlib import Path
ROOT=Path(__file__).resolve().parent
f=json.loads((ROOT/"fixtures.json").read_text());kind=sys.argv[1];stage=sys.argv[2];allowed=set(sys.argv[3].split(',')) if len(sys.argv)>3 else {'member'}
checks=[]
for role in ['guest','customer','member','admin']:
 session='final-audience-admin' if role=='admin' else 'final-access-'+role
 prefix=['agent-browser','--session',session]
 if role!='guest':prefix+=['--state',f'/tmp/arraysubs-final-access-{role}-state.json']
 url=f['urls'][kind]+'?qa-final-stage='+stage
 r=subprocess.run(prefix+['open',url],capture_output=True,text=True,timeout=40)
 if r.returncode:raise RuntimeError(r.stdout+r.stderr)
 r=subprocess.run(['agent-browser','--session',session,'snapshot'],capture_output=True,text=True,check=True,timeout=30)
 snapshot=r.stdout;marker='QA FINAL PROTECTED '+kind.removeprefix('author_').upper()
 passed=(marker in snapshot)==(role in allowed)
 checks.append({'kind':kind,'stage':stage,'role':role,'expected_allowed':role in allowed,'actual_allowed':marker in snapshot,'passed':passed})
 (ROOT/f'evidence/{kind}-{stage}-{role}.txt').write_text(snapshot)
 if '--no-screenshots' not in sys.argv:
  subprocess.run(['agent-browser','--session',session,'screenshot','--full',str(ROOT/f'evidence/{kind}-{stage}-{role}.png')],capture_output=True,text=True,check=True,timeout=30)
 if not passed:
  (ROOT/f'evidence/{kind}-{stage}-audience.json').write_text(json.dumps(checks,indent=2));raise AssertionError(checks[-1])
(ROOT/f'evidence/{kind}-{stage}-audience.json').write_text(json.dumps(checks,indent=2))
print(json.dumps(checks),flush=True)
