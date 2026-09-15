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

### 2026-09-15 — Cierre: reputación interna rol cliente (seguir en otro PC)

- **Qué se cambió:** Rediseño de las cards internas del **rol cliente** (superadmin/distribuidor sin tocar). **Evolución de la satisfacción:** media acumulada ponderada; el badge negro es la media del periodo, no el último día. Chip «Satisfacción media» con delta vs mes anterior (si el mes previo no tiene datos, compara con el último mes con encuestas o con este mes sin hoy). **Crecimiento acumulado:** opción B (total histórico + chip del incremento del rango). **Puntos de mejora:** barras tipo mockup; el cuadrado/+info abre el detalle. **Detalle de operario y de punto de mejora:** mismas pastillas que el dashboard (Hoy / 7 días / 30 días / 12 meses / Acumulado); el eje X sigue esa granularidad (hora / día / semana / mes). Si un punto de mejora no se ha marcado en el rango, el gráfico no pinta una línea a 0%. **Orden de cards (solo cliente):** fila 1 Evolución | Puntos de mejora; fila 2 Crecimiento | Operarios (`flex-direction: column-reverse` en `.client-dashboard-insights-stack`). Iconos de info quitados. Modales de detalle con scroll en landscape. Sin migraciones.
- **Por qué:** Alinear el dashboard interno al mockup y dejar el trabajo pusheado para continuar mañana en otro PC.
- **Qué falta:** Ranking de **operarios** (lista) aún no tiene un pase de mockup completo. Comparativa de sector sigue siendo placeholder. Superadmin/distribuidor conservan gráficos y filtros antiguos.
- **Riesgos o pendientes:** PHP-FPM corre como `nobody`; tras Blade: `sudo -u nobody php artisan view:clear`. El eje de Apex usa `overwriteCategories` (el formatter no trae índice). En otro PC: `git pull origin main` (rama `main`), `composer install` si hace falta; **no hay migrate** en este lote. Piezas: `ClientDashboard.php`, `InternalReputationMetrics.php`, `client-dashboard.blade.php`, `client-dashboard-charts-script.blade.php`, `client-panel-theme.blade.php`, `employee-detail-modal.blade.php`, `lang/{es,en,pt}/client.php`.

### 2026-09-15 — Crecimiento acumulado: solo filtro superior (opción B)

- **Qué se cambió:** En el rol cliente se quitan las pastillas internas (Rango/Días/Horas) y el forzado de Horas en «Hoy». El gráfico es siempre el **total histórico** (opción B): el último punto coincide con el acumulado global; el chip muestra el incremento del rango («+N en 30 días») y en **Acumulado** el **total** (`14 total`), no «este mes». 7 días se agrupa por día, 30 días por **semana**, rangos largos por mes. Las fechas del eje X se pintan con `overwriteCategories` (el formatter de Apex no traía índice y dejaba el eje vacío en PC). En móvil, como mucho 6 fechas y marcadores en extremos si hay muchos puntos.
- **Por qué:** El filtro interno era del gráfico viejo y, en 30 días, ~31 etiquetas diarias hacían ilegible el eje. El chip de Acumulado decía «este mes» aunque coincidiera con el total.
- **Qué falta:** «Tendencia» → «Evolución de la satisfacción».
- **Riesgos o pendientes:** Superadmin conserva Rango/Días/Horas. Tras Blade: `sudo -u nobody php artisan view:clear`.

### 2026-09-15 — Reputación interna: crecimiento acumulado de encuestas (rol cliente)

