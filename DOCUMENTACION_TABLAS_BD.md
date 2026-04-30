# Documentacion de tablas de base de datos

Este archivo resume las tablas detectadas en las migraciones del proyecto y el objetivo de cada campo.

> Nota: esta guia esta pensada como documentacion funcional/tecnica. En tablas legacy donde no se definen todos los campos en migraciones actuales, se documentan los campos visibles y se recomienda ampliar con el modelo/SQL historico.

## Tablas de negocio principales

### `clients`
**Para que sirve:** almacena la ficha principal de cada cliente (antes farmacia), su estado y datos comerciales.

- `nif`: identificador fiscal del cliente.
- `razon_social`: nombre legal de la empresa cliente.
- `calle`: direccion principal.
- `pais`: pais del cliente.
- `codigo_postal`: codigo postal.
- `ciudad`: ciudad.
- `sector`: sector al que pertenece el cliente.
- `telefono_negocio`: telefono general del negocio.
- `telefono_cliente`: telefono de contacto del cliente.
- `created_by`: usuario interno que creo el registro.
- `fecha_inicio_alta`: fecha de inicio de relacion comercial.
- `fecha_fin`: fecha de finalizacion (si aplica).
- `deleted_at`: borrado logico (soft delete).
- `logo`: ruta o referencia al logo del cliente.
- `last_call_at`: fecha/hora de la ultima llamada registrada.
- `next_call_at`: fecha/hora prevista de proxima llamada.

### `users`
**Para que sirve:** cuentas de acceso al panel (admin, distribuidor, cliente u otros roles).

- `id`: identificador interno del usuario.
- `email`: correo de login.
- `password`: hash de contrasena.
- `fullname`: nombre completo del usuario.
- `name`: nombre corto o alternativo.
- `role`: rol de permisos dentro del sistema.
- `pharmacy_id`: relacion legacy con farmacia (tabla previa).
- `email_verified_at`: fecha de verificacion de email.
- `password_reset_token`: token de recuperacion de contrasena.
- `last_login_at`: ultimo acceso.
- `created_at`: fecha/hora de alta del registro.
- `updated_at`: fecha/hora de ultima actualizacion.
- `remember_token`: token de sesion persistente ("recordarme").
- `client_id`: relacion actual con cliente.
- `dni`: identificador documental de persona.
- `admin_email`: email administrativo adicional.

### `employees`
**Para que sirve:** personal asociado a cada cliente.

- `id`: identificador del empleado.
- `created_at`: fecha de alta.
- `updated_at`: fecha de actualizacion.
- `pharmacy_id`: relacion legacy con farmacia.
- `name`: nombre del empleado.
- `position`: puesto/cargo.
- `is_active`: estado del empleado.
- `client_id`: cliente al que pertenece.
- `alias`: nombre mostrado alternativo.
- `photo`: foto del empleado.

### `nfctokens`
**Para que sirve:** tokens NFC asignados a empleados para captacion/identificacion en encuestas.

- `id`: identificador del token.
- `pharmacy_id`: relacion legacy con farmacia.
- `employee_id`: empleado asociado al token.
- `token`: valor unico del token NFC.
- `is_active`: estado activo/inactivo del token.
- `created_at`: fecha de creacion.
- `updated_at`: fecha de actualizacion.
- `client_id`: cliente asociado.

### `csat_surveys`
**Para que sirve:** respuestas de encuestas de satisfaccion (CSAT) y punto de mejora.

- `id`: identificador de la encuesta.
- `pharmacy_id`: referencia legacy a farmacia.
- `employee_id`: empleado evaluado o asociado.
- `score`: puntuacion de satisfaccion.
- `improvementreason_id`: motivo de mejora legacy.
- `locale_used`: idioma usado en la encuesta.
- `device_hash`: huella del dispositivo de origen.
- `created_at`: fecha de respuesta.
- `updated_at`: fecha de actualizacion.
- `client_id`: cliente propietario de la encuesta.
- `improvement_point_option_id`: opcion de mejora seleccionada (modelo intermedio legacy).
- `improvement_option_id`: opcion de mejora seleccionada (modelo actual).
- `positive_scores_used`: snapshot historico JSONB de las valoraciones positivas aplicadas cuando se creo la encuesta.

