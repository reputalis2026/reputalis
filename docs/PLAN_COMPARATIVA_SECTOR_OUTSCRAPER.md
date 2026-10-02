# Plan: Comparativa sector vía Outscraper

**Estado:** UI fake en panel **rol cliente** (2026-09-17 / commit posterior). Datos reales **no** implementados.  
**Relacionado:** [`PLAN_REPUTACION_OUTSCRAPER.md`](PLAN_REPUTACION_OUTSCRAPER.md) (ficha propia del cliente), [`HANDOFFS.md`](HANDOFFS.md).

---

## 1. Objetivo

Mostrar en Comparativa sector la posición del cliente frente a farmacias del mismo **código postal**, **ciudad** y **provincia** (KPIs, banner de proyección 5★, scatter/histograma, evolución), con datos públicos de Google Maps vía **Outscraper Places** (sin OAuth GBP).

---

## 2. Qué hay hoy (hecho)

- Pestaña / menú **Comparativa sector** en rol cliente.
- UI mockup: 3 bloques (CP / ciudad / provincia), KPIs, banner, gráficos Apex (scatter en CP; histograma en ciudad/provincia; evolución apilada).
- Datos de `App\Support\SectorComparison\FakeSectorComparisonBuilder` (deterministas, badge «datos de demostración»).
- Staff sin preview de panel cliente: placeholder.

Piezas: `FakeSectorComparisonBuilder`, `sector-comparison.blade.php`, `sector-comparison-charts-script.blade.php`, i18n `lang/{es,en,pt}/client.php`, estilos en `client-panel-theme.blade.php`, `ClientDashboard::getSectorComparison()`.

---

## 3. Decisión de arquitectura (acordada)

| Tema | Decisión |
|------|----------|
| Fuente | Outscraper **Places** (`/google-maps-search`), cobro **por ficha** |
| Estrategia coste | Scrape **provincial mensual** → **BD compartida** por zona; no 1 llamada por cliente al abrir el panel |
| CP y ciudad | **Filtros** sobre el catálogo provincial (`postal_code` / `city`), no scrapes aparte |
| Ficha propia | Nota/reseñas del sync de reputación externa (más fresco que el catálogo zonal) |
| Ranking | En app: nota ↓, empate por nº reseñas ↓ |
| Evolución | Snapshots propios de posición (Outscraper no da histórico de ranking) |
| Frecuencia set zonal | **Mensual** (default); CP/ciudad salen del mismo set |
| Provincia en cliente | Hoy no hay campo claro → **añadir o derivar** (CP / ciudad) |

Ejemplo de queries: `farmacia, {zona}, España` con `region=ES`, `language=es`, `limit` hasta 500. Provincias densas (p. ej. Málaga ~800–1000 oficinas): **trocear** (municipios/CPs/coordenadas) + deduplicar por `place_id`. `skipPlaces` en la misma query suele no dar más de ~500 resultados útiles de Maps.

---

## 4. Coste orientativo

Mismos tramos que reputación externa (Places): ~500 fichas/mes gratis, luego ~3 USD / 1.000.

| Acción | Orden de magnitud |
|--------|-------------------|
| Refresco mensual 1 provincia media (Almería ~200–400) | céntimos / &lt; 1 USD |
| Málaga completa (~800–1000, troceada) | ~1–3 USD/mes si el free tier ya se gastó en syncs diarios |
| 50 clientes en la misma provincia | **mismo** coste de set (cache compartida) |

No llamar Outscraper en cada request del dashboard.

---

## 5. Qué falta (futuro)

### Spike (antes de producto)

1. [ ] API key real / driver `http` (compartido con reputación externa).
2. [ ] Probar 1 provincia (Almería o Málaga): cobertura, `rating`/`reviews`/`postal_code`/`city`, créditos.
3. [ ] Validar si hace falta troceo y categoría «farmacia» vs ruido (parafarmacias, etc.).

### Datos y dominio

4. [ ] Campo o resolución de **provincia** en cliente.
5. [ ] Tablas (borrador): catálogo zonal (`place_id`, nombre, rating, reviews, postal, city, province, last_seen_at…) + snapshots de posición del cliente por ámbito/fecha.
6. [ ] Ampliar gateway Outscraper para devolver **listas** de fichas (`limit` &gt; 1), no solo la primera.
7. [ ] Job/comando mensual de refresco por provincia (async si el set es grande).
8. [ ] Sustituir `FakeSectorComparisonBuilder` por lectura de BD + cálculo de KPIs/gráficos.
9. [ ] Banner 5★ con fórmula real (p. ej. reutilizar lógica de `RatingProjection` vs el de delante).
10. [ ] Serie de evolución a partir de snapshots mensuales.
11. [ ] Quitar badge de demostración cuando haya datos reales; empty states si falta CP/ciudad/set.

### UI (opcional / polish)

12. [ ] Seguir alineando al mockup (pastillas 6/12 meses en evolución, etc.).
13. [ ] Preview staff del panel cliente si se desea ver la misma UI.

---

## 6. Riesgos

- Cobertura Maps ≠ censo oficial de farmacias.
- Fichas sin `postal_code`/`city` → excluirlas del filtro o completar.
- Sin provincia en ficha de cliente, el bloque provincial queda ambiguo.
- Free tier compartido con sync 3×/día de reputación externa.

---

## 7. Orden sugerido

1. Spike Outscraper 1 provincia → anotar fichas + créditos.  
2. Modelo BD + job mensual + seed Almería/Málaga.  
3. Cablear UI real y evolución.  
4. Pulido visual y vacíos.

---

## Registro

| Fecha | Nota |
|-------|------|
| 2026-10-02 | Documento creado tras UI fake; arquitectura provincial mensual + filtros CP/ciudad acordada en chat. |
