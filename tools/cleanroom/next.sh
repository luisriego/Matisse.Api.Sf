#!/usr/bin/env bash
# ==============================================================================
# Cleanroom Dispatcher — Next Atom Selector (Matisse.Api.Sf)
# ==============================================================================
# Reads tools/cleanroom/stages.json and docs/stages/log.md to find the next
# unsealed atom, creates/switches git branches (unless --status/--dry-run),
# and displays the work card.
#
# Usage:
#   ./tools/cleanroom/next.sh              # Switch to next atom branch & show summary
#   ./tools/cleanroom/next.sh --cat        # Switch & cat the full atom card
#   ./tools/cleanroom/next.sh --status     # Dry-run: show status without touching git
#   ./tools/cleanroom/next.sh --json       # Dry-run: emit JSON payload for agents
# ==============================================================================
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
STAGES_JSON="${REPO_ROOT}/tools/cleanroom/stages.json"
LOG_FILE="${REPO_ROOT}/docs/stages/log.md"
STATE_DIR="${REPO_ROOT}/tools/cleanroom/.state"

RED='\033[0;31m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
BOLD='\033[1m'
NC='\033[0m'

SHOW_CARD=false
DRY_RUN=false
JSON_OUTPUT=false

for arg in "$@"; do
    case "${arg}" in
        --cat)
            SHOW_CARD=true
            ;;
        --status|--dry-run)
            DRY_RUN=true
            ;;
        --json)
            DRY_RUN=true
            JSON_OUTPUT=true
            ;;
        *)
            echo -e "${RED}Unknown flag: ${arg}${NC}" >&2
            echo "Usage: $0 [--cat] [--status|--dry-run] [--json]" >&2
            exit 1
            ;;
    esac
done

mkdir -p "$(dirname "${LOG_FILE}")" "${STATE_DIR}"
if [[ ! -f "${LOG_FILE}" ]]; then
    cat > "${LOG_FILE}" << 'EOF'
# Cleanroom Gate Log — Matisse.Api.Sf

| Timestamp | Stage | Atom | Status | Commit |
|-----------|-------|------|--------|--------|
EOF
fi

is_atom_sealed() {
    local atom_id="$1"
    grep -q "| ${atom_id} | SEALED |" "${LOG_FILE}" 2>/dev/null
}

is_atom_blocked() {
    local atom_id="$1"
    grep -q "| ${atom_id} | BLOCKED |" "${LOG_FILE}" 2>/dev/null
}

TOTAL_STAGES=$(jq '.stages | length' "${STAGES_JSON}")
TOTAL_ATOMS=$(jq '[.stages[].atoms[]] | length' "${STAGES_JSON}")
SEALED_COUNT=0
BLOCKED_ATOM=""
BLOCKED_STAGE=""

NEXT_STAGE_ID=""
NEXT_STAGE_NAME=""
NEXT_STAGE_SPEC=""
NEXT_ATOM_ID=""
NEXT_ATOM_SPEC=""
NEXT_ATOM_NAME=""
NEXT_ATOM_LEVEL=""
NEXT_ATOM_MODE=""
NEXT_ATOM_CARD=""
NEXT_ATOM_COVERS=""
NEXT_ATOM_TEST=""
NEXT_STAGE_INDEX=-1

