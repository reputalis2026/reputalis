# Plan: reputación Google vía Outscraper

**Estado:** fases **1–7 implementadas** en código (2026-08-07). Pendiente: **Fase 0** (API Outscraper real) + **Fase 8** (cierre deploy / docs finales / prueba con clientes reales).  
**Email de alertas:** aplazado (opcional V2).

**Relacionado:** [`docs/HANDOFFS.md`](HANDOFFS.md) (entrada 2026-08-14 resumen de continuación), [`CONTEXTO_PARA_IA.md`](../CONTEXTO_PARA_IA.md), encuesta CSAT (`ClientImprovementConfig` / `survey.blade.php`).

---

## 1. Objetivo de producto

Mostrar en Reputalis la reputación pública de Google Maps de cada cliente **sin** OAuth de Google Business Profile, usando **Outscraper Places** (datos agregados).

### Qué sí

- Nota visible de Google (`rating`).
- Número total de reseñas (`reviews`).
- Desglose por estrellas 1–5 (`reviews_per_score`).
- **Puntuación exacta calculada** (media aritmética propia a partir del desglose).
- Estimación: cuántas reseñas nuevas de **5★** harían falta para acercarse a un objetivo (p. ej. 4,4) — siempre como **estimación**, no garantía de lo que mostrará Google.
- Alertas cuando entre escaneos aumenten las de **1★ o 2★**.
- **Histórico propio** en BD: evolución por **día / mes / año** (sin hora en la UI).

### Qué no

- Fecha, texto ni autor de cada reseña.
- Descargar listados de reseñas en el cron diario.
- Filtros tipo “el día 29 hubo exactamente N reseñas de 4★” a nivel reseña individual (solo lo que se deduce de fotos diarias).
- Responder reseñas desde Reputalis.
- API oficial Google Places / GBP OAuth.

---

## 2. Decisiones cerradas

| Tema | Decisión |
|------|----------|
| Proveedor | **Outscraper Google Maps Places** (`/google-maps-search`), cobro **por ficha** |
| No usar a diario | Endpoint **Reviews** (cobro por reseña); solo eventual/anomalías si más adelante se decide |
| Place ID | Campo en **alta/ficha del cliente** (`clients`). **Quitar** el Place ID del formulario de encuesta |
| Encuesta | Solo **lee** el Place ID del cliente para el redirect `writereview` |
| Datos guardados | Snapshots agregados: rating, total, stars_1…5, media calculada, timestamps internos |
| Histórico UI | Agrupado por **día**, luego mes y año — **sin hora exacta** en pantallas |
| Cron | 3×/día (10:00, 17:00, 23:55): foto + comparación + alertas; el “día” del histórico puede ser la foto de cierre (23:55) o la última del día |
| Identificador Outscraper | Empezar con `place_id`; en bootstrap guardar también `google_id` si viene; en prueba validar cuál trae siempre `reviews_per_score` |
| UI | Página **Reputación externa**: primero datos funcionales sin diseño; después gráficos del histórico |
| i18n | ES / EN / PT como el resto del panel |

---

## 3. Coste Outscraper (orientativo)

Producto correcto: **Places** (no Reviews).

| Tramo | Precio aprox. |
|-------|----------------|
| Primeras 500 **fichas**/mes | Gratis |
| 501 → 100.000 | ~3 USD / 1.000 fichas |
| Más de 100.000 | ~1 USD / 1.000 fichas |

Cada sync de una farmacia = **1 ficha** facturable (aunque sea la misma cada día).

Ejemplo: 100 clientes × 3 sync/día × 30 ≈ 9.000 fichas/mes → ~25 USD tras free tier.

