# Contexto del proyecto Reputalis para otra IA

Documento de handoff: estado actual de clases, modelos, rutas y convenciones para continuar el desarrollo con prompts.

---

## REGLA CRITICA DE REPOSITORIO (NO BORRAR)

Los siguientes archivos son de contexto vivo del proyecto y **NO se pueden borrar nunca**:

- `CONTEXTO_PARA_IA.md`
- `DESCRIPCION_CLASES.md`
- `RESUMEN_PROYECTO.md`

Si hay que actualizar información, se **edita** su contenido; no se eliminan del repositorio.

---

## Actualizacion reciente (Abr 2026)

Cambios aplicados y validados en esta fase:

- **Login panel Filament**: restaurado `AdminPanelProvider` y registro correcto de rutas GET/POST de `/admin/login`.
- **Overlay global panel**: activado en Filament con `->spa()` + hooks:
  - `PanelsRenderHook::BODY_START` -> markup de overlay global.
  - `PanelsRenderHook::SCRIPTS_BEFORE` -> script global (eventos `livewire:navigating`, `livewire:navigated`, hook `Livewire.request`).
  - Vistas nuevas: `resources/views/filament/components/panel-loading-overlay-markup.blade.php` y `panel-loading-overlay-script.blade.php`.
- **Encuesta publica/NFC**: restaurado `App\Http\Controllers\SurveyController` completo (incluye `/survey/nfc/{token}`), `resources/views/survey.blade.php` y `resources/views/survey-nfc-invalid.blade.php`.
- **ClientResource / Encuesta**: restaurada subpagina `PuntosDeMejora` y su ruta `/admin/clients/{record}/puntos-de-mejora`.
- **Encuesta publica multidioma por cliente**: la configuracion de encuesta usa columnas por idioma (`es`, `pt`, `en`) y `default_locale`; la vista publica resuelve el idioma del usuario final desde `Accept-Language`, normaliza variantes como `pt-BR`/`en-US`, cae al idioma por defecto de esa encuesta y finalmente a `es`.
- **Traducciones del panel cliente (fase 1)**: añadida base por archivos PHP en `lang/es`, `lang/en`, `lang/pt` (`panel`, `dashboard`, `client`, `survey`, `employees`, `common`). El panel autenticado Filament resuelve `App\Support\PanelLocale::SESSION_KEY` desde sesion mediante `App\Http\SetPanelLocale`, con fallback a `config('app.locale')` y finalmente `es`. El user menu de Filament incluye selector simple `Español / English / Português` con texto e icono/bandera secundaria; cambia por `GET /admin/language/{locale}` sin tocar BD.
- **Traducciones panel autenticado (fase 2)**: ampliada cobertura sin tocar BD ni permisos. El rol **cliente** tiene traducidos dashboard/widgets, perfil, encuesta en lectura/edicion Filament, empleados y pantallas alcanzables de empleado. Se inicio cobertura de **superadmin/distribuidor** en `ClientResource`, `EmployeeResource`, `CsatSurveyResource`, `SectorResource`, llamadas, notificaciones y mensajes. Siguen pendientes para fases siguientes recursos completos de distribuidores, NFC, sectores avanzados, textos dinamicos de negocio y auditoria final de cada modal/accion menos frecuente.
- **Traducciones panel autenticado (fase 3 cierre)**: completada cobertura por archivos `lang/` para `DistributorResource` y paginas, `NfcTokenResource` (aunque permanece oculto y sin CRUD), `ClientCalls`, `ViewEmployee` y hardcodes visibles residuales de `app/Filament` / `resources/views/filament`. El panel autenticado queda localizado todo lo posible sin BD: quedan fuera por diseño los textos persistidos en BD (mensajes ya guardados, nombres comerciales, sectores como datos), Pulse, encuesta publica y literales tecnicos/no visibles (`gray`, keys de estado, rutas, nombres de columnas).
- **Permisos empleados**:
  - `EmployeeResource` ahora alinea `canView/canEdit/canDelete` con permisos del cliente contenedor.
  - `CreateEmployee` valida `client_id` para distribuidor y cliente.
  - `ListEmployees` mantiene control estricto de acceso.
- **CSAT en panel**:
  - restaurado `CsatSurveyResource`.
  - visibilidad y filtros por rol (superadmin/distribuidor/cliente) ajustados.
- **Paginas Filament** `AdminNotifications`, `ClientCalls`, `DistributorMessages`: agregado `canAccess()` para bloquear acceso por URL a roles no permitidos.

### Sesion 8 abr 2026 — empleados, distribuidor y NFC

- **403 al abrir “Crear empleado” como distribuidor (o cliente):** Filament ejecuta `mountCanAuthorizeResourceAccess` → `EmployeeResource::canAccess()`, que por defecto era igual a `canViewAny()` (solo superadmin). **Solucion:** override de `EmployeeResource::canAccess()` = `canViewAny() || canCreate()`; el listado global sigue restringido en `ListEmployees::authorizeAccess()` (`canViewAny` solo).
- **Autorizacion fina en la pagina de alta:** `CreateEmployee::authorizeAccess()` con `?client_id=` valida `ClientResource::canEdit($client)`; sin query string delega en `parent` (`canCreate`). `EmployeeResource::canCreate()` para rol **cliente** usa `Client::where('owner_id', $user->id)->exists()` en lugar de depender solo de `ownedClient`.
- **Error al guardar empleado:** `nfctokens.employee_id` NOT NULL pero insert con null. Causa: `Employee` con `$incrementing = false` y UUID generado solo en PostgreSQL; tras el INSERT Eloquent no rellenaba `$model->id`, y `nfcTokens()->create()` no podia fijar la FK. **Solucion:** `Employee::booted()` → evento `creating`: asignar `Str::uuid()` si no hay clave.
- **Error al eliminar empleado:** FK `nfctokens.employee_id` seguia en `ON DELETE SET NULL` (migracion original) mientras `employee_id` es NOT NULL (migracion `2026_03_23_120500_unique_employee_id_on_nfctokens`). PostgreSQL intentaba poner NULL y fallaba. **Solucion:** migracion `2026_04_08_161000_nfctokens_employee_fk_cascade_on_delete.php` — FK con `cascadeOnDelete()` (al borrar empleado se borra su token).
- **Codigo CLIEN duplicado** (crear cliente/distribuidor): ya documentado en flujo CreateClient/CreateDistributor con `nextClientCode()` usando `Client::withTrashed()`; mantener coherencia si se toca de nuevo.

---

## 0. Contexto global: qué hace la aplicación

**Reputalis** es una plataforma de **gestión de reputación y satisfacción** orientada inicialmente a negocios del sector farmacia/parafarmacia (aunque el modelo de datos es genérico: “clientes”). Permite:

1. **Gestionar clientes de negocio (y distribuidores)**  
   Desde el panel de administración se dan de alta **clientes** (por ejemplo farmacias) con datos fiscales, contacto y un usuario propietario que accede al panel. Opcionalmente existen **distribuidores**: son también registros de tipo cliente cuyo propietario tiene rol distribuidor y puede crear/editar clientes (farmacias) que queden asociados a él. Un **SuperAdmin** ve todo y puede activar/desactivar clientes y dar fechas de vigencia (`fecha_inicio_alta`, `fecha_fin`).

