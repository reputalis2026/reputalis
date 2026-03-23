# Resumen del Proyecto Reputalis

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
- **Páginas solo para rol cliente:** **ClientPuntosDeMejora** y **ClientEmpleados** (Filament Pages independientes, datos de `ownedClient`). Menú del cliente: Dashboard, Puntos de mejora, Empleados. Sin breadcrumbs de ClientResource. SuperAdmin/Distribuidor usan ClientResource (Clientes → [Cliente] → Puntos de mejora / Empleados).
- **DistributorResource:** CRUD de distribuidores (Client con rol distribuidor).
- **EmployeeResource, NfcTokenResource, CsatSurveyResource, SectorResource.** EmployeeResource no está en el menú; se usa desde Cliente → Empleados (cliente solo consulta en ClientEmpleados).
- **Puntos de mejora por cliente:** en ClientResource, subpágina por cliente (ClientImprovementConfig + ClientImprovementOption): título + lista de opciones (mín. 2). SuperAdmin y Distribuidor editan; el cliente solo consulta en ClientPuntosDeMejora.
- **Notificaciones/mensajes del panel:** AdminNotifications (SuperAdmin), DistributorMessages (distribuidor). Flujo: distribuidor crea cliente inactivo → notificación a SuperAdmin y distribuidor; SuperAdmin activa → notificación al distribuidor (PanelMessageService).

#### 5. Encuestas CSAT
- **API:** `POST /api/surveys/create` con `client_code`, `score` (1–5), opcionalmente `improvement_option_id` (si score 1–3), `employee_code`, etc. Límites por IP y por dispositivo.
- **Página pública:** `/survey`, `/survey/{client_code}` para rellenar encuesta (misma API de envío). Puntos de mejora por cliente se muestran si score 1–3.
- **Encuesta por NFC (token):** `GET /survey/nfc/{token}` resuelve `NfcToken` activo y renderiza la encuesta preasignando el empleado (crea `CsatSurvey` con `employee_id`).

#### 6. PWA “El Pulso del Día”
- `/pulse`: login para propietarios de cliente. Dashboard por `client_code` con métricas de satisfacción (CsatMetrics).

#### 7. Otros modelos y soporte
- **Sector** (catálogo), **ImprovementReason** (legacy), **PanelMessage / PanelMessageRecipient**. Servicios: **CsatMetrics**, **PanelMessageService**, **ImprovementReasonLabelResolver** (legacy).

---

## Estructura relevante (resumida)

- **Modelos:** User, Client, Employee, NfcToken, CsatSurvey, ImprovementReason, Sector, PanelMessage, PanelMessageRecipient, ClientImprovementConfig, ClientImprovementOption, ClientImprovementReasonLabel (legacy).
- **Filament:** ClientResource (CreateClient, EditClient, PuntosDeMejora, Empleados, etc.), DistributorResource, EmployeeResource, NfcTokenResource, CsatSurveyResource, SectorResource. Páginas solo para rol cliente: **ClientPuntosDeMejora**, **ClientEmpleados** (solo lectura, menú: Dashboard, Puntos de mejora, Empleados). Widgets: CsatStatsOverviewWidget, ClientsOverviewWidget.
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
- **CONTEXTO_PARA_IA.md** es la referencia actual; este resumen puede quedar desactualizado antes que aquel.

---

**Última actualización:** Marzo 2026  
**Estado:** Núcleo operativo (clientes, distribuidores, encuestas CSAT, Pulse, puntos de mejora, estado y vigencia con confirmación). Pendiente: Google, alertas, contratos, documentos.
