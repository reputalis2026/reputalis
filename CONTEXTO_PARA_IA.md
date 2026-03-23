# Contexto del proyecto Reputalis para otra IA

Documento de handoff: estado actual de clases, modelos, rutas y convenciones para continuar el desarrollo con prompts.

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
   Cada cliente puede tener **empleados** (para asociar encuestas o tokens NFC), **tokens NFC** (para identificar dispositivo/empleado en encuestas físicas o kioscos), **puntos de mejora** (un único bloque: título general + lista de respuestas configurables, mínimo 2; configurado en Cliente → Puntos de mejora en Filament) y **motivos de mejora** globales (ImprovementReason: legacy; la encuesta usa ya solo la configuración por cliente). Los **sectores** (Farmacia, Herbolario, etc.) son un catálogo para clasificar clientes.

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
  - `improvementConfig()` → HasOne ClientImprovementConfig (configuración única: título + lista de respuestas para la encuesta negativa).

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
- **Score:** entero 1–5. Si score 1–3: puede ir `improvement_option_id` (opción elegida de la configuración de puntos de mejora del cliente) y/o `improvementreason_id` (compatibilidad).
- **Relaciones:** `client()` BelongsTo Client; `employee()` BelongsTo Employee (nullable); `improvementReason()` BelongsTo ImprovementReason (nullable); `improvementOption()` BelongsTo ClientImprovementOption (nullable).

### 2.5 NfcToken (`App\Models\NfcToken`)

- **Tabla:** `nfctokens`
- **PK:** UUID
- **Fillable:** `client_id`, `employee_id`, `token`, `is_active`
- **Restricción 1–1:** `employee_id` está en `NOT NULL` y tiene `UNIQUE` (un empleado no puede tener 2 tokens distintos).
- **Relaciones:** `client()` BelongsTo Client; `employee()` BelongsTo Employee.

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
- **Fillable:** `id`, `client_id`, `title`
- **Uso:** configuración única de puntos de mejora por cliente. Un **título** general (ej. “¿En qué podemos mejorar?”) y una lista de **opciones** (ClientImprovementOption). Una sola fila por cliente (`client_id` unique). **Importante:** al crear un config nuevo desde la app hay que asignar `id` en PHP antes de `save()` (p. ej. con `firstOrNew` + `$config->id = (string) Str::uuid()` si `!$config->exists`), porque PostgreSQL no siempre devuelve el UUID por defecto a Eloquent y las opciones necesitan `client_improvement_config_id` no nulo.
- **Relaciones:** `client()` BelongsTo Client; `options()` HasMany ClientImprovementOption (ordenado por sort_order, created_at).

### 2.11 ClientImprovementOption (`App\Models\ClientImprovementOption`)

- **Tabla:** `client_improvement_options`
- **PK:** UUID
- **Fillable:** `client_improvement_config_id`, `label`, `sort_order`
- **Uso:** cada respuesta/opción de la configuración; el usuario de la encuesta elige una y se guarda su `id` en `CsatSurvey.improvement_option_id`.
- **Relaciones:** `clientImprovementConfig()` BelongsTo ClientImprovementConfig.

### 2.12 ClientImprovementReasonLabel (`App\Models\ClientImprovementReasonLabel`) — legacy

- **Tabla:** `client_improvement_reason_labels`
- **Uso:** antes se usaba para “solo texto visible por cliente”. El flujo actual usa **ClientImprovementConfig + ClientImprovementOption** (un título + lista de respuestas por cliente). La pantalla “Personalizar motivos de mejora” fue eliminada; esta tabla se mantiene por compatibilidad; no interviene en la encuesta pública.

---

## 3. Filament Resources (panel `/admin`)