2. **Recoger encuestas de satisfacción (CSAT)**  
   Los clientes de negocio pueden medir la satisfacción de sus usuarios finales (por ejemplo clientes de la farmacia) mediante **encuestas CSAT** (puntuación 1–5). Si la puntuación es baja (1–3), se puede indicar un **motivo de mejora** (tiempo de espera, trato, información, disponibilidad de producto, otro). Las encuestas se crean vía **API** (`POST /api/surveys/create`) con `client_code`, puntuación, opcionalmente empleado y motivo de mejora; hay límites por IP y por dispositivo para evitar abusos. En el panel se consultan y se explotan en métricas (media, total, % satisfechos, etc.).

3. **Panel “El Pulso del Día” (PWA)**  
   Los **propietarios de cliente** (rol cliente) pueden iniciar sesión en una aplicación aparte en `/pulse`, que actúa como PWA. Tras el login ven un **dashboard** con métricas de satisfacción de su propio cliente (resumen del día, tendencias). Solo tienen acceso al cliente del que son propietarios.

4. **Página pública de encuesta**  
   Existe una vista en `/survey` y `/survey/{client_code}` para que los usuarios finales rellenen la encuesta (por ejemplo desde un enlace o QR de la farmacia). La lógica de envío es la misma API de creación de encuestas; la página puede servir como frontend para enviar los datos.

5. **Configuración por cliente**  
   Cada cliente puede tener **empleados** (para asociar encuestas o tokens NFC), **tokens NFC** (para identificar dispositivo/empleado en encuestas físicas o kioscos), **encuesta (bloque CSAT negativo)** (un único bloque en Filament: modo visual de la escala 1–5, textos configurables por idioma `es`/`pt`/`en`, idioma por defecto, mínimo 2 respuestas; pantalla **Cliente → Encuesta**) y **motivos de mejora** globales (ImprovementReason: legacy; la encuesta usa ya solo la configuración por cliente). El **score** enviado a la API sigue siendo numérico 1–5; el campo `display_mode` solo cambia la presentación en la vista pública (`numbers` = dígitos, `faces` = imágenes) y las traducciones solo cambian los textos visibles. Los **sectores** (Farmacia, Herbolario, etc.) son un catálogo para clasificar clientes.

En resumen: la aplicación sirve para **dar de alta clientes (farmacias/distribuidores), recoger encuestas de satisfacción vía API o página pública, y que los propietarios vean métricas en el panel Filament y en la PWA “El Pulso del Día”**. Lo pendiente (Google, alertas, contratos, documentos, etc.) ampliaría reputación online e interna sin cambiar este núcleo.

---

## 1. Información general

| Aspecto | Valor |
|--------|--------|
| **Proyecto** | Reputalis |
| **Framework** | Laravel 11.x |
| **Panel admin** | Filament 3.x |
| **Base de datos** | PostgreSQL (UUIDs con `gen_random_uuid()`) |
| **Frontend** | TailwindCSS + Vite |
| **PHP** | ^8.2 |

- Todas las tablas principales usan **UUID** como clave primaria (`$keyType = 'string'`, `$incrementing = false`).
- La entidad central de negocio es **Cliente** (tabla `clients`). Antes se llamaba “Pharmacy”; se renombró con la migración `2026_02_12_000000_rename_pharmacies_to_clients.php`. Cualquier referencia antigua a “pharmacy” en migraciones se traduce a “client” en código actual.

### 1.1 Producción público / HTTPS (VPS)

- **Dominio:** `reputalis.org` y `www.reputalis.org` — tráfico **HTTPS** con **Let's Encrypt** (Certbot + plugin **Nginx**). Certificados: `/etc/letsencrypt/live/reputalis.org/` (renovación vía **`certbot.timer`**).
- **Nginx:** único sitio habilitado típico: `/etc/nginx/sites-enabled/reputalis` → `/etc/nginx/sites-available/reputalis` (Certbot suele añadir `ssl_certificate` y redirección HTTP→HTTPS).
- **Firewall (UFW):** política por defecto suele ser **deny incoming**; deben existir reglas **ALLOW** para **TCP 80** y **TCP 443**. Si solo está abierto el **80**, desde fuera **HTTPS no carga** aunque `curl` en el propio servidor funcione.
- **Laravel:** conviene `APP_URL=https://reputalis.org` en `.env` del servidor para URLs y cookies coherentes (no obligatorio para que Nginx sirva TLS).
- **Guía operativa detallada / backup del vhost:** `docs/GUIA_SSL_CERTBOT_PROGRESO.md`.

---

## 2. Modelos (Eloquent)

### 2.1 User (`App\Models\User`)

- **Tabla:** `users`
- **PK:** UUID
- **Fillable:** `id`, `name`, `fullname`, `dni`, `email`, `admin_email`, `password`, `role`, `client_id`
- **Roles:** constantes `ROLE_SUPERADMIN`, `ROLE_CLIENTE`, `ROLE_DISTRIBUIDOR`
- **Métodos:** `isSuperAdmin()`, `isClientOwner()`, `isDistributor()`, `getFilamentName()`, `findByIdentifier(string)` (login por email, insensible a mayúsculas)
- **Relaciones:**
  - `client()` → BelongsTo Client (cuando el usuario pertenece a un cliente)
  - `ownedClient()` → HasOne Client (propietario del cliente, `owner_id`)
  - `createdClients()` → HasMany Client (clientes creados por este usuario, `created_by`)
  - `receivedPanelMessages()` → HasMany PanelMessageRecipient (bandeja de notificaciones/mensajes del panel)
- **Panel Filament:** implementa `FilamentUser` y `HasName`; `canAccessPanel()` permite acceso si `role` es `superadmin`, `cliente` o `distribuidor`.
- **Seguridad:** contraseña con cast `'password' => 'hashed'`.

### 2.2 Client (`App\Models\Client`)

- **Tabla:** `clients`
- **PK:** UUID
- **Soft deletes:** sí (`SoftDeletes`)
- **Fillable:** `id`, `code`, `namecommercial`, `nif`, `razon_social`, `calle`, `pais`, `codigo_postal`, `ciudad`, `sector`, `telefono_negocio`, `telefono_cliente`, `owner_id`, `created_by`, `is_active`, `fecha_inicio_alta`, `fecha_fin`, `logo`, `last_call_at`, `next_call_at`
- **Casts:** `owner_id`, `created_by` string; `is_active` boolean; `fecha_inicio_alta`, `fecha_fin` date; `last_call_at`, `next_call_at` datetime.
- **Activación y vigencia:** al activar el cliente (`is_active = true`), la **fecha de fin (expiración)** es obligatoria. En el formulario de edición (solo SuperAdmin) se establece mediante un **selector de duración de activación**: 12, 24 o 36 meses (la app calcula `fecha_fin` desde `fecha_inicio_alta` + meses) u **“Otra fecha”** (DatePicker manual). Si el cliente está inactivo, no se exige duración ni fecha de fin.
- **Relaciones:**
  - `owner()` → BelongsTo User
  - `createdBy()` → BelongsTo User
  - `users()` → HasMany User
  - `employees()` → HasMany Employee
  - `csatSurveys()` → HasMany CsatSurvey
  - `nfcTokens()` → HasMany NfcToken
- `calls()` → HasMany ClientCall (ordenado desc por `called_at`)
  - `improvementReasonLabels()` → HasMany ClientImprovementReasonLabel (legacy; ver §2.11).
  - `improvementConfig()` → HasOne ClientImprovementConfig (configuración única: `display_mode` de la escala, `default_locale`, pregunta/título multidioma y lista de respuestas para la encuesta negativa).

