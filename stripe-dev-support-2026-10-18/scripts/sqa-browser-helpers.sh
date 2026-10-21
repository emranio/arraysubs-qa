# Source in zsh: helpers for staging Stripe QA browser flows.
SQA_PW=${SQA_PW:-/root/.sqa/sqa-passwords.txt} # "uid password" lines, mode 0600, never inside qa/
SQA_Q=/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/stripe-dev-support-2026-10-18
SQA_SITE=https://mirror-help.arrayhash.com
ts(){ date +%s%N | cut -c1-13; }
sqa_login(){ # session uid login
  local p; p=$(awk -v u=$2 '$1==u{p=$2} END{print p}' $SQA_PW)
  agent-browser --session $1 open "$SQA_SITE/my-account/?sqa=$(ts)" >/dev/null
  agent-browser --session $1 fill "#username" "$3" >/dev/null
  agent-browser --session $1 fill "#password" "$p" >/dev/null
  agent-browser --session $1 click "button[name=login]" >/dev/null
  agent-browser --session $1 wait --load networkidle >/dev/null
  agent-browser --session $1 get text main | grep -m1 -o "Log out"
}
sqa_cart_checkout(){ # session -> opens block checkout with product 68271 (or $2)
  agent-browser --session $1 open "$SQA_SITE/shop/?add-to-cart=${2:-68271}&sqa=$(ts)" >/dev/null
  agent-browser --session $1 wait --load networkidle >/dev/null
  agent-browser --session $1 open "$SQA_SITE/checkout/?sqa=$(ts)" >/dev/null
  agent-browser --session $1 wait --load networkidle >/dev/null; sleep 2
}
sqa_type_card(){ # session card -> scrolls Payment Element into view; card field assumed at (160,295) after centering
  agent-browser --session $1 eval 'const f=document.querySelector("iframe[title=\"Secure payment input frame\"]"); f.scrollIntoView({block:"center"}); 1' >/dev/null; sleep 1
  agent-browser --session $1 mouse move 160 295 >/dev/null; agent-browser --session $1 mouse down left >/dev/null; agent-browser --session $1 mouse up left >/dev/null
  agent-browser --session $1 keyboard type "$2" >/dev/null; agent-browser --session $1 keyboard type "1230" >/dev/null; agent-browser --session $1 keyboard type "123" >/dev/null; sleep 1
}
sqa_shot(){ agent-browser --session $1 screenshot ${3:-} $SQA_Q/screenshots/$2.png >/dev/null && echo "shot $2"; }
sqa_place(){ # session -> clicks Place Order by ref, waits for order-received
  local r; r=$(agent-browser --session $1 snapshot -i | grep 'button "Place Order"' | grep -o "ref=e[0-9]*" | cut -d= -f2)
  agent-browser --session $1 click @$r >/dev/null
  for i in $(seq 1 30); do sleep 2; u=$(agent-browser --session $1 get url); case "$u" in *order-received*) break;; esac; done; echo "$u" | sed -E 's/key=[^&]+/key=<r>/'
}
