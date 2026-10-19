#!/usr/bin/env bash
# ==============================================================================
# Cleanroom Work Card Generator — Matisse.Api.Sf
# ==============================================================================
# Generates all Atom work cards (docs/stages/stage-XX-atom-YY.md) and initializes
# docs/stages/log.md from tools/cleanroom/stages.json.
# ==============================================================================
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
STAGES_JSON="${REPO_ROOT}/tools/cleanroom/stages.json"
STAGES_DIR="${REPO_ROOT}/docs/stages"
LOG_FILE="${STAGES_DIR}/log.md"

mkdir -p "${STAGES_DIR}"

if [[ ! -f "${LOG_FILE}" ]]; then
    cat > "${LOG_FILE}" << 'EOF'
# Cleanroom Gate Log — Matisse.Api.Sf

| Timestamp | Stage | Atom | Status | Commit |
|-----------|-------|------|--------|--------|
EOF
fi

STAGE_COUNT=$(jq '.stages | length' "${STAGES_JSON}")

for (( s=0; s<STAGE_COUNT; s++ )); do
    STAGE_ID=$(jq -r ".stages[${s}].id" "${STAGES_JSON}")
    STAGE_NAME=$(jq -r ".stages[${s}].name" "${STAGES_JSON}")
    STAGE_SPEC=$(jq -r ".stages[${s}].spec" "${STAGES_JSON}")
    ATOM_COUNT=$(jq ".stages[${s}].atoms | length" "${STAGES_JSON}")

    for (( a=0; a<ATOM_COUNT; a++ )); do
        ATOM_ID=$(jq -r ".stages[${s}].atoms[${a}].id" "${STAGES_JSON}")
        SPEC_ATOM=$(jq -r ".stages[${s}].atoms[${a}].spec_atom" "${STAGES_JSON}")
        ATOM_NAME=$(jq -r ".stages[${s}].atoms[${a}].name" "${STAGES_JSON}")
        LEVEL=$(jq -r ".stages[${s}].atoms[${a}].level" "${STAGES_JSON}")
        MODE=$(jq -r ".stages[${s}].atoms[${a}].mode" "${STAGES_JSON}")
        CARD_REL=$(jq -r ".stages[${s}].atoms[${a}].card" "${STAGES_JSON}")
        COVERS=$(jq -r ".stages[${s}].atoms[${a}].covers" "${STAGES_JSON}")
        TEST_FILE=$(jq -r ".stages[${s}].atoms[${a}].test_file" "${STAGES_JSON}")
        GIVEN=$(jq -r ".stages[${s}].atoms[${a}].given" "${STAGES_JSON}")
        WHEN=$(jq -r ".stages[${s}].atoms[${a}].when" "${STAGES_JSON}")
        THEN=$(jq -r ".stages[${s}].atoms[${a}].then" "${STAGES_JSON}")
        ALLOWLIST_MD=$(jq -r ".stages[${s}].atoms[${a}].allowlist[] | \"- \`\" + . + \"\`\"" "${STAGES_JSON}")

        CARD_PATH="${REPO_ROOT}/${CARD_REL}"
        cat > "${CARD_PATH}" << EOF
# Work Card: \`${ATOM_ID}\` (${SPEC_ATOM})

- **Stage**: \`${STAGE_ID}\` — ${STAGE_NAME}
- **Spec**: \`${STAGE_SPEC}\` (\`${SPEC_ATOM}\`)
- **Level**: \`${LEVEL}\`
- **Execution Mode**: \`${MODE}\`
- **Branch**: \`${ATOM_ID}\` (from \`${STAGE_ID}\`)

---

## 1. Regla Comprobable (BDD Specification)

\`\`\`yaml
ID: ${SPEC_ATOM}
Spec: ${STAGE_SPEC}
Nivel: ${LEVEL}
Modo: ${MODE}
Dado: ${GIVEN}
Cuando: ${WHEN}
Entonces: ${THEN}
Cubre: ${COVERS}
Test: ${TEST_FILE}
\`\`\`

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

${ALLOWLIST_MD}

---

## 3. Ciclo Cleanroom (\`Red → Green → Refactor → Gate → Seal\`)

1. **Red / Characterization**:
   - Si el modo es \`tdd\`: escribir primero el test en \`${TEST_FILE}\` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es \`characterization\`: escribir o aislar el caso específico de \`${SPEC_ATOM}\` en \`${TEST_FILE}\` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en \`${COVERS}\` respetando SRP, DRY y Arquitectura Hexagonal (\`declare(strict_types=1);\`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en \`tests/Context/*/Domain/*Mother.php\` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   \`\`\`bash
   ./tools/cleanroom/gate.sh ${STAGE_ID} ${ATOM_ID}
   \`\`\`
EOF
    done
done

echo "Generated all work cards in ${STAGES_DIR}."
