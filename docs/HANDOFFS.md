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