- **ClientResource:** CRUD de clientes (solo registros con `owner.role = cliente`). Permisos: SuperAdmin ve/edita todo; **Distribuidor solo ve/edita clientes que él creó** (`created_by === user->id`). Cliente (rol cliente) puede ver su propio cliente (`owner_id`) pero **no ve el ítem “Clientes” en el menú** (`shouldRegisterNavigation` false para cliente); ; el rol cliente usa las páginas independientes ClientPuntosDeMejora y ClientEmpleados (ver más abajo). Solo SuperAdmin puede eliminar (soft delete) y force delete. **Tabla de clientes:** además de Ver y Editar, hay acciones **“Puntos de mejora”** (icono `heroicon-o-light-bulb`) y **“Empleados”** (icono `heroicon-o-user-group`) que enlazan a las subpáginas del registro; visibles si `canView($record)`. **ViewClient y EditClient:** en la cabecera hay también acciones “Puntos de mejora” y “Empleados” con las mismas URLs. **Subpágina Empleados (ClientResource → Empleados):** listado de empleados del cliente en formato **tarjetas** (foto o placeholder, nombre, alias). **SuperAdmin** puede ver/crear/editar/borrar empleados de cualquier cliente. **Distribuidor** solo de clientes que él creó (`created_by === user->id`): ver, crear, editar, borrar. Crear/editar empleado se hace desde la subpágina “Añadir empleado” (enlace a EmployeeResource create con `?client_id=`) o “Editar” en cada tarjeta (EmployeeResource edit); tras guardar se redirige a la subpágina Empleados del cliente. **Estado y vigencia (solo Editar cliente, solo SuperAdmin):** sección “Estado y vigencia” con: (1) Toggle “Cliente activo”; (2) selector **“Duración de activación”** (12 meses, 24 meses, 36 meses, Otra fecha); (3) campo **“Fecha de fin (expiración)”**. Al activar el cliente es obligatorio elegir una duración o “Otra fecha”. Si se elige 12/24/36 meses, `fecha_fin` se calcula automáticamente desde `fecha_inicio_alta` + meses y el DatePicker se muestra deshabilitado con esa fecha; si se elige “Otra fecha”, el DatePicker queda habilitado para elegir manualmente. Al cargar un cliente ya activo, si su `fecha_fin` coincide con ~12, ~24 o ~36 meses desde `fecha_inicio_alta`, se preselecciona esa opción; si no, “Otra fecha” con la fecha guardada. Solo el SuperAdmin puede activar/desactivar y ver/editar esta sección. **Confirmación al cambiar expiración:** si al pulsar Guardar se ha modificado algo relacionado con la expiración (duración 12/24/36/Otra o fecha manual), aparece un modal de confirmación (“Has cambiado la fecha de expiración”, con texto en español y botones Aceptar/Cancelar). Si se cancela, no se guardan los cambios; si se acepta, se guarda todo con normalidad. Si no hay cambios en expiración, el guardado es directo sin modal. **Implementación:** el modal se controla en `EditClient` con la propiedad Livewire `$showExpirationConfirmModal` y la vista `resources/views/filament/resources/client-resource/pages/edit-client-expiration-modal.blade.php` (incluida vía `getFooter()`); métodos públicos `confirmExpirationSave()` y `closeExpirationConfirmModal()`.
- **Llamadas (ClientResource → Llamadas):** subpágina por cliente para SuperAdmin y Distribuidor. Muestra resumen **“Última llamada”** y **“Próxima llamada”** (badge “Vencida” si `next_call_at < now()`). Incluye botones **“Registrar llamada de hoy”** (modal con notas: crea `ClientCall` y actualiza `clients.last_call_at` y `clients.next_call_at = now() + 30 días`) y **“Programar próxima llamada”** (DateTimePicker para editar `next_call_at`). Debajo muestra el histórico `client_calls` con acción **“Editar notas”** (sin tocar `called_at`).
- **DistributorResource:** mismo modelo `Client`, filtrado por `owner.role = distribuidor`. Slug `/distribuidores`. Solo SuperAdmin puede ver/crear/editar.
- **EmployeeResource:** CRUD de empleados (formulario con name, alias, photo, position, is_active). **No aparece en el menú** (`shouldRegisterNavigation` false); la gestión se hace desde **Cliente → Empleados**. SuperAdmin: ver/crear/editar/borrar cualquier empleado. Distribuidor: ver/crear/editar/borrar solo empleados de clientes que él creó. Cliente: solo **ver** empleados de su `ownedClient` (desde el ítem de menú “Empleados”). Create acepta `?client_id=` en la URL y redirige tras guardar a ClientResource empleados del cliente.
- **NfcTokenResource:** **oculto** (sin menú global y sin CRUD accesible). La gestión del token se hace **desde** `EmployeeResource` dentro de la sección **“Token NFC”**.
- **CsatSurveyResource:** listado/consulta de encuestas CSAT (no creación desde panel; se crean por API).
- **SectorResource:** CRUD de sectores (nombre, orden).
- **ClientCalls (página global “Llamadas”):** listado de clientes ordenado por `next_call_at` ascendente (nulls al final). Visible para SuperAdmin y Distribuidor. Muestra “Última llamada” y “Próxima llamada”; si `next_call_at` está vencida se resalta en rojo. Acción “Ver” abre la subpágina del cliente `ClientResource → Llamadas`.

