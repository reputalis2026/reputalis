# Contexto técnico para continuar el desarrollo (IA y equipo)

**Propósito:** handoff operativo: rutas, API, convenciones que suelen romperse y enlaces al resto de docs. **No** duplica el catálogo de modelos ni el listado de tablas; para eso usa [`DESCRIPCION_CLASES.md`](DESCRIPCION_CLASES.md) y [`DOCUMENTACION_TABLAS_BD.md`](DOCUMENTACION_TABLAS_BD.md).

**Antes que nada:** lee [`README_AI.md`](README_AI.md). Infra del VPS: [`docs/OPERACIONES_SERVIDOR.md`](docs/OPERACIONES_SERVIDOR.md) y comandos: [`docs/RUNBOOK.md`](docs/RUNBOOK.md). Bitácora: [`docs/HANDOFFS.md`](docs/HANDOFFS.md).

---

## Documentos de contexto (no borrar del repo)

`CONTEXTO_PARA_IA.md`, `RESUMEN_PROYECTO.md` y `DESCRIPCION_CLASES.md` se **actualizan**, no se eliminan. El índice maestro es `README_AI.md`.

---

## Handoff reciente (resumen)

- **Filament:** `AdminPanelProvider` (trackeado en Git), login `/admin/login`, overlay global (`->spa()` + hooks `STYLES_AFTER` / `BODY_START` / `SCRIPTS_BEFORE` / `SIDEBAR_FOOTER`). Branding rol cliente: `brandName` = Reputalis, `homeUrl` = null, avatar = logo del cliente (`User::getFilamentAvatarUrl()`), override `resources/views/vendor/filament-panels/components/logo.blade.php`. Shell visual cliente: `App\Support\ClientPanel` + CSS `client-panel-theme` (sidebar oscuro / canvas menta; la clase se reaplica en `livewire:navigated`), **solo rol cliente**.
- **Imágenes por cliente:** rutas `storage/app/public/img/{code}/logo/` y `img/{code}/employees/{employee_id}/` vía `App\Support\ClientImagePaths`. Migración de legacy: `php artisan clients:migrate-images`. Galería: `ClientImagesGallery` desde **Herramientas adicionales** (`AdditionalTools`); acceso superadmin + distribuidor (`created_by`).
- **Rol cliente (nav):** grupos **Panel principal** (Reputación interna / externa / Comparativa sector), **Gestión** (Empleados) y **Documentos** (Certificados e Informes, placeholders). En escritorio las pestañas de página se ocultan (el menú lateral las sustituye); en móvil el hamburguesa abre el mismo sidebar. Logo del cliente, idioma y logout van al **pie del sidebar**, no a la topbar. Encuesta/CSAT ocultos en menú; listado de clientes redirige al dashboard interno. Superadmin/distribuidor no cambian de cromo.
- **Reputación interna (rol cliente):** cabecera propia (título + subtítulo + pastillas de rango) y 4 KPI cards; las agujas ApexCharts de la primera fila quedan solo para superadmin/distribuidor. Nuevo rango `last_year` (12 meses) en `InternalReputationDateRange`.
- **Encuesta pública y NFC:** `SurveyController` (web), vistas `survey.blade.php`, `survey-nfc-invalid.blade.php`; API `POST /api/surveys/create`.
- **Encuesta por cliente:** subpágina Filament `PuntosDeMejora` (UI **Encuesta**): `ClientImprovementConfig` + `ClientImprovementOption`, multidioma `es`/`pt`/`en`, `default_locale`, `positive_scores`, `display_mode` (`numbers` | `faces`), mensajes multidioma de reseña en Google (`google_review_message_*`). El **Place ID** vive en **`clients.google_place_id`** (alta/ficha); la encuesta solo lo muestra en lectura.
- **Reputación externa (Google / Outscraper):** contenido en `ReputacionExterna`; el rol cliente entra por el ítem de menú; superadmin/distribuidor siguen usando las pestañas del Dashboard. Sync `external-reputation:sync` 3×/día; alertas 1★/2★ + anomalía `total_drop`; plan en `docs/PLAN_REPUTACION_OUTSCRAPER.md`.
- **Empleados:** `EmployeeResource::canAccess()` = `canViewAny() || canCreate()` (evita 403 al abrir rutas del recurso). UUID en `Employee::booted()` al crear. FK `nfctokens.employee_id` → **ON DELETE CASCADE** (migración `2026_04_08_161000_nfctokens_employee_fk_cascade_on_delete`). Borrado solo empleados **inactivos**; pestañas activos/inactivos en `ClientResource → Empleados`.
- **Traducciones panel autenticado:** `lang/*`, `SetPanelLocale`, `/admin/language/{locale}`; separado del idioma de encuesta pública.
- **Seguridad de páginas:** `canAccess()` en `AdminNotifications`, `ClientCalls`, `DistributorMessages`, `AdditionalTools`, `ClientImagesGallery`.

Detalle de producto, stack y módulos: [`RESUMEN_PROYECTO.md`](RESUMEN_PROYECTO.md).

---

## Rutas

### Web (`routes/web.php`)

