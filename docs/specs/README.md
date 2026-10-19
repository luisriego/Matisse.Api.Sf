# Specs de Matisse

Catálogo para partir el producto en átomos y, después, en tests. Este directorio no contiene tests.

Fuente de verdad de negocio: las specs con estado **ratificada**. El código puede ir por delante (hay máquina de estados de cobro) o por detrás (una ficha aún no está escrita). `docs/NEXTSTEPS.md` describe un circuito de aprobación que esta carpeta descarta.

## Estados

| Estado | Significado |
|---|---|
| ratificada | Decisión del 18 oct 2026 o regla ya fijada en dominio y aceptada |
| implementada | Hay código. La ficha de negocio está inferida del código, no ratificada frase a frase |
| bloqueada | No se escribe el átomo hasta cerrar la decisión o el contrato externo |
| descartada | No se construye |

## Dónde está cada cosa

- Hecho: [hecho.md](hecho.md)
- Por hacer y bloqueado: [por-hacer.md](por-hacer.md)
- Contexto técnico y arquitectura del proyecto (13 Bounded Contexts): [ARQUITECTURA-Y-CONTEXTO.md](ARQUITECTURA-Y-CONTEXTO.md)
- Estándar industrial de testing (8 niveles): [TESTING-STANDARD-INDUSTRIA.md](TESTING-STANDARD-INDUSTRIA.md)
- Sistema Atómico Cleanroom (`next.sh` / `gate.sh`): [SISTEMA-ATOMICO-CLEANROOM.md](SISTEMA-ATOMICO-CLEANROOM.md)
- Catálogo completo de Fases y Átomos (`stage-01`..`stage-08`, `atom-01`..`atom-32`): [CATALOGO-ATOMOS.md](CATALOGO-ATOMOS.md)

## Átomo

Un átomo es una sola regla comprobable. Se extrae de una spec; no se inventa al escribir el test.

```
ID: INF-03
Spec: SPEC-INF
Nivel: unit | integration | acceptance
Dado: estado previo
Cuando: acción única
Entonces: resultado observable
Cubre: clase o endpoint
Test: pendiente
```

## Niveles de test en este repositorio

Los tres niveles salen de PHPUnit. No hace falta otro framework para empezar.

| Nivel | Qué demuestra | Dónde vive hoy | Suite |
|---|---|---|---|
| unit | Dominio, value objects y handlers con dependencias dobladas. Object Mother. | `tests/Context/*/Domain`, `tests/Context/*/Application` | `unit` |
| integration | HTTP autenticado y persistencia real | `tests/Context/*/Infrastructure` | `authenticated_integration` |
| acceptance | Un átomo de negocio de punta a punta contra la API | Aún no hay suite propia. Se añadirá cuando se extraigan los átomos | por crear |

Complementos que el proyecto ya tiene y no sustituyen a los tres niveles: PHPStan, PHP CS Fixer y `composer test:mutation` sobre el dominio crítico.

## Orden del siguiente paso

1. Extraer los átomos marcados en [hecho.md](hecho.md), empezando por SPEC-INF.
2. Escribir el test que falla, en el nivel indicado, antes del cambio de producción que aún no exista.
3. No abrir átomos de [por-hacer.md](por-hacer.md) mientras la fila siga bloqueada.