**Nota:** No existe recurso Filament para **ImprovementReason** (motivos base). Los códigos se gestionan por datos/seeders; la configuración visible al usuario es por cliente en **Puntos de mejora** (ClientResource → PuntosDeMejora).

**Widgets:** `CsatStatsOverviewWidget`, **`ClientsOverviewWidget`** (en el Dashboard; para distribuidores solo muestra clientes con `created_by = auth()->id()`).

**Páginas propias:** `Dashboard`, `EditProfile`, `Auth\Login`. **Páginas específicas para rol cliente (solo lectura):** **ClientPuntosDeMejora** (`App\Filament\Pages\ClientPuntosDeMejora`) y **ClientEmpleados** (`App\Filament\Pages\ClientEmpleados`). Son páginas Filament independientes (no ClientResource); cargan datos de `auth()->user()->ownedClient`. Solo aparecen en el menú para rol cliente (`shouldRegisterNavigation` y `canAccess`: `isClientOwner()` y existe `ownedClient`). **Menú lateral del cliente:** solo Dashboard, Puntos de mejora, Empleados (sin "Clientes" ni breadcrumbs de ClientResource). ClientPuntosDeMejora: título "Tus puntos de mejora", título del bloque + lista de respuestas en solo lectura. ClientEmpleados: título "Tus empleados", grid de tarjetas (foto, nombre, alias) en solo lectura. Sin botón "Volver al dashboard"; la navegación es por el menú lateral. SuperAdmin y Distribuidor acceden a Puntos de mejora y Empleados vía **ClientResource** (Clientes → [Cliente] → Puntos de mejora / Empleados). **Bandeja de notificaciones/mensajes:** `AdminNotifications` (solo SuperAdmin; menú "Sistema" → Notificaciones), `DistributorMessages` (solo distribuidor; menú "Comunicación" → Mensajes). La pantalla antigua “Personalizar motivos de mejora” / “Textos visibles por cliente” (**CustomizeImprovementReasons**) fue eliminada; el distribuidor y el cliente configuran puntos de mejora solo desde **Cliente → Puntos de mejora**. En las páginas con tabla (AdminNotifications, DistributorMessages) la tabla debe definir explícitamente `->query(fn () => $this->getTableQuery())` para evitar el error "Table must have a query()". Listado = recipients del usuario; acción Ver = modal con cuerpo del mensaje y enlace a editar cliente; al abrir se marca como leído. El login POST manual en `web.php` usa `User::findByIdentifier()` y redirige a `/admin`.

**Puntos de mejora por cliente (ClientResource):** la página **PuntosDeMejora** es una subpágina por cliente (subnavegación “Puntos de mejora”). **Estructura:** un único bloque por cliente: (1) título general (TextInput, ej. “¿En qué podemos mejorar?”) y (2) Repeater de respuestas/opciones (mínimo 2; añadir/editar/eliminar/reordenar). Usa **ClientImprovementConfig** (uno por cliente) y **ClientImprovementOption**. Al guardar: `firstOrNew(['client_id'])`, si no existe se asigna `$config->id = Str::uuid()`, se asigna título y `save()`; luego `$configId = $config->getKey()` y se borran opciones antiguas y se crean las nuevas con ese `$configId` (evita NOT NULL en `client_improvement_options`). **Permisos:** **SuperAdmin** ve y edita título y respuestas de cualquier cliente; **Distribuidor** solo de clientes que él creó (`created_by`); **Cliente (rol cliente)** solo puede **ver** (formulario en solo lectura, sin botón Guardar ni añadir/borrar). **Acceso:** SuperAdmin y Distribuidor ven el ítem “Clientes” en el menú; en la **tabla** de clientes cada fila tiene una acción **“Puntos de mejora”** (icono bombilla) que lleva a la configuración de ese cliente; además en las páginas **Ver Cliente** y **Editar Cliente** hay un botón de cabecera **“Puntos de mejora”**. El rol cliente usa la página independiente **ClientPuntosDeMejora** (menú “Puntos de mejora”), no ClientResource. Si un cliente intenta entrar a la lista de clientes por URL, se redirige a sus Puntos de mejora (ListClients::mount).

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

