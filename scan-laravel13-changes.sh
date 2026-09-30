#!/usr/bin/env bash
#
# scan-laravel13-changes.sh
#
# Scans a Laravel application for code patterns affected by the
# Laravel 12 -> 13 upgrade (High/Medium impact changes only).
#
# Usage:
#   ./scan-laravel13-changes.sh [path-to-project]
#
# Defaults to the current directory if no path is given.

set -uo pipefail

PROJECT_DIR="${1:-.}"

if [ ! -d "$PROJECT_DIR" ]; then
    echo "Error: '$PROJECT_DIR' is not a directory." >&2
    exit 1
fi

# Directories to skip.
EXCLUDE_DIRS=(vendor node_modules storage bootstrap/cache .git)

build_prune_args() {
    local args=()
    for d in "${EXCLUDE_DIRS[@]}"; do
        args+=(-path "*/$d" -prune -o)
    done
    printf '%s\n' "${args[@]}"
}

echo "Scanning: $(cd "$PROJECT_DIR" && pwd)"
echo "=========================================="

# ---------------------------------------------------------------------------
# 1. VerifyCsrfToken / ValidateCsrfToken references (High impact)
# ---------------------------------------------------------------------------
echo
echo "## 1. CSRF middleware rename (VerifyCsrfToken -> PreventRequestForgery)"
echo "----------------------------------------------------------------------"

mapfile -t CSRF_HITS < <(
    grep -rn --include="*.php" \
        -e "VerifyCsrfToken" \
        -e "ValidateCsrfToken" \
        "$PROJECT_DIR" \
        2>/dev/null \
    | grep -v -E "/(vendor|node_modules|storage|bootstrap/cache)/"
)

if [ "${#CSRF_HITS[@]}" -eq 0 ]; then
    echo "No matches found. Nothing to update here."
else
    printf '%s\n' "${CSRF_HITS[@]}"
    echo
    echo "-> Replace with: Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery"
    echo "   (old class names still work as deprecated aliases, but update direct references,"
    echo "   especially in bootstrap/app.php middleware config and test setup/withoutMiddleware calls.)"
fi

# ---------------------------------------------------------------------------
# 2. ->upsert( calls (Medium impact - empty uniqueBy now throws on MySQL/MariaDB)
# ---------------------------------------------------------------------------
echo
echo "## 2. upsert() calls — verify uniqueBy is always non-empty"
echo "----------------------------------------------------------------------"

mapfile -t UPSERT_HITS < <(
    grep -rn --include="*.php" \
        -e "->upsert(" \
        "$PROJECT_DIR" \
        2>/dev/null \
    | grep -v -E "/(vendor|node_modules|storage|bootstrap/cache)/"
)

if [ "${#UPSERT_HITS[@]}" -eq 0 ]; then
    echo "No matches found. Nothing to update here."
else
    printf '%s\n' "${UPSERT_HITS[@]}"
    echo
    echo "-> Manually confirm each call passes a non-empty \$uniqueBy array as the 2nd argument."
    echo "   Laravel 13 throws InvalidArgumentException on MySQL/MariaDB if it's empty."
fi

# ---------------------------------------------------------------------------
# 3. Bonus: model instantiation inside boot() methods (Very Low, but a hard break)
# ---------------------------------------------------------------------------
echo
echo "## 3. (Bonus) 'new static()' or model queries inside boot() methods"
echo "----------------------------------------------------------------------"

mapfile -t BOOT_HITS < <(
    grep -rln --include="*.php" "function boot(" "$PROJECT_DIR" 2>/dev/null \
    | grep -v -E "/(vendor|node_modules|storage|bootstrap/cache)/"
)

if [ "${#BOOT_HITS[@]}" -eq 0 ]; then
    echo "No boot() methods found."
else
    echo "Files with a boot() method — review manually for 'new static()' or"
    echo "Eloquent queries run during model booting (now throws LogicException):"
    printf '%s\n' "${BOOT_HITS[@]}"
fi

# ---------------------------------------------------------------------------
# 4. Bonus: JobAttempted / QueueBusy event listeners (renamed properties)
# ---------------------------------------------------------------------------
echo
echo "## 4. (Bonus) exceptionOccurred / \$connection property usage on queue events"
echo "----------------------------------------------------------------------"

mapfile -t QUEUE_HITS < <(
    grep -rn --include="*.php" \
        -e "exceptionOccurred" \
        -e "JobAttempted" \
        -e "QueueBusy" \
        "$PROJECT_DIR" \
        2>/dev/null \
    | grep -v -E "/(vendor|node_modules|storage|bootstrap/cache)/"
)

if [ "${#QUEUE_HITS[@]}" -eq 0 ]; then
    echo "No matches found."
else
    printf '%s\n' "${QUEUE_HITS[@]}"
    echo
    echo "-> JobAttempted: \$exceptionOccurred (bool) is now \$exception (Throwable|null)."
    echo "-> QueueBusy: \$connection is now \$connectionName."
fi

echo
echo "=========================================="
echo "Scan complete."
