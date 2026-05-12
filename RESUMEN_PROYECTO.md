# Resumen del proyecto Reputalis

**Propósito:** visión de producto, qué está implementado y qué queda fuera. Para rutas, API y convenciones de implementación, usar [`CONTEXTO_PARA_IA.md`](CONTEXTO_PARA_IA.md). Para tablas y clases en detalle: [`DOCUMENTACION_TABLAS_BD.md`](DOCUMENTACION_TABLAS_BD.md) y [`DESCRIPCION_CLASES.md`](DESCRIPCION_CLASES.md). Entrada general para IAs: [`README_AI.md`](README_AI.md). Bitácora de cambios: [`docs/HANDOFFS.md`](docs/HANDOFFS.md).

---

## Documentos de contexto (no borrar del repo)

`CONTEXTO_PARA_IA.md`, `RESUMEN_PROYECTO.md` y `DESCRIPCION_CLASES.md` se mantienen en el repositorio y se **actualizan** con el tiempo; el índice y normas están en `README_AI.md`.

---

## Información general

| Aspecto | Valor |
|--------|--------|
| Proyecto | Reputalis |
| Framework | Laravel 11.x |
| Panel | Filament 3.x |
| Base de datos | PostgreSQL (UUIDs) |
| Frontend | TailwindCSS + Vite |
| PHP | ^8.2 |

---

## Qué es Reputalis

Plataforma de **gestión de satisfacción (CSAT)** y clientes (orientación inicial farmacia/parafarmacia, modelo genérico). Incluye:

1. **Panel Filament** (`/admin`): superadmin, distribuidor y cliente propietario con permisos distintos.
2. **Encuestas:** API `POST /api/surveys/create`, página pública `/survey/{client_code}`, NFC `/survey/nfc/{token}`.
3. **PWA “El Pulso del Día”** (`/pulse`): dashboard de métricas para el propietario del cliente.

La entidad central de negocio es **Client** (`clients`). Los distribuidores son **el mismo modelo** filtrado por `owner.role = distribuidor`.

---

## Estado actual (alto nivel)

- Núcleo operativo: clientes, distribuidores, empleados y NFC estable, encuesta configurable por cliente (multidioma, `display_mode`, `positive_scores`), CSAT en panel, llamadas, mensajes de panel, Pulse, i18n del panel autenticado vía `lang/`.
- **Empleados (2026):** pestañas activo/inactivo, copiar enlace NFC desde tarjetas, borrado solo inactivos, sincronización `employees.is_active` ↔ `nfctokens.is_active`, pantalla NFC si empleado inactivo.
- **Cambios técnicos recientes** (login Filament, overlay SPA, permisos `EmployeeResource`, UUID empleado, FK NFC cascade, traducciones fases 1–3): resumidos en [`CONTEXTO_PARA_IA.md`](CONTEXTO_PARA_IA.md) para no duplicar listas largas aquí.

---

## Implementado y funcional (resumen)

### Autenticación y roles

- **User:** roles `superadmin`, `cliente`, `distribuidor`; helpers `isSuperAdmin()`, `isClientOwner()`, `isDistributor()`; acceso panel según rol.

### Clientes

- **Client:** datos fiscales y comerciales, `owner_id`, `created_by`, vigencia (`fecha_inicio_alta`, `fecha_fin`), `is_active`, soft deletes, logo.
- **Solo superadmin** restaura desde pestaña de eliminados.
- **Activación:** al activar, flujo de duración 12/24/36 meses u “otra fecha”; modal de confirmación si cambia expiración (`EditClient`).

### Distribuidores

- Mismo **Client** con owner distribuidor; `DistributorResource` solo superadmin; distribuidor crea clientes con `created_by` enlace a él.

### Panel Filament

- **ClientResource** con subpáginas Encuesta (`PuntosDeMejora`), Empleados, Llamadas; permisos por rol.
- **Cliente (rol):** menú Dashboard / Encuesta / Empleados vía páginas dedicadas; sin edición cruzada por URL donde está bloqueado.
- **Notificaciones:** `PanelMessage` / `PanelMessageService` en alta y activación de cliente.
- **Idioma panel:** sesión + archivos `lang/`; independiente de la encuesta pública.

### Encuestas CSAT

- API con límites por IP y dispositivo; validación de opciones de mejora del cliente; `positive_scores_used` como histórico.
- Vista pública con idioma por `Accept-Language` y fallback a `default_locale` y `es`.
- NFC: token estable por empleado; validaciones de cliente/empleado/token activos.

### Pulse

- Login propietario; métricas vía `CsatMetrics` y `positive_scores_used`.

### Otros

- **Sector**, **ImprovementReason** (legacy compat), **ClientCall**, widgets de dashboard.

---

## Estructura relevante (índice)

- **Modelos:** User, Client, Employee, NfcToken, CsatSurvey, ClientImprovementConfig, ClientImprovementOption, PanelMessage, PanelMessageRecipient, Sector, etc.
- **Filament:** recursos anteriores + páginas cliente + notificaciones.
- **Rutas:** `routes/web.php`, `routes/api.php`.

Detalle por clase: `DESCRIPCION_CLASES.md`.

---

## Pendiente / no implementado

- Tablas previstas en migraciones sin app completa: contratos, documentos, Google (OAuth, reseñas, métricas), alertas, benchmarks, ajustes legacy de settings.
- Posible estrategia futura para textos persistidos en BD (traducciones de datos).

---

## Notas

- UUID en tablas principales; convención `Client` vs nombre legacy `pharmacy` en migraciones antiguas.
- `CONTEXTO_PARA_IA.md` suele estar más al día en detalles técnicos puntuales; este resumen prioriza **qué existe** a nivel funcional.
- Operación del servidor (comandos): [`docs/RUNBOOK.md`](docs/RUNBOOK.md).

**Última revisión documental:** mayo 2026 (reorganización de docs + mantenimiento de contenido alineado con código).
