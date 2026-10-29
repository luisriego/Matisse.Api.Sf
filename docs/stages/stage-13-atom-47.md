# Tarjeta de Etapa: stage-13 — Átomo FIX-10 (`atom-47-security-allow-resident-self-service-and-me`)

---

## 1. Peregrino & Mochila
- **Objetivo Atómico (SRP)**: FIX-10: Autogestión segura de perfil/password/avatar para ROLE_USER (anti-IDOR) y nuevo endpoint GET /api/v1/users/me
- **Stage**: `stage-13` — SPEC-FIX-SEC: Seguridad, Autogestión de Residente ROLE_USER y Endpoint /api/v1/users/me
- **Spec**: `SPEC-FIX-SEC` (`FIX-10`) | **Nivel**: `integration` | **Modo**: `tdd`
- **Rama dedicada**: `atom-47-security-allow-resident-self-service-and-me` (consolidación fast-forward en `stage-13`).
- **TDD Innegociable**: `RED -> GREEN -> REFACTOR -> GATE -> SELLO`.

---

## 2. Botas (Contexto & Especificación)
- **Artefacto principal bajo cobertura**: `src/Context/User/Infrastructure/Http/Controller/MeUserGetController.php`
- **Archivo de prueba objetivo**: `tests/Context/User/Infrastructure/Http/Controller/ResidentSelfServiceAndMeControllerTest.php`
- **Contrato BDD Comprobable**:
  - **Dado (Given)**: un usuario autenticado con ROLE_USER (residente, sin ROLE_SYNDIC) y otro usuario distinto en el sistema
  - **Cuando (When)**: el residente consulta GET /api/v1/users/me, actualiza su propio perfil PUT /api/v1/users/{su_id} o intenta modificar el perfil de otro usuario PUT /api/v1/users/{otro_id}
  - **Entonces (Then)**: GET /api/v1/users/me devuelve 200 OK con sus datos (id, email, name, roles, residentUnitId), PUT sobre su propio ID devuelve 204 y PUT sobre el ID de otro usuario sin ser síndico devuelve 403 Forbidden
- **Casos de prueba obligatorios en `tests/Context/User/Infrastructure/Http/Controller/ResidentSelfServiceAndMeControllerTest.php`**:
  1. `testMeEndpointReturnsAuthenticatedUserProfile`: Autentica un usuario residente (`ROLE_USER`), hace `GET /api/v1/users/me` y verifica `200 OK` con `id`, `email`, `name` y `roles`.
  2. `testResidentCanUpdateOwnProfileAndChangeOwnPassword`: Verifica que un residente con `ROLE_USER` pueda invocar `PUT /api/v1/users/{ownId}` (`204`) y `PATCH /api/v1/users/change-password` (`200`).
  3. `testResidentCannotUpdateAnotherUsersProfileReturns403`: Verifica que cuando un residente con `ROLE_USER` invoca `PUT /api/v1/users/{otherUserId}`, el controlador rechaza la operación con `403 Forbidden` (anti-IDOR).

---

## 3. Ropa (Modus Operandi)
1. **Test (RED / Characterization)**:
   - Crea o actualiza `tests/Context/User/Infrastructure/Http/Controller/ResidentSelfServiceAndMeControllerTest.php` con los casos de prueba obligatorios de la sección 2.
   - Ejecuta el oráculo de tests:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/User/Infrastructure/Http/Controller/ResidentSelfServiceAndMeControllerTest.php
     ```
2. **Implementación (GREEN)**:
   - En `config/packages/security.yaml`: antes de la regla general `methods: [POST, PUT, PATCH, DELETE], roles: ROLE_SYNDIC`, añadir reglas explícitas con `roles: ROLE_USER` para `^/api/v1/users/change-password` (`PATCH`), `^/api/v1/users/[0-9a-f-]+$` (`PUT`) y `^/api/v1/users/[0-9a-f-]+/avatar$` (`POST`).
   - En `UpdateUserController` y `UploadAvatarController`: inyectar `#[CurrentUser] ?UserInterface $currentUser = null` y verificar que si `$currentUser instanceof User && $currentUser->id() !== $id && !in_array('ROLE_SYNDIC', $currentUser->getRoles(), true)`, devuelva `403 Forbidden` (o lance `AccessDeniedException`).
   - Crear `src/Context/User/Infrastructure/Http/Controller/MeUserGetController.php` y registrar la ruta `user_me` (`GET /api/v1/users/me`) en `src/Context/User/Infrastructure/Http/routes.yaml` ANTES de `user_find` (`/api/v1/users/{id}`) devolviendo `JsonResponse` con `id`, `email`, `name`, `lastName`, `roles`, `isActive` y `residentUnitId` del usuario autenticado.
   - Vuelve a ejecutar PHPUnit y verifica verde:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/User/Infrastructure/Http/Controller/ResidentSelfServiceAndMeControllerTest.php
     ```
   - Ejecuta análisis estático y formateador sobre los archivos tocados:
     ```bash
     ./tools/cleanroom/exec_php.sh tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src/Context/User/Infrastructure/Http/Controller/MeUserGetController.php src/Context/User/Infrastructure/Http/Controller/UpdateUserController.php src/Context/User/Infrastructure/Http/Controller/UploadAvatarController.php tests/Context/User/Infrastructure/Http/Controller/ResidentSelfServiceAndMeControllerTest.php
     ./tools/cleanroom/exec_php.sh vendor/bin/phpstan analyse src/Context/User/Infrastructure/Http/Controller/MeUserGetController.php src/Context/User/Infrastructure/Http/Controller/UpdateUserController.php src/Context/User/Infrastructure/Http/Controller/UploadAvatarController.php tests/Context/User/Infrastructure/Http/Controller/ResidentSelfServiceAndMeControllerTest.php --memory-limit=512M
     ```
3. **Gate & Sello**:
   ```bash
   ./tools/cleanroom/gate.sh stage-13 atom-47-security-allow-resident-self-service-and-me --auto-commit
   ```

---

## 4. Mapa con Fronteras (Scope Allowlist)
Solo se permite crear o modificar los siguientes archivos durante este átomo (cualquier archivo fuera de esta lista abortará el `Gate 0`):
- `config/packages/security.yaml`
- `src/Context/User/Infrastructure/Http/routes.yaml`
- `src/Context/User/Infrastructure/Http/Controller/MeUserGetController.php`
- `src/Context/User/Infrastructure/Http/Controller/UpdateUserController.php`
- `src/Context/User/Infrastructure/Http/Controller/UploadAvatarController.php`
- `tests/Context/User/Infrastructure/Http/Controller/ResidentSelfServiceAndMeControllerTest.php`

---

## 5. Silbato & Libro de Frases
- Si el oráculo de `gate.sh` falla **3 veces consecutivas** o supera **10 minutos** de bucle en la misma hipótesis -> el circuit breaker registrará `STATUS: BLOCKED` en `docs/stages/log.md`.
- Al completar con éxito el Gate (código de salida `0`), emite bloque YAML final con `STATUS: SEALED`:
```yaml
ATOM: atom-47-security-allow-resident-self-service-and-me
STAGE: stage-13
SPEC: FIX-10
STATUS: SEALED
```