- **Qué se cambió:** La card «Histórico de encuestas» pasa a «Crecimiento acumulado de encuestas» en el **rol cliente**: en modo *Rango de fechas* la serie es el total acumulado (parte del total previo al rango, `baseline`, y termina en el total histórico), con estilo del mockup (verde `#12a37a`, marcadores blancos, etiqueta oscura en el último punto, sin título de eje Y) y chip «+N este mes» (mes calendario). Los modos *Días* y *Horas* siguen siendo distribución (no acumulada) con el nuevo estilo; el turno mañana/noche en móvil se conserva. Superadmin/distribuidor mantienen el gráfico anterior. `InternalReputationMetrics::getSurveyHistoryByRange` devuelve `cumulative` y `baseline`; nuevo `getSurveysThisMonth()`. Etiquetas de mes sin año cuando el rango cae en un solo año.
- **Por qué:** Segundo gráfico del rediseño al mockup, manteniendo los filtros existentes.
- **Qué falta:** «Tendencia» → «Evolución de la satisfacción», ranking de empleados, puntos de mejora.
- **Riesgos o pendientes:** El flag `clientStyle` / `cumulative` viaja en la config JSON del gráfico; el JS (`renderHistoryChart`) elige la variante. Tras editar Blade: `sudo -u nobody php artisan view:clear`.

### 2026-09-13 — Reputación interna: filtro y primera fila (rol cliente)

- **Qué se cambió:** En el dashboard de reputación interna del **rol cliente**, el `<select>` de rango y la card de agujas Apex se sustituyen por pastillas (Acumulado / Hoy / 7 días / 30 días / 12 meses / Personalizado) y cuatro KPI: satisfacción media (2 decimales + delta vs mes anterior), encuestas (total + respuestas de hoy), % valoraciones positivas y barras 1–5. Nuevo tipo de rango `last_year`. Superadmin/distribuidor conservan el filtro y las agujas.
- **Por qué:** Primera pieza del rediseño de gráficos al mockup, sin tocar el resto del dashboard.
- **Qué falta:** Evolución, ranking de empleados, puntos de mejora y el resto de bloques internos.
- **Riesgos o pendientes:** El delta «este mes» compara el mes calendario actual con el anterior, independiente del rango de pastillas. Tras editar Blade, `sudo -u nobody php artisan view:clear` (PHP-FPM corre como `nobody`).

### 2026-09-13 — Cuenta cliente en el sidebar (sin pastillas móviles)

- **Qué se cambió:** Eliminadas las pastillas Interna/Externa/Sector. El cambio de reputación en móvil queda solo en el menú hamburguesa. El logo del cliente, perfil, idiomas y logout pasan al pie del sidebar; se ocultan en la esquina superior derecha.
- **Por qué:** El usuario pidió no usar pastillas bajo el título y llevar la cuenta (logo + logout) al lateral.
- **Qué falta:** —
- **Riesgos o pendientes:** En móvil la topbar queda solo con el hamburguesa; hay que abrir el menú para cambiar de sección o cerrar sesión.

### 2026-09-13 — Navegación reputación en móvil (rol cliente)

- **Qué se cambió:** El CSS ya no oculta toda la topbar (eso escondía el hamburguesa). En pantallas &lt;1024px el rol cliente ve pastillas **Interna / Externa / Sector** y puede abrir el menú lateral. En escritorio el cambio sigue siendo solo por el sidebar.
- **Por qué:** En móvil el panel principal desaparecía y no había forma de pasar de reputación interna a externa.
- **Qué falta:** —
- **Riesgos o pendientes:** —

### 2026-09-13 — Paleta cliente persistente en navegación SPA

- **Qué se cambió:** La clase `reputalis-client-panel` se vuelve a poner en `<html>`/`<body>` en el evento `livewire:navigated` (mismo patrón que el dark mode de Filament). El CSS del tema pasa al hook `STYLES_AFTER` con `id` estable.
- **Por qué:** Al pinchar Reputación externa u otra opción del menú, el SPA quitaba la clase y se perdía la paleta hasta recargar.
- **Qué falta:** —
- **Riesgos o pendientes:** —

### 2026-09-13 — Contorno dashboard rol cliente (sidebar y colores)

