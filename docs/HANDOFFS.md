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