### `client_calls`
**Para que sirve:** registro de llamadas de seguimiento por cliente.

- `id`: identificador de llamada.
- `client_id`: cliente llamado.
- `called_at`: fecha/hora de la llamada.
- `notes`: observaciones de la llamada.

## Configuracion de puntos de mejora

### `improvementreasons`
**Para que sirve:** catalogo base de razones/puntos de mejora.

- `id`: identificador UUID de la razon.
- `code`: codigo interno unico de la razon.
- `is_active`: estado activo/inactivo.
- `created_at`: fecha de creacion.
- `updated_at`: fecha de actualizacion.

### `client_improvement_reason_labels`
**Para que sirve:** etiqueta personalizada por cliente para cada razon de mejora.

- `id`: identificador.
- `client_id`: cliente propietario de la personalizacion.
- `improvement_reason_code`: codigo de la razon (catalogo base).
- `label`: texto personalizado mostrado al cliente.
- `created_at`: fecha de creacion.
- `updated_at`: fecha de actualizacion.

### `client_improvement_points`
**Para que sirve:** definicion de puntos de mejora habilitados por cliente.

- `id`: identificador.
- `client_id`: cliente propietario.
- `improvement_reason_code`: razon base asociada.
- `title`: titulo visible del punto.
- `sort_order`: orden de visualizacion.
- `created_at`: fecha de creacion.
- `updated_at`: fecha de actualizacion.

### `client_improvement_point_options`
**Para que sirve:** opciones concretas de respuesta por cada punto de mejora (estructura legacy/intermedia).

- `id`: identificador.
- `client_improvement_point_id`: punto de mejora padre.
- `label`: texto de opcion.
- `sort_order`: orden de visualizacion.
- `created_at`: fecha de creacion.
- `updated_at`: fecha de actualizacion.

### `client_improvement_configs`
**Para que sirve:** configuracion central de pantalla/encuesta de mejora por cliente.

- `id`: identificador.
- `client_id`: cliente propietario.
- `title`: titulo mostrado en interfaz.
- `created_at`: fecha de creacion.
- `updated_at`: fecha de actualizacion.
- `display_mode`: modo de visualizacion de opciones (UI).
- `survey_question_text`: pregunta principal mostrada en encuesta.
- `default_locale`: idioma por defecto de la encuesta publica.
- `title_es`: titulo del bloque en espanol.
- `title_pt`: titulo del bloque en portugues.
- `title_en`: titulo del bloque en ingles.
- `survey_question_text_es`: pregunta principal en espanol.
- `survey_question_text_pt`: pregunta principal en portugues.
- `survey_question_text_en`: pregunta principal en ingles.
- `positive_scores`: JSONB/array de puntuaciones que siguen el flujo positivo.

### `client_improvement_options`
**Para que sirve:** opciones activas de mejora vinculadas a la configuracion actual por cliente.

- `id`: identificador.
- `client_improvement_config_id`: configuracion padre.
- `label`: texto de opcion.
- `label_es`: texto de opcion en espanol.
- `label_pt`: texto de opcion en portugues.
- `label_en`: texto de opcion en ingles.
- `sort_order`: orden de aparicion.
- `created_at`: fecha de creacion.
- `updated_at`: fecha de actualizacion.

## Mensajeria interna de panel

### `panel_messages`
**Para que sirve:** mensajes emitidos dentro del panel entre perfiles internos.

- `id`: identificador del mensaje.
- `type`: tipo/categoria del mensaje.
- `sender_user_id`: usuario emisor.
- `client_id`: cliente relacionado (si aplica).
- `title`: asunto/titulo.
- `body`: cuerpo del mensaje.
- `created_at`: fecha de envio/creacion.
- `updated_at`: fecha de actualizacion.