- `POST /api/surveys/create` → `App\Http\Controllers\Api\SurveyController@store` (middleware `throttle:surveys`). Parámetros: `client_code` (required), `employee_code` (opcional), `score` (1–5), `improvement_option_id` (UUID de ClientImprovementOption; obligatorio si score 1–3 y no se envía `improvement_reason_code`) o `improvement_reason_code` (compatibilidad), `locale_used`, `device_hash`. Rate limit por IP; límite adicional por `device_hash`: máx 10 encuestas por cliente en 24 h. Si se envía `improvement_option_id`, se valida que la opción pertenezca al cliente (vía option→clientImprovementConfig→client_id) y se guarda en `csat_surveys.improvement_option_id`.

---

## 5. Controladores y requests

- **App\Http\Controllers\PulseController:** login Pulse, `authenticate`, dashboard por `client_code`, métricas (usa `CsatMetrics`), servicio worker y manifest PWA.
- **App\Http\Controllers\SurveyController (web):** vistas de encuesta (show, manifest, sw). Cuando hay `$client`, carga **puntos de mejora con opciones** (`$client->improvementPoints()->with('options')`), filtra los que tienen al menos una opción y pasa `improvementPoints` a la vista (cada punto: título + lista de opciones con `id` y `label`). Si el cliente no tiene puntos con opciones, en puntuación negativa se envía solo el score sin motivo.
- **App\Http\Controllers\SurveyController (web):** también incluye `showNfc(token)` para `/survey/nfc/{token}`. Valida token activo, resuelve `client` + `employee`, y renderiza `survey.blade.php` con `showNfcDemo=false` y `employee_code` preasignado (para que el API cree `CsatSurvey` con `employee_id`).
- **App\Http\Controllers\Api\SurveyController:** `store` para crear encuesta; valida con `StoreSurveyRequest`; busca cliente por `client_code`, empleado opcional por `employee_code` (campo `name` del empleado). Si score 1–3: si viene `improvement_option_id`, valida que la opción pertenezca al cliente (vía ClientImprovementOption → clientImprovementConfig → client_id) y guarda en `csat_surveys.improvement_option_id`; opcionalmente rellena `improvementreason_id` desde `improvement_reason_code`. Si solo viene `improvement_reason_code`, valida motivo activo y guarda `improvementreason_id`.
- **App\Http\Requests\Api\StoreSurveyRequest:** reglas: `client_code` required; `score` 1–5; `improvement_option_id` nullable uuid; si score 1–3, obligatorio **uno de**: `improvement_reason_code` o `improvement_option_id` (validación en `withValidator`).

---

## 6. Soporte / servicios

- **App\Support\CsatMetrics:** métricas para dashboard (media de score, total, % satisfechos 4–5, cuenta “hoy”). Métodos estáticos: `getMetrics(?clientId, period)` con periodos `today`, `7`, `30`, `all`; cache 5 min por usuario/cliente/periodo. `dateRangeForPeriod(period)`. Los no-SuperAdmin solo ven su cliente.

- **App\Support\PanelMessageService:** creación de mensajes del panel. `notifyClientPendingActivation(Client $client)`: crea mensaje tipo `client_pending_activation`, destinatarios = todos los SuperAdmin + el distribuidor que creó el cliente (se llama desde `CreateClient::afterCreate()` cuando el usuario es distribuidor). `notifyClientActivated(Client $client)`: crea mensaje tipo `client_activated` para el distribuidor (`created_by` del cliente); se llama desde `EditClient::afterSave()` cuando el cliente pasa de inactivo a activo. **Importante:** al crear `PanelMessage` se genera el UUID en PHP (`Str::uuid()`) y se pasa en `create(['id' => $messageId, ...])` para que `$message->id` esté disponible al crear los `PanelMessageRecipient` (PostgreSQL no siempre devuelve el default a Eloquent).

- **App\Support\ImprovementReasonLabelResolver:** resolución de textos por defecto de motivos de mejora (legacy). La encuesta pública y la configuración de puntos de mejora usan **ClientImprovementConfig + ClientImprovementOption** (título y opciones por cliente); se envía `improvement_option_id`.