for (( s=0; s<TOTAL_STAGES; s++ )); do
    STAGE_ID=$(jq -r ".stages[${s}].id" "${STAGES_JSON}")
    STAGE_NAME=$(jq -r ".stages[${s}].name" "${STAGES_JSON}")
    STAGE_SPEC=$(jq -r ".stages[${s}].spec" "${STAGES_JSON}")
    ATOM_COUNT=$(jq ".stages[${s}].atoms | length" "${STAGES_JSON}")

    for (( a=0; a<ATOM_COUNT; a++ )); do
        ATOM_ID=$(jq -r ".stages[${s}].atoms[${a}].id" "${STAGES_JSON}")

        if is_atom_sealed "${ATOM_ID}"; then
            SEALED_COUNT=$((SEALED_COUNT + 1))
            continue
        fi

        if is_atom_blocked "${ATOM_ID}" && [[ -z "${BLOCKED_ATOM}" ]]; then
            BLOCKED_ATOM="${ATOM_ID}"
            BLOCKED_STAGE="${STAGE_ID}"
        fi

        if [[ -z "${NEXT_ATOM_ID}" ]]; then
            NEXT_STAGE_ID="${STAGE_ID}"
            NEXT_STAGE_NAME="${STAGE_NAME}"
            NEXT_STAGE_SPEC="${STAGE_SPEC}"
            NEXT_ATOM_ID="${ATOM_ID}"
            NEXT_ATOM_SPEC=$(jq -r ".stages[${s}].atoms[${a}].spec_atom" "${STAGES_JSON}")
            NEXT_ATOM_NAME=$(jq -r ".stages[${s}].atoms[${a}].name" "${STAGES_JSON}")
            NEXT_ATOM_LEVEL=$(jq -r ".stages[${s}].atoms[${a}].level" "${STAGES_JSON}")
            NEXT_ATOM_MODE=$(jq -r ".stages[${s}].atoms[${a}].mode" "${STAGES_JSON}")
            NEXT_ATOM_CARD=$(jq -r ".stages[${s}].atoms[${a}].card" "${STAGES_JSON}")
            NEXT_ATOM_COVERS=$(jq -r ".stages[${s}].atoms[${a}].covers" "${STAGES_JSON}")
            NEXT_ATOM_TEST=$(jq -r ".stages[${s}].atoms[${a}].test_file" "${STAGES_JSON}")
            NEXT_STAGE_INDEX=${s}
        fi
    done
done

if [[ "${JSON_OUTPUT}" == true ]]; then
    jq -n \
        --arg project "Matisse.Api.Sf" \
        --argjson total_stages "${TOTAL_STAGES}" \
        --argjson total_atoms "${TOTAL_ATOMS}" \
        --argjson sealed_atoms "${SEALED_COUNT}" \
        --arg blocked_atom "${BLOCKED_ATOM}" \
        --arg next_stage_id "${NEXT_STAGE_ID}" \
        --arg next_stage_name "${NEXT_STAGE_NAME}" \
        --arg next_atom_id "${NEXT_ATOM_ID}" \
        --arg next_atom_spec "${NEXT_ATOM_SPEC}" \
        --arg next_atom_name "${NEXT_ATOM_NAME}" \
        --arg next_atom_level "${NEXT_ATOM_LEVEL}" \
        --arg next_atom_mode "${NEXT_ATOM_MODE}" \
        --arg next_atom_card "${NEXT_ATOM_CARD}" \
        --arg next_atom_covers "${NEXT_ATOM_COVERS}" \
        --arg next_atom_test "${NEXT_ATOM_TEST}" \
        '{
            project: $project,
            progress: {
                total_stages: $total_stages,
                total_atoms: $total_atoms,
                sealed_atoms: $sealed_atoms,
                pending_atoms: ($total_atoms - $sealed_atoms)
            },
            blocked_atom: (if $blocked_atom == "" then null else $blocked_atom end),
            next: (if $next_atom_id == "" then null else {
                stage_id: $next_stage_id,
                stage_name: $next_stage_name,
                atom_id: $next_atom_id,
                spec_atom: $next_atom_spec,
                name: $next_atom_name,
                level: $next_atom_level,
                mode: $next_atom_mode,
                card: $next_atom_card,
                covers: $next_atom_covers,
                test_file: $next_atom_test
            } end)
        }'
    exit 0
fi

if [[ -n "${BLOCKED_ATOM}" ]]; then
    echo -e "${RED}${BOLD}==============================================================================${NC}"
    echo -e "${RED}${BOLD}  CIRCUIT BREAKER ACTIVE: ${BLOCKED_ATOM} (${BLOCKED_STAGE}) is BLOCKED${NC}"
    echo -e "${RED}${BOLD}==============================================================================${NC}"
    echo -e "${YELLOW}An oracle failed 3 consecutive times on this atom.${NC}"
    echo -e "Inspect ${LOG_FILE} and resolve the issue before removing the BLOCKED entry."
    exit 3
