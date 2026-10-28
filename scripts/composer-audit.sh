#!/usr/bin/env bash
set -euo pipefail

# Run from the directory containing composer.lock. Transport failures (100)
# can be transient; audit findings and other failures must stop immediately.
for attempt in 1 2 3; do
  echo "Composer locked audit: attempt ${attempt}/3"
  if composer audit --locked --no-interaction; then
    exit 0
  else
    status=$?
  fi

  if [[ "$status" -ne 100 || "$attempt" -eq 3 ]]; then
    echo "Composer audit failed (exit ${status}); security check did not pass." >&2
    exit "$status"
  fi

  delay=$((attempt * 5))
  echo "Composer transport failed; retrying in ${delay}s." >&2
  sleep "$delay"
done