---

## 7. Migraciones relevantes (orden lógico)

- Creación base: `create_pharmacies_table` → `create_users_table` → `add_is_active`, `add_remember_token` → `create_sessions_table`.
- Entidades por cliente: `create_employees_table`, `create_nfctokens_table`, `ensure_improvementreasons_uuid`, `create_csat_surveys_table` (todas con `pharmacy_id` inicialmente).
- Renombrado: `rename_pharmacies_to_clients` (tabla y columnas `pharmacy_id` → `client_id`).
- Ajustes clients: `add_client_details_fields`, `create_sectors_table`, `add_created_by_to_clients_table`, `add_admin_email_to_users_table`, `add_fecha_inicio_alta_to_clients_table`, `add_fecha_fin_to_clients_table`, `migrate_pharmacy_owner_role_to_cliente`, `add_deleted_at_to_clients_table`, `add_logo_to_clients_table`.
- **Mensajes del panel:** `2026_02_24_100000_create_panel_messages_tables` (panel_messages, panel_message_recipients).
- **Etiquetas motivos de mejora por cliente (legacy):** `2026_02_24_100001_create_client_improvement_reason_labels_table`.
- **Puntos de mejora por cliente (modelo actual):** `2026_02_24_200000_create_client_improvement_configs_table` (client_id unique, title). `2026_02_24_200001_create_client_improvement_options_table` (client_improvement_config_id, label, sort_order; FK cascade). `2026_02_24_200002_switch_csat_surveys_to_improvement_option` (añade improvement_option_id FK a client_improvement_options, elimina improvement_point_option_id). `2026_02_24_200003_drop_old_improvement_point_tables` (elimina client_improvement_point_options y client_improvement_points). Las tablas antiguas por “motivo base” (improvement_reason_code) ya no se usan.

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
- Al crear modelos que luego se usan como FK (ej. PanelMessage antes de PanelMessageRecipient, o ClientImprovementConfig antes de ClientImprovementOption): si la tabla usa UUID por defecto en BD, generar el UUID en la app (`Str::uuid()`) y asignarlo al modelo antes de `save()` (o en `create(['id' => $id, ...])`) para evitar que el modelo quede sin `id` y falle al crear registros relacionados. En PuntosDeMejora se usa `firstOrNew` + `$config->id = Str::uuid()` cuando el config es nuevo, luego `$configId = $config->getKey()` para las opciones.
- Al crear cliente o distribuidor se crea el usuario propietario (owner) con rol correspondiente y opcionalmente contraseña; en edición hay toggle “Cambiar contraseña” para no obligar a rellenar siempre.

---

## 10. Pendiente / no implementado (referencia rápida)

- Migraciones/tablas: contracts, pharmacysettings/clientsettings, googleoauthaccounts, googlelocations, googlereviewscache, googlemetricsdaily, alertevents, alertreads, alertrecipients, documents, benchmarkruns (existen migraciones antiguas o vacías; no hay modelos ni recursos Filament para ellas).
- Integración Google (OAuth, ubicaciones, reseñas, métricas).
- Sistema de alertas (eventos, destinatarios, lecturas).
- Contratos, documentos, benchmarks.
- **RESUMEN_PROYECTO.md** es un resumen de alto nivel del estado del proyecto; para detalle (modelos, rutas, formularios, flujos) usar este documento (CONTEXTO_PARA_IA.md).

---

## Resumen de lo implementado (flujo distribuidor / notificaciones / motivos / puntos de mejora)