- `GET /` — welcome.
- **Pulse:** `GET /pulse`, `POST /pulse/login`, bajo `auth`: dashboard `/pulse/{client_code}`, manifest, SW, `GET /api/pulse/{client_code}`.
- **Encuesta:** `GET /survey`, `GET /survey/{client_code}`, SW/manifest por cliente, `GET /survey/nfc/{token}`.
- **Filament:** fallback `POST /admin/login` si aplica.

### API (`routes/api.php`)

- `POST /api/surveys/create` — `Api\SurveyController@store`, throttle `surveys`. Parámetros clave: `client_code`, `score` 1–5, `employee_code` opcional, `improvement_option_id` o `improvement_reason_code` según reglas de `StoreSurveyRequest`, `locale_used`, `device_hash`.

---

## Controladores y piezas críticas (punteros)

- **`App\Http\Controllers\Api\SurveyController`:** validación, límites por IP/dispositivo, `positive_scores` y `improvement_option_id`.
- **`App\Http\Controllers\SurveyController` (web):** locale vía `Accept-Language`, `surveyPositiveScores`, bloqueos NFC si token o empleado inactivos.
- **`App\Http\Controllers\PulseController`:** login propietario cliente, métricas con `CsatMetrics`.
- **`App\Support\CsatMetrics`:** agregados y caché; respeta `positive_scores_used` en encuestas.
- **`App\Support\PanelMessageService`:** notificaciones activación cliente; **generar UUID de `PanelMessage` en PHP** antes de recipients.
- **Vista encuesta:** `resources/views/survey.blade.php` — pantalla de valoración con layout tipo mockup (logo `public/img/logoReputalis.png`, pregunta editable grande, textos fijos, sin tarjeta Cliente/Empleado; contenido pegado al logo y viewport `100dvh` sin scroll); **caritas y assets de números** sin rediseñar aún. Flujo positivo/mejor según `POSITIVE_SCORES`; tras valoración positiva mensaje configurable, **contador 5→1** y redirección a `writereview?placeid=...` si hay `google_place_id`; SW encuesta caché **v8**.

---

## Filament y panel

Listado por clase (recursos, páginas, permisos): [`DESCRIPCION_CLASES.md`](DESCRIPCION_CLASES.md). Convenciones de permisos y UUID en formularios: sección **Convenciones que evitan bugs** más abajo.

---

## Convenciones que evitan bugs

- **UUID en PHP** antes de `save()` cuando haya FKs hijas inmediatas (`ClientImprovementConfig`, `PanelMessage`, opciones, etc.).
- **Employee:** siempre UUID en evento `creating` por `$incrementing = false`.
- **Distribuidor:** en listados, `created_by === auth()->id()` donde corresponda.
- **Cliente vs distribuidor:** mismo modelo `Client`; rol del **owner** en `users.role`.
- **Código de cliente:** `Client.code` en URLs públicas y API.
- **Imágenes:** no usar `clients/` ni `employees/` planos; subir a `img/{code}/…`. En FileUpload de logo/foto existentes, usar `fetchFileInformation(false)` + `getUploadedFileUsing` (evita loading infinito de Filepond). Conservar historial con `deleteUploadedFileUsing` vacío si la galería debe listar archivos previos.
- **`AdminPanelProvider`:** ya no está en `.gitignore`; cambios de branding/hooks deben ir al repo.

---

## Migraciones que suelen importar

- Renombre `pharmacies` → `clients` y columnas `pharmacy_id` → `client_id`.
- `clients` incluye **`google_place_id`** / **`google_id`** (Place ID fuente única; encuesta y reputación externa).
- `client_improvement_configs` / `client_improvement_options` y campos multidioma + `positive_scores` + `display_mode` + **`google_review_message_*`** (reseña Google tras valoración positiva; columna `google_place_id` legacy).
- `client_external_reputation_snapshots` / `client_external_reputation_alerts` (fotos agregadas Google vía Outscraper + alertas 1★/2★).
- Sync: `App\Support\ExternalReputation\*` + `OUTSCRAPER_*` en `.env` (`OUTSCRAPER_DRIVER=fake|http`).
- `csat_surveys.positive_scores_used` (snapshot).
- `2026_04_08_161000_nfctokens_employee_fk_cascade_on_delete`.

Lista amplia: buscar en `database/migrations/` o ampliar desde `RESUMEN_PROYECTO.md` / código.

---

## Seeders

- `ImprovementReasonSeeder`, `SectorSeeder`, usuario superadmin en `DatabaseSeeder` — **no** documentar aquí credenciales; rotar si hubo exposición.

---

## Pendiente / roadmap de producto

Google, alertas, contratos, documentos, benchmarks: [`RESUMEN_PROYECTO.md`](RESUMEN_PROYECTO.md) § “Pendiente / no implementado”.

---

## Cómo usar este archivo en prompts

- “Según CONTEXTO_PARA_IA: añadir ruta X / cambiar throttle de API / convención UUID en modelo Y”.
- Para columnas exactas o relaciones campo a campo: `DOCUMENTACION_TABLAS_BD.md` + modelo en código.
- Para responsabilidad de una clase concreta: `DESCRIPCION_CLASES.md`.

Si el detalle diverge del código, **manda** el código.