**Nota:** “Distribuidores” en Filament son **los mismos Client** con `owner.role = distribuidor`. No hay modelo Distributor; se usa `Client` filtrado por rol en `DistributorResource`.

### 2.3 Employee (`App\Models\Employee`)

- **Tabla:** `employees`
- **PK:** UUID
- **Fillable:** `client_id`, `name`, `alias`, `photo`, `position`, `is_active`
- **Campos:** `name` (nombre completo o visible), `alias` (identificador corto para encuestas o como código), `photo` (ruta de imagen subida, disco `public`, directorio `employees`). Se usa como **catálogo de empleados por cliente** para poder asociar encuestas a un empleado concreto (`employee_id` en CsatSurvey) en el futuro.
- **Relaciones:** `client()` BelongsTo Client; `csatSurveys()` HasMany CsatSurvey; `nfcTokens()` HasOne NfcToken.
- **Regla NFC (refactor):** cada `Employee` tiene **exactamente 1 token lógico NFC** (aunque se puedan imprimir/cargar varias tarjetas con el mismo token). La URL pública usa ese token: `/survey/nfc/{token}`.

### 2.4 CsatSurvey (`App\Models\CsatSurvey`)

- **Tabla:** `csat_surveys`
- **PK:** UUID
- **Fillable:** `client_id`, `employee_id`, `score`, `improvementreason_id`, `improvement_option_id`, `locale_used`, `device_hash`
- **Score:** entero 1–5. Si score 1–3: puede ir `improvement_option_id` (opción elegida del bloque Encuesta / `ClientImprovementConfig` del cliente) y/o `improvementreason_id` (compatibilidad).
- **Relaciones:** `client()` BelongsTo Client; `employee()` BelongsTo Employee (nullable); `improvementReason()` BelongsTo ImprovementReason (nullable); `improvementOption()` BelongsTo ClientImprovementOption (nullable).

### 2.5 NfcToken (`App\Models\NfcToken`)

- **Tabla:** `nfctokens`
- **PK:** UUID
- **Fillable:** `client_id`, `employee_id`, `token`, `is_active`
- **Restricción 1–1:** `employee_id` está en `NOT NULL` y tiene `UNIQUE` (un empleado no puede tener 2 tokens distintos).
- **Relaciones:** `client()` BelongsTo Client; `employee()` BelongsTo Employee.
- **Borrado de empleados (importante PostgreSQL):** al eliminar un `Employee`, la app elimina primero su `NfcToken` asociado (hook `Employee::deleting`) para evitar violaciones por `NOT NULL` cuando la FK intenta aplicar `ON DELETE SET NULL`.

### 2.6 ClientCall (`App\Models\ClientCall`)

- **Tabla:** `client_calls`
- **PK:** UUID
- **Fillable:** `id`, `client_id`, `called_at`, `notes`
- **Campos:** `called_at` (fecha/hora de la llamada); `notes` (texto libre, nullable)
- **Relaciones:** `client()` BelongsTo Client
- **Integración con `clients`:** al registrar una llamada se actualizan `clients.last_call_at` y `clients.next_call_at = now() + 30 días`. El histórico se consulta vía `Client::calls()`.

### 2.7 ImprovementReason (`App\Models\ImprovementReason`)

- **Tabla:** `improvementreasons`
- **PK:** UUID
- **Fillable:** `code`, `is_active`
- **Uso:** códigos para motivos de mejora en encuestas CSAT (ej. `waiting_time`, `sympathy`, `information`, `product_availability`, `other`). Los textos se pueden resolver por i18n en frontend.
- **Relación:** `csatSurveys()` HasMany CsatSurvey.

### 2.7 Sector (`App\Models\Sector`)

- **Tabla:** `sectors`
- **Fillable:** `name`, `sort_order`
- **Sin UUID:** ID auto-incremental (tabla pequeña de catálogo).
- **Métodos:** `clientsCount()` (cuenta Client donde `sector` = nombre), `canDelete()` (true si no hay clientes con ese sector).

### 2.8 PanelMessage (`App\Models\PanelMessage`)

- **Tabla:** `panel_messages`
- **PK:** UUID (incluir `id` en fillable y generar en app con `Str::uuid()` al crear, para que los recipients tengan `panel_message_id` válido; PostgreSQL no siempre devuelve el default a Eloquent).
- **Fillable:** `id`, `type`, `sender_user_id`, `client_id`, `title`, `body`
- **Tipos:** `TYPE_CLIENT_PENDING_ACTIVATION`, `TYPE_CLIENT_ACTIVATED`
- **Relaciones:** `sender()` BelongsTo User; `client()` BelongsTo Client; `recipients()` HasMany PanelMessageRecipient.

### 2.9 PanelMessageRecipient (`App\Models\PanelMessageRecipient`)

- **Tabla:** `panel_message_recipients`
- **PK:** UUID
- **Fillable:** `panel_message_id`, `user_id`, `read_at`
- **Relaciones:** `panelMessage()` BelongsTo PanelMessage; `user()` BelongsTo User.
- **Métodos:** `isRead()`, `markAsRead()`.

### 2.10 ClientImprovementConfig (`App\Models\ClientImprovementConfig`)

- **Tabla:** `client_improvement_configs`
- **PK:** UUID
- **Fillable:** `id`, `client_id`, `default_locale`, `title`, `title_es`, `title_pt`, `title_en`, `display_mode`, `survey_question_text`, `survey_question_text_es`, `survey_question_text_pt`, `survey_question_text_en`, `positive_scores`
- **Constantes:** `DISPLAY_MODE_NUMBERS` (`numbers`), `DISPLAY_MODE_FACES` (`faces`).
- **`normalizeDisplayMode(?string)`:** devuelve `numbers` o `faces`; ante `null` o valor inválido hace **fallback a `numbers`** (compatibilidad con filas antiguas).
- **Locales soportados:** `es`, `pt`, `en`. `default_locale` pertenece solo a la encuesta publica del usuario final, no al panel ni al usuario propietario.
- **Helpers:** `normalizeLocale()` normaliza variantes tipo `pt-BR`/`en-US`; `normalizeDefaultLocale()` cae a `es`; `surveyQuestionTextForLocale()` y `titleForLocale()` aplican fallback por idioma; `positiveScores()` / `isPositiveScore()` resuelven qué scores 1..5 siguen el flujo positivo.
- **Valoraciones positivas:** `positive_scores` es JSONB/array por cliente. Por defecto `[4, 5]` para conservar el comportamiento histórico. En panel se valida como bloque final consecutivo hasta 5: válidos `[5]`, `[4,5]`, `[3,4,5]`, `[2,3,4,5]`; no se permite vacío, las cinco, ni combinaciones partidas.
- **Uso:** configuración única de la **encuesta (pregunta y opciones tras puntuación baja)** por cliente. Incluye **`display_mode`** (cómo se pinta la escala 1–5), pregunta principal y título por idioma, un idioma por defecto de encuesta, las valoraciones positivas y una lista de **opciones** (`ClientImprovementOption`). Las columnas antiguas `title` y `survey_question_text` se mantienen como compatibilidad y se sincronizan con español; la lectura/escritura del módulo usa las columnas multidioma. Una sola fila por cliente (`client_id` unique). **Importante:** al crear un config nuevo desde la app hay que asignar `id` en PHP antes de `save()` (p. ej. con `firstOrNew` + `$config->id = (string) Str::uuid()` si `!$config->exists`), porque PostgreSQL no siempre devuelve el UUID por defecto a Eloquent y las opciones necesitan `client_improvement_config_id` no nulo.
- **Relaciones:** `client()` BelongsTo Client; `options()` HasMany ClientImprovementOption (ordenado por sort_order, created_at).
- **Assets (encuesta pública):** imágenes bajo `public/survey-rating/` — `numbers/{1..5}.png` y `faces/cara{1..5}.png`, opcionalmente **`.webp`** homónimos para `<picture>`; rutas vía `asset()` en `survey.blade.php`.