- **Creación de cliente por distribuidor:** En `CreateClient` el cliente se guarda siempre con `is_active = false` y `created_by = auth()->id()`. Si el usuario es distribuidor, en `afterCreate()` se llama a `PanelMessageService::notifyClientPendingActivation($this->record)`, que crea un mensaje y lo asigna a todos los SuperAdmin y al propio distribuidor.
- **Activación por SuperAdmin:** En `EditClient` se guarda `$wasInactiveBeforeSave = $this->record->is_active === false` en `mutateFormDataBeforeSave`. En `afterSave()`, si `$wasInactiveBeforeSave && $this->record->is_active`, se llama a `PanelMessageService::notifyClientActivated($this->record)` para notificar al distribuidor (`created_by` del cliente).
- **Puntos de mejora por cliente:** Cada cliente tiene **una** configuración (**ClientImprovementConfig**): título general (ej. “¿En qué podemos mejorar?”) + lista de **ClientImprovementOption** (mínimo 2). En el panel: Cliente → Puntos de mejora (título + Repeater de respuestas). Al guardar se usa `firstOrNew` + UUID explícito para el config nuevo y `$configId = $config->getKey()` para crear las opciones. En la encuesta (`/survey/{client_code}`): si puntuación 1–3 se muestra el título y las opciones como botones; al elegir una se envía `improvement_option_id`. La API acepta `improvement_option_id` y lo guarda en `csat_surveys.improvement_option_id`. SuperAdmin y Distribuidor editan; rol cliente solo ve (solo lectura). **Acceso:** SuperAdmin y Distribuidor ven “Clientes”; en la tabla hay acción “Puntos de mejora” por fila y en Ver/Editar cliente hay botón “Puntos de mejora” en la cabecera; rol cliente usa la página **ClientPuntosDeMejora** (menú “Puntos de mejora”).
- **Fecha de fin al activar cliente:** En la edición de cliente (solo SuperAdmin), al marcar “Cliente activo” es obligatorio indicar la vigencia. **Selector “Duración de activación”:** 12 meses, 24 meses, 36 meses u “Otra fecha”. Con 12/24/36, `fecha_fin` se calcula desde `fecha_inicio_alta` + meses y se muestra en un DatePicker deshabilitado; con “Otra fecha” se elige la fecha manualmente. Al cargar un cliente activo, si su `fecha_fin` encaja con ~12, ~24 o ~36 meses desde `fecha_inicio_alta` se preselecciona esa opción; si no, “Otra fecha” con la fecha guardada. La lógica de notificaciones al activar (PanelMessageService) se mantiene igual. **Confirmación al cambiar expiración:** si al guardar se detecta cambio en duración o fecha de fin (solo cuando el cliente está activo), se muestra un modal de confirmación en español (Aceptar/Cancelar); al cancelar no se guarda; al aceptar se guarda todo.
- **Empleados por cliente:** Catálogo de empleados (name, alias, photo, position, is_active) por cliente para futuras encuestas. **ClientResource → Empleados:** subpágina con listado en **tarjetas** (foto o placeholder, nombre, alias). SuperAdmin y Distribuidor pueden crear, editar y borrar; Distribuidor solo en clientes que él creó. Rol **cliente** usa la página **ClientEmpleados** (menú “Empleados”) en solo lectura, no ClientResource. EmployeeResource no está en el menú; create/edit se abren desde la subpágina (create con `?client_id=`; redirect tras guardar a Empleados del cliente).
- **NFC por empleado (UX en panel):** en `EmployeeResource` hay una sección **“Token NFC”** (solo lectura) y un campo con **“Copiar enlace de encuesta”** para `/survey/nfc/{token}`. En la subpágina de tarjetas **“Empleados”** se muestra un botón **“Copiar enlace”** que copia al portapapeles la URL de encuesta del token del empleado.

---

## 11. Cómo usar este contexto para prompts

- Para **añadir campos o relaciones:** indicar modelo y tabla; respetar UUID y convención de nombres (snake_case en BD, camelCase en relaciones).
- Para **nuevos Resources Filament:** reutilizar patrón de permisos de `ClientResource`/`DistributorResource` (canViewAny, canEdit, canView, canDelete por rol).
- Para **API:** mantener validación con Form Requests y respuestas JSON en español en mensajes de error.
- Para **nuevas entidades:** crear migración (UUID con `gen_random_uuid()` en PostgreSQL), modelo con relaciones y casts, y Resource si aplica al panel.
- Para **traducciones:** los títulos y opciones visibles en la encuesta salen de **ClientImprovementConfig** y **ClientImprovementOption** por cliente.
- **Encuesta negativa (1–3):** se muestra el título y las opciones del cliente; la respuesta elegida se guarda en `CsatSurvey.improvement_option_id`. La API acepta `improvement_option_id` (y opcionalmente `improvement_reason_code` por compatibilidad).

Si necesitas un detalle concreto (por ejemplo una firma de método o la lista exacta de columnas de una tabla), pide “según CONTEXTO_PARA_IA.md, [descripción]” y la otra IA puede basarse en este archivo.
