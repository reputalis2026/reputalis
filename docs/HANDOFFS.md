# Handoffs y decisiones (registro)

**Propósito:** bitácora **cronológica** de cambios relevantes (producto, código o servidor): qué se hizo, por qué, qué queda pendiente y riesgos. No sustituye Git ni tickets; sirve para orientar a la siguiente persona o IA.

Al cerrar una tarea, aplicar primero las **Reglas de cierre para IA** en [`README_AI.md`](../README_AI.md) (qué documentos revisar y checklist).

**Relacionado:** [`CONTEXTO_PARA_IA.md`](../CONTEXTO_PARA_IA.md) (contexto técnico vivo), [`docs/OPERACIONES_SERVIDOR.md`](OPERACIONES_SERVIDOR.md) (estado del VPS), [`docs/RUNBOOK.md`](RUNBOOK.md) (comandos).

---

## Cómo añadir una entrada

Copia el bloque plantilla al **inicio** del archivo (debajo de esta sección), más reciente arriba.

### Plantilla

```markdown
### YYYY-MM-DD — Título breve

- **Qué se cambió:** …
- **Por qué:** …
- **Qué falta:** …
- **Riesgos o pendientes:** …
```

---

## Entradas

### 2026-08-14 — Handoff reputación Outscraper (fases 1–7) + qué falta

- **Qué se cambió (resumen completo):** reputación externa Google vía Outscraper Places, fases **1–7**:
  1. Place ID en `clients` (`google_place_id` / `google_id`); encuesta solo lee; Filament ficha.
  2. Tablas `client_external_reputation_snapshots` + `client_external_reputation_alerts`.
  3. Paquete `App\Support\ExternalReputation\*` (HTTP, fake, sync, proyección, detector alertas).
  4. `php artisan external-reputation:sync` + schedule 10:00/17:00/23:55 Madrid + cron `reputalis-scheduler`.
  5. UI `ReputacionExterna` (acceso Dashboard → pestaña externa; **no** subnav).
  6. Gráficos ApexCharts (nota, total, desglose 1–5) en cards.
  7. Alertas `negative_increase` / anomalía `total_drop`; badge; marcar leídas.
  Extra: botón **Simular sync (fake +1)** para pruebas sin API.
- **Migraciones:** `2026_08_07_100000` … `130000` (place fields, tablas, sync fields, kind/delta).
- **Docs clave:** [`docs/PLAN_REPUTACION_OUTSCRAPER.md`](PLAN_REPUTACION_OUTSCRAPER.md), [`docs/RUNBOOK.md`](RUNBOOK.md) § reputación externa.
- **Por qué:** handoff para seguir desde otro PC; el trabajo estaba solo en el VPS sin push.
- **Qué falta:**
  - **Fase 0:** cuenta Outscraper + `OUTSCRAPER_API_KEY` + `OUTSCRAPER_DRIVER=http` + validar `reviews_per_score`.
  - **Fase 8 operativa:** migrate en entornos que falten; probar 1–2 clientes reales; medir créditos; revisar cron con driver real.
  - **Opcional:** email alertas; ocultar/simular solo en non-prod; dropear Place ID legacy en configs.
- **Riesgos o pendientes:** sin API key el cron usa **fake** (datos inventados). No commitear `.env`. En el otro PC: `git pull` + `composer install` si hace falta + `php artisan migrate` + copiar vars `OUTSCRAPER_*` del `.env.example`.

### 2026-08-07 — Fase 7: alertas reputación externa

- **Qué se cambió:** `NegativeReviewAlertDetector` (prioridad: subida 1★/2★ → `negative_increase`; si solo baja el total → anomalía `total_drop`). Migración `kind` + `delta_reviews_total`. UI: card siempre visible, badge en pestaña externa, marcar una/todas leídas, textos diferenciados. Tests unitarios del detector. Email a owner/distribuidor **no** implementado (opcional V2).
- **Por qué:** Avisar de reseñas malas nuevas sin confundir con borrados en Google.
- **Qué falta:** Fase 8 docs/deploy + `OUTSCRAPER_API_KEY` real; email si producto lo confirma.
- **Riesgos o pendientes:** Anomalías `total_drop` también cuentan en el badge de no leídas.

### 2026-08-07 — Fase 6: gráficos reputación externa

