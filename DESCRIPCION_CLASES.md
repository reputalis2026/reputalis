## Descripción de clases principales

- **App\Models\User**: Representa a los usuarios del sistema (superadmin, cliente, distribuidor), controla el acceso al panel Filament y expone helpers como `isSuperAdmin()`, `isClientOwner()` e `isDistributor()`, además de relaciones con el cliente que posee y los mensajes de panel recibidos.

- **App\Models\Client**: Entidad central de cliente (farmacia/negocio); guarda datos fiscales, de contacto, estado y vigencia, y se relaciona con su propietario (`owner`), el usuario que lo creó (`createdBy`), sus usuarios internos, empleados, encuestas CSAT, tokens NFC, configuración de puntos de mejora, etiquetas personalizadas y el historial de llamadas.

- **App\Models\Employee**: Empleado de un cliente, con nombre, alias, foto, puesto y estado activo; se asegura de tener UUID propio y se relaciona con el cliente, sus encuestas CSAT y su token NFC (relación 1–1 lógica).

- **App\Models\NfcToken**: Token NFC asignado a un empleado y un cliente; almacena el identificador físico (`token`) y si está activo, y permite navegar al cliente y al empleado asociados.

- **App\Models\CsatSurvey**: Encuesta CSAT registrada por el sistema; guarda cliente, empleado (opcional), puntuación, motivo de mejora clásico (`ImprovementReason`) u opción personalizada de puntos de mejora (`ClientImprovementOption`), idioma y `device_hash` para limitar abusos.

- **App\Models\Sector**: Catálogo simple de sectores con nombre y orden; expone métodos para contar clientes en un sector y determinar si se puede eliminar (solo cuando no tiene clientes).

- **App\Models\ImprovementReason**: Catálogo legado de códigos de motivo de mejora (por ejemplo, tiempo de espera, trato, etc.); define si un motivo está activo y su relación con las encuestas CSAT que lo usan.

- **App\Models\ClientImprovementConfig**: Configuración de puntos de mejora por cliente (título de la pregunta y vínculo al cliente); gestiona la colección de opciones (`ClientImprovementOption`) ordenadas que se mostrarán en la encuesta negativa y en el panel del cliente.

- **App\Models\ClientImprovementOption**: Opción concreta dentro de la configuración de mejora de un cliente (texto de la opción y orden de visualización); se asocia a una `ClientImprovementConfig` y puede ser enlazada desde encuestas CSAT negativas.

- **App\Models\ClientImprovementReasonLabel**: Permite personalizar, por cliente, el texto visible de cada código de motivo de mejora; guarda el cliente, el código de `ImprovementReason` y la etiqueta final que verá el usuario.

- **App\Models\PanelMessage**: Mensaje del panel interno (notificaciones/mensajes entre superadmin y distribuidores); almacena tipo de mensaje, remitente, cliente relacionado, título y cuerpo, y se relaciona con sus destinatarios (`PanelMessageRecipient`).

- **App\Models\PanelMessageRecipient**: Asociación entre un mensaje de panel y un usuario receptor; registra si el mensaje ha sido leído (`read_at`) y ofrece helpers para marcarlo como leído.

- **App\Models\ClientCall**: Registro de una llamada realizada a un cliente; guarda cliente, fecha de llamada y notas, y se usa para el historial de llamadas y para campos `last_call_at`/`next_call_at` en `Client`.

- **App\Support\CsatMetrics**: Servicio de dominio que calcula métricas agregadas de CSAT (media, porcentaje satisfechos, total, encuestas de hoy) para un cliente o globalmente, aplicando ventanas temporales (hoy, 7, 30 días, todo) y caché de 5 minutos.

- **App\Support\PanelMessageService**: Servicio que centraliza la creación de mensajes de panel relacionados con la activación de clientes: notifica a superadmins cuando un distribuidor crea un cliente pendiente de activar y avisa al distribuidor cuando el superadmin lo activa.

- **App\Support\ImprovementReasonLabelResolver**: Servicio que resuelve el texto final de los motivos de mejora combinando las etiquetas personalizadas por cliente (`ClientImprovementReasonLabel`) con un conjunto de textos por defecto, y devuelve listados completos para usarlos en encuestas o APIs.

- **App\Filament\Resources\ClientResource**: Recurso Filament que define formularios, tablas, permisos y navegación para gestionar clientes en el panel (`/admin`), incluyendo bloques de facturación, administrador, acceso a plataforma, estado y vigencia, acciones para puntos de mejora, empleados, llamadas, soft deletes y control de acceso según rol.

- **App\Filament\Pages\ClientPuntosDeMejora**: Página Filament de solo lectura para el rol cliente que muestra, en su propio menú, el título y las opciones configuradas de sus puntos de mejora (`ClientImprovementConfig` y `ClientImprovementOption`) a partir del `ownedClient` del usuario.

- **App\Filament\Pages\ClientEmpleados**: Página Filament de solo lectura para el rol cliente que lista los empleados (`Employee`) de su `ownedClient`, ordenados por nombre, como vista amigable dentro del menú del cliente.

- **App\Http\Controllers\Api\SurveyController**: Controlador API que recibe y valida las encuestas CSAT (`POST /api/surveys/create`), resuelve cliente y empleado, valida que los motivos/opciones de mejora sean válidos para ese cliente, aplica límites por dispositivo y persiste la encuesta registrando logs.

---

## Filament: Resources, Pages y Widgets adicionales

- **App\Filament\Resources\EmployeeResource**: Recurso Filament para gestionar empleados de los clientes (formularios, tabla, filtros y permisos); se usa principalmente desde la subpágina `Empleados` de `ClientResource` y no aparece en el menú, respetando los roles (superadmin, distribuidor, cliente).