### `panel_message_recipients`
**Para que sirve:** destinatarios de cada mensaje y estado de lectura.

- `id`: identificador.
- `panel_message_id`: mensaje asociado.
- `user_id`: usuario destinatario.
- `read_at`: fecha/hora de lectura.
- `created_at`: fecha de asignacion.
- `updated_at`: fecha de actualizacion.

## Catalogos y soporte funcional

### `sectors`
**Para que sirve:** catalogo de sectores de negocio para clasificar clientes.

- `id`: identificador del sector.
- `name`: nombre visible del sector.
- `sort_order`: orden para listados/UI.
- `created_at`: fecha de creacion.
- `updated_at`: fecha de actualizacion.

### `pharmacies` (legacy)
**Para que sirve:** tabla historica previa a `clients`; se mantiene por compatibilidad/migracion.

- `id`: identificador.
- `code`: codigo interno.
- `namecommercial`: nombre comercial.
- `owner_id`: propietario/usuario principal.
- `created_at`: fecha de creacion.
- `updated_at`: fecha de actualizacion.
- `is_active`: estado activo/inactivo.

## Tablas tecnicas de Laravel e infraestructura

### `sessions`
**Para que sirve:** sesiones activas de usuario.

- `id`: identificador de sesion.
- `user_id`: usuario autenticado.
- `ip_address`: IP de origen.
- `user_agent`: agente/navegador.
- `payload`: datos serializados de sesion.
- `last_activity`: timestamp de ultima actividad.

### `cache`
**Para que sirve:** almacenamiento de cache de aplicacion.

- `key`: clave de cache.
- `value`: valor cacheado.
- `expiration`: momento de expiracion.

### `cache_locks`
**Para que sirve:** locks de cache para evitar condiciones de carrera.

- `key`: clave de lock.
- `owner`: propietario del lock.
- `expiration`: expiracion del lock.

### `jobs`
**Para que sirve:** cola de trabajos pendientes.

- `id`: identificador del job.
- `queue`: cola de ejecucion.
- `payload`: datos del trabajo.
- `attempts`: numero de intentos.
- `reserved_at`: momento de reserva por worker.
- `available_at`: momento desde el que puede procesarse.
- `created_at`: creacion del job.

### `job_batches`
**Para que sirve:** agrupacion de jobs por lote.

- `id`: identificador del lote.
- `name`: nombre del lote.
- `total_jobs`: jobs totales.
- `pending_jobs`: jobs pendientes.
- `failed_jobs`: jobs fallidos.
- `failed_job_ids`: ids de jobs fallidos.
- `options`: opciones serializadas del lote.
- `cancelled_at`: fecha de cancelacion.
- `created_at`: fecha de creacion.
- `finished_at`: fecha de finalizacion.

### `failed_jobs`
**Para que sirve:** historico de trabajos fallidos en cola.

- `id`: identificador.
- `uuid`: UUID de job.
- `connection`: conexion de cola.
- `queue`: nombre de cola.
- `payload`: contenido del job.
- `exception`: error capturado.
- `failed_at`: fecha del fallo.

## Tablas legacy de analitica/documental (campos parciales en migraciones actuales)

Estas tablas existen por continuidad historica. En migraciones presentes se observan principalmente campos de control (`id`, `created_at`, `updated_at`), por lo que conviene completar su documentacion con fuente de datos historica:

- `contracts`: contratos/documentos contractuales.
- `documents`: gestion documental.
- `googleoauthaccounts`: cuentas OAuth de Google.
- `googlelocations`: ubicaciones/perfiles de negocio en Google.
- `googlereviewscache`: cache de resenas de Google.
- `googlemetricsdaily`: metricas diarias de Google.
- `alertevents`: eventos de alerta.
- `alertreads`: lecturas de alertas por usuario.
- `alertrecipients`: destinatarios de alertas.
- `benchmarkruns`: ejecuciones de procesos de benchmark.
- `pharmacysettings`: configuracion historica por farmacia.

