# Guía para IAs y mantenimiento (Reputalis)

**Propósito:** punto de entrada único. Indica qué leer, normas mínimas, **reglas de cierre para IA** (actualizar docs al terminar tareas) y dónde está cada cosa. No sustituye el código ni la documentación detallada.

---

## Orden de lectura recomendado

1. **Este archivo** (`README_AI.md`) — normas, **reglas de cierre para IA**, mapa de docs y advertencias.
2. [`CONTEXTO_PARA_IA.md`](CONTEXTO_PARA_IA.md) — contexto técnico vivo: rutas, API y convenciones.
3. [`RESUMEN_PROYECTO.md`](RESUMEN_PROYECTO.md) — producto y estado funcional.
4. [`DOCUMENTACION_TABLAS_BD.md`](DOCUMENTACION_TABLAS_BD.md) — tablas y campos (BD).
5. [`DESCRIPCION_CLASES.md`](DESCRIPCION_CLASES.md) — clases, Filament y servicios.
6. [`docs/OPERACIONES_SERVIDOR.md`](docs/OPERACIONES_SERVIDOR.md) — qué está configurado en el VPS (sin secretos).
7. [`docs/RUNBOOK.md`](docs/RUNBOOK.md) — comandos operativos (SSH, Fail2Ban, Git, informe, logs).
8. [`docs/HANDOFFS.md`](docs/HANDOFFS.md) — registro de cambios y pendientes por fecha.

---

## Qué hace cada documento

| Documento | Contenido |
|-----------|-----------|
| `README_AI.md` | Entrada, orden de lectura, normas, **reglas de cierre para IA**, rutas importantes. |
| `CONTEXTO_PARA_IA.md` | Handoff técnico: rutas web/API, convenciones UUID/Filament, dónde profundizar. |
| `RESUMEN_PROYECTO.md` | Qué es Reputalis, qué está hecho, qué falta, flujo de negocio alto nivel. |
| `DOCUMENTACION_TABLAS_BD.md` | Tablas y campos orientados a migraciones/modelos. |
| `DESCRIPCION_CLASES.md` | Modelos, Filament, controladores y servicios en formato lista. |
| `docs/OPERACIONES_SERVIDOR.md` | Estado del VPS: SSH, Fail2Ban, correo relay, informes, Git deploy key (sin secretos). |
| `docs/RUNBOOK.md` | Comandos rutinarios: conexión, comprobaciones, logs. |
| `docs/HANDOFFS.md` | Bitácora: fecha, cambio, motivo, pendientes, riesgos. |

---

## Normas para cualquier IA que trabaje aquí

- **Idioma:** respuestas y documentación orientadas al equipo en **español** claro.
- **Secretos:** no incluir contraseñas, tokens, claves privadas ni contenido de `.env` en documentación. No pegar app passwords en chats ni en Markdown del repo.
- **Cambios:** cambios mínimos y enfocados al objetivo; no refactor masivo sin petición explícita.
- **Git:** el remoto `origin` usa **SSH** con deploy key en el servidor; no volver a poner tokens en URLs `git remote`.
- **Documentos de contexto:** `CONTEXTO_PARA_IA.md`, `RESUMEN_PROYECTO.md` y `DESCRIPCION_CLASES.md` son **referencia viva** del proyecto: **actualizar**, no borrar del repositorio.
- **Producción:** rutas y despliegue pueden variar; la fuente de verdad del comportamiento es el **código** y las migraciones.

---

## Reglas de cierre para IA

Al **terminar una tarea** con cambios relevantes en código, infra o producto, la IA debe **revisar y actualizar la documentación** correspondiente **sin esperar** a que el usuario lo pida. Las reglas detalladas están **solo aquí**; otros archivos enlazan a esta sección cuando hace falta.

### Qué documento tocar según el tipo de cambio

- **Cambios técnicos relevantes** (features, bugs importantes, decisiones de implementación, riesgos o pendientes para la siguiente sesión): añadir o actualizar una entrada en [`docs/HANDOFFS.md`](docs/HANDOFFS.md) (fecha, qué, por qué, qué falta, riesgos).
- **Pasos operativos, despliegue, seguridad, correo, cron, Fail2Ban, Git o mantenimiento del servidor** (comandos, rutas de scripts, orden de comprobaciones): revisar o actualizar [`docs/RUNBOOK.md`](docs/RUNBOOK.md) y, si cambia el *estado* configurado del VPS (no solo el comando), [`docs/OPERACIONES_SERVIDOR.md`](docs/OPERACIONES_SERVIDOR.md).
- **Arquitectura, rutas, API, convenciones** (UUID, Filament, patrones que deben seguirse): revisar [`CONTEXTO_PARA_IA.md`](CONTEXTO_PARA_IA.md).
- **Producto o flujo funcional** (qué hace la app, módulos, pendientes de negocio): revisar [`RESUMEN_PROYECTO.md`](RESUMEN_PROYECTO.md).
- **Esquema o significado de tablas/campos**: revisar [`DOCUMENTACION_TABLAS_BD.md`](DOCUMENTACION_TABLAS_BD.md).
- **Estructura de clases, servicios, recursos Filament o controladores** (responsabilidades por archivo): revisar [`DESCRIPCION_CLASES.md`](DESCRIPCION_CLASES.md).

Si varias categorías aplican, actualiza **todos** los documentos afectados en la misma sesión de cierre.

### Checklist final (antes de dar la tarea por cerrada)

- [ ] **Código** hecho y coherente con el objetivo.
- [ ] **Documentación** revisada según las reglas de arriba (solo los archivos que correspondan).
- [ ] **Handoff** ([`docs/HANDOFFS.md`](docs/HANDOFFS.md)) actualizado si hubo cambio técnico o de contexto relevante para quien sigue.
- [ ] **Runbook** ([`docs/RUNBOOK.md`](docs/RUNBOOK.md)) u **operaciones** ([`docs/OPERACIONES_SERVIDOR.md`](docs/OPERACIONES_SERVIDOR.md)) actualizados si cambió algo operativo en servidor o despliegue.
- [ ] **Sin secretos** en ningún Markdown (ni contraseñas, ni app passwords, ni claves privadas, ni contenido de `.env`).

---

## Rutas importantes en el código

| Área | Ruta típica |
|------|-------------|
| Rutas web | `routes/web.php` |
| API encuestas | `routes/api.php` — `POST /api/surveys/create` |
| Panel Filament | `app/Providers/Filament/AdminPanelProvider.php`, `app/Filament/` |
| Modelos | `app/Models/` |
| Traducciones panel | `lang/es`, `lang/en`, `lang/pt` |
| Vistas encuesta pública | `resources/views/survey.blade.php`, `survey-nfc-invalid.blade.php` |

---

## Advertencias

- **Cliente vs farmacia:** la entidad de negocio es `Client` (tabla `clients`); nombres legacy `pharmacy` pueden aparecer en migraciones antiguas.
- **UUID en PostgreSQL:** varios modelos generan `id` en PHP (`Str::uuid()`) antes de relaciones hijas; ver convenciones en `CONTEXTO_PARA_IA.md`.
- **Encuesta vs “puntos de mejora”:** en código la subpágina Filament puede llamarse `PuntosDeMejora`; en UI suele mostrarse como **Encuesta**.

---

## README del producto

La documentación orientada a desarrolladores humanos del esqueleto Laravel sigue en [`README.md`](README.md).
