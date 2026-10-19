# Sistema Atómico Cleanroom TDD (`Matisse.Api.Sf`)

Este documento especifica la arquitectura y mejoras del **Sistema Atómico Cleanroom TDD** para `Matisse.Api.Sf`. El objetivo es descomponer cada especificación en unidades indivisibles de trabajo (**átomos**), garantizando **SRP**, **DRY**, trazabilidad en Git y verificación automática sin intervención humana entre átomos.

---

## 1. Flujo del Ciclo de Vida Atómico

```
┌──────────────────┐
│ docs/stages/     │
│ log.md +         │
│ stages.json      │
└────────┬─────────┘
         │ 1. Lee el primer átomo no sellado (SEALED)
         ▼
┌──────────────────────────────────────────────────────────┐
│ tools/cleanroom/next.sh --cat                            │
│  • Asegura/crea rama de fase: stage-XX                   │
│  • Crea/cambia a rama dedicada: atom-YY-<slug>           │
│  • Imprime la tarjeta: docs/stages/stage-XX-atom-YY.md   │
└────────┬─────────────────────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────────────────────┐
│ Micro-ciclo TDD del Átomo                                │
│  • [RED]      Escribir/ajustar test del átomo            │
│               - Modo "tdd": el test DEBE fallar primero  │
│               - Modo "characterization": fija invariante │
│  • [GREEN]    Código mínimo en src/ (si aplica)          │
│  • [REFACTOR] Limpieza SRP / DRY dentro de la allowlist  │
└────────┬─────────────────────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────────────────────┐
│ tools/cleanroom/gate.sh <stage-id> <atom-id>             │
│  1. Scope Boundary Check (git status vs allowlist)       │
│  2. Artifact Existence Check (tests[] + sources[])       │
│  3. Oráculos Declarativos en orden de velocidad/rigor:   │
│     a) PHP-CS-Fixer (--dry-run --diff)                   │
│     b) PHPStan (nivel 8 sobre archivos del átomo)        │
│     c) PHPUnit (unit / integration / acceptance / arch)  │
│     d) Infection Mutation Testing (cuando aplica)        │
│  4. Circuit Breaker: 3 fallos consecutivos => BLOCKED    │
│  5. Sello y Consolidación Git:                           │
│     • Commit en rama atom-YY-<slug>                      │
│     • Tag inmutable: seal-atom-YY-<slug>                 │
│     • Registro **SEALED** en docs/stages/log.md          │
│     • Fast-forward merge hacia rama stage-XX             │
│     • Si es el último átomo de stage-XX: tag seal-stage  │
└────────┬─────────────────────────────────────────────────┘
         │
         ├──► ¿Quedan átomos en la fase/spec? ──► Sí ──► Vuelve a next.sh --cat (0 interacción humana)
         │
         └──► ¿Todas las fases selladas?      ──► Sí ──► git push --tags origin <branches>
```

---

## 2. Mejoras Incorporadas al Sistema Atómico en `Matisse.Api.Sf`

Respecto al diseño base, se han incorporado **7 mejoras de ingeniería** para eliminar fricciones operativas y elevar el rigor de calidad:

### Mejora 1 — Dualidad Explícita de Modo (`tdd` vs `characterization`)
En `Matisse.Api.Sf` conviven dos tipos de átomos:
- **`characterization` (Specs `implementada` / `ratificada` en `hecho.md`)**: El código de producción ya existe en `src/Context/*`. El átomo consiste en escribir o endurecer el test (unitario, integración o aceptación) que blinda la regla de negocio, verificar que pase en verde y que ningún mutante sobreviva.
- **`tdd` (Cambios nuevos o correcciones de comportamiento)**: El test se escribe primero y debe fallar por la razón de negocio esperada (**Red**) antes de modificar el archivo de producción en `src/` (**Green**).
Cada átomo en `tools/cleanroom/stages.json` declara `"mode": "characterization" | "tdd"` y `"level": "unit" | "integration" | "acceptance" | "architecture"`.

### Mejora 2 — Circuit Breaker Automático al 3er Fallo Consecutivo (`BLOCKED`)
- `gate.sh` inspecciona las últimas filas de `docs/stages/log.md` para el átomo activo.
- Si un oráculo falla **3 veces consecutivas** para el mismo `<atom-id>` sin un `SEALED` intermedio:
  1. Registra automáticamente una fila `**BLOCKED**` en `docs/stages/log.md`.
  2. Emite `STATUS: BLOCKED` en `tools/cleanroom/last_verdict.json`.
  3. Termina con código de salida `4` (`CIRCUIT_BREAKER_TRIPPED`), deteniendo inmediatamente el bucle autónomo para evitar *thrashing*.
- Además, `next.sh` detecta si el átomo actual está en estado `**BLOCKED**` y se detiene informando la causa hasta que se desbloquee con `tools/cleanroom/next.sh --reset-blocked`.

### Mejora 3 — Modo de Inspección Segura (`next.sh --status` / `--dry-run`)
- Invocar `tools/cleanroom/next.sh --status` o `tools/cleanroom/next.sh --dry-run` muestra el estado completo del progreso (fases selladas, átomos sellados, átomo activo y su tarjeta) **sin crear ni cambiar ramas Git**, permitiendo inspeccionar la cola en cualquier momento sin alterar el árbol de trabajo actual.
- Invocar `tools/cleanroom/next.sh --cat` (modo de ejecución normal del agente) gestiona las ramas `stage-XX` y `atom-YY-<slug>` y muestra la tarjeta lista para ejecutar.