fi

if [[ -z "${NEXT_ATOM_ID}" ]]; then
    echo -e "${GREEN}${BOLD}==============================================================================${NC}"
    echo -e "${GREEN}${BOLD}  ALL ${TOTAL_STAGES} STAGES AND ${TOTAL_ATOMS} ATOMS ARE SEALED.${NC}"
    echo -e "${GREEN}${BOLD}==============================================================================${NC}"
    exit 0
fi

echo -e "${CYAN}${BOLD}==============================================================================${NC}"
echo -e "${CYAN}${BOLD}  CLEANROOM DISPATCHER — Matisse.Api.Sf${NC}"
echo -e "${CYAN}${BOLD}==============================================================================${NC}"
echo -e "  Progress:  ${GREEN}${SEALED_COUNT}/${TOTAL_ATOMS}${NC} atoms sealed across ${TOTAL_STAGES} stages"
echo -e "  Stage:     ${YELLOW}${NEXT_STAGE_ID}${NC} — ${NEXT_STAGE_NAME}"
echo -e "  Next Atom: ${GREEN}${NEXT_ATOM_ID}${NC} (${NEXT_ATOM_SPEC})"
echo -e "  Title:     ${NEXT_ATOM_NAME}"
echo -e "  Level:     ${BOLD}${NEXT_ATOM_LEVEL}${NC} (mode: ${NEXT_ATOM_MODE})"
echo -e "  Covers:    ${NEXT_ATOM_COVERS}"
echo -e "  Test File: ${NEXT_ATOM_TEST}"
echo -e "  Card:      ${NEXT_ATOM_CARD}"
echo -e "${CYAN}${BOLD}==============================================================================${NC}"

if [[ "${DRY_RUN}" == true ]]; then
    echo -e "  ${YELLOW}[DRY-RUN / STATUS MODE] Git branches were not modified.${NC}"
    if [[ "${SHOW_CARD}" == true ]] && [[ -f "${REPO_ROOT}/${NEXT_ATOM_CARD}" ]]; then
        echo ""
        cat "${REPO_ROOT}/${NEXT_ATOM_CARD}"
    fi
    exit 0
fi

cd "${REPO_ROOT}"

# Ensure stage branch exists
if ! git show-ref --verify --quiet "refs/heads/${NEXT_STAGE_ID}"; then
    if [[ ${NEXT_STAGE_INDEX} -eq 0 ]]; then
        BASE_BRANCH="main"
    else
        PREV_INDEX=$((NEXT_STAGE_INDEX - 1))
        BASE_BRANCH=$(jq -r ".stages[${PREV_INDEX}].id" "${STAGES_JSON}")
    fi
    echo -e "${YELLOW}-> Creating stage branch '${NEXT_STAGE_ID}' from '${BASE_BRANCH}'...${NC}"
    git checkout -b "${NEXT_STAGE_ID}" "${BASE_BRANCH}"
else
    git checkout "${NEXT_STAGE_ID}" --quiet
fi

# Ensure atom branch exists
if ! git show-ref --verify --quiet "refs/heads/${NEXT_ATOM_ID}"; then
    echo -e "${YELLOW}-> Creating atom branch '${NEXT_ATOM_ID}' from '${NEXT_STAGE_ID}'...${NC}"
    git checkout -b "${NEXT_ATOM_ID}" "${NEXT_STAGE_ID}"
else
    echo -e "${YELLOW}-> Switching to existing atom branch '${NEXT_ATOM_ID}'...${NC}"
    git checkout "${NEXT_ATOM_ID}" --quiet
fi

echo ""
echo -e "  Active branch: ${GREEN}$(git branch --show-current)${NC}"
echo -e "  When ready, seal with:"
echo -e "    ${BOLD}./tools/cleanroom/gate.sh ${NEXT_STAGE_ID} ${NEXT_ATOM_ID}${NC}"
echo ""

if [[ "${SHOW_CARD}" == true ]] && [[ -f "${REPO_ROOT}/${NEXT_ATOM_CARD}" ]]; then
    cat "${REPO_ROOT}/${NEXT_ATOM_CARD}"
fi