### 2.11 ClientImprovementOption (`App\Models\ClientImprovementOption`)

- **Tabla:** `client_improvement_options`
- **PK:** UUID
- **Fillable:** `id`, `client_improvement_config_id`, `label`, `label_es`, `label_pt`, `label_en`, `sort_order`
- **Uso:** cada respuesta/opción de la configuración; el usuario de la encuesta elige una y se guarda su `id` en `CsatSurvey.improvement_option_id`. Las columnas multidioma solo cambian la presentación; la identidad para métricas sigue siendo el registro/UUID de la opción. La columna antigua `label` se mantiene como compatibilidad y se sincroniza con español.
- **Relaciones:** `clientImprovementConfig()` BelongsTo ClientImprovementConfig.

### 2.12 ClientImprovementReasonLabel (`App\Models\ClientImprovementReasonLabel`) — legacy

- **Tabla:** `client_improvement_reason_labels`
- **Uso:** antes se usaba para “solo texto visible por cliente”. El flujo actual usa **ClientImprovementConfig + ClientImprovementOption** (un título + lista de respuestas por cliente). La pantalla “Personalizar motivos de mejora” fue eliminada; esta tabla se mantiene por compatibilidad; no interviene en la encuesta pública.

---

## 3. Filament Resources (panel `/admin`)

- **ClientResource:** CRUD de clientes (solo registros con `owner.role = cliente`). Permisos: SuperAdmin ve/edita todo; **Distribuidor solo ve/edita clientes que él creó** (`created_by === user->id`). Cliente (rol cliente) puede ver su propio cliente (`owner_id`) pero **no ve el ítem “Clientes” en el menú** (`shouldRegisterNavigation` false para cliente); ; el rol cliente usa las páginas independientes ClientPuntosDeMejora y ClientEmpleados (ver más abajo). Solo SuperAdmin puede eliminar (soft delete) y force delete, y también puede **restaurar** clientes eliminados desde la pestaña “Clientes eliminados”. **Distribuidor no ve la pestaña de eliminados**. **Tabla de clientes:** además de Ver y Editar, hay acciones **“Encuesta”** (icono `heroicon-o-light-bulb`) y **“Empleados”** (icono `heroicon-o-user-group`) que enlazan a las subpáginas del registro; visibles si `canView($record)`. **ViewClient y EditClient:** en la cabecera hay también acciones “Encuesta” y “Empleados” con las mismas URLs. **Subpágina Empleados (ClientResource → Empleados):** listado de empleados del cliente en formato **tarjetas** (foto o placeholder, nombre, alias). **SuperAdmin** puede ver/crear/editar/borrar empleados de cualquier cliente. **Distribuidor** solo de clientes que él creó (`created_by === user->id`): ver, crear, editar, borrar. Crear/editar empleado se hace desde la subpágina “Añadir empleado” (enlace a EmployeeResource create con `?client_id=`) o “Editar” en cada tarjeta (EmployeeResource edit); tras guardar se redirige a la subpágina Empleados del cliente. **Estado y vigencia (solo Editar cliente, solo SuperAdmin):** sección “Estado y vigencia” con: (1) Toggle “Cliente activo”; (2) selector **“Duración de activación”** (12 meses, 24 meses, 36 meses, Otra fecha); (3) campo **“Fecha de fin (expiración)”**. Al activar el cliente es obligatorio elegir una duración o “Otra fecha”. Si se elige 12/24/36 meses, `fecha_fin` se calcula automáticamente desde `fecha_inicio_alta` + meses y el DatePicker se muestra deshabilitado con esa fecha; si se elige “Otra fecha”, el DatePicker queda habilitado para elegir manualmente. Al cargar un cliente ya activo, si su `fecha_fin` coincide con ~12, ~24 o ~36 meses desde `fecha_inicio_alta`, se preselecciona esa opción; si no, “Otra fecha” con la fecha guardada. Solo el SuperAdmin puede activar/desactivar y ver/editar esta sección. **Confirmación al cambiar expiración:** si al pulsar Guardar se ha modificado algo relacionado con la expiración (duración 12/24/36/Otra o fecha manual), aparece un modal de confirmación (“Has cambiado la fecha de expiración”, con texto en español y botones Aceptar/Cancelar). Si se cancela, no se guardan los cambios; si se acepta, se guarda todo con normalidad. Si no hay cambios en expiración, el guardado es directo sin modal. **Implementación:** el modal se controla en `EditClient` con la propiedad Livewire `$showExpirationConfirmModal` y la vista `resources/views/filament/resources/client-resource/pages/edit-client-expiration-modal.blade.php` (incluida vía `getFooter()`); métodos públicos `confirmExpirationSave()` y `closeExpirationConfirmModal()`.
- **Llamadas (ClientResource → Llamadas):** subpágina por cliente para SuperAdmin y Distribuidor. Muestra resumen **“Última llamada”** y **“Próxima llamada”** (badge “Vencida” si `next_call_at < now()`). Incluye botones **“Registrar llamada de hoy”** (modal con notas: crea `ClientCall` y actualiza `clients.last_call_at` y `clients.next_call_at = now() + 30 días`) y **“Programar próxima llamada”** (DateTimePicker para editar `next_call_at`). Debajo muestra el histórico `client_calls` con acción **“Editar notas”** (sin tocar `called_at`).
- **DistributorResource:** mismo modelo `Client`, filtrado por `owner.role = distribuidor`. Slug `/distribuidores`. Solo SuperAdmin puede ver/crear/editar.
- **EmployeeResource:** CRUD de empleados (formulario con name, alias, photo, position, is_active). **No aparece en el menú** (`shouldRegisterNavigation` false); la gestión se hace desde **Cliente → Empleados**. SuperAdmin: ver/crear/editar/borrar cualquier empleado. Distribuidor: ver/crear/editar/borrar solo empleados de clientes que él creó. Cliente: solo **ver** empleados de su `ownedClient` (desde el ítem de menú “Empleados”). Create acepta `?client_id=` en la URL y redirige tras guardar a ClientResource empleados del cliente.
- **NfcTokenResource:** **oculto** (sin menú global y sin CRUD accesible). La gestión del token se hace **desde** `EmployeeResource` dentro de la sección **“Token NFC”**.
- **CsatSurveyResource:** listado/consulta de encuestas CSAT (no creación desde panel; se crean por API).
- **SectorResource:** CRUD de sectores (nombre, orden).
- **ClientCalls (página global “Llamadas”):** listado de clientes ordenado por `next_call_at` ascendente (nulls al final). Visible para SuperAdmin y Distribuidor. Muestra “Última llamada” y “Próxima llamada”; si `next_call_at` está vencida se resalta en rojo. Acción “Ver” abre la subpágina del cliente `ClientResource → Llamadas`.

**Nota:** No existe recurso Filament para **ImprovementReason** (motivos base). Los códigos se gestionan por datos/seeders; la configuración visible al usuario es por cliente en **Encuesta** (ClientResource → página `PuntosDeMejora`, ruta/clase sin renombrar).