- **Qué se cambió:** Shell visual **solo para rol cliente**: sidebar oscuro, marca Reputalis + tagline, canvas menta, pie con nombre/ciudad del negocio. Menú en tres grupos — **Panel principal** (Reputación interna / externa / Comparativa sector), **Gestión** (Empleados), **Documentos** (Certificados, Informes). Las pestañas de reputación de la página se ocultan en ese rol (el lateral las sustituye). Gráficos y métricas **sin tocar**. Superadmin/distribuidor conservan el cromo ámbar de Filament. Piezas: `App\Support\ClientPanel`, tema CSS `client-panel-theme`, i18n `client.nav.*`.
- **Por qué:** Primera fase del rediseño al mockup: contorno (lateral y colores) antes de retocar gráficos uno a uno.
- **Qué falta:** Afinar gráficos y bloques nuevos del mockup (alertas internas, notificaciones, crecimiento acumulado, mini barras 1–5 por empleado). Opcional: rellenar ciudad en ficha para el pie del sidebar.
- **Riesgos o pendientes:** El tema vive en la clase `reputalis-client-panel` y se reaplica en `livewire:navigated` porque el SPA de Filament la quita al cambiar de página. Comparativa sector sigue siendo placeholder. No aplicar este look al panel de superadmin.

### 2026-09-10 — Encuesta: pantalla puntos de mejora (mockup)

- **Qué se cambió:** `step-reason` en `survey.blade.php` alineado al mockup: logo Reputalis, título editable (mismo estilo que la pregunta de valoración), texto fijo «Selecciona una opción», cards centradas con fondo `#eef2f6` y bordes redondeados. `step-thanks-low` (tras puntos de mejora): logo, check cyan, «¡Gracias!», subtítulo y footer «Encuesta gestionada por REPUTALIS». Sin tocar SW/PWA.
- **Por qué:** Misma línea visual que la pantalla de puntuación.
- **Qué falta:** —
- **Riesgos o pendientes:** Con muchas opciones la lista hace scroll interno; el título de mejora sigue siendo el configurado en Filament Encuesta. Place ID sigue en la **ficha del cliente** (no en Encuesta). Los campos `google_review_message_*` pueden quedar en BD sin UI.

### 2026-09-10 — Escala CSAT Pantone en números/caritas

- **Qué se cambió:** Recoloración de assets `public/survey-rating/numbers/{1-5}.{png,webp}` y `faces/cara{1-5}.{png,webp}` a la escala oficial: 1 `#EE2737`, 2 `#FF6A13`, 3 `#FFB81C`, 4 `#A4D65E`, 5 `#00B140` (misma forma; solo círculo de color). Alineados los hex de dashboard/gráficos reputación interna-externa.
- **Por qué:** Unificar encuesta y panel con la guía «Escala de valoración CSAT» Pantone.
- **Qué falta:** (opcional) centralizar la paleta en un único helper/config PHP para no duplicar hex.
- **Riesgos o pendientes:** Caché de navegador puede mostrar imágenes viejas hasta hard-refresh; no se ha tocado el SW de la PWA (rediseño futuro).

### 2026-09-10 — Encuesta pública: layout mockup (fase 1)

- **Qué se cambió:** Rediseño visual de `step-rating` en `resources/views/survey.blade.php`: fondo blanco, logo `public/img/logoReputalis.png`, pregunta editable **más grande**, textos fijos multidioma, sin card ámbar ni bloque Cliente/Empleado. Contenido pegado al logo (sin hueco grande), viewport `100dvh` sin scroll en móvil, footer siempre visible. SW PWA encuesta a **v8**. **No** se tocaron caritas ni imágenes de números.
- **Por qué:** Alinear la primera pantalla de la encuesta con el mockup de producto, dejando puntuación (círculos de color / caritas) para fases siguientes.
- **Qué falta:** Fase 2 — círculos 1–5 de colores del mockup (modo números). Fase 3 — adaptar modo caritas al mismo estilo.
- **Riesgos o pendientes:** PWA con caché antigua puede requerir recarga/cerrar pestaña; la demo NFC sigue visible solo cuando `showNfcDemo` es true. El empleado NFC sigue asociándose en backend aunque no se muestre en UI.

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
