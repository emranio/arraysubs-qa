from browser_checks import *

ab('set','viewport','1440','1000')
open_page('/profile-builder/profile-form')
check('Footer QA field' in ab('get','text','#arraysubs-main-root'),'Profile Form saved custom field survives reload')
click('Remove field');ab('wait','--text','Keep Field')
d=json.loads(ab('snapshot','-i','--json'))['data']
target=next(k for k,v in d['refs'].items() if v['role']=='button' and v['name']=='Remove Field')
ab('click','@'+target);ab('wait','--fn','!document.querySelector(".arraysubs-modal-backdrop")');settle()
check('Footer QA field' not in ab('get','text','#arraysubs-main-root'),'Profile Form saved field removed from draft')
click('Discard Changes');check('Footer QA field' in ab('get','text','#arraysubs-main-root'),'Profile Form discard restores removed saved field')
easy_link('Profile Form','/profile-builder/profile-form')

route='/members-access/login-limit';open_page(route);initial=form_state()
fill('Default max sessions per user',4,'spinbutton');fill('Rule name','Discard global login edits');click('Discard Changes')
check(form_state()==initial,'Login Limit discard restores both global settings and rule values')
fill('Default max sessions per user',4,'spinbutton');fill('Rule name','Footer QA combined login save');save('Save Login Limits');saved=form_state()
open_page(route);check(form_state()==saved,'Login Limit global and rule edits both survive the same save/reload')
screenshot('login-global-and-rules-saved')

route='/profile-builder/my-account';open_page(route)
def labels():return js('[...document.querySelectorAll(".arraysubs-mae-item__label")].map(e=>e.textContent)')
initial_labels=labels();ab('focus',ref('Drag to reorder'));ab('press','Space');settle();ab('press','ArrowDown');settle();ab('press','Space');settle()
check(labels()!=initial_labels,'My Account keyboard reorder changes menu order')
click('Discard Changes');check(labels()==initial_labels,'My Account discard restores menu order')