- **Qué se cambió:** En `ReputacionExterna`, tres gráficos ApexCharts (CDN, misma base que el Dashboard): evolución nota Google + calculada, total de reseñas, barras apiladas 1–5★. Datos de `getHistoryChartConfig()` según filtros día/mes/año. Script `external-reputation-charts-script.blade.php`. i18n de títulos de gráficos.
- **Por qué:** Ver tendencia visual además de la tabla V1.
- **Qué falta:** Fase 7 pulir alertas; Fase 8 docs/deploy + API real.
- **Riesgos o pendientes:** Con pocos puntos el gráfico se ve escaso hasta acumular syncs diarios.

### 2026-08-07 — Fase 5: UI Reputación externa V1

- **Qué se cambió:** Página Filament `ClientResource/Pages/ReputacionExterna` (ruta `/{record}/reputacion-externa`; **no** en subnav — se abre desde Dashboard → pestaña externa). Vista Blade con nota Google, total, desglose 1–5, media calculada, estimación a objetivo, última sync/error, alertas recientes, histórico día/mes/año (sin hora). Botón «Sincronizar ahora» si `canEdit` y hay Place ID. i18n `client.external_reputation.*` en `es`/`en`/`pt`.
- **Por qué:** Ver y forzar sync de la reputación agregada sin depender solo del cron, manteniendo el acceso junto a reputación interna.
- **Qué falta:** Fase 6 gráficos; Fase 7 pulir alertas (lista básica ya en V1).
- **Riesgos o pendientes:** Sin Place ID la página avisa y no sincroniza. Driver fake sigue inventando datos si no hay API key.

### 2026-08-07 — Fase 4: comando sync + schedule 3×/día

- **Qué se cambió:** Comando `php artisan external-reputation:sync` (`--client`, `--only-missing`, `--dry-run`). Schedule en `routes/console.php` a 10:00 / 17:00 / 23:55 `Europe/Madrid`. Cron sistema `/etc/cron.d/reputalis-scheduler` (`schedule:run` cada minuto como `www-data`). Docs RUNBOOK + OPERACIONES.
- **Por qué:** Automatizar las fotos de reputación sin depender de la UI.
- **Qué falta:** Fase 5 UI Reputación externa.
- **Riesgos o pendientes:** Sin `OUTSCRAPER_API_KEY` el cron usa driver fake (datos inventados). Verificar `php artisan schedule:list` y permisos de `www-data` sobre el proyecto.

### 2026-08-07 — Fase 3: cliente Outscraper + sync de reputación

