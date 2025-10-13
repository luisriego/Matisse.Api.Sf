Plan de próximos pasos en forma de roadmap práctico, priori.

1) Reglas de negocio para creación de slips (prioridad alta)
- Definir el ciclo de vida del slip
    - Estados: draft -> submitted -> validated -> posted -> cancelled/rejected.
    - Transiciones válidas y quién las puede ejecutar.
- Validaciones obligatorias al crear/actualizar
    - Campos requeridos, formato y rangos.
    - Reglas contables/numéricas (p.ej., sumas que cuadren, totales vs. detalle).
    - Idempotencia: evitar duplicados si se reintenta la misma operación (clave natural o idempotency-key).
- Reglas temporales y de negocio
    - Ventanas de tiempo permitidas (fechas válidas, cierres de periodo).
    - Límites por rol/permiso (máximo importe, tipos de slip permitidos).
    - Moneda, redondeos, tipos de cambio (si aplica).
- Errores y mensajes
    - Catálogo de errores de dominio claros y mapeados a respuestas API.
- Auditoría y eventos
    - Qué eventos de dominio se emiten por cada cambio y qué metadatos incluyen.
- Aceptación
    - Criterios Given/When/Then para cada regla; casos felices, errores y edge cases.

2) Backend/API (prioridad alta)
- Modelo y casos de uso
    - Consolidar agregados/VOs del slip y comandos/queries por vertical slice: CreateSlip, SubmitSlip, ValidateSlip, PostSlip, CancelSlip, GetSlip(s).
- API pública
    - Endpoints y contrato: request/response estables, paginación, ordenación, filtros, correlación (request-id), versionado (v1).
    - Documentación OpenAPI y ejemplos de error.
- Persistencia y consistencia
    - Transacciones por comando; bloqueo y control de concurrencia (optimista).
    - Soft delete vs. cancelación de negocio.
- Integraciones
    - Preparar publicación de eventos para proyecciones/lecturas y notificaciones.
- Performance
    - Índices base, límites de payload, compresión y caching HTTP donde aplique.
- Aceptación
    - Tests de integración de repositorios y de casos de uso clave con base real de test.

3) Frontend: ¿ya hacerlo? Recomendación
- Sí, pero arrancando con lo mínimo viable alineado a los casos de uso cerrados de backend.
- Decisión tecnológica
    - Opción 1: SSR con Twig (rápida para backoffice interno).
    - Opción 2: SPA (React/Vue) si habrá interacciones ricas o escalabilidad de UI.
- Alcance MVP de UI
    - Listado de slips con filtros básicos.
    - Detalle del slip.
    - Flujo de creación/edición y envío.
    - Estados visuales y mensajes de error del dominio.
- Integración con API
    - Consumo del OpenAPI generado; un cliente tipado ayuda a evitar errores.
- UX y validaciones
    - Validación en cliente alineada con el servidor (sin duplicar reglas complejas).
    - Estados de carga, vacíos, reintentos e idempotencia desde UI.
- Aceptación
    - Prototipo navegable con 3–5 user flows testeados de extremo a extremo.

4) Calidad y pruebas (prioridad alta, transversal)
- Unit tests exhaustivos en dominio y handlers (casos felices/errores/bordes).
- Integration tests de repositorios y endpoints críticos.
- E2E para los principales flujos del frontend (si SPA).
- Cobertura objetivo 85–90% en dominio/aplicación.
- Análisis estático y estilo (PSR-12, linters, type hints).

5) Seguridad y control de acceso
- Autenticación y autorización por operaciones y estados del slip.
- Rate limiting, control de tamaño de payload, sanitización y validaciones de entrada.
- Registro de auditoría y trazabilidad (correlation-id).

6) Operaciones y entrega
- Entornos: dev, test/staging, prod; parametrización por entorno.
- Migraciones de base de datos y datos semilla.
- CI/CD: tests, calidad, seguridad y despliegue automatizados.
- Observabilidad: logs estructurados, métricas básicas y alertas.

7) Sugerencia de plan por sprints (ejemplo 2–3 semanas)
- Sprint 1
    - Cerrar reglas de negocio y contrato API para Create/Submit.
    - Implementar CreateSlip y SubmitSlip con validaciones y eventos.
    - Tests unitarios e integración; documentación OpenAPI.