### Mejora 4 — Auto-Detección del Runtime de Ejecución (Contenedor `matisse-app` vs Imagen Docker Efímera vs Host)
- `tools/cleanroom/exec_php.sh` abstrae cómo se invocan `phpunit`, `phpstan`, `php-cs-fixer` e `infection`:
  1. Si el contenedor `matisse-app` está corriendo (`docker ps`), ejecuta vía `docker exec -u $(id -u):$(id -g) matisse-app ...`.
  2. Si `php` 8.3 está instalado en el host local, lo usa directamente.
  3. Si `matisse-app` no está levantado y el test es unitario/estático (sin BD), puede usar el contenedor efímero PHP 8.3 disponible en Docker local (`docker run --rm ...`).

### Mejora 5 — Auto-Fix Previo Opcional de Estilo (`--auto-fix-style`)
- En la bitácora histórica de otros proyectos, más del 60% de los rechazos (`REJECTED`) de `gate.sh` se debían únicamente a detalles menores de formato de `php-cs-fixer` (espacios, imports ordenados, comas finales).
- `gate.sh` soporta `--auto-fix-style` (activo por defecto en modo autónomo): antes de evaluar el oráculo `code_style`, aplica `php-cs-fixer fix` exclusivamente sobre los ficheros declarados en `{sources}` y `{tests}` de la `allowlist` del átomo, y luego verifica con `--dry-run --diff`. Esto evita consumir intentos del Circuit Breaker por simples saltos de línea.

### Mejora 6 — Oráculo de No-Regresión de Baseline PHPStan
- Además de exigir **PHPStan Nivel 8** sobre los ficheros `{sources}` y `{tests}` tocados por el átomo, `gate.sh` ejecuta el análisis general (`composer analyze:phpstan`) para garantizar que el cambio no rompa ningún contrato en el resto del proyecto.

### Mejora 7 — Consolidación de Fases y Push Final Automatizado
- Cada vez que se sella un átomo en su rama `atom-<id>-<slug>`:
  1. Se etiqueta `seal-<atom-id>`.
  2. Se hace `git checkout stage-XX && git merge --ff-only atom-<id>-<slug>`.
  3. Si todos los átomos de `stage-XX` están sellados, se etiqueta `seal-stage-XX`.
- Cuando todas las etapas activas están selladas (o al invocar `tools/cleanroom/gate.sh --push-sealed`), se consolidan las ramas `stage-XX` y se ejecuta `git push --tags origin <branches>`.

---

## 3. Estructura de Archivos del Sistema Atómico

```
Matisse.Api.Sf/
├── docs/
│   ├── specs/
│   │   ├── README.md                        # Índice maestro de especificaciones
│   │   ├── hecho.md                         # Specs de lo implementado/ratificado y sus átomos base
│   │   ├── por-hacer.md                     # Specs bloqueadas o trabajo futuro (SPEC-BOL, SPEC-ABR...)
│   │   ├── ARQUITECTURA-Y-CONTEXTO.md       # Radiografía de los 13 Bounded Contexts y estado actual
│   │   ├── TESTING-STANDARD-INDUSTRIA.md    # Los 8 niveles de test estándar en la industria
│   │   ├── SISTEMA-ATOMICO-CLEANROOM.md     # Protocolo del sistema atómico mejorado (este documento)
│   │   └── CATALOGO-ATOMOS.md               # Especificación detallada Given/When/Then de cada átomo
│   └── stages/
│       ├── log.md                           # Bitácora inmutable de sellos (SEALED / REJECTED / BLOCKED)
│       ├── stage-01-atom-01.md              # Tarjeta de trabajo del átomo 01
│       ├── stage-01-atom-02.md              # Tarjeta de trabajo del átomo 02
│       └── ...
└── tools/
    └── cleanroom/
        ├── exec_php.sh                      # Wrapper inteligente de ejecución (Docker/Host)
        ├── next.sh                          # Dispatcher autónomo de etapas y átomos
        ├── gate.sh                          # Motor de validación, sellado y consolidación Git
        └── stages.json                      # Manifiesto declarativo de etapas, átomos, allowlists y oráculos
```

---

## 4. Anatomía de una Tarjeta de Átomo (`docs/stages/stage-XX-atom-YY.md`)

Cada tarjeta sigue una estructura estándar de 5 secciones (compacta, inequívoca y 100% determinista):

1. **Identificación y Metadatos**:
   - ID del Átomo (`atom-XX-<slug>`), Código de Spec (`INF-01`, `TEN-01`, etc.), Etapa (`stage-XX`), Nivel de Test (`unit` | `integration` | `acceptance` | `architecture`) y Modo (`characterization` | `tdd`).
2. **Especificación Given / When / Then (Regla Comprobable Única)**:
   - **Dado** (*Arrange*): Estado inicial exacto (unidades, importes en céntimos, fechas, roles).
   - **Cuando** (*Act*): Acción única ejecutada (método de dominio, comando/query o endpoint HTTP).
   - **Entonces** (*Assert*): Resultado observable exacto (céntimos por unidad, suma total, evento emitido, código HTTP o excepción lanzada).
3. **Ciclo Operativo (Red → Green → Refactor → Gate)**:
   - Comandos exactos para ejecutar el test individual y el gate.
4. **Frontera de Ámbito (`allowlist`)**:
   - Lista cerrada de ficheros de `tests/` y `src/` que el átomo tiene permiso para crear o modificar. Cualquier modificación fuera de esta lista provoca `SCOPE_VIOLATION` en `gate.sh`.
5. **Oráculos y Criterio de Sello**:
   - Lista de oráculos que deben pasar con código `0` y límite de 3 intentos antes de `BLOCKED`.