- **Qué se cambió:** `config/services.php` `outscraper.*`; `.env.example` `OUTSCRAPER_*`. Paquete `App\Support\ExternalReputation\` (gateway HTTP, fake, DTO, proyección, `ExternalReputationSyncService`). Campos `clients.external_reputation_last_synced_at` / `external_reputation_last_error`. Binding en `AppServiceProvider`. Tests unitarios de proyección/parseo/fake. Sin comando artisan ni UI aún (fase 4–5).
- **Por qué:** Poder sincronizar fotos agregadas y alertas sin cuenta (driver fake) o con API real.
- **Qué falta:** Fase 4 comando + schedule; Fase 5 UI.
- **Riesgos o pendientes:** Sin `OUTSCRAPER_API_KEY` y driver `http` el sync falla. Validar `reviews_per_score` con place_id real cuando haya cuenta. Doc API: https://app.outscraper.cloud/api-docs#tag/google/GET/maps/search

### 2026-08-07 — Fase 2: tablas snapshots y alertas de reputación externa

- **Qué se cambió:** Migración `2026_08_07_110000_create_client_external_reputation_tables`: `client_external_reputation_snapshots` (rating, total, stars_1…5, calculated_rating, snapshot_date, raw_payload) y `client_external_reputation_alerts` (deltas 1★/2★). Modelos `ClientExternalReputationSnapshot` / `ClientExternalReputationAlert` con helpers de media y proyección de 5★. Relaciones en `Client`. Docs actualizadas. Sin UI ni sync Outscraper aún.
- **Por qué:** Base de datos para el histórico día/mes/año y alertas del plan Outscraper Places.
- **Qué falta:** Fase 3 (cliente API + sync), fase 4 cron, fase 5 UI.
- **Riesgos o pendientes:** Tras deploy: `php artisan migrate`. Varios snapshots por día están permitidos; la UI deberá elegir el último del día.

### 2026-08-07 — Fase 1: Place ID en ficha de cliente

- **Qué se cambió:** Migración `2026_08_07_100000_add_google_place_fields_to_clients_table` (`clients.google_place_id`, `clients.google_id`) con copia desde `client_improvement_configs`. Formulario alta/edición/ficha cliente: sección Google Maps. Encuesta (`PuntosDeMejora`) ya no edita Place ID (solo lectura desde cliente). `SurveyController` y `Client::googleReviewUrl()` usan el Place ID del cliente. Columna en config queda legacy. Docs y plan actualizados.
- **Por qué:** Una sola fuente de Place ID para encuesta y futura reputación Outscraper.
- **Qué falta:** Fases 2+ del plan (snapshots BD, sync Outscraper, UI reputación). Opcional: dropear `client_improvement_configs.google_place_id` más adelante.
- **Riesgos o pendientes:** Clientes sin Place ID en ficha no redirigen a Google (igual que antes sin configurarlo). Tras deploy en otros entornos: `php artisan migrate`.

### 2026-08-06 — Plan reputación Outscraper reescrito (alcance cerrado)

- **Qué se cambió:** Reescritura completa de [`docs/PLAN_REPUTACION_OUTSCRAPER.md`](PLAN_REPUTACION_OUTSCRAPER.md): desarrollo paso a paso (fases 0–8). Alcance: Outscraper **Places** (agregados), Place ID en **alta de cliente** (encuesta solo lee), snapshots en BD, histórico día/mes/año, media calculada + estimación de 5★, cron 3×/día, UI Reputación externa V1 luego gráficos. **Sin** fechas ni listado de reseñas individuales.
- **Por qué:** Cliente de producto confirmó que no necesita historial por reseña; prefiere fotos agregadas y histórico propio barato.
- **Qué falta:** Implementar fases del plan; prueba real Outscraper (créditos + `reviews_per_score`).
- **Riesgos o pendientes:** Validar que `place_id`/`google_id` devuelven siempre `reviews_per_score`. Migrar Place ID fuera de `client_improvement_configs`.

### 2026-07-31 — Plan reputación Google vía Outscraper (sin implementar)

- **Qué se cambió:** Primera versión del plan (incluía opción de reseñas con fecha). **Supersedida** el 2026-08-06.
- **Por qué:** Pivot tras descartar Places/OAuth oficial.
- **Qué falta:** — (ver entrada 2026-08-06).
- **Riesgos o pendientes:** —

### 2026-07-16 — Herramientas adicionales, galería de imágenes y branding cliente

- **Qué se cambió:**
  - **Almacenamiento de imágenes:** logos y fotos pasan a `storage/app/public/img/{client_code}/logo/` y `img/{client_code}/employees/{employee_id}/`. Helper `App\Support\ClientImagePaths`. Comando `php artisan clients:migrate-images` (ya ejecutado en este VPS). FileUpload de cliente/distribuidor/empleado conserva historial (`deleteUploadedFileUsing` vacío) y evita cuelgue de preview con `fetchFileInformation(false)` + `getUploadedFileUsing`.
  - **Herramientas adicionales:** página Filament `AdditionalTools` (antes “Ajustes”); cards Sectores (solo superadmin) e Imágenes de clientes. `SectorResource` sin navegación propia.
  - **Galería:** `ClientImagesGallery` (superadmin + distribuidor con `created_by`); acordeón de clientes con logo pequeño, filtro por nombre, secciones negocio/empleados, carpeta por empleado, badge “Actual”, descarga, miniaturas grandes al abrir.
  - **Rol cliente:** menú Dashboard / Empleados / Certificados / Informes (placeholders); encuesta CSAT oculta en nav; `ListClients` redirige al dashboard; logo delante del nombre en listado; branding panel: nombre comercial arriba (mayúsculas, sin clic) y logo en avatar de perfil (`User` implementa `HasAvatar`); `AdminPanelProvider` con `brandName` / `homeUrl` / sidebar `18rem` (vuelve a trackearse en Git; antes estaba en `.gitignore`).
  - **Dashboard:** filtros horarios Hoy (`00-11` / `12-23`), detalle operario con rangos locales y tooltips, títulos de detalle.
  - Vista override logo: `resources/views/vendor/filament-panels/components/logo.blade.php`.
- **Por qué:** organizar assets por cliente, dar a admin/distribuidor una herramienta de consulta de imágenes, y personalizar la experiencia del rol cliente en el panel.
- **Qué falta:** contenido real de Certificados e Informes; opcional ampliar branding al rol distribuidor (logo top-left ya descrito en textos de `DistributorResource`).
- **Riesgos o pendientes:** sin `php artisan storage:link` las URLs `/storage/...` fallan. Tras deploy en otro entorno, ejecutar `clients:migrate-images` si aún hay rutas `clients/` o `employees/`. Nombres muy largos en sidebar hacen crecer el header en altura (no se truncan).

### 2026-07-12 — Encuesta: reseña en Google Maps con Place ID y contador

- **Qué se cambió:** Migración `2026_07_12_120000_add_google_review_fields_to_client_improvement_configs_table.php` (`google_place_id`, `google_review_message_*`) — **aplicada en producción** (`php artisan migrate`). Modelo `ClientImprovementConfig` con `normalizeGooglePlaceId()`, `googleReviewUrl()` y textos por defecto. Filament `PuntosDeMejora`: sección «Reseña en Google Maps», Place ID obligatorio al guardar, botón `?` al [Place ID Finder](https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder). Vista pública `survey.blade.php`: mensaje editable, contador 5→1 y redirección automática a `writereview?placeid=...` (sin botón manual «Dejar reseña»). i18n `lang/{es,en,pt}/client.php`. Docs: `CONTEXTO_PARA_IA.md`, `DOCUMENTACION_TABLAS_BD.md`, `DESCRIPCION_CLASES.md`, `RESUMEN_PROYECTO.md`.
- **Por qué:** Tras valoración positiva, guiar al usuario a dejar reseña en Google del negocio concreto (no búsqueda genérica por nombre).
- **Qué falta:** Clientes existentes deben **guardar la encuesta** con su Place ID antes de que funcione la redirección (sin Place ID: solo mensaje de gracias, sin contador). Integración OAuth/Google Business (tablas legacy) sigue pendiente.
- **Riesgos o pendientes:** Place ID incorrecto redirige a ficha/reseña de otro negocio; validar con el cliente. PWA con SW cacheada puede tardar en reflejar cambios de texto hasta recarga.

### 2026-06-07 — ClientResource: dashboard V1 y navegación del registro

- **Qué se cambió:** Nueva subpágina `ClientDashboard` (`/{record}/dashboard`) con resumen CSAT 7 días y tarjetas operativas (encuesta, empleados, llamadas). Trait `HasClientPageTitle` en subpáginas del cliente. Subnav reordenada: Dashboard → Ficha → Encuesta → Empleados → Llamadas. Tab `ViewClient` renombrada a «Ficha». i18n en `lang/{es,en,pt}/client.php`. Doc: [`docs/client-dashboard-v1.md`](client-dashboard-v1.md).
- **Por qué:** Vista resumen operativa del cliente sin duplicar la ficha; header unificado con nombre comercial; base escalable para analítica V2.
- **Qué falta:** Gráficos, series temporales, actividad reciente, comparativas, selector de periodo; opcional landing en `dashboard` desde listados.
- **Riesgos o pendientes:** Los listados siguen enlazando a `view`, no a `dashboard`. Permisos de Llamadas sin cambios (tarjeta llamadas oculta para roles sin acceso).

### 2026-05-12 — Documentación: índice, runbook, handoffs y validación

- **Qué se cambió:** Índice en `README_AI.md` con `docs/RUNBOOK.md` y `docs/HANDOFFS.md`. Creación de `docs/HANDOFFS.md` y `docs/RUNBOOK.md`. Ajuste de solapamientos en `CONTEXTO_PARA_IA.md` (sin párrafo de producto ni tabla de stack duplicada; Filament remitido a `DESCRIPCION_CLASES.md`; flujos negocio-distribuidor solo en `RESUMEN_PROYECTO.md`). Enlaces cruzados en `RESUMEN_PROYECTO.md`, `DOCUMENTACION_TABLAS_BD.md`, `DESCRIPCION_CLASES.md`, `docs/OPERACIONES_SERVIDOR.md`. `DESCRIPCION_CLASES.md`: nota operativa sustituida por punteros a contexto y runbook.
- **Por qué:** Un solo punto de entrada para IAs, BD y clases separadas, VPS sin secretos, comandos en runbook y bitácora explícita.
- **Qué falta:** Entradas nuevas en `HANDOFFS.md` por cada despliegue o cambio de infra relevante; mantener `CONTEXTO_PARA_IA.md` al día con rutas/API/convenciones.
- **Riesgos o pendientes:** Rutas absolutas con fecha en `OPERACIONES_SERVIDOR.md` pueden quedar obsoletas al rotar backups; revisar enlaces si se renombran archivos.