**Widgets:** `CsatStatsOverviewWidget`, **`ClientsOverviewWidget`** (en el Dashboard; para distribuidores solo muestra clientes con `created_by = auth()->id()`).

**Páginas propias:** `Dashboard`, `EditProfile`, `Auth\Login`. **Páginas específicas para rol cliente (solo lectura):** **ClientPuntosDeMejora** (`App\Filament\Pages\ClientPuntosDeMejora`) y **ClientEmpleados** (`App\Filament\Pages\ClientEmpleados`). Son páginas Filament independientes (no ClientResource); cargan datos de `auth()->user()->ownedClient`. Solo aparecen en el menú para rol cliente (`shouldRegisterNavigation` y `canAccess`: `isClientOwner()` y existe `ownedClient`). **Menú lateral del cliente:** solo Dashboard, Encuesta, Empleados (sin "Clientes" ni breadcrumbs de ClientResource). ClientPuntosDeMejora: título de página **“Tu encuesta”**, etiqueta de menú **“Encuesta”**; muestra modo de puntuación legible, título del bloque y lista de respuestas en solo lectura. ClientEmpleados: título "Tus empleados", grid de tarjetas (foto, nombre, alias) en solo lectura. Sin botón "Volver al dashboard"; la navegación es por el menú lateral. SuperAdmin y Distribuidor acceden a Encuesta y Empleados vía **ClientResource** (Clientes → [Cliente] → Encuesta / Empleados). **Bandeja de notificaciones/mensajes:** `AdminNotifications` (solo SuperAdmin; menú "Sistema" → Notificaciones), `DistributorMessages` (solo distribuidor; menú "Comunicación" → Mensajes). La pantalla antigua “Personalizar motivos de mejora” / “Textos visibles por cliente” (**CustomizeImprovementReasons**) fue eliminada; el distribuidor y el cliente configuran la encuesta solo desde **Cliente → Encuesta**. En las páginas con tabla (AdminNotifications, DistributorMessages) la tabla debe definir explícitamente `->query(fn () => $this->getTableQuery())` para evitar el error "Table must have a query()". Listado = recipients del usuario; acción Ver = modal con cuerpo del mensaje y enlace a editar cliente; al abrir se marca como leído. El login POST manual en `web.php` usa `User::findByIdentifier()` y redirige a `/admin`.

**Encuesta por cliente (ClientResource):** la página Filament **PuntosDeMejora** (`App\Filament\Resources\ClientResource\Pages\PuntosDeMejora`) se muestra al usuario como **“Encuesta”** (subnavegación y breadcrumbs). **Estructura:** un único bloque por cliente: (1) **modo de puntuación** — `Radio::make('display_mode')` (etiquetas **Números** / **Caritas**; valores `numbers` y `faces`; por defecto y fallback `numbers`); (2) título general (TextInput, ej. “¿En qué podemos mejorar?”); (3) Repeater de respuestas/opciones (mínimo 2; añadir/editar/eliminar/reordenar). Usa **ClientImprovementConfig** (uno por cliente) y **ClientImprovementOption**. Al guardar: `firstOrNew(['client_id'])`, si no existe se asigna `$config->id = Str::uuid()`, se persisten **`display_mode`**, título y `save()`; luego `$configId = $config->getKey()` y se borran opciones antiguas y se crean las nuevas con ese `$configId` (evita NOT NULL en `client_improvement_options`). **Además**, al guardar el Repeater se **filtran** los items con `label` vacío/null: se ignoran y solo se persisten respuestas con `label` rellenado (permite dejar bloques vacíos sin romper el guardado). **Inicialización automática al crear cliente:** `CreateClient::afterCreate()` crea esta configuración si no existe todavía para el cliente recién creado, con UUID generado en PHP, título por defecto **“¿En qué podemos mejorar?”** y dos opciones iniciales (`sort_order` 1 y 2): **“Tiempo de espera”** y **“Atención recibida”**. **Fallback en clientes legacy:** al abrir `PuntosDeMejora`, si el cliente aún no tenía `ClientImprovementConfig`, la página también lo crea automáticamente con ese mismo set por defecto para evitar pantallas vacías y asegurar edición inmediata. **Permisos:** **SuperAdmin** ve y edita modo, título y respuestas de cualquier cliente; **Distribuidor** solo de clientes que él creó (`created_by`); **Cliente (rol cliente)** solo puede **ver** (formulario en solo lectura, sin botón Guardar ni añadir/borrar). **Acceso:** SuperAdmin y Distribuidor ven el ítem “Clientes” en el menú; en la **tabla** de clientes cada fila tiene una acción **“Encuesta”** (icono bombilla) que lleva a la configuración de ese cliente; además en **Ver Cliente** y **Editar Cliente** hay un botón de cabecera **“Encuesta”**. El rol cliente usa la página independiente **ClientPuntosDeMejora** (menú “Encuesta”), no ClientResource. Si un cliente intenta entrar a la lista de clientes por URL, se redirige a **ClientPuntosDeMejora** (ListClients::mount).

**Encuesta por cliente multidioma (actualización Abr 2026):** `PuntosDeMejora` mantiene la misma regla de permisos (`canEditPuntos`: SuperAdmin edita todo, Distribuidor solo clientes con `created_by`, Cliente solo lectura). El formulario conserva `display_mode`, añade `default_locale` (`es`, `pt`, `en`) y exige campos completos para pregunta principal, título de bloque y cada opción en los tres idiomas (`*_es`, `*_pt`, `*_en`). Ya no se filtran opciones incompletas al guardar: si alguna traducción queda vacía se bloquea el guardado. La vista de solo lectura del cliente muestra el idioma por defecto, los tres textos de pregunta/título y las respuestas por idioma. La creación por defecto (`CreateClient::ensureDefaultImprovementConfig` y fallback de `PuntosDeMejora`) genera desde PHP el UUID de config/opciones y rellena los tres idiomas: pregunta base, “¿En qué podemos mejorar?” / “Em que podemos melhorar?” / “What can we improve?”, y opciones “Tiempo de espera” / “Tempo de espera” / “Waiting time” y “Atención recibida” / “Atendimento recebido” / “Service received”. Las columnas antiguas se conservan como compatibilidad y se sincronizan con español.

**Valoraciones positivas configurables (actualización Abr 2026):** en `PuntosDeMejora` se añade la sección **“Valoraciones positivas”** con 5 checkboxes (1..5) y ayuda “Las valoraciones no marcadas irán al punto de mejora.” La configuración se guarda en `client_improvement_configs.positive_scores` (JSONB). Validación: al menos una marcada, no pueden estar las cinco, y deben formar un bloque final consecutivo hasta 5. Nuevos clientes y clientes existentes quedan por defecto con `[4,5]`. La vista de solo lectura del cliente muestra este dato de forma informativa. Permisos sin cambios: SuperAdmin edita cualquier cliente, Distribuidor solo sus clientes, Cliente solo lectura.

---

## 4. Rutas

### 4.1 Web (`routes/web.php`)

- `GET /` → welcome.
- **Pulse (PWA “El Pulso del Día”):**
  - `GET /pulse` → login Pulse.
  - `POST /pulse/login` → autenticar; solo propietarios de cliente (`isClientOwner()`); redirige a `/pulse/{client_code}`.
  - `POST /logout` → cierre de sesión.
  - Dentro de `middleware('auth')`: `GET /api/pulse/{client_code}`, `GET /pulse/{client_code}/sw.js`, `GET /pulse/{client_code}/manifest.json`, `GET /pulse/{client_code}` (dashboard).
