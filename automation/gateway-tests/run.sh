#!/usr/bin/env bash
#
# Run the gateway test suites against the local WordPress install.
#
# Usage: qa/automation/gateway-tests/run.sh [wordpress-root]
#
# Exits non-zero if any suite has a failing assertion, so this is safe to gate a
# release on.

set -uo pipefail

WP_ROOT="${1:-/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public}"
SUITE_DIR="wp-content/plugins/qa/automation/gateway-tests"

cd "$WP_ROOT" || {
  echo "WordPress root not found: $WP_ROOT" >&2
  exit 2
}

status=0

for suite in paypal mollie; do
  echo
  echo "═══ ${suite} ═══"

  if ! wp eval-file "${SUITE_DIR}/${suite}.php" --allow-root; then
    status=1
  fi
done

echo
if [ "$status" -eq 0 ]; then
  echo "All gateway suites passed."
else
  echo "One or more gateway suites failed." >&2
fi

exit "$status"
