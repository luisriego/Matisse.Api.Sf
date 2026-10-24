#!/usr/bin/env bash
# ==============================================================================
# Cleanroom Gate Runner & Seal Consolidator — Matisse.Api.Sf
# ==============================================================================
# Validates an atom against multi-tier quality gates, enforces the 3-strike
# circuit breaker, commits on the atom branch, tags `seal-<atom-id>`, merges
# (--ff-only) into `<stage-id>`, records progress in docs/stages/log.md, and
# seals the stage (`seal-<stage-id>`) when all its atoms are complete.
#
# Usage:
#   ./tools/cleanroom/gate.sh <stage-id> <atom-id> [--with-mutation] [--push-on-complete]
# ==============================================================================
set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

if [[ $# -lt 2 ]]; then
    echo -e "${RED}Usage: $0 <stage-id> <atom-id> [--with-mutation] [--push-on-complete]${NC}" >&2
    exit 1
fi

STAGE_ID="$1"
ATOM_ID="$2"
shift 2

WITH_MUTATION=false
PUSH_ON_COMPLETE=false

for arg in "$@"; do
    case "${arg}" in
        --with-mutation)
            WITH_MUTATION=true
            ;;
        --push-on-complete)
            PUSH_ON_COMPLETE=true
            ;;
        *)
            echo -e "${RED}Unknown flag: ${arg}${NC}" >&2
            exit 1
            ;;
    esac
done

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
STAGES_JSON="${REPO_ROOT}/tools/cleanroom/stages.json"
LOG_FILE="${REPO_ROOT}/docs/stages/log.md"
EXEC_PHP="${REPO_ROOT}/tools/cleanroom/exec_php.sh"
STATE_DIR="${REPO_ROOT}/tools/cleanroom/.state"
FAILURE_FILE="${STATE_DIR}/${ATOM_ID}.failures"

mkdir -p "${STATE_DIR}" "$(dirname "${LOG_FILE}")"
cd "${REPO_ROOT}"

ATOM_JSON=$(jq -c ".stages[] | select(.id == \"${STAGE_ID}\") | .atoms[] | select(.id == \"${ATOM_ID}\")" "${STAGES_JSON}")
if [[ -z "${ATOM_JSON}" ]]; then
    echo -e "${RED}ERROR: Atom '${ATOM_ID}' not found in stage '${STAGE_ID}' inside stages.json.${NC}" >&2
    exit 1
fi

SPEC_ATOM=$(echo "${ATOM_JSON}" | jq -r '.spec_atom')
ATOM_LEVEL=$(echo "${ATOM_JSON}" | jq -r '.level')
COVERS_FILE=$(echo "${ATOM_JSON}" | jq -r '.covers')
TEST_FILE=$(echo "${ATOM_JSON}" | jq -r '.test_file')

record_oracle_failure() {
    local reason="$1"
    local count=0
    if [[ -f "${FAILURE_FILE}" ]]; then
        count=$(cat "${FAILURE_FILE}")
    fi
    count=$((count + 1))
    echo "${count}" > "${FAILURE_FILE}"

    echo -e "${RED}${BOLD}==============================================================================${NC}"
    echo -e "${RED}${BOLD}  GATE FAILED (${count}/3) — ${ATOM_ID}: ${reason}${NC}"
    echo -e "${RED}${BOLD}==============================================================================${NC}"

    if [[ ${count} -ge 3 ]]; then
        local ts
        ts=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
        echo "| ${ts} | ${STAGE_ID} | ${ATOM_ID} | BLOCKED | 3 consecutive oracle failures (${reason}) |" >> "${LOG_FILE}"
        echo -e "${RED}${BOLD}  CIRCUIT BREAKER TRIGGERED: 3 consecutive failures. Atom marked BLOCKED.${NC}"
        exit 3
    fi
    exit 1
}

echo -e "${CYAN}${BOLD}==============================================================================${NC}"
echo -e "${CYAN}${BOLD}  CLEANROOM GATE — Stage: ${STAGE_ID} | Atom: ${ATOM_ID} (${SPEC_ATOM})${NC}"
echo -e "${CYAN}${BOLD}==============================================================================${NC}"