- **Encuesta pública:**
  - `GET /survey`, `GET /survey/{client_code}`, `GET /survey/{client_code}/sw.js`, `GET /manifest/{client_code}.json`.
  - `GET /survey/nfc/{token}` → resuelve token activo → cliente + empleado y renderiza la vista de encuesta CSAT preasignando `employee_id` vía `employee_code`.
- **Filament login fallback:** `POST /admin/login` (cuando el formulario no es manejado por Livewire).

### 4.2 API (`routes/api.php`)

- `POST /api/surveys/create` → `App\Http\Controllers\Api\SurveyController@store` (middleware `throttle:surveys`). Parámetros: `client_code` (required), `employee_code` (opcional), `score` (1–5), `improvement_option_id` (UUID de ClientImprovementOption; obligatorio si el score **no** está en `positive_scores` y no se envía `improvement_reason_code`) o `improvement_reason_code` (compatibilidad), `locale_used`, `device_hash`. Rate limit por IP; límite adicional por `device_hash`: máx 10 encuestas por cliente en 24 h. Si se envía `improvement_option_id`, se valida que la opción pertenezca al cliente (vía option→clientImprovementConfig→client_id) y se guarda en `csat_surveys.improvement_option_id`.

---

## 5. Controladores y requests

- **App\Http\Controllers\PulseController:** login Pulse, `authenticate`, dashboard por `client_code`, métricas (usa `CsatMetrics`), servicio worker y manifest PWA.
- **App\Http\Controllers\SurveyController (web):** vistas de encuesta (show, manifest, sw). Cuando hay `$client`, lee `$client->improvementConfig`, calcula `surveyDisplayMode` con `ClientImprovementConfig::normalizeDisplayMode($config?->display_mode)`, obtiene `surveyPositiveScores` desde `positive_scores` y resuelve `surveyLocale` desde `Accept-Language` (`es`, `pt`, `en`, normalizando variantes regionales). Si la traducción detectada está completa para pregunta, título y opciones, se usa; si no, cae a `default_locale` de esa encuesta; si tampoco es resoluble, cae a `es`. Construye **`improvementBlock`** con título y opciones traducidas, manteniendo siempre `id` de `ClientImprovementOption` para enviar `improvement_option_id`. **Landing** `/survey` sin cliente: `improvementBlock` nulo, `surveyDisplayMode` = `numbers`, `surveyLocale` = `es`, `surveyPositiveScores = [4,5]`.
- **App\Http\Controllers\SurveyController (web):** también incluye `showNfc(token)` para `/survey/nfc/{token}`. Valida token activo, resuelve `client` + `employee`, y renderiza `survey.blade.php` con `showNfcDemo=false` y `employee_code` preasignado (para que el API cree `CsatSurvey` con `employee_id`).
- **App\Http\Controllers\Api\SurveyController:** `store` para crear encuesta; valida con `StoreSurveyRequest`; busca cliente por `client_code`, empleado opcional por `employee_code` (campo `name` del empleado). Si el score **no** está en `client_improvement_configs.positive_scores`: si viene `improvement_option_id`, valida que la opción pertenezca al cliente (vía ClientImprovementOption → clientImprovementConfig → client_id) y guarda en `csat_surveys.improvement_option_id`; opcionalmente rellena `improvementreason_id` desde `improvement_reason_code`. Si solo viene `improvement_reason_code`, valida motivo activo y guarda `improvementreason_id`.
- **App\Http\Requests\Api\StoreSurveyRequest:** reglas: `client_code` required; `score` 1–5; `improvement_option_id` nullable uuid; si el score no está configurado como positivo, obligatorio **uno de**: `improvement_reason_code` o `improvement_option_id` (validación en `withValidator`, resolviendo el cliente por `client_code`).

### 5.1 Vista pública `resources/views/survey.blade.php` (escala y UX)

- **Score:** cada botón del 1–5 lleva `data-score="{{ $n }}"`; la API y `CsatSurvey.score` siguen siendo enteros 1–5.
- **Flujo positivo vs mejora:** el JS recibe `POSITIVE_SCORES` desde `SurveyController` y usa `isPositiveScore(score)` en lugar de la antigua condición fija `score >= 4`. Si el score está marcado como positivo, se envía y se muestra el cierre positivo; si no, se muestra `improvementBlock` y se envía `improvement_option_id`.
- **Modo caras (`display_mode = faces`):** cinco botones con `<picture>`: `source` WebP `survey-rating/faces/cara{n}.webp` (opcional en disco) e `<img>` PNG `cara{n}.png` vía `asset()`.
- **Modo números (`display_mode = numbers`):** si existe `public/survey-rating/numbers/{n}.png`, mismo patrón `<picture>` con `.webp` + `.png`; si no hay PNG para ese dígito, se muestra el número en texto dentro de un botón **con borde** (estilo clásico).
- **Sin marco en imágenes:** clases `btn-score--faces` y `btn-score--numbers` aplican en CSS borde y fondo transparentes para que solo se vea el gráfico; hover ligero (opacidad/escala) y `focus-visible` con anillo ámbar.
- **Spinner hasta carga de media:** si modo **caras**, o modo **números** con al menos un PNG en `numbers/`, Blade define `$ratingSpinnerReveal` y se renderizan `#rating-spinner` (5 celdas con SVG `animate-spin`) y `#rating-buttons` (grid real, oculto al inicio). El script `waitForRatingImages()` cuenta cada `<img>` bajo `#rating-buttons` (`load` y `error` cuentan igual); al completar todas o tras **3000 ms**, se oculta el spinner y se muestra el grid con transición de opacidad. Si no hay imágenes de rating (solo dígitos), no hay doble grid ni ese JS.
- **`<head>` en PWA:** hasta cinco `link rel="preload" as="image" fetchpriority="high"` según exista `faces/cara1.png` (precarga caras existentes) o, en caso contrario, números `.png` que existan (`file_exists` por archivo).
- **Overlay de envío:** `id="overlay"` / `id="overlay-text"` solo para el estado “Enviando…” (`z-10`); la carga inicial de imágenes **no** usa ese overlay.
- **Textos genéricos del cierre:** la vista usa el mismo `surveyLocale` resuelto por `SurveyController` para mensajes no configurables por cliente: agradecimiento positivo, subtítulo, CTA “Dejar reseña en Google”, agradecimiento tras mejora, subtítulo negativo, fallback “¿Por qué?” y “Enviando...”. Estos textos están en un array local de Blade (`es`, `pt`, `en`), no en la configuración por cliente.
- **Opciones negativas:** `improvementBlock.options` se renderiza como lista vertical de botones/cards de ancho completo (`space-y-3`, `rounded-2xl`, padding amplio, sombra ligera y foco ámbar) para móvil. Al pulsar una opción se añade estado visual ámbar antes del envío, pero se mantiene intacto el `data-option-id` y el payload sigue enviando `improvement_option_id`.

### 5.2 Service worker de encuesta (`SurveyController::sw`)

- Caché `reputalis-pwa-{clientCode}-**v5**` (subida desde v4 al añadir `positive_scores` al flujo público). En `install`, `PRECACHE_URLS` (JSON inyectado desde PHP) incluye la URL de la página de encuesta del cliente, el manifiesto y **cada PNG** bajo `survey-rating/faces/` y `survey-rating/numbers/` que exista en disco (`is_file`). Cada recurso se añade con `cache.add(url).catch(...)` para no abortar todo el install si falla uno. En `fetch`, además de `/survey/*` y `/manifest/*`, se cachean respuestas GET `ok` de **`/survey-rating/`** para reutilizar assets en visitas posteriores.
- Los **WebP** no van en el precache del SW (el `<picture>` elige WebP o PNG según el navegador); se pueden añadir después en PHP de forma análoga si se desea.

