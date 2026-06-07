# Client dashboard V1 (Filament)

Documentación de desarrollo de la primera iteración del dashboard por cliente dentro de `ClientResource`.

**Relacionado:** [`docs/HANDOFFS.md`](HANDOFFS.md), [`DESCRIPCION_CLASES.md`](../DESCRIPCION_CLASES.md).

---

## Objetivo

Ofrecer una **vista resumen operativa** del cliente dentro del panel Filament, integrada en la subnavegación del registro, sin duplicar la ficha de datos (`ViewClient`).

En la misma iteración se unificó el **header** de las subpáginas del cliente (nombre comercial, sin subtítulo) y se renombró la tab de ficha de «Cliente» a **«Ficha»**.

---

## Archivos creados

| Archivo | Rol |
|---------|-----|
| `app/Filament/Resources/ClientResource/Pages/ClientDashboard.php` | Page Livewire del dashboard |
| `app/Filament/Resources/ClientResource/Pages/Concerns/HasClientPageTitle.php` | Trait: título = `namecommercial` |
| `resources/views/filament/resources/client-resource/pages/client-dashboard.blade.php` | Vista V1 por bloques |

---

## Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `app/Filament/Resources/ClientResource.php` | Ruta `dashboard`, orden subnav, `SubNavigationPosition::Top` |
| `app/Filament/Resources/ClientResource/Pages/ViewClient.php` | Trait de título; tab «Ficha» |
| `app/Filament/Resources/ClientResource/Pages/Empleados.php` | Trait de título (elimina `getTitle()` propio) |
| `app/Filament/Resources/ClientResource/Pages/Llamadas.php` | Idem |
| `app/Filament/Resources/ClientResource/Pages/PuntosDeMejora.php` | Idem |
| `lang/es/client.php`, `lang/en/client.php`, `lang/pt/client.php` | `menu.dashboard`, `menu.profile`, bloque `dashboard.*` |

**No modificados en esta iteración:** `EditClient`, `ListClients`, `CreateClient`, permisos de `Llamadas`, enlaces por defecto desde listados.

---

## Comportamiento nuevo

### Ruta y subnavegación

- **URL:** `/{record}/dashboard` (clave Filament: `dashboard`).
- **Orden de tabs:** Dashboard → Ficha → Encuesta → Empleados → Llamadas.
- **Visibilidad dashboard:** `ClientResource::canView($record)` (misma base que la ficha).
- **Entrada por defecto desde listados:** sigue siendo `view` (`/{record}`), no `dashboard`.

### Header en subpáginas del cliente

Todas las páginas que usan `HasClientPageTitle` muestran **solo el nombre comercial** como título principal. La sección activa se indica por la tab de subnavegación, no por subtítulo.

### Contenido del dashboard V1

**Bloque 1 — Métricas CSAT (7 días)**  
Fuente: `CsatMetrics::getMetrics($clientId, CsatMetrics::PERIOD_7_DAYS)`.

- Nota media
- Total encuestas (7 días)
- % satisfechos
- Encuestas hoy

**Bloque 2 — Estado operativo** (tarjetas con enlace)

| Tarjeta | Fuente de datos | Enlace |
|---------|-----------------|--------|
| Encuesta | `improvementConfig` + `options_count`; estados: sin configurar / incompleta (<2 resp.) / configurada | `puntos-de-mejora` |
| Empleados | `employees()` activos / inactivos / total | `empleados` |
| Llamadas | Solo si `canSeeCalls()` (misma lógica que tab Llamadas): `last_call_at`, `next_call_at`, vencida, total | `llamadas` |

---

## Decisiones tomadas

1. **Clase `ClientDashboard`** en lugar de `Dashboard` — evita colisión con `App\Filament\Pages\Dashboard`.
2. **Base `Page` + `InteractsWithRecord`** — mismo patrón que Empleados/Llamadas; no `ViewRecord` (resumen, no infolist).
3. **Trait `HasClientPageTitle`** — una sola regla de título reutilizable en todas las subpáginas del registro.
4. **Vista Blade por secciones** (`data-dashboard-section`) — sin widgets Filament ni filtros en V1; preparada para insertar bloques V2.
5. **Métodos de resumen en la Page** (`getCsatSummary`, `getSurveySummary`, etc.) — la lógica queda en PHP; la vista solo compone bloques.
6. **`SubNavigationPosition::Top`** en `ClientResource` — tabs del registro arriba del contenido (UX al añadir más tabs).
7. **Tab «Ficha»** — `ViewClient::getNavigationLabel()` usa `client.menu.profile`; el modelo sigue siendo «Cliente» en otros contextos (`client.resource.model_label`).

---

## Pendiente para V2

- Gráficos de evolución (series temporales CSAT, tendencias).
- Actividad reciente (últimas encuestas, llamadas, cambios).
- Comparativas (periodos, benchmarks, otros clientes del distribuidor).
- KPIs ampliados y selector de periodo en el dashboard del cliente.
- Opcional: hacer `dashboard` la landing al abrir un cliente desde listados/widgets.
- Opcional: partial compartido para tarjetas última/próxima llamada con `llamadas.blade.php`.

La vista incluye un comentario placeholder al final del Blade para nuevas secciones V2.

---

## i18n

Claves nuevas bajo `client.menu.*` y `client.dashboard.*` en `lang/{es,en,pt}/client.php`.

Principales:

- `client.menu.dashboard` — etiqueta tab Dashboard
- `client.menu.profile` — etiqueta tab Ficha
- `client.dashboard.csat.*` — métricas CSAT
- `client.dashboard.operations.*` — cabecera bloque operativo
- `client.dashboard.survey|employees|calls.*` — tarjetas
- `client.dashboard.actions.*` — enlaces «Ver …»