# ------------------------------------------------------------------------------
# Gate 0: Allowlist & Scope Check
# ------------------------------------------------------------------------------
echo -e "\n${YELLOW}[Gate 0/5] Checking modified files against Atom allowlist...${NC}"
ALLOWLIST=$(echo "${ATOM_JSON}" | jq -r '.allowlist[]')

CHANGED_FILES=$(git status --porcelain | awk '{print $2}' || true)
for file in ${CHANGED_FILES}; do
    # Always allow docs/stages, docs/specs, and tools/cleanroom metadata
    if [[ "${file}" == docs/stages/* ]] || [[ "${file}" == docs/specs/* ]] || [[ "${file}" == tools/cleanroom/* ]]; then
        continue
    fi
    allowed=false
    while IFS= read -r pattern; do
        # Support glob patterns (e.g. "migrations/*") by unquoting the pattern
        unquoted_pattern="${pattern//\"/}"
        if [[ "${file}" == ${unquoted_pattern} ]] || [[ "${file}" == "${unquoted_pattern}" ]]; then
            allowed=true
            break
        fi
    done <<< "${ALLOWLIST}"

    if [[ "${allowed}" == false ]]; then
        record_oracle_failure "File '${file}' is outside the atom allowlist"
    fi
done
echo -e "${GREEN}  OK: Working tree changes respect allowlist.${NC}"

# ------------------------------------------------------------------------------
# Gate 1: Required Artifacts Check
# ------------------------------------------------------------------------------
echo -e "\n${YELLOW}[Gate 1/5] Verifying required artifacts exist...${NC}"
if [[ ! -f "${REPO_ROOT}/${COVERS_FILE}" ]]; then
    record_oracle_failure "Missing covered production artifact: ${COVERS_FILE}"
fi
if [[ ! -f "${REPO_ROOT}/${TEST_FILE}" ]]; then
    record_oracle_failure "Missing test artifact: ${TEST_FILE}"
fi
echo -e "${GREEN}  OK: '${COVERS_FILE}' and '${TEST_FILE}' exist.${NC}"

# ------------------------------------------------------------------------------
# Gate 2: Test Oracle Execution
# ------------------------------------------------------------------------------
echo -e "\n${YELLOW}[Gate 2/5] Running PHPUnit Oracle (${ATOM_LEVEL}) -> ${TEST_FILE}...${NC}"
if ! "${EXEC_PHP}" vendor/bin/phpunit "${TEST_FILE}" --colors=never; then
    record_oracle_failure "Atom test file failed: ${TEST_FILE}"
fi
echo -e "${GREEN}  OK: Atom test file passed.${NC}"

# ------------------------------------------------------------------------------
# Gate 3: Static Analysis (PHPStan + PHP-CS-Fixer)
# ------------------------------------------------------------------------------
echo -e "\n${YELLOW}[Gate 3/5] Running Static Analysis (PHPStan + PHP-CS-Fixer)...${NC}"
if ! "${EXEC_PHP}" vendor/bin/phpstan analyse "${COVERS_FILE}" "${TEST_FILE}" --memory-limit=512M --no-progress; then
    record_oracle_failure "PHPStan failed on ${COVERS_FILE} or ${TEST_FILE}"
fi
if ! "${EXEC_PHP}" tools/php-cs-fixer/vendor/bin/php-cs-fixer fix "${COVERS_FILE}" --dry-run --diff --path-mode=intersection; then
    record_oracle_failure "PHP-CS-Fixer coding standard check failed"
fi
if ! "${EXEC_PHP}" tools/php-cs-fixer/vendor/bin/php-cs-fixer fix "${TEST_FILE}" --dry-run --diff --path-mode=intersection; then
    record_oracle_failure "PHP-CS-Fixer coding standard check failed"
fi
echo -e "${GREEN}  OK: Static analysis and code style passed.${NC}"

# ------------------------------------------------------------------------------
# Gate 4: Optional Mutation Testing (Infection)
# ------------------------------------------------------------------------------
if [[ "${WITH_MUTATION}" == true ]]; then
    echo -e "\n${YELLOW}[Gate 4/5] Running Infection Mutation Oracle on ${COVERS_FILE}...${NC}"
    if ! "${EXEC_PHP}" vendor/bin/infection --filter="${COVERS_FILE}" --threads=4 --no-progress; then
        record_oracle_failure "Infection mutation score below threshold on ${COVERS_FILE}"
    fi
    echo -e "${GREEN}  OK: Mutation testing passed.${NC}"
else
    echo -e "\n${YELLOW}[Gate 4/5] Mutation testing skipped (use --with-mutation to enable).${NC}"
fi

# Reset consecutive failure counter on green gates
rm -f "${FAILURE_FILE}"

# ------------------------------------------------------------------------------
# Gate 5: Git Seal & Fast-Forward Consolidation
# ------------------------------------------------------------------------------
echo -e "\n${YELLOW}[Gate 5/5] Sealing Atom and Fast-Forwarding Stage '${STAGE_ID}'...${NC}"

CURRENT_BRANCH=$(git branch --show-current)
if [[ "${CURRENT_BRANCH}" == "${ATOM_ID}" ]]; then
    git add "${COVERS_FILE}" "${TEST_FILE}"
    if ! git diff --cached --quiet; then
        git commit -m "feat(${STAGE_ID}): complete ${ATOM_ID} (${SPEC_ATOM})"
        echo -e "${GREEN}  Committed changes on '${ATOM_ID}'.${NC}"
    fi
    git tag -f "seal-${ATOM_ID}"
    echo -e "${GREEN}  Tagged 'seal-${ATOM_ID}'.${NC}"

    if git show-ref --verify --quiet "refs/heads/${STAGE_ID}"; then
        git checkout "${STAGE_ID}" --quiet
        git merge --ff-only "${ATOM_ID}"
        echo -e "${GREEN}  Fast-forwarded '${STAGE_ID}' to '${ATOM_ID}'.${NC}"
    fi
fi

COMMIT_HASH=$(git rev-parse --short HEAD)
TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
echo "| ${TIMESTAMP} | ${STAGE_ID} | ${ATOM_ID} | SEALED | ${COMMIT_HASH} |" >> "${LOG_FILE}"

if [[ "$(git branch --show-current)" == "${STAGE_ID}" ]]; then
    git add "${LOG_FILE}"
    if ! git diff --cached --quiet; then
        git commit -m "chore(${STAGE_ID}): record seal for ${ATOM_ID}"
    fi
fi

# Check if all atoms in STAGE_ID are sealed
STAGE_ATOMS=$(jq -r ".stages[] | select(.id == \"${STAGE_ID}\") | .atoms[].id" "${STAGES_JSON}")
ALL_STAGE_SEALED=true
for a_id in ${STAGE_ATOMS}; do
    if ! grep -q "| ${a_id} | SEALED |" "${LOG_FILE}" 2>/dev/null; then
        ALL_STAGE_SEALED=false
        break
    fi
done

if [[ "${ALL_STAGE_SEALED}" == true ]]; then
    git tag -f "seal-${STAGE_ID}"
    echo -e "${GREEN}${BOLD}  STAGE COMPLETE: Tagged 'seal-${STAGE_ID}'.${NC}"
fi

# Check if all stages are sealed and --push-on-complete was requested
ALL_ATOMS=$(jq -r '.stages[].atoms[].id' "${STAGES_JSON}")
ALL_PROJECT_SEALED=true
for a_id in ${ALL_ATOMS}; do
    if ! grep -q "| ${a_id} | SEALED |" "${LOG_FILE}" 2>/dev/null; then
        ALL_PROJECT_SEALED=false
        break
    fi
done

if [[ "${ALL_PROJECT_SEALED}" == true ]] && [[ "${PUSH_ON_COMPLETE}" == true ]]; then
    STAGE_BRANCHES=$(jq -r '.stages[].id' "${STAGES_JSON}" | tr '\n' ' ')
    echo -e "${CYAN}${BOLD}  Pushing all sealed stages and tags to origin...${NC}"
    # shellcheck disable=SC2086
    git push --tags origin ${STAGE_BRANCHES}
fi

echo -e "${GREEN}${BOLD}==============================================================================${NC}"
echo -e "${GREEN}${BOLD}  GATE PASSED & SEALED: ${ATOM_ID} (${COMMIT_HASH})${NC}"
echo -e "${GREEN}${BOLD}==============================================================================${NC}"
