# Descripción de clases principales

**Propósito:** catálogo orientado a **clases** (modelos, Filament, controladores, servicios). No describe campo a campo cada tabla; para eso usa [`DOCUMENTACION_TABLAS_BD.md`](DOCUMENTACION_TABLAS_BD.md). Normas y orden de lectura: [`README_AI.md`](README_AI.md).

Los documentos `CONTEXTO_PARA_IA.md`, `DESCRIPCION_CLASES.md` y `RESUMEN_PROYECTO.md` son referencia viva: **actualizar**, no borrar del repositorio.

---

- **App\Models\User**: Representa a los usuarios del sistema (superadmin, cliente, distribuidor), controla el acceso al panel Filament y expone helpers como `isSuperAdmin()`, `isClientOwner()` e `isDistributor()`, además de relaciones con el cliente que posee y los mensajes de panel recibidos.

- **App\Models\Client**: Entidad central de cliente (farmacia/negocio); guarda datos fiscales, de contacto, estado y vigencia, y se relaciona con su propietario (`owner`), el usuario que lo creó (`createdBy`), sus usuarios internos, empleados, encuestas CSAT, tokens NFC, configuración de **encuesta** (`ClientImprovementConfig`: idioma por defecto, textos multidioma, valoraciones positivas, opciones y modo visual de escala), etiquetas personalizadas y el historial de llamadas.

- **App\Models\Employee**: Empleado de un cliente, con nombre, alias histórico, foto, puesto y estado activo; se relaciona con el cliente y su token NFC (`hasOne` `nfcTokens()`). En `booted()` asigna **UUID en `creating`** si no hay clave, para que tras guardar exista `id` en PHP (Eloquent no rellena el default de PostgreSQL en modelos no autoincrementales); además sincroniza `employees.is_active` hacia `nfctokens.is_active` cuando cambia el estado del empleado.

- **App\Models\NfcToken**: Token NFC asignado a un empleado y un cliente; almacena el identificador (`token`) y si está activo. El valor `token` es estable y no se regenera al editar, activar o desactivar empleados; solo se crea si faltaba realmente. La FK **`employee_id` → `employees.id`** está en **ON DELETE CASCADE** (migración `2026_04_08_161000_...`); antes era SET NULL e incompatible con `employee_id` NOT NULL.

- **App\Models\CsatSurvey**: Encuesta CSAT registrada por el sistema; guarda cliente, empleado (opcional), puntuación 1–5, motivo de mejora clásico (`ImprovementReason`) u opción configurada en la encuesta del cliente (`ClientImprovementOption`), snapshot histórico de valoraciones positivas (`positive_scores_used`), idioma y `device_hash` para limitar abusos.

- **App\Models\Sector**: Catálogo simple de sectores con nombre y orden; expone métodos para contar clientes en un sector y determinar si se puede eliminar (solo cuando no tiene clientes).

- **App\Models\ImprovementReason**: Catálogo legado de códigos de motivo de mejora (por ejemplo, tiempo de espera, trato, etc.); define si un motivo está activo y su relación con las encuestas CSAT que lo usan.

- **App\Models\ClientImprovementConfig**: Configuración de la **encuesta** por cliente: `display_mode` (`numbers` \| `faces`, solo afecta la vista pública; el score sigue siendo numérico), `default_locale` (`es`, `pt`, `en`), `positive_scores` (JSONB/array de valoraciones que siguen el flujo positivo, por defecto `[4,5]`), pregunta principal y título de bloque en columnas por idioma (`*_es`, `*_pt`, `*_en`). Mantiene `title` y `survey_question_text` como compatibilidad sincronizada con español. Expone helpers para normalizar locale, validar/resolver valoraciones positivas y devolver textos localizados.

- **App\Models\ClientImprovementOption**: Opción concreta dentro de la configuración de mejora de un cliente; guarda el texto visible por idioma (`label_es`, `label_pt`, `label_en`), conserva `label` como compatibilidad en español y se asocia a una `ClientImprovementConfig`. Las encuestas CSAT negativas se enlazan por su UUID (`improvement_option_id`), no por el texto mostrado.

- **App\Models\ClientImprovementReasonLabel**: Permite personalizar, por cliente, el texto visible de cada código de motivo de mejora; guarda el cliente, el código de `ImprovementReason` y la etiqueta final que verá el usuario.

- **App\Models\PanelMessage**: Mensaje del panel interno (notificaciones/mensajes entre superadmin y distribuidores); almacena tipo de mensaje, remitente, cliente relacionado, título y cuerpo, y se relaciona con sus destinatarios (`PanelMessageRecipient`).