### 5.3 Scripts de assets

- **`scripts/process_survey_faces.py`:** convierte/corta/normaliza PNG de caritas (fondo casi blanco → transparencia, canvas 512×512). Requiere Pillow (`scripts/requirements-faces.txt`). Uso: cinco rutas de entrada en orden 1…5.
- **`public/survey-rating/`:** `faces/` y `numbers/`; convención de nombres `cara1.png`…`cara5.png`, `1.png`…`5.png`, y opcionalmente `.webp` junto a cada PNG.

---

## 6. Soporte / servicios

- **App\Support\CsatMetrics:** métricas para dashboard (media de score, total, % satisfechos 4–5, cuenta “hoy”). Métodos estáticos: `getMetrics(?clientId, period)` con periodos `today`, `7`, `30`, `all`; cache 5 min por usuario/cliente/periodo. `dateRangeForPeriod(period)`. Los no-SuperAdmin solo ven su cliente.
- **Optimización dashboard (abril 2026):** migración `2026_04_07_110000_add_high_priority_dashboard_indexes` crea índices en `csat_surveys(created_at)`, `csat_surveys(client_id, created_at)`, `csat_surveys(client_id, created_at, score)` y en `clients(is_active, namecommercial)` + `clients(is_active, fecha_fin)` para acelerar tabs y métricas.

- **App\Support\PanelMessageService:** creación de mensajes del panel. `notifyClientPendingActivation(Client $client)`: crea mensaje tipo `client_pending_activation`, destinatarios = todos los SuperAdmin + el distribuidor que creó el cliente (se llama desde `CreateClient::afterCreate()` cuando el usuario es distribuidor). `notifyClientActivated(Client $client)`: crea mensaje tipo `client_activated` para el distribuidor (`created_by` del cliente); se llama desde `EditClient::afterSave()` cuando el cliente pasa de inactivo a activo. **Importante:** al crear `PanelMessage` se genera el UUID en PHP (`Str::uuid()`) y se pasa en `create(['id' => $messageId, ...])` para que `$message->id` esté disponible al crear los `PanelMessageRecipient` (PostgreSQL no siempre devuelve el default a Eloquent).

- **App\Support\ImprovementReasonLabelResolver:** resolución de textos por defecto de motivos de mejora (legacy). La encuesta pública y la configuración **Encuesta** en panel usan **ClientImprovementConfig + ClientImprovementOption** (título y opciones por cliente); se envía `improvement_option_id`.

---

## 7. Migraciones relevantes (orden lógico)

- Creación base: `create_pharmacies_table` → `create_users_table` → `add_is_active`, `add_remember_token` → `create_sessions_table`.
- Entidades por cliente: `create_employees_table`, `create_nfctokens_table`, `ensure_improvementreasons_uuid`, `create_csat_surveys_table` (todas con `pharmacy_id` inicialmente).
- Renombrado: `rename_pharmacies_to_clients` (tabla y columnas `pharmacy_id` → `client_id`).
- Ajustes clients: `add_client_details_fields`, `create_sectors_table`, `add_created_by_to_clients_table`, `add_admin_email_to_users_table`, `add_fecha_inicio_alta_to_clients_table`, `add_fecha_fin_to_clients_table`, `migrate_pharmacy_owner_role_to_cliente`, `add_deleted_at_to_clients_table`, `add_logo_to_clients_table`.
- **Mensajes del panel:** `2026_02_24_100000_create_panel_messages_tables` (panel_messages, panel_message_recipients).
- **Etiquetas motivos de mejora por cliente (legacy):** `2026_02_24_100001_create_client_improvement_reason_labels_table`.
- **Encuesta por cliente / ClientImprovementConfig (modelo actual):** `2026_02_24_200000_create_client_improvement_configs_table` (client_id unique, title). `2026_02_24_200001_create_client_improvement_options_table` (client_improvement_config_id, label, sort_order; FK cascade). `2026_02_24_200002_switch_csat_surveys_to_improvement_option` (añade improvement_option_id FK a client_improvement_options, elimina improvement_point_option_id). `2026_02_24_200003_drop_old_improvement_point_tables` (elimina client_improvement_point_options y client_improvement_points). **`2026_04_02_120000_add_display_mode_to_client_improvement_configs_table`:** columna `display_mode` (string, default `numbers`). **`2026_04_28_080500_add_multilingual_fields_to_client_improvement_tables`:** añade `default_locale`, `title_es/pt/en`, `survey_question_text_es/pt/en` y `label_es/pt/en`; backfill de español desde columnas antiguas, traducciones base para valores estándar y copia del texto original para opciones personalizadas no inferibles. **`2026_04_28_100500_add_positive_scores_to_client_improvement_configs_table`:** añade `positive_scores` JSONB y backfill `[4,5]` para conservar el comportamiento histórico. Las tablas antiguas por “motivo base” (improvement_reason_code) ya no se usan.
- **NFC / empleados (integridad):** `2026_03_23_120500_unique_employee_id_on_nfctokens` (employee_id NOT NULL + indice unico). **`2026_04_08_161000_nfctokens_employee_fk_cascade_on_delete`:** sustituye `ON DELETE SET NULL` por **`ON DELETE CASCADE`** en `nfctokens.employee_id` → `employees.id` (obligatorio tras NOT NULL en employee_id).

---

## 8. Seeders

- **DatabaseSeeder:** llama a `ImprovementReasonSeeder`, `SectorSeeder` y crea usuario SuperAdmin (email y pass en el seeder).
- **ImprovementReasonSeeder:** `firstOrCreate` por código: `waiting_time`, `sympathy`, `information`, `product_availability`, `other`.
- **SectorSeeder:** sectores por defecto: Farmacia, Herbolario, Parafarmacia, Centro de salud, Otro (con `sort_order`).

---

## 9. Convenciones de código

- Respuestas en **español** (mensajes, etiquetas, documentación para el usuario).
- Clientes y distribuidores comparten modelo **Client** y tabla **clients**; se distinguen por `users.role` del `owner_id`.
- Código de cliente (`Client.code`) se usa en URLs públicas (Pulse, encuesta, API) y se genera en las páginas Create (Client/Distributor).
- Permisos Filament: comprobar `auth()->user()->isSuperAdmin()`, `isClientOwner()`, `isDistributor()` y relaciones `owner_id` / `created_by` según recurso.
- **Distribuidor:** en listados y widgets de clientes (ClientResource, ClientsOverviewWidget) filtrar por `created_by = auth()->id()` para que solo vea los clientes que él creó; `canView`/`canEdit` por registro deben comprobar `$record->created_by === $user->id`.
- **Listados Filament (rendimiento):** en `ListClients::getTableQuery()` se aplica eager loading explícito de `createdBy` (`with(['createdBy:id,name,fullname,email'])`) y selección mínima de columnas de `clients` usadas por tabla/tabs para reducir payload y evitar N+1.
- Al crear modelos que luego se usan como FK (ej. PanelMessage antes de PanelMessageRecipient, o ClientImprovementConfig antes de ClientImprovementOption): si la tabla usa UUID por defecto en BD, generar el UUID en la app (`Str::uuid()`) y asignarlo al modelo antes de `save()` (o en `create(['id' => $id, ...])`) para evitar que el modelo quede sin `id` y falle al crear registros relacionados. En la página Filament **Encuesta** (`PuntosDeMejora`) se usa `firstOrNew` + `$config->id = Str::uuid()` cuando el config es nuevo, luego `$configId = $config->getKey()` para las opciones.
- **Employee:** con `$incrementing = false` y default UUID en PostgreSQL, **siempre** asignar el UUID en PHP en `creating` (`App\Models\Employee::booted`) para que tras guardar exista `$employee->id` y las relaciones (`nfcTokens()->create()`) rellenen `employee_id`. Sin eso, `nfctokens` puede insertarse con `employee_id` null.
- **nfctokens:** la FK a `employees` debe ser **CASCADE** al borrar empleado, no SET NULL, porque `employee_id` es NOT NULL.
- Al crear cliente o distribuidor se crea el usuario propietario (owner) con rol correspondiente y opcionalmente contraseña; en edición hay toggle “Cambiar contraseña” para no obligar a rellenar siempre.