- Sprint 2
    - Implementar Validate/Post/Cancel.
    - Índices y mejoras de rendimiento; catálogo de errores.
    - Prototipo frontend: listado, detalle y creación.
- Sprint 3
    - Endpoints de consulta avanzados (filtros, paginación).
    - E2E básicos del frontend; pulir UX y manejo de errores.
    - Observabilidad y hardening de seguridad; preparar release.

Decisiones que necesitaría de ti
- ¿Quiénes son los usuarios y qué permisos/roles tendrán en el flujo del slip?
- ¿Qué estados exactos y qué transiciones permitidas definimos?
- ¿Front con Twig (rápido) o SPA (mayor inversión y flexibilidad)?
- ¿Seguimos con SQLite inicialmente o planificamos migración a PostgreSQL/MySQL para producción?
- ¿Necesitamos multimoneda, multilenguaje o zonas horarias específicas?

Si te parece, comenzamos por cerrar el catálogo de reglas del slip y el contrato del endpoint de creación; con eso dejamos el backend listo para que el front pueda avanzar en paralelo con seguridad.

Definición operativa del ciclo de vida del slip que incluya: estados, transiciones, roles, reglas (guards), eventos y cómo implementarlo en el dominio (con opción de usar Symfony Workflow para visualización sin perder el control de negocio).

1) Estados del slip
- draft: creado, editable, sin compromiso.
- submitted: enviado para revisión; campos críticos congelados.
- validated: aprobado; listo para asentar/contabilizar.
- posted: asentado/contabilizado; estado final.
- cancelled: cancelado por decisión operativa (antes de posted).
- rejected: rechazado en revisión; requiere corrección.
- Opcional: rework como alias de draft tras rechazo; podemos modelar rejected -> draft para reintentos.

2) Transiciones válidas y actores
- draft -> submitted
    - Quién: Autor del slip o rol ROLE_AUTHOR/ROLE_USER con permiso create/submit-own.
    - Efecto: Congela campos críticos.
- submitted -> validated
    - Quién: Revisor rol ROLE_REVIEWER/ROLE_VALIDATOR.
    - Efecto: Marca conforme; prepara para posting.
- validated -> posted
    - Quién: Contable/Poster rol ROLE_ACCOUNTANT/ROLE_POSTER.
    - Efecto: Asienta en el libro mayor o sistema asociado; estado final.
- submitted -> rejected
    - Quién: Revisor rol ROLE_REVIEWER/ROLE_VALIDATOR (con motivo obligatorio).
    - Efecto: Devuelve a rework; opción 1: estado rejected hasta que se edite; opción 2: transición automática rejected -> draft.
- draft -> cancelled
    - Quién: Autor o Admin; motivo opcional.
- submitted -> cancelled
    - Quién: Admin o Autor si dentro de ventana de cancelación.
- validated -> cancelled
    - Quién: Admin; solo si no existen preasientos ni bloqueos externos.
- Estados finales: posted. Estados no finales pero bloqueantes: cancelled, rejected (hasta rework).
- Reingreso
    - rejected -> draft (rework)
    - draft (rework) -> submitted (resubmit)

3) Reglas (guards) por transición
- Globales
    - Idempotencia: no repetir operación con el mismo idempotency-key/externalRef.
    - Concurrencia: versión del agregado/ETag debe coincidir (optimistic locking).
    - Periodo abierto y fecha válida (no posterior a hoy si aplica).
    - Límites por rol: importe máximo, tipos de slip, monedas permitidas.
    - Unicidad: no duplicar externalRef por proveedor/periodo.
- draft -> submitted
    - Requeridos completos; documentos adjuntos si son obligatorios.
    - Balance/totalización consistente; redondeos correctos.
- submitted -> validated
    - No cambios desde el submit; firma o checklist del revisor completado.
    - Regla de segregación de funciones: el revisor no puede ser el autor si la política lo requiere.
- validated -> posted
    - Periodo contable abierto; asiento contable balanceado; tipo de cambio fijado.
    - Integraciones externas disponibles; política de reintento definida si falla posting.
- submitted -> rejected
    - Motivo obligatorio; se registran observaciones para rework.
- -> cancelled
    - Sin side-effects irreversibles; si existen, se requiere rollback o prohibir.