- **App\Models\PanelMessageRecipient**: Asociación entre un mensaje de panel y un usuario receptor; registra si el mensaje ha sido leído (`read_at`) y ofrece helpers para marcarlo como leído.

- **App\Models\ClientCall**: Registro de una llamada realizada a un cliente; guarda cliente, fecha de llamada y notas, y al crearse actualiza `last_call_at` y `next_call_at` en `Client`.

- **App\Support\CsatMetrics**: Servicio de dominio que calcula métricas agregadas de CSAT (media, porcentaje satisfechos, total, encuestas de hoy) para un cliente o globalmente, aplicando ventanas temporales (hoy, 7, 30 días, todo), caché de 5 minutos y el snapshot histórico `positive_scores_used` de cada encuesta.

- **App\Support\PanelMessageService**: Servicio que centraliza la creación de mensajes de panel relacionados con la activación de clientes: notifica a superadmins cuando un distribuidor crea un cliente pendiente de activar y avisa al distribuidor cuando el superadmin lo activa.

- **App\Support\ImprovementReasonLabelResolver**: Servicio que resuelve el texto final de los motivos de mejora combinando las etiquetas personalizadas por cliente (`ClientImprovementReasonLabel`) con un conjunto de textos por defecto, y devuelve listados completos para usarlos en encuestas o APIs.

- **App\Support\PanelLocale**: Helper de traducciones del panel autenticado; define locales soportados (`es`, `en`, `pt`), textos nativos para el selector y la clave de sesión `panel_locale`, resolviendo siempre con fallback seguro a `config('app.locale')` o `es`. No usa base de datos ni modifica el idioma por defecto de encuestas públicas. En fase 2 la cobertura se amplio dentro de los archivos existentes `panel.php`, `dashboard.php`, `client.php`, `survey.php`, `employees.php` y `common.php`.

- **App\Http\SetPanelLocale**: Middleware registrado en `AdminPanelProvider` después de `StartSession`; aplica `app()->setLocale()` para el panel Filament a partir de la sesión del usuario autenticado. Mantiene separado el idioma del panel autenticado de `ClientImprovementConfig::default_locale` y de la detección `Accept-Language` de la encuesta pública/NFC.

- **App\Filament\Resources\ClientResource**: Recurso Filament que define formularios, tablas, permisos y navegación para gestionar clientes en el panel (`/admin`), incluyendo bloques de facturación, administrador, acceso a plataforma, estado y vigencia, acciones para **Encuesta** (subpágina `PuntosDeMejora`), empleados, llamadas, soft deletes y control de acceso según rol.

- **Traducciones del panel autenticado (fase 2-3):** `ClientResource`, `DistributorResource`, `EmployeeResource`, `CsatSurveyResource`, `SectorResource`, `NfcTokenResource`, `AdminNotifications`, `DistributorMessages`, `Dashboard`, `ClientCalls`, `CsatStatsOverviewWidget`, `ClientsOverviewWidget`, `EditProfile` y páginas cliente usan `__()` con claves de `lang/`. El panel autenticado queda cubierto todo lo posible sin base de datos; quedan fuera textos persistidos/dinámicos, Pulse y encuesta pública.

- **App\Filament\Resources\ClientResource\Pages\PuntosDeMejora**: Subpágina **Encuesta** en `ClientResource`; edición (superadmin/distribuidor) o solo lectura (cliente). `display_mode`, `default_locale`, valoraciones positivas 1..5 (bloque final consecutivo hasta 5, no vacío ni las cinco), textos en `es`/`pt`/`en`. UUID en PHP para config y opciones nuevas; si falta configuración (legacy), la página la crea al abrir con valores base y `[4,5]`.

- **App\Filament\Pages\ClientEmpleados**: Página Filament de solo lectura para el rol cliente que lista los empleados (`Employee`) de su `ownedClient`, ordenados por nombre, como vista amigable dentro del menú del cliente.

- **App\Http\Controllers\Api\SurveyController**: Controlador API que recibe y valida las encuestas CSAT (`POST /api/surveys/create`), resuelve cliente activo y empleado, consulta `positive_scores` para saber si el score requiere punto de mejora, guarda ese set en `positive_scores_used`, valida que los motivos/opciones de mejora sean válidos para ese cliente, aplica límites por dispositivo y persiste la encuesta registrando logs.

---

## Filament: Resources, Pages y Widgets adicionales

- **App\Filament\Resources\EmployeeResource**: Recurso Filament para gestionar empleados (formulario con nombre, foto, puesto y activo; alias y Token NFC están ocultos en Create/Edit); oculto en menú; acceso desde **Cliente → Empleados**. Incluye **`canAccess()`** = `canViewAny() || canCreate()` porque Filament ejecuta `mountCanAuthorizeResourceAccess` → `canAccess()` al abrir rutas del recurso (evita 403 para distribuidor/cliente con permiso de alta aunque `canViewAny` sea solo superadmin). **`canViewAny()`** sigue limitando el índice global; **`canCreate()`** incluye comprobación por `owner_id` para rol cliente. **Borrado:** `canDelete()` solo permite borrar empleados inactivos y `canDeleteAny()` está deshabilitado para evitar borrado masivo de activos.

- **App\Filament\Resources\EmployeeResource\Pages\CreateEmployee**: Alta de empleado; con **`?client_id=`** autoriza con **`ClientResource::canEdit($client)`**; valida `client_id` en `mutateFormDataBeforeCreate` para distribuidor (`created_by`) y cliente (`ownedClient` / propiedad del cliente). No muestra breadcrumb ni acción “crear y crear otro”; al crear token por ausencia real lo hace con el mismo `is_active` del empleado.

- **App\Filament\Resources\NfcTokenResource**: Recurso Filament técnico para tokens NFC, mantenido por compatibilidad; oculta navegación y CRUD porque la gestión real se hace desde la ficha de `Employee` (un token lógico por empleado).

- **App\Filament\Resources\CsatSurveyResource**: Recurso Filament para listar y consultar encuestas CSAT; visible por rol con alcance de datos (superadmin todo, distribuidor sus clientes, cliente su propio cliente).

- **App\Filament\Resources\SectorResource**: Recurso Filament de configuración para el catálogo de sectores; permite crear, editar y eliminar sectores controlando que no tengan clientes asociados antes de borrarlos.

- **App\Filament\Resources\DistributorResource**: Recurso Filament para gestionar distribuidores (también basados en `Client`, filtrados por `owner.role = distribuidor`); define formularios, tabla y permisos específicos solo para superadmin.

- **App\Filament\Resources\ClientResource\Pages\ListClients**: Página de listado de clientes que define pestañas (todos, eliminados), restringe el acceso/navegación por rol y redirige a `ClientPuntosDeMejora` cuando el usuario es cliente propietario; optimizada con `with(['createdBy:id,name,fullname,email'])` en `getTableQuery()` y selección mínima de columnas para evitar N+1 y reducir carga.

- **App\Filament\Resources\ClientResource\Pages\CreateClient**: Página para crear un nuevo cliente y su usuario propietario en una sola operación, generando el código `CLIENxxxxxx`, asignando fechas y disparando notificaciones de “cliente pendiente de activación” cuando lo crea un distribuidor. Además, tras crear el cliente inicializa automáticamente su configuración de encuesta si no existe: crea `ClientImprovementConfig` con `default_locale = es`, `positive_scores = [4,5]`, pregunta/título en español, portugués e inglés, y dos `ClientImprovementOption` por defecto (**Tiempo de espera / Tempo de espera / Waiting time** y **Atención recibida / Atendimento recebido / Service received**) usando UUID generados en PHP.

- **App\Filament\Resources\ClientResource\Pages\EditClient**: Página de edición de cliente que sincroniza datos del propietario, controla la activación y fecha de expiración con un modal de confirmación y, al activar un cliente inactivo, envía un mensaje al distribuidor correspondiente.

- **App\Filament\Resources\ClientResource\Pages\ViewClient**: Página de visualización de la ficha de un cliente (solo lectura), que carga datos del propietario y ofrece una acción rápida para ir a la edición cuando los permisos lo permiten.

- **App\Filament\Resources\ClientResource\Pages\Empleados**: Subpágina de un cliente que muestra sus empleados en tarjetas y permite a superadmin/distribuidor crearlos/editar/borrar con alcance por cliente (`created_by` para distribuidor). La vista usa botones tipo pestaña **“Empleados activos”** / **“Empleados inactivos”** mediante `employeeStatusTab`, de modo que la pantalla carga solo un grupo cada vez. Las tarjetas muestran foto/iniciales, nombre y `position` bajo el nombre si existe; mantienen **Copiar enlace** para `/survey/nfc/{token}` con fallback de portapapeles y feedback visible. El borrado solo se muestra y ejecuta en empleados inactivos; `deleteEmployee()` rechaza activos aunque se manipule el frontend. El rol cliente no debe usar esta ruta: su acceso por URL queda bloqueado (`403`) y su vista oficial es `ClientEmpleados` en solo lectura.

- **App\Filament\Resources\ClientResource\Pages\Llamadas**: Subpágina de un cliente que gestiona el historial de llamadas (`ClientCall`) y los campos `last_call_at`/`next_call_at`, con acciones para registrar la llamada de hoy, programar la próxima y editar notas.

- **App\Filament\Pages\Dashboard**: Página principal del panel Filament que muestra el dashboard, inyectando widgets como `ClientsOverviewWidget` para dar una visión rápida del estado de clientes y su actividad de encuestas.

- **App\Filament\Pages\Auth\Login**: Página de login personalizada de Filament que usa `User::findByIdentifier()` para permitir autenticación por usuario o email, aplica rate limiting y valida que el usuario tenga acceso al panel `admin`.

- **App\Filament\Pages\AdminNotifications**: Página de bandeja de notificaciones para superadmin; lista `PanelMessageRecipient` asociados al usuario con información de tipo, cliente, remitente y estado leído/no leído, permitiendo abrir y marcar mensajes como leídos.

- **App\Filament\Pages\DistributorMessages**: Página de bandeja de mensajes para distribuidores; muestra los `PanelMessageRecipient` destinados al distribuidor, especialmente los relacionados con la activación de clientes.

- **App\Filament\Pages\ClientCalls**: Página de listado global de llamadas pendientes por cliente (última y próxima llamada) para superadmin y distribuidores, con enlace directo a la subpágina `Llamadas` de cada cliente.

- **App\Filament\Pages\EditProfile**: Página de edición de perfil del usuario autenticado en el panel Filament; agrupa los campos de nombre, email y cambio de contraseña en una sección de formulario sencilla.

- **App\Filament\Widgets\CsatStatsOverviewWidget**: Widget de dashboard que muestra cuatro métricas clave de CSAT (nota media, encuestas totales, porcentaje de satisfechos y encuestas de hoy) usando `CsatMetrics`, con enlaces directos a los listados filtrados de encuestas.

- **App\Filament\Widgets\ClientsOverviewWidget**: Widget de dashboard que ofrece una visión rápida de clientes por pestañas (activos, inactivos, con baja próxima y distribuidores), mostrando fechas, teléfonos, actividad diaria de encuestas y filtros según el rol del usuario; el porcentaje diario de satisfechos usa `positive_scores_used`.

- **database/migrations/2026_04_30_112300_add_positive_scores_used_to_csat_surveys_table.php**: Migración que añade `csat_surveys.positive_scores_used` (JSONB nullable) y realiza backfill con la configuración aplicable o `[4,5]` como fallback.

---

## Controladores HTTP (web y PWA)

- **App\Http\Controllers\SurveyController**: Controlador web de la encuesta CSAT pública; sirve la landing `/survey`, la encuesta fija por cliente `/survey/{client_code}`, gestiona encuestas vía NFC (`/survey/nfc/{token}`) y genera manifest y service worker PWA por cliente (cache `v5`). Para NFC resuelve token, cliente y empleado; bloquea si el token está inactivo, si el empleado está inactivo o si cliente/empleado no corresponden. Cuando el empleado está inactivo usa `survey-nfc-invalid.blade.php` con mensaje móvil específico y botón “Cerrar”. Para la vista pública resuelve `surveyLocale` desde `Accept-Language` (`es`, `pt`, `en`, normalizando variantes regionales), cae a `default_locale` del cliente y finalmente a `es`; pasa `surveyPositiveScores` y `improvementBlock` con textos traducidos y `id` de opción intacto. La vista usa `surveyPositiveScores` para decidir flujo positivo vs punto de mejora, el mismo `surveyLocale` para mensajes genéricos de cierre y pinta las opciones negativas como cards/botones verticales de ancho completo. No modifica el contrato de API ni el valor numérico del score.

- **App\Http\Controllers\PulseController**: Controlador de la PWA “El Pulso del Día”; gestiona login específico para propietarios de cliente, redirección al dashboard `/pulse/{client_code}`, manifest y service worker por cliente, y un endpoint JSON de métricas diarias/acumuladas basado en `CsatMetrics`.

---

## Nota operativa

Cambios recientes de rutas, panel o despliegue: [`CONTEXTO_PARA_IA.md`](CONTEXTO_PARA_IA.md) § “Handoff reciente”. Comandos en servidor: [`docs/RUNBOOK.md`](docs/RUNBOOK.md).