- **App\Filament\Resources\NfcTokenResource**: Recurso Filament técnico para tokens NFC, mantenido por compatibilidad; oculta navegación y CRUD porque la gestión real se hace desde la ficha de `Employee` (un token lógico por empleado).

- **App\Filament\Resources\CsatSurveyResource**: Recurso Filament para listar y consultar encuestas CSAT en el panel; solo visible para superadmin, con filtros por puntuación, fechas y cliente, y vista de detalle de cada encuesta.

- **App\Filament\Resources\SectorResource**: Recurso Filament de configuración para el catálogo de sectores; permite crear, editar y eliminar sectores controlando que no tengan clientes asociados antes de borrarlos.

- **App\Filament\Resources\DistributorResource**: Recurso Filament para gestionar distribuidores (también basados en `Client`, filtrados por `owner.role = distribuidor`); define formularios, tabla y permisos específicos solo para superadmin.

- **App\Filament\Resources\ClientResource\Pages\ListClients**: Página de listado de clientes que define pestañas (todos, eliminados), restringe el acceso/navegación por rol y redirige a `ClientPuntosDeMejora` cuando el usuario es cliente propietario.

- **App\Filament\Resources\ClientResource\Pages\CreateClient**: Página para crear un nuevo cliente y su usuario propietario en una sola operación, generando el código `CLIENxxxxxx`, asignando fechas y disparando notificaciones de “cliente pendiente de activación” cuando lo crea un distribuidor.

- **App\Filament\Resources\ClientResource\Pages\EditClient**: Página de edición de cliente que sincroniza datos del propietario, controla la activación y fecha de expiración con un modal de confirmación y, al activar un cliente inactivo, envía un mensaje al distribuidor correspondiente.

- **App\Filament\Resources\ClientResource\Pages\ViewClient**: Página de visualización de la ficha de un cliente (solo lectura), que carga datos del propietario y ofrece una acción rápida para ir a la edición cuando los permisos lo permiten.

- **App\Filament\Resources\ClientResource\Pages\PuntosDeMejora**: Subpágina de un cliente dentro de `ClientResource` para configurar (superadmin/distribuidor) o consultar (cliente) el bloque de puntos de mejora, persistiendo título y opciones en `ClientImprovementConfig` y `ClientImprovementOption`.

- **App\Filament\Resources\ClientResource\Pages\Empleados**: Subpágina de un cliente que muestra sus empleados, permite a superadmin/distribuidor crearlos/borrarlos (delegando en `EmployeeResource`) y, para el rol cliente, actúa como vista de sólo lectura “Empleados de este cliente”.

- **App\Filament\Resources\ClientResource\Pages\Llamadas**: Subpágina de un cliente que gestiona el historial de llamadas (`ClientCall`) y los campos `last_call_at`/`next_call_at`, con acciones para registrar la llamada de hoy, programar la próxima y editar notas.

- **App\Filament\Pages\Dashboard**: Página principal del panel Filament que muestra el dashboard, inyectando widgets como `ClientsOverviewWidget` para dar una visión rápida del estado de clientes y su actividad de encuestas.

- **App\Filament\Pages\Auth\Login**: Página de login personalizada de Filament que usa `User::findByIdentifier()` para permitir autenticación por usuario o email, aplica rate limiting y valida que el usuario tenga acceso al panel `admin`.

- **App\Filament\Pages\AdminNotifications**: Página de bandeja de notificaciones para superadmin; lista `PanelMessageRecipient` asociados al usuario con información de tipo, cliente, remitente y estado leído/no leído, permitiendo abrir y marcar mensajes como leídos.

- **App\Filament\Pages\DistributorMessages**: Página de bandeja de mensajes para distribuidores; muestra los `PanelMessageRecipient` destinados al distribuidor, especialmente los relacionados con la activación de clientes.

- **App\Filament\Pages\ClientCalls**: Página de listado global de llamadas pendientes por cliente (última y próxima llamada) para superadmin y distribuidores, con enlace directo a la subpágina `Llamadas` de cada cliente.

- **App\Filament\Pages\EditProfile**: Página de edición de perfil del usuario autenticado en el panel Filament; agrupa los campos de nombre, email y cambio de contraseña en una sección de formulario sencilla.

- **App\Filament\Widgets\CsatStatsOverviewWidget**: Widget de dashboard que muestra cuatro métricas clave de CSAT (nota media, encuestas totales, porcentaje de satisfechos y encuestas de hoy) usando `CsatMetrics`, con enlaces directos a los listados filtrados de encuestas.

- **App\Filament\Widgets\ClientsOverviewWidget**: Widget de dashboard que ofrece una visión rápida de clientes por pestañas (activos, inactivos, con baja próxima y distribuidores), mostrando fechas, teléfonos, actividad diaria de encuestas y filtros según el rol del usuario.

---

## Controladores HTTP (web y PWA)

- **App\Http\Controllers\SurveyController**: Controlador web de la encuesta CSAT pública; sirve la landing `/survey`, la encuesta fija por cliente `/survey/{client_code}`, gestiona encuestas vía NFC (`/survey/nfc/{token}`) y genera manifest y service worker PWA por cliente para la parte de encuestas.

- **App\Http\Controllers\PulseController**: Controlador de la PWA “El Pulso del Día”; gestiona login específico para propietarios de cliente, redirección al dashboard `/pulse/{client_code}`, manifest y service worker por cliente, y un endpoint JSON de métricas diarias/acumuladas basado en `CsatMetrics`.