---

## 10. Pendiente / no implementado (referencia rápida)

- Migraciones/tablas: contracts, pharmacysettings/clientsettings, googleoauthaccounts, googlelocations, googlereviewscache, googlemetricsdaily, alertevents, alertreads, alertrecipients, documents, benchmarkruns (existen migraciones antiguas o vacías; no hay modelos ni recursos Filament para ellas).
- Integración Google (OAuth, ubicaciones, reseñas, métricas).
- Sistema de alertas (eventos, destinatarios, lecturas).
- Contratos, documentos, benchmarks.
- **RESUMEN_PROYECTO.md** es un resumen de alto nivel del estado del proyecto; para detalle (modelos, rutas, formularios, flujos) usar este documento (CONTEXTO_PARA_IA.md).

---

## Resumen de lo implementado (flujo distribuidor / notificaciones / motivos / encuesta por cliente)

- **Creación de cliente por distribuidor:** En `CreateClient` el cliente se guarda siempre con `is_active = false` y `created_by = auth()->id()`. Si el usuario es distribuidor, en `afterCreate()` se llama a `PanelMessageService::notifyClientPendingActivation($this->record)`, que crea un mensaje y lo asigna a todos los SuperAdmin y al propio distribuidor.
- **Activación por SuperAdmin:** En `EditClient` se guarda `$wasInactiveBeforeSave = $this->record->is_active === false` en `mutateFormDataBeforeSave`. En `afterSave()`, si `$wasInactiveBeforeSave && $this->record->is_active`, se llama a `PanelMessageService::notifyClientActivated($this->record)` para notificar al distribuidor (`created_by` del cliente).
- **Encuesta por cliente:** Cada cliente tiene **una** configuración (**ClientImprovementConfig**): **`display_mode`** (presentación de la escala 1–5 en la encuesta pública: `numbers` o `faces`), **`default_locale`** de la encuesta pública y textos configurables en `es`, `pt`, `en` para pregunta principal, título del bloque y opciones. En el panel: Cliente → **Encuesta** (Radio modo + selector idioma por defecto + campos por idioma + Repeater de respuestas multidioma). Al guardar se usa `firstOrNew` + UUID explícito para el config nuevo y `$configId = $config->getKey()` para crear las opciones. En la encuesta (`/survey/{client_code}` y NFC): si puntuación 1–3 se muestra el título y las opciones en el idioma resuelto por `Accept-Language` → `default_locale` → `es`; la escala 1–5 respeta `surveyDisplayMode`; al elegir una opción se envía `improvement_option_id`. La API acepta `improvement_option_id` y lo guarda en `csat_surveys.improvement_option_id` (sin cambios de contrato). SuperAdmin y Distribuidor editan; rol cliente solo ve (solo lectura).
- **Fecha de fin al activar cliente:** En la edición de cliente (solo SuperAdmin), al marcar “Cliente activo” es obligatorio indicar la vigencia. **Selector “Duración de activación”:** 12 meses, 24 meses, 36 meses u “Otra fecha”. Con 12/24/36, `fecha_fin` se calcula desde `fecha_inicio_alta` + meses y se muestra en un DatePicker deshabilitado; con “Otra fecha” se elige la fecha manualmente. Al cargar un cliente activo, si su `fecha_fin` encaja con ~12, ~24 o ~36 meses desde `fecha_inicio_alta` se preselecciona esa opción; si no, “Otra fecha” con la fecha guardada. La lógica de notificaciones al activar (PanelMessageService) se mantiene igual. **Confirmación al cambiar expiración:** si al guardar se detecta cambio en duración o fecha de fin (solo cuando el cliente está activo), se muestra un modal de confirmación en español (Aceptar/Cancelar); al cancelar no se guarda; al aceptar se guarda todo.
- **Empleados por cliente:** Catálogo de empleados (name, alias, photo, position, is_active) por cliente para futuras encuestas. **ClientResource → Empleados:** subpágina con listado en **tarjetas** (foto o placeholder, nombre, alias). SuperAdmin y Distribuidor pueden crear, editar y borrar; Distribuidor solo en clientes que él creó. Rol **cliente** usa la página **ClientEmpleados** (menú “Empleados”) en solo lectura, no ClientResource. EmployeeResource no está en el menú; create/edit se abren desde la subpágina (create con `?client_id=`; redirect tras guardar a Empleados del cliente). **Permisos recurso:** `EmployeeResource::canAccess()` debe permitir crear/ver rutas del recurso a quien `canCreate()`/`canViewAny()` aplique (Filament llama `canAccess` al montar paginas del recurso). **Alta:** con `client_id` en query, autorizacion alineada a `ClientResource::canEdit` en `CreateEmployee`.
- **NFC por empleado (UX en panel):** en `EmployeeResource` hay una sección **“Token NFC”** (solo lectura) y un campo con **“Copiar enlace de encuesta”** para `/survey/nfc/{token}`. En la subpágina de tarjetas **“Empleados”** se muestra un botón **“Copiar enlace”** que copia al portapapeles la URL de encuesta del token del empleado. Un empleado tiene como mucho un token; borrar empleado cascada el token en BD.

---

## 11. Cómo usar este contexto para prompts

- Para **añadir campos o relaciones:** indicar modelo y tabla; respetar UUID y convención de nombres (snake_case en BD, camelCase en relaciones).
- Para **nuevos Resources Filament:** reutilizar patrón de permisos de `ClientResource`/`DistributorResource` (canViewAny, canEdit, canView, canDelete por rol).
- Para **API:** mantener validación con Form Requests y respuestas JSON en español en mensajes de error.
- Para **nuevas entidades:** crear migración (UUID con `gen_random_uuid()` en PostgreSQL), modelo con relaciones y casts, y Resource si aplica al panel.
- Para **traducciones:** los títulos y opciones visibles en la encuesta salen de **ClientImprovementConfig** y **ClientImprovementOption** por cliente. El aspecto de la escala 1–5 (números vs caritas) lo fija `display_mode` en `ClientImprovementConfig`, no la API.
- **Encuesta negativa (1–3):** se muestra el título y las opciones del cliente; la respuesta elegida se guarda en `CsatSurvey.improvement_option_id`. La API acepta `improvement_option_id` (y opcionalmente `improvement_reason_code` por compatibilidad).

Si necesitas un detalle concreto (por ejemplo una firma de método o la lista exacta de columnas de una tabla), pide “según CONTEXTO_PARA_IA.md, [descripción]” y la otra IA puede basarse en este archivo.