4) Eventos de dominio por transición
- SlipWasSubmitted
- SlipWasValidated
- SlipWasPosted
- SlipWasRejected (con reason)
- SlipWasCancelled (con reason)
- Opcional: SlipWasResubmitted
  Estos eventos alimentan proyecciones, notificaciones y auditoría.

5) Datos de auditoría a registrar
- actorId, actorRole, occurredAt, source/ip, idempotencyKey, previousState -> newState, reason (si aplica).

6) Implementación recomendada (dominio primero)
- Estado como Value Object/Enum
    - SlipStatus: Draft, Submitted, Validated, Posted, Cancelled, Rejected.
- Métodos de transición en el agregado
    - submit(actor, metadata), validate(actor), post(actor), reject(actor, reason), cancel(actor, reason), resubmit(actor).
    - Cada método: verifica estado actual, valida guards, cambia estado, congela/descongela campos cuando toque, registra evento.
- Autorización
    - Comprobación doble:
        - En aplicación/infra: voter/policy por operación y actor/rol.
        - En dominio: invariantes de segregación de funciones y límites de negocio.
- Concurrencia
    - Versión en el agregado y control optimista en repositorio.
- Idempotencia
    - Aceptar un idempotency-key en comandos; registrar ejecución y devolver mismo resultado si se reintenta.
- Persistencia
    - Guardar status, timestamps por hito (submittedAt, validatedAt, postedAt, cancelledAt, rejectedAt), actorIds y reason.

7) Opción: visualización con Symfony Workflow (opcional)
- Podemos añadir un workflow de tipo “state_machine” para visualizar y orquestar guards en infraestructura, pero mantener las reglas críticas en el dominio. Útil para diagramas y trazabilidad. Si no lo usamos, seguimos 100% dominio.

8) Comandos/Use Cases a crear
- SubmitSlipCommand, ValidateSlipCommand, PostSlipCommand, RejectSlipCommand, CancelSlipCommand, ResubmitSlipCommand.
- Queries: GetSlipByIdQuery, SearchSlipsQuery (con filtros por estado).

9) Matriz de transición resumida
- draft: submit, cancel
- submitted: validate, reject, cancel
- rejected: resubmit (-> draft), cancel
- validated: post, cancel
- posted: (final, sin transiciones)
- cancelled: (final, sin transiciones)
  Reglas exactas se ajustan a tus políticas; arriba están las por defecto recomendadas.

10) Criterios de aceptación (ejemplos)
- draft -> submitted
    - Dado un slip draft válido
    - Cuando el autor hace submit con idempotency-key K
    - Entonces el estado es submitted, se congelan los campos críticos y se emite SlipWasSubmitted; un segundo submit con K devuelve el mismo resultado sin duplicar.
- submitted -> validated
    - Dado un slip submitted
    - Cuando un revisor con permiso valida
    - Entonces estado validated y se emite SlipWasValidated; si el revisor es el autor y la política prohíbe, se rechaza con DomainException.
- validated -> posted
    - Dado un slip validated con periodo abierto
    - Cuando un poster asienta
    - Entonces estado posted y se emite SlipWasPosted; si falla el asiento externo, se mantiene validated y se informa error recuperable.
- submitted -> rejected
    - Dado un slip submitted
    - Cuando un revisor lo rechaza con motivo M
    - Entonces estado rejected y se emite SlipWasRejected con M.
- -> cancelled
    - Dado un slip no posted
    - Cuando un actor autorizado lo cancela con motivo C
    - Entonces estado cancelled y se emite SlipWasCancelled con C.

11) Qué necesito decidir contigo ahora
- ¿Permitimos cancelaciones en validated? ¿Solo admins?
- ¿Rechazado vuelve automáticamente a draft o mantiene estado rejected hasta edición?
- ¿Segregación de funciones obligatoria entre autor y revisor? ¿y entre revisor y poster?
- Ventana de cancelación tras submitted (p.ej., X horas/días).
- Campos que se congelan en submitted y cuáles siguen editables en rework.
- ¿Idempotency-key obligatorio en todas las transiciones mutables?

Si te parece, cerramos estas decisiones y preparo el detalle final de la matriz de transiciones y guards con los roles exactos. Luego lo trasladamos a comandos/handlers y a los tests de aceptación para cada transición.