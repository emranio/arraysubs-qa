from browser_checks import *
ab('set','viewport','1440','1000')
route='/members-access/login-limit';open_page(route);initial=form_state()
fill('Enter default max sessions per user',4,'spinbutton');fill('Rule name','Discard global login edits');click('Discard Changes')
check(form_state()==initial,'Login Limit discard restores both global settings and rule values')
fill('Enter default max sessions per user',4,'spinbutton');fill('Rule name','Footer QA combined login save');save('Save Login Limits');saved=form_state()
open_page(route);check(form_state()==saved,'Login Limit global and rule edits both survive the same save/reload')
screenshot('login-global-and-rules-saved')

route='/profile-builder/my-account';open_page(route)
def labels():return js('[...document.querySelectorAll(".arraysubs-mae-item__label")].map(e=>e.textContent)')
initial_labels=labels();ab('focus',ref('Drag to reorder'));ab('press','Space');settle();ab('press','ArrowDown');settle();ab('press','Space');settle()
check(labels()!=initial_labels,'My Account keyboard reorder changes menu order')
click('Discard Changes');check(labels()==initial_labels,'My Account discard restores menu order')
