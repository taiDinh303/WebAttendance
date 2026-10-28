#!/usr/bin/env bash
set -euo pipefail

audit_script="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)/composer-audit.sh"
test_dir="$(mktemp -d)"
trap 'rm -f -- "$test_dir/composer" "$test_dir/sleep" "$test_dir/state" "$test_dir/delays"; rmdir -- "$test_dir"' EXIT

cat > "$test_dir/composer" <<'FAKE'
#!/usr/bin/env bash
set -euo pipefail
[[ "$*" == 'audit --locked --no-interaction' ]] || exit 64
index=$(cat "$AUDIT_TEST_DIR/state")
read -r -a statuses <<< "$AUDIT_TEST_STATUSES"
echo "$((index + 1))" > "$AUDIT_TEST_DIR/state"
exit "${statuses[$index]:-99}"
FAKE
cat > "$test_dir/sleep" <<'FAKE'
#!/usr/bin/env bash
printf '%s\n' "$1" >> "$AUDIT_TEST_DIR/delays"
FAKE
chmod +x "$test_dir/composer" "$test_dir/sleep"
export PATH="$test_dir:$PATH"
export AUDIT_TEST_DIR="$test_dir"

check() {
  local statuses="$1" expected_status="$2" expected_attempts="$3" expected_delays="$4" status=0
  export AUDIT_TEST_STATUSES="$statuses"
  echo 0 > "$test_dir/state"
  : > "$test_dir/delays"
  bash "$audit_script" || status=$?
  [[ "$status" -eq "$expected_status" ]] || { echo "Unexpected exit for $statuses: $status" >&2; exit 1; }
  [[ "$(cat "$test_dir/state")" -eq "$expected_attempts" ]] || { echo 'Unexpected attempt count' >&2; exit 1; }
  [[ "$(paste -sd, "$test_dir/delays")" == "$expected_delays" ]] || { echo 'Unexpected retry delay' >&2; exit 1; }
  echo "PASS: [$statuses] => exit $status, $expected_attempts attempt(s)"
}

check '0' 0 1 ''
check '100 0' 0 2 '5'
check '100 100 0' 0 3 '5,10'
check '100 100 100' 100 3 '5,10'
check '1' 1 1 ''
check '2' 2 1 ''
check '3' 3 1 ''
check '100 1' 1 2 '5'
check '127' 127 1 ''
echo 'All 9 Composer audit retry cases passed.'
