# Resumen del Proyecto Reputalis

## Regla de mantenimiento (obligatoria)

Estos 3 documentos son de seguimiento continuo y **no se pueden borrar nunca**:

- `CONTEXTO_PARA_IA.md`
- `DESCRIPCION_CLASES.md`
- `RESUMEN_PROYECTO.md`

Siempre se actualizan, nunca se eliminan.

## Información General

**Proyecto:** Reputalis  
**Framework:** Laravel 11.x  
**Panel administrativo:** Filament 3.x  
**Base de datos:** PostgreSQL (UUIDs con `gen_random_uuid()`)  
**Frontend:** TailwindCSS + Vite  
**PHP:** ^8.2

**Documento de referencia detallado:** para estado actual de modelos, rutas, convenciones y flujos, usar **CONTEXTO_PARA_IA.md**. Este resumen ofrece una visión general.

---

## Estado Actual del Proyecto

### Actualizacion reciente (Abr 2026)

- Restaurado provider del panel (`AdminPanelProvider`) y login Filament (`/admin/login` GET+POST operativo).
- Restaurada encuesta publica + NFC (`SurveyController`, `survey.blade.php`, `survey-nfc-invalid.blade.php`).
- Restaurada/normalizada subpagina **Encuesta** de cliente (`ClientResource -> PuntosDeMejora`).
- Ajustados permisos de empleados (crear/ver/editar/borrar por rol y cliente contenedor).
- **Empleados (abr 2026, detalle):** `EmployeeResource::canAccess()` alineado con Filament SPA (`canViewAny` o `canCreate`); página **Crear empleado** con `?client_id=` autoriza vía `ClientResource::canEdit`; **UUID de empleado** generado en modelo al crear (evita `employee_id` nulo en `nfctokens`); migración **FK cascade** al borrar empleado para `nfctokens`.
- Restaurado listado CSAT en panel (`CsatSurveyResource`) con alcance por rol.
- Agregado overlay global de carga/navegacion para Filament (hooks + script + markup).
- Añadido `canAccess()` en paginas críticas (`AdminNotifications`, `ClientCalls`, `DistributorMessages`) para bloquear URL directa.
- Añadida internacionalización mínima de la encuesta pública por cliente: columnas `es/pt/en`, `default_locale`, resolución por `Accept-Language` y fallback `idioma detectado -> default_locale -> es`.

### Implementado y funcional

#### 1. Autenticación y roles
- **User:** UUID, roles `superadmin`, `cliente`, `distribuidor`. Métodos `isSuperAdmin()`, `isClientOwner()`, `isDistributor()`. Relaciones con Client (owner, createdBy, panel messages).

#### 2. Clientes (entidad central)
- **Client** (tabla `clients`): antes “Pharmacy”; renombrado. Datos fiscales, contacto, `owner_id`, `created_by`, `is_active`, `fecha_inicio_alta`, `fecha_fin`, `logo`, soft deletes.
- **Soft deletes:** el listado incluye pestaña de “Clientes eliminados”; **solo SuperAdmin** puede verlos y restaurarlos.
- **Activación y vigencia (solo SuperAdmin en Editar cliente):**
  - Toggle “Cliente activo”.
  - **Duración de activación:** 12, 24 o 36 meses (se calcula `fecha_fin` desde `fecha_inicio_alta`) u “Otra fecha” (DatePicker manual). Obligatorio al activar.
  - **Confirmación al cambiar expiración:** si se cambia duración o fecha de fin y se pulsa Guardar, se muestra un modal (“Has cambiado la fecha de expiración”) con Aceptar/Cancelar; Cancelar no guarda, Aceptar guarda todo. Implementado con Livewire (`showExpirationConfirmModal`) y vista en el footer de EditClient.

#### 3. Distribuidores
- Mismo modelo **Client**, filtrado por `owner.role = distribuidor` (DistributorResource). Solo SuperAdmin gestiona distribuidores. Un distribuidor puede crear clientes (farmacias) que quedan con `created_by = distribuidor`.

#### 4. Panel Filament (`/admin`)
- **ClientResource:** CRUD de clientes (solo `owner.role = cliente`). SuperAdmin ve/edita todo; Distribuidor solo clientes que él creó. El rol cliente no ve “Clientes” en el menú; usa páginas propias de solo lectura (ClientPuntosDeMejora, ClientEmpleados).
- **Alta de cliente con encuesta inicial:** al crear un cliente en `CreateClient`, si todavía no existe configuración para ese `client_id`, se crea automáticamente `ClientImprovementConfig` con `default_locale = es`, `positive_scores = [4,5]`, pregunta principal, título y 2 opciones base traducidas a `es`, `pt`, `en`. Además, la página `PuntosDeMejora` aplica el mismo fallback para clientes antiguos: al abrir **Encuesta**, si faltaba esa configuración, la crea en ese momento con los mismos valores base. SuperAdmin y distribuidor pueden editar luego estos valores desde **Encuesta**; el rol cliente solo los visualiza en modo lectura.
- **Páginas solo para rol cliente:** **ClientPuntosDeMejora** y **ClientEmpleados** (Filament Pages independientes, datos de `ownedClient`). Menú del cliente: Dashboard, Encuesta, Empleados. Sin breadcrumbs de ClientResource. SuperAdmin/Distribuidor usan ClientResource (Clientes → [Cliente] → Encuesta / Empleados).
- **DistributorResource:** CRUD de distribuidores (Client con rol distribuidor).
- **EmployeeResource, NfcTokenResource, CsatSurveyResource, SectorResource.** EmployeeResource no está en el menú; se usa desde Cliente → Empleados (cliente solo consulta en ClientEmpleados). Alta/baja de empleado mantiene token NFC 1:1 (cascade en BD al eliminar).
- **Encuesta por cliente:** en ClientResource, subpágina **Encuesta** (modelo `PuntosDeMejora` en código): **ClientImprovementConfig** (`display_mode` números/caritas, `default_locale`, `positive_scores`, pregunta/título por idioma) + **ClientImprovementOption** (`label_es`, `label_pt`, `label_en`, mín. 2). SuperAdmin y Distribuidor editan; el rol cliente solo consulta en modo lectura. Assets opcionales: `public/survey-rating/numbers/*.png`, `public/survey-rating/faces/*.png`.
- **Notificaciones/mensajes del panel:** AdminNotifications (SuperAdmin), DistributorMessages (distribuidor). Flujo: distribuidor crea cliente inactivo → notificación a SuperAdmin y distribuidor; SuperAdmin activa → notificación al distribuidor (PanelMessageService).