Referencias: [Google Maps Places API](https://outscraper.com/google-maps-api/), [pricing](https://outscraper.com/pricing/).

**Prueba obligatoria antes de producción:** 10–20 fichas reales y comprobar créditos + `reviews_per_score`.

---

## 4. Arquitectura

```
Alta / ficha Cliente
  └─ google_place_id (+ google_id tras primer sync)
        │
        ├─ Encuesta CSAT ──► solo lee place_id → writereview
        │
        └─ Outscraper Places (batch query=)
              │
              ├─ Bootstrap (1ª vez) → snapshot día 0
              │
              └─ Cron 10:00 / 17:00 / 23:55
                    ├─ guardar snapshot
                    ├─ comparar con foto anterior → alertas 1★/2★
                    └─ histórico UI = agregar por fecha (día/mes/año)
```

---

## 5. Desarrollo paso a paso

Orden recomendado. Ir marcando checkboxes al completar.

### Fase 0 — Cuenta y prueba Outscraper (antes o en paralelo al código)

1. [ ] Crear cuenta Outscraper y generar API key.
2. [ ] Añadir a `.env` (nunca al repo): `OUTSCRAPER_API_KEY=...`
3. [ ] Probar en UI o curl `GET /google-maps-search` con:
   - un `place_id` real (`ChIJ...`);
   - el `google_id` si se obtiene;
   - opcionalmente URL de Maps.
4. [ ] Verificar en la respuesta:
   - [ ] `rating`
   - [ ] `reviews`
   - [ ] `reviews_per_score` (o `_1`…`_5`) presente
   - [ ] suma 1+2+3+4+5 ≈ `reviews`
   - [ ] `place_id` / `google_id` estables
5. [ ] Anotar qué tipo de `query` garantiza el desglose (place_id vs google_id).
6. [ ] Revisar consumo de créditos (1 ficha por consulta).

Documentación API: [docs.outscraper.com — google-maps-search](https://docs.outscraper.com/endpoints/google-maps-search/).

---

### Fase 1 — Place ID en el cliente (fuente única)

**Hoy (tras 2026-08-07):** Place ID en **`clients`**; encuesta solo lectura.

**Antes:** `client_improvement_configs.google_place_id` (obligatorio al guardar encuesta).

1. [x] Migración: añadir a `clients` `google_place_id` + `google_id`
2. [x] Migración de datos: copiar desde configs
3. [x] Modelo `Client`: fillable + `normalizeGooglePlaceId()` / `googleReviewUrl()`
4. [x] Filament `ClientResource` create/edit + infolist
5. [x] Validación formato Place ID al guardar (opcional; no obligatorio vacío)
6. [x] Encuesta: quitar input; solo lectura del cliente
7. [x] Vista pública survey: Place ID desde `$client`
8. [ ] Limpieza opcional: dropear columna legacy en configs (más adelante)
9. [x] i18n
10. [x] Docs vivas / HANDOFFS

---

### Fase 2 — Base de datos de reputación

Sin tabla de reseñas individuales.

#### 2.1 Tabla de snapshots

`client_external_reputation_snapshots` — **creada** (migración `2026_08_07_110000_…`).

#### 2.2 Tabla de alertas

`client_external_reputation_alerts` — **creada** (misma migración).

#### 2.3 Modelos Eloquent

1. [x] Migraciones.
2. [x] Modelos + relación `Client::externalReputationSnapshots()` / `externalReputationAlerts()`.
3. [x] Helpers `calculateRatingFromStars` / `fiveStarsNeededForTarget` en el modelo snapshot (sin factory; datos de prueba bajo demanda).

---

### Fase 3 — Cliente HTTP Outscraper + dominio

1. [x] Config `config/services.php` → `outscraper.*` + `.env.example`
2. [x] `OutscraperPlacesClient` + `FakeOutscraperPlacesClient` + `PlaceMetrics`
3. [x] `ExternalReputationSyncService` (snapshot, google_id, last_synced/error, alertas 1★/2★)
4. [x] `RatingProjection`
5. [x] Errores: log + `external_reputation_last_error` sin tumbar batch
6. [x] Tests unitarios (`tests/Unit/ExternalReputation/`)

Doc API: [GET maps/search](https://app.outscraper.cloud/api-docs?ln=es#tag/google/GET/maps/search)

Uso rápido (tinker / código):

```php
config(['services.outscraper.driver' => 'fake']); // o http + API key
$svc = app(\App\Support\ExternalReputation\ExternalReputationSyncService::class);
$svc->syncClient($client);
```

---

### Fase 4 — Comandos y schedule

1. [x] `php artisan external-reputation:sync` (`--client`, `--only-missing`, `--dry-run`)
2. [x] Bootstrap = primer sync (no hace falta comando aparte)
3. [x] Schedule 10:00 / 17:00 / 23:55 `Europe/Madrid` en `routes/console.php`
4. [x] Documentado en [`docs/RUNBOOK.md`](RUNBOOK.md) y [`docs/OPERACIONES_SERVIDOR.md`](OPERACIONES_SERVIDOR.md)
5. [x] Cron VPS `/etc/cron.d/reputalis-scheduler` → `schedule:run` cada minuto

---

### Fase 5 — UI Reputación externa (V1 sin diseño)

Página Filament bajo el cliente (como Dashboard / Encuesta), p. ej. `ReputacionExterna`.

1. [x] Página en `ClientResource` (ruta); acceso desde Dashboard (pestaña externa junto a interna/sector), **sin** ítem en el subnav superior.
2. [x] Permisos: mismos roles que vean ficha cliente (superadmin, distribuidor del cliente, owner). Sync manual solo si `canEdit`.
3. [x] Contenido V1 (maquetación mínima / Blade simple):
   - nota Google;
   - total reseñas;
   - desglose 1–5 (números o barras simples);
   - puntuación exacta calculada (etiqueta clara);
   - bloque estimación “N de 5★ para objetivo X” (objetivo inicial `RatingProjection::DEFAULT_TARGET`);
   - última sync / estado error;
   - botón “Sincronizar ahora” (quien pueda editar el cliente).
4. [x] Histórico V1: tabla por **días** (últimos 30): fecha, rating, total, calculated, stars_1…5.
5. [x] Selectores mes / año: filtrar o agregar esos días (sin hora).
6. [x] i18n completo (`es` / `en` / `pt`).
7. [x] Si falta Place ID: mensaje + enlace a editar ficha cliente.
8. [x] Dashboard pestaña «externa» redirige a esta página (mismas pestañas de reputación arriba).

---

### Fase 6 — Gráficos (V2 UI)

Cuando V1 esté estable:

1. [x] Gráfico evolución nota (Google + calculada) por día / periodo.
2. [x] Gráfico total de reseñas en el tiempo.
3. [x] Series stacked del desglose 1–5.
4. [x] Vistas mes (puntos = días) y año (puntos = meses) — mismos filtros del histórico.
5. [x] Librería: ApexCharts (CDN, misma que el Dashboard interno).

---

### Fase 7 — Alertas

1. [x] Al sync: si `stars_1` o `stars_2` suben respecto al snapshot anterior → crear alerta (`negative_increase`).
2. [x] Mostrar en página reputación (lista / badge / marcar leídas).
3. [ ] (Opcional V2) email al owner / distribuidor — **aplazado**; confirmar canal con producto.
4. [x] Evitar alertas falsas: solo alerta negativa si suben 1★/2★; si solo baja el total → anomalía `total_drop` (aparte, no tratada como reseña mala).

Detector: `NegativeReviewAlertDetector`. Campos `kind`, `delta_reviews_total` en alertas.

---

### Fase 8 — Cierre documental y deploy

1. [x] `docs/HANDOFFS.md` entradas de implementación (fases 1–7 + resumen 2026-08-14).
2. [x] `CONTEXTO_PARA_IA.md`, `DOCUMENTACION_TABLAS_BD.md`, `DESCRIPCION_CLASES.md`, `RESUMEN_PROYECTO.md` (actualizados con reputación externa).
3. [x] `README_AI.md` índice al plan.
4. [ ] Migraciones en **otros** entornos si aún no están + `.env` Outscraper real (`OUTSCRAPER_API_KEY`, `OUTSCRAPER_DRIVER=http`).
5. [ ] Verificar cron schedule en VPS al poner driver real.
6. [ ] Prueba con 1–2 clientes reales y medición de coste.
7. [ ] Quitar o restringir botón **Simular sync (fake +1)** en producción si molesta (hoy visible a quien pueda editar).

---

## 6. Checklist rápido (vista global)

- [ ] Fase 0 — Prueba Outscraper Places + créditos  
- [x] Fase 1 — Place ID en `clients`; encuesta solo lee  
- [x] Fase 2 — Tabla snapshots (+ alertas)  
- [x] Fase 3 — Cliente API + sync + cálculos  
- [x] Fase 4 — Artisan + schedule 3×/día  
- [x] Fase 5 — UI Reputación externa V1  
- [x] Fase 6 — Gráficos  
- [x] Fase 7 — Alertas (email opcional pendiente)  
- [ ] Fase 8 — Docs parciales hechos; falta API real + prueba clientes + pulir simulate en prod

---

## 7. Antecedentes

- Encuesta ya usa Place ID para redirect Google (hoy en `client_improvement_configs`) → **se mueve al cliente**.
- Integración Google Places Details oficial: implementada y **eliminada**; no reabrir.
- Plan anterior (reseñas individuales con fecha): **descartado** por decisión de producto 2026-08-06.

---

## 8. Registro de matizaciones

### 2026-08-14 — Handoff: continuar desde otro PC

- Código fases 1–7 listo en repo. **Antes no estaba committed/pushed** (solo en el VPS). Ver HANDOFFS 2026-08-14.
- Siguiente trabajo: Fase 0 (clave Outscraper) + prueba real + resto Fase 8 operativa.

### 2026-08-07 — Fase 7 implementada

- Detector + UI alertas (badge, leídas, anomalía `total_drop`). Email aplazado. Siguiente: Fase 8 (docs/deploy + API real).

### 2026-08-07 — Fase 6 implementada

- Gráficos ApexCharts en `ReputacionExterna` (nota, total, desglose 1–5) ligados a filtros día/mes/año. Siguiente: Fase 7 (pulir alertas).

### 2026-08-07 — Fase 5 implementada

- Página Filament `ReputacionExterna` + i18n + histórico día/mes/año + sync manual. Siguiente: Fase 6 (gráficos) / Fase 7 (pulir alertas).

### 2026-08-07 — Fase 4 implementada

- Comando + schedule + cron `reputalis-scheduler`. Siguiente: Fase 5 (UI).

### 2026-08-07 — Fase 3 implementada

- Cliente Outscraper + fake + sync service + tests. Siguiente: Fase 4 (comando artisan + cron).

### 2026-08-07 — Fase 2 implementada

- Tablas snapshots + alertas y modelos con helpers de cálculo. Siguiente: Fase 3 (Outscraper HTTP + sync; puede ir con mock sin cuenta).

### 2026-08-07 — Fase 1 implementada

- Place ID movido a `clients`; encuesta solo lectura; survey usa `Client::googleReviewUrl()`.
- Datos migrados desde configs. Siguiente: Fase 2 (snapshots BD).

### 2026-08-06 — Alcance cerrado y reescritura del plan

- Cliente no quiere fechas ni listado de reseñas.
- Quiere: nota, total, desglose, puntuación calculada, estimación de 5★, histórico propio por día/mes/año desde cron.
- Place ID solo en alta/ficha de cliente; encuesta reutiliza.
- Proveedor: Outscraper **Places** (por ficha). Cron 3×/día para fotos y alertas.
- Documento reescrito como guía de desarrollo paso a paso (fases 0–8).

### 2026-07-31 — Creación del plan (obsoleto en alcance)

- Borrador inicial con opción de historial por reseña; supersedido por esta versión.