#### 5. Encuestas CSAT
- **API:** `POST /api/surveys/create` con `client_code`, `score` (1–5), opcionalmente `improvement_option_id` si el score no está configurado como positivo, `employee_code`, etc. Límites por IP y por dispositivo. El contrato no cambia; la validación interna consulta `client_improvement_configs.positive_scores`.
- **Página pública:** `/survey`, `/survey/{client_code}` para rellenar encuesta (misma API de envío; score siempre 1–5). Resuelve idioma del cliente final por `Accept-Language`, normaliza `pt-BR`/`en-US`, usa traducción configurada si está completa, cae al `default_locale` de la encuesta y finalmente a español. Escala 1–5 con `data-score`; el JS decide flujo positivo vs punto de mejora con `surveyPositiveScores` en lugar de `score >= 4`; tras puntuación baja, `improvementBlock` muestra textos traducidos en botones/cards verticales de ancho completo y envía siempre `improvement_option_id`. Los mensajes genéricos de cierre (agradecimientos, subtítulos, CTA Google y envío) usan el mismo `surveyLocale`.
- **Encuesta por NFC (token):** `GET /survey/nfc/{token}` resuelve `NfcToken` activo y renderiza la encuesta preasignando el empleado (crea `CsatSurvey` con `employee_id`).

#### 6. PWA “El Pulso del Día”
- `/pulse`: login para propietarios de cliente. Dashboard por `client_code` con métricas de satisfacción (CsatMetrics).

#### 7. Otros modelos y soporte
- **Sector** (catálogo), **ImprovementReason** (legacy), **PanelMessage / PanelMessageRecipient**. Servicios: **CsatMetrics**, **PanelMessageService**, **ImprovementReasonLabelResolver** (legacy).

---

## Estructura relevante (resumida)

- **Modelos:** User, Client, Employee, NfcToken, CsatSurvey, ImprovementReason, Sector, PanelMessage, PanelMessageRecipient, ClientImprovementConfig, ClientImprovementOption, ClientImprovementReasonLabel (legacy).
- **Filament:** ClientResource (CreateClient, EditClient, PuntosDeMejora — UI “Encuesta”, Empleados, etc.), DistributorResource, EmployeeResource, NfcTokenResource, CsatSurveyResource, SectorResource. Páginas solo para rol cliente: **ClientPuntosDeMejora**, **ClientEmpleados** (solo lectura, menú: Dashboard, Encuesta, Empleados). Widgets: CsatStatsOverviewWidget, ClientsOverviewWidget.
- **Rutas:** web (Pulse, survey, admin), api (surveys/create).
- **Vistas propias:** encuesta pública, Pulse, modal de confirmación de expiración en EditClient (`edit-client-expiration-modal.blade.php`).

---

## Pendiente / no implementado

- Tablas/migraciones: contracts, (pharmacy/client)settings, googleoauthaccounts, googlelocations, googlereviewscache, googlemetricsdaily, alertevents, alertreads, alertrecipients, documents, benchmarkruns (existen migraciones antiguas o vacías; sin modelos/Resources).
- Integración Google (OAuth, ubicaciones, reseñas, métricas).
- Sistema de alertas, contratos, documentos, benchmarks.

---

## Notas importantes

- **UUID** en tablas principales; PostgreSQL `gen_random_uuid()`.
- Clientes y distribuidores comparten **Client**; se distinguen por `users.role` del `owner_id`.
- Código de cliente (`Client.code`) en URLs públicas (Pulse, encuesta, API).
- La internacionalización actual se limita a la encuesta pública por cliente; no traduce aún todo el panel ni mueve textos globales a archivos de idioma.
- **CONTEXTO_PARA_IA.md** es la referencia actual; este resumen puede quedar desactualizado antes que aquel.

---

**Última actualización:** 28 abril 2026 (documentado: encuesta pública multidioma por cliente, textos genéricos de cierre traducidos, layout de opciones negativas y valoraciones positivas configurables).  
**Estado:** Núcleo operativo (clientes, distribuidores, encuestas CSAT, Pulse, configuración **Encuesta** por cliente multidioma con `display_mode` en escala pública, estado y vigencia con confirmación). Pendiente: Google, alertas, contratos, documentos.
