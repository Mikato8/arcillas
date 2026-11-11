# Repositorios open source de calculadoras para arcillas, cerámica, fundición, minerales y metalurgia no ferrosa

> **Proyecto:** Arcillas MX · ARCILLAS PROTEÍNAS Y DESARROLLOS GARCÍA
> **Autor:** investigador técnico (equipo Arcillas MX)
> **Fecha de verificación:** 2026-11-11, 13:30–14:00 (America/Mexico_City)
> **Objetivo:** inventario real y verificado de software open source reutilizable para las 8 calculadoras del sitio, con estrellas, licencia, actividad, fórmulas implementadas y recomendación.

---

## 0. Resumen

Se verificaron **19 repositorios reales** (todos con `curl` → **HTTP 200** y con metadata leída de la API de GitHub el 2026-11-11), de los cuales **18 tienen código inspeccionable** (nicoll55/Beno está vacío). Se clonaron en local (`git clone --depth 1`) y se leyeron README y archivos de código concretos: no se reportan estrellas, licencias ni fórmulas "de memoria".

Hallazgos principales:

1. **UMF/Seger** es la categoría mejor cubierta: hay al menos 4 implementaciones reales y una de ellas (**openglaze**, MIT) trae tabla de pesos moleculares, coeficientes de expansión térmica de Appen/Sankey y datos de materiales en JSON. Es la mejor fuente para portar a PHP.
2. **Balance metalúrgico**: la referencia matemática más sólida es el artículo de 911Metallurgist (fórmulas de 2 y 3 productos), y **MetalurgIA** (MIT) da el flujo de UI y la resolución matricial, aunque su cálculo de cola tiene un error evidente.
3. **Granulometría/Stokes**: hay 4 repos; el más útil por licencia es **particle-size-distribution-curve** (MIT), y el más completo conceptualmente es **PSD-Analyser** (GPL, reimplementar).
4. **Geoquímica/CIPW**: hay 3 implementaciones (R y MATLAB); sirven de referencia de normalización catiónica para la fórmula estructural, no para publicar CIPW.
5. **Huecos reales**: **no existe** repositorio open source que implemente dosificación de bentonita en arena verde ni normalización precio/humedad/costo landed para minerales. Esas dos calculadoras deben construirse con fórmulas de práctica industrial y normas (ASTM, Incoterms), no adaptando código.
6. **Licencias**: 11 de 19 tienen licencia clara (MIT/BSD/GPL). Varios de los repos más "directos" (GlazeHub, cone6-umf-pro, glazelab, pySSAS, cipw) **no tienen licencia**, por lo que su código no puede copiarse; sí sus ideas y fórmulas.

---

## 1. Método de verificación

- **Búsqueda**: API de GitHub (`/search/repositories`) y web, con consultas en inglés y español (ver §8). Se respetaron los límites de la API sin autenticación (~10 búsquedas/min; 60 req/h de core) espaciando llamadas.
- **Metadata**: `GET https://api.github.com/repos/{owner}/{repo}` para estrellas, lenguaje, licencia, fechas, forks, topics.
- **Existencia**: `curl -s -o /dev/null -m 30 -w '%{http_code}'` sobre la URL pública de cada repo → todos **200**.
- **Contenido**: `git clone --depth 1` + lectura de README, LICENSE y archivos de código, buscando las fórmulas con `grep`/lectura directa.
- **Estado**: se clasifica "activo" si el último push está a ≤12 meses de la fecha de verificación; "semi-activo" ≤24 meses; "abandonado" >24 meses.
- **Restricción**: la fecha del entorno es 2026-11-11; algunas fechas de repos nuevos (2026) son coherentes con el reloj del sistema. No se inventó ninguna cifra; lo no verificable se marca como tal.

---

## 2. Tabla resumen (19 repos verificados)

> Estrellas/licencias/fechas: API de GitHub, 2026-11-11. "Último push" indica la última vez que el repositorio recibió commits.

| # | Repositorio | ★ | Lenguaje | Licencia | Creado | Último push | Estado | Qué implementa (verificado en código) |
|---|---|---|---|---|---|---|---|---|
| 1 | [KyaniteLabs/openglaze](https://github.com/KyaniteLabs/openglaze) | 5 | Python | MIT | 2026-03-16 | 2026-11-11 | activo | UMF/Seger, CTE (Appen/Sankey), biblioteca de materiales, objetivos por cono, gestor de recetas, API Flask |
| 2 | [Mariki816/GlazeHub](https://github.com/Mariki816/GlazeHub) | 18 | HTML/Python | sin licencia | 2014-10-16 | 2019-04-12 | abandonado | Calculadora de recetas de esmalte + precios de químicos + pedido por correo (Flask/SQLite) |
| 3 | [robocomm/cone6-umf-pro](https://github.com/robocomm/cone6-umf-pro) | 0 | JavaScript | sin licencia | 2026-03-13 | 2026-03-13 | activo (nuevo) | UMF para gres de cono 6, normalización a RO, biblioteca de recetas |
| 4 | [iLanis-agent/glazelab](https://github.com/iLanis-agent/glazelab) | 0 | HTML/JS | sin licencia | 2026-10-07 | 2026-10-07 | activo (nuevo) | UMF/Seger puro en 74 líneas de JS, composiciones de 13 materiales, Si:Al, conjetura de cono |
| 5 | [dlizotte-uwo/glaze-formulator](https://github.com/dlizotte-uwo/glaze-formulator) | 2 | R | GPL-3.0 | 2018-11-19 | 2026-10-05 | activo | Receta inversa desde UMF objetivo por mínimos cuadrados; datos de Glazy |
| 6 | [lamanopottery/clay-shrink-calculator](https://github.com/lamanopottery/clay-shrink-calculator) | 0 | TypeScript | MIT | 2022-12-20 | 2022-12-23 | semi-activo | Contracción de arcilla: dimensiones originales/finales, factor de escala |
| 7 | [emilyparkes/kiln-it](https://github.com/emilyparkes/kiln-it) | 2 | TypeScript | sin licencia | 2020-09-14 | 2024-02-09 | semi-activo | Registro de piezas: arcilla, % contracción, estado de cocción, esmaltes; app full-stack |
| 8 | [pjparis/pySSAS](https://github.com/pjparis/pySSAS-python-Sediment-Size-Analysis-by-Sieve) | 11 | Jupyter | sin licencia | 2017-07-08 | 2020-02-25 | abandonado | Análisis por mallas: % peso, % acumulado, phi, estadísticos de Folk & Ward, modas |
| 9 | [paddy-r/PSD-Analyser](https://github.com/paddy-r/PSD-Analyser) | 5 | Python | GPL-3.0 | 2021-01-11 | 2023-03-25 | semi-activo | Ajuste lognormal de PSD, cuantiles d_q, momentos, algoritmo PDA |
| 10 | [AgyeyaMishra/particle-size-distribution-curve](https://github.com/AgyeyaMishra/particle-size-distribution-curve) | 6 | Jupyter | MIT | 2021-03-19 | 2021-07-17 | semi-activo | % pasante acumulado desde masa retenida, curva semilog |
| 11 | [weber1158/StokesSimulator](https://github.com/weber1158/StokesSimulator) | 0 | MATLAB | MIT | 2026-04-23 | 2026-05-05 | activo (nuevo) | Ley de Stokes, corrección por relación de aspecto, densidad/viscosidad de aire y agua |
| 12 | [prommis/prommis](https://github.com/prommis/prommis) | 24 | Jupyter/Python | BSD (GitHub: NOASSERTION) | 2023-12-11 | 2026-11-09 | activo | Modelos IDAES de procesos: tostación, lixiviación, extracción por solventes, costos |
| 13 | [clayton-iliquin/software-MetalurgIA](https://github.com/clayton-iliquin/software-MetalurgIA) | 5 | Python | MIT | 2024-02-17 | 2024-05-29 | activo | Balances de molienda/hidrociclón y 1–3 concentrados; Streamlit |
| 14 | [HarmanBhangu1313/pcb-metallurgy-valuation](https://github.com/HarmanBhangu1313/pcb-metallurgy-valuation) | 0 | Python | MIT | 2026-10-28 | 2026-11-05 | activo (nuevo) | Valuación metalúrgica: ensayes por componente, precios, balance hidrometalúrgico, pagable |
| 15 | [TheRFrog/shinyNORRRM](https://github.com/TheRFrog/shinyNORRRM) | 4 | R | GPL-3.0 | 2022-04-10 | 2022-11-27 | abandonado | Norma CIPW (Verma 2002/2003) + índices (Mg#, CIA, CIW, ACNK, MALI) + clasificación TAS |
| 16 | [tobiste/cipwnormR](https://github.com/tobiste/cipwnormR) | 0 | R | ambigua (LICENSE incompleto) | 2026-10-26 | 2026-10-26 | activo (nuevo) | Norma CIPW en R, gráfico TAS |
| 17 | [wjsawyer/cipw](https://github.com/wjsawyer/cipw) | 0 | MATLAB | sin licencia | 2014-02-28 | 2014-03-03 | abandonado | Norma CIPW desde 11 óxidos; pesos moleculares y minerales normativos explícitos |
| 18 | [nicoll55/Beno](https://github.com/nicoll55/Beno) | 0 | — | sin licencia | 2023-02-11 | 2023-02-11 | **vacío** | Descripción "Bentonite Calculator", pero **sin archivos** (solo `.git`) |
| 19 | [yunlongcasting/CastCalc](https://github.com/yunlongcasting/CastCalc) | 7 | Python | MIT | 2026-08-15 | 2026-08-17 | activo (nuevo) | Calculadoras de fundición: sistema de colada, diseño de mazarotas, retiro/sobreespesor/ángulo de salida |

Notas:
- **BSD de prommis**: `LICENSE.md` contiene texto de licencia estilo BSD de 3 cláusulas; GitHub la clasifica como `NOASSERTION`.
- **cipwnormR**: el archivo `LICENSE` solo contiene `YEAR: 2026` y `COPYRIGHT HOLDER: cipwnormR authors` (plantilla sin completar) → licencia no especificada.
- **Beno**: verificado con `ls` tras clonar; no hay código. Se descarta.

---

## 3. Fichas por categoría

### 3.1 Esmaltes cerámicos y UMF/Seger

#### 3.1.1 KyaniteLabs/openglaze — **MIT** ⭐5, activo

- **Qué es**: calculadora de esmaltes, analizador UMF, estimador de CTE y gestor de recetas, self-hosted (Flask + SQLite + Docker).
- **Código verificado**:
  - `core/chemistry/umf.py` (661 líneas): clase `UMFAnalyzer` con `_calculate_moles()` (masa/PM por óxido) y `_normalize_to_fluxes()` (divide entre la suma de óxidos fundentes); `FLUX_OXIDES = {K2O, Na2O, CaO, MgO, ZnO, BaO, SrO, Li2O, FeO}`; pesos moleculares de 21 óxidos (SiO2 60.084; Al2O3 101.961; K2O 94.196; CaO 56.077…); `UMFResult` con `ratios`, `limit_warnings`, `surface_prediction`.
  - `core/chemistry/thermal_expansion.py`: `CTE = Σ fracción_molar × coeficiente` (método Appen/Winkler-Mackenzie) y comparación con rangos típicos de pasta (gres 5.5–6.5 ×10⁻⁶/°C).
  - `ceramics-foundation/data/thermal-expansion.json`: coeficientes con fuente ("Appen (1950), John Sankey"), p. ej. Na2O 39.5; K2O 46.5; CaO 13.0; MgO 3.4; Al2O3 4.2 (Sankey); SiO2 3.8; B2O3 −2.0; ZrO2 −6.0 (×10⁻⁶/K).
  - `ceramics-foundation/data/umf-target-ranges.json`: rangos objetivo por cono (Stull vía Glazy, Digitalfire, Ceramic Arts Network) con advertencias.
- **Vale la pena adaptar**: la estructura de datos (materiales con análisis de óxidos + PM), la fórmula UMF y la tabla de coeficientes CTE. **Licencia MIT** permite reutilizar con atribución.
- **Riesgo**: los valores "hardcoded" de CTE en `materials.py` son de respaldo y no coinciden con los del JSON; al portar, usar solo el JSON documentado.

#### 3.1.2 iLanis-agent/glazelab — sin licencia ⭐0, activo (2026-10)

- **Qué es**: UMF/Seger estático en JS puro (sin build), 74 líneas de motor.
- **Código verificado** (`engine.js`): tabla de PM (`K2O 94.196`, `Na2O 61.979`, `CaO 56.077`, `MgO 40.304`, `Al2O3 101.961`, `SiO2 60.083`…); materiales con composición de óxidos en base cocida y "masa por unidad de fórmula"; `umf(recipe)` calcula unidades de fórmula = %/M, suma moles, normaliza a fundentes (K2O+Na2O+CaO+MgO+BaO+ZnO); devuelve `siAl` y `fluxBreakdown`. Tiene `tests/build_corpus.py` + `tests/run_tests.js` (oráculo en Python).
- **Vale la pena adaptar**: es la implementación más compacta y legible para portar a PHP; **no se puede copiar** (sin licencia), pero las fórmulas son hechos matemáticos.
- **Uso**: base del caso de prueba del §A de `formulas-referencias.md` (se ejecutó el motor en Node y coincidió con el cálculo a mano).

#### 3.1.3 robocomm/cone6-umf-pro — sin licencia ⭐0

- **Qué es**: app React de una página para UMF de **gres de cono 6**, con biblioteca de recetas guardada.
- **Código verificado** (`src/App.jsx`): pesos moleculares; `moles = weight / mw`; normaliza a `roMoles = CaO+MgO+K2O+Na2O+SrO` (no incluye ZnO/BaO, a diferencia del estándar); tabla de objetivos de cono 6 (K2O 0.05–0.15; Na2O 0–0.10; Al2O3 0.25–0.35; SiO2 3.0–4.5).
- **Vale la pena adaptar**: los objetivos numéricos por cono y el UX de "receta + biblioteca". **Ojo**: su normalización RO es una variante (no la UMF estándar de "todos los fundentes = 1"); no usarla como referencia normativa.

#### 3.1.4 Mariki816/GlazeHub — sin licencia ⭐18, abandonado (2019)

- **Qué es**: calculadora de recetas de esmalte con precios de químicos, cotización, impuestos/envío y pedido por correo (Flask + SQLite + SQLAlchemy).
- **Código verificado**: `model.py`, `converter.py`, `pricecompute.py`, `glazehub_2.2_changedcalc.py`; README detalla el modelo (Recipe–Component–Chemical, precios por rango de peso) y el flujo de cotización.
- **Vale la pena adaptar**: el **modelo de precios por rango de cantidad** y el flujo "receta → costo → pedido" es exactamente la lógica comercial que necesita un broker. Sin licencia: reimplementar.
- **Estado**: último push 2019; el propio README advierte que no corre fuera del autor (le falta la lista de precios de Clay Planet).

#### 3.1.5 dlizotte-uwo/glaze-formulator — **GPL-3.0** ⭐2, activo

- **Qué es**: app Shiny (R) que **invierte** la UMF: dada una UMF objetivo, calcula la receta de materiales.
- **Código verificado**: `app.R` construye `materials_wt_matrix` (óxidos × materiales) y `materials_mol_matrix` (moles por 100 g = %/PM); `OptimizationDetails.Rmd` deriva el problema de **mínimos cuadrados** `(M r − τ)ᵀ(M r − τ)` y su solución; usa el CSV de materiales de Glazy (`glazy-data-20200830.csv`).
- **Vale la pena adaptar**: la formulación matemática del inverso (útil para "¿qué mezcla de arcillas me da esta composición?") y el uso de un dataset de materiales. **GPL**: no copiar código en un sitio propietario; reimplementar el álgebra (que además es estándar).

### 3.2 Pastas y arcillas (contracción, absorción, mezclas)

#### 3.2.1 lamanopottery/clay-shrink-calculator — **MIT** ⭐0

- **Código verificado**: `src/App.tsx` calcula `shrinkage = porcentaje/100` y `ShrinkPair` usa `rate = 1 - shrinkage` para convertir dimensiones originales ↔ finales; valida 0–100 %.
- **Vale la pena adaptar**: la conversión bidireccional y el UX mínimo; es un caso de uso, no una fórmula avanzada.

#### 3.2.2 emilyparkes/kiln-it — sin licencia ⭐2

- **Qué es**: app full-stack (React/Express/SQLite) para registrar piezas de cerámica con arcilla, % de contracción, estado de cocción y esmaltes.
- **Código verificado**: migraciones y seeds en `server/db/` (`clay.js`, `shapes.js`, `glazes.js`, `creations.js`); la contracción se guarda por arcilla, no se calcula.
- **Vale la pena adaptar**: el **modelo de datos** "arcilla con % contracción" y el registro de piezas (útil para "mis mezclas" o para fichas de material con contracción medida en planta).

#### 3.2.3 Mezclas de arcillas

No se encontró un repositorio específico de "mezcla de arcillas" con resolución de proporciones. La lógica es la **regla de mezclas** (promedio ponderado de propiedades) y, para objetivos múltiples, **mínimos cuadrados con suma = 1 y no negativos**; el único repo con esa maquinaria es `glaze-formulator` (GPL, §3.1.5). Se recomienda implementación propia (ver `formulas-referencias.md`, calculadora C2).

### 3.3 Fundición y bentonita para arena verde

#### 3.3.1 yunlongcasting/CastCalc — **MIT** ⭐7, activo

- **Qué es**: calculadoras de ingeniería de fundición en Python puro, sin dependencias, con **cada fórmula citada en el código** (documentación bilingüe chino/inglés).
- **Código verificado**: `castcalc/pouring.py` (tiempo de colada, área de estrangulamiento, secciones de mazarota/ataque), `castcalc/riser.py` (criterio de módulo + criterio volumétrico, `η` de alimentación 14/30/35 % para mazarota normal/aislada/exotérmica), `castcalc/params.py` (retiro lineal por aleación, sobreespesor GB/T 6414, ángulo de salida, espesor mínimo), `tests/test_golden.py`.
- **Vale la pena adaptar**: la **disciplina de citar la fuente de cada constante** y el patrón de calculadora con "lookup" + "wizard"; sirve de plantilla conceptual para la calculadora de bentonita, aunque **no implementa bentonita**.
- **Dato útil**: su tabla de retiro lineal (hierro gris 0.8–1.0 %; acero 1.6–2.0 %; aluminio 1.0–1.2 %) es referenciable para la ficha de fundición.

#### 3.3.2 nicoll55/Beno — ⭐0, repositorio vacío

- Descripción: "Bentonite Calculator". **Verificado**: el clon solo contiene `.git`, sin archivos. No utilizable; se documenta para que nadie lo cite como fuente.

> **Hueco identificado**: no hay repositorio open source que implemente la **dosificación de bentonita en arena verde** (búsquedas "bentonite", "green sand", "sand testing", "molding sand"). La fórmula se construye desde la composición típica de la arena verde (sílice 75–85 %, bentonita 5–11 %, agua 2–4 %, lodo inerte 3–5 %, antracita 0–1 %; ver Wikipedia/sand casting) y la práctica de reposición por pérdida al quemado. Es una **oportunidad de contenido original** para arcillas.mx.

### 3.4 Procesamiento de minerales, granulometría y Stokes

#### 3.4.1 pjparis/pySSAS — sin licencia ⭐11, abandonado

- **Código verificado** (`pySSAS.py`): `%` de peso por fracción, `%` acumulado, `phi = -log2(mm)` (a partir de `2**(-phi)`), puntos medios por clase, estadísticos de **Folk & Ward (1957)** (media, clasificación, asimetría, curtosis), modas y gráficas PDF/CDF; hay notebook con documentación completa.
- **Vale la pena adaptar**: la **semántica de granulometría** (phi, % retenido/pasante, interpolación de cuantiles). Sin licencia: reimplementar.

#### 3.4.2 paddy-r/PSD-Analyser — **GPL-3.0** ⭐5

- **Código verificado**: `psdanalyser/psd_analyser.py` ajusta **lognormal** a CDF/PDF; `equations.md` documenta la derivación: `C(d) = ½[1 + erf(ln(d/M)/(S√2))]`, cuantiles `d_q = exp[M + √(2S²)·erf⁻¹(2q−1)]`, momentos `m_n = exp(nM + ½n²S²)`, Sauter `d[3,2]` y volumen `d[4,3]`; algoritmo PDA.
- **Vale la pena adaptar**: la matemática de cuantiles y momentos para D10/D50/D90 y para ajustar distribuciones; **GPL**: reimplementar.

#### 3.4.3 AgyeyaMishra/particle-size-distribution-curve — **MIT** ⭐6

- **Código verificado**: `src/Particle Size Distribution Curve.py` calcula `percent_finer = (total − cumulative) / total × 100` y grafica en escala semilog.
- **Vale la pena adaptar**: es el código más simple y **MIT** para la lógica de % pasante; se puede portar casi literalmente (con atribución).

#### 3.4.4 weber1158/StokesSimulator — **MIT** ⭐0

- **Código verificado**: `src/algorithms/stokes_law_model_water.m` implementa `V_p = (2/9)·(r²·(ρ_p − ρ_f)·9.81)/μ_f`; modelos de densidad/viscosidad del aire por altitud; corrección por relación de aspecto.
- **Vale la pena adaptar**: validación numérica de Stokes y el manejo de unidades (cm/h, m/s). MIT permite reutilizar la fórmula con atribución.

### 3.5 Metalurgia no ferrosa: balances, recuperación y valuación

#### 3.5.1 clayton-iliquin/software-MetalurgIA — **MIT** ⭐5

- **Qué es**: app Streamlit para balances de circuitos de molienda e hidrociclón y balances metalúrgicos de 1 a 3 concentrados.
- **Código verificado** (`Utilities/mass_balance.py`):
  - Carga circulante por sólidos: `cc = (1/sol_over − 1/sol_feed) / (1/sol_feed − 1/sol_under)`.
  - Balance 1 concentrado: `feed_tons_dry = feed_ton·(100−humedad)/100`; `conc_ratio = (feed_law − tail_law)/(conc_law − tail_law)` (esto es **1/K**, la inversa de la razón de concentración); `conc_tons_dry = feed_tons_dry·conc_ratio` (correcto, equivale a F/K); **`tail_tons_dry = feed_tons_dry − conc_ratio` está mal** (debe restar la masa de concentrado, no la razón).
  - Balance 2 concentrados: plantea el sistema matricial `[[1,1,1],[c1,c2,t1],[c1',c2',t2]]·x = [1,f1,f2]` y multiplica por las toneladas secas; la distribución porcentual `100·(masa·ley)/(masa_feed·ley_feed)` sí está bien y la razón `F/C` también.
- **Vale la pena adaptar**: el flujo de UI, la estructura de tablas de balance y la resolución matricial para 2–3 productos. **MIT**: se puede adaptar con atribución, corrigiendo los errores anteriores.

#### 3.5.2 HarmanBhangu1313/pcb-metallurgy-valuation — **MIT** ⭐0 (nuevo)

- **Código verificado** (`metallurgy_engine.py`): base de datos de composición por tipo de componente (IC, capacitor, conector, transformador, transistor…), rangos mín/medio/máx, precios de metales en vivo, consumo de reactivos y **modelo de pagable/profit**; app Streamlit con detección YOLO.
- **Vale la pena adaptar**: la lógica de **valuación pagable** (metal fino × precio × pagable − cargos) aplicada a concentrados de Cu/Zn/Pb/Ag en lugar de e-waste. Es un caso de negocio distinto, pero la estructura de cálculo es reutilizable.

#### 3.5.3 prommis/prommis — BSD ⭐24

- **Qué es**: "Process Optimization and Modeling for Minerals Sustainability" (DOE/IDAES): modelos de tostación, lixiviación, extracción por solventes, intercambio iónico, evaporación, costos; documentación en Read the Docs.
- **Vale la pena adaptar**: ideas de **balance de materia/energía** y modelado de procesos; es demasiado pesado (IDAES/Pyomo) para el sitio. Referencia conceptual y de nomenclatura.

### 3.6 Geoquímica y normas

#### 3.6.1 TheRFrog/shinyNORRRM — **GPL-3.0** ⭐4

- **Código verificado**: `R/ultimateCIPW.R` (norma CIPW según Verma 2002/2003), `R/adjRock.R` (ajuste de Fe), `R/Indices.R`: Mg# `100·MgO/(MgO+FeO)` molar, CIA, CIW, ACNK, MALI, TAS; `data/` con plantillas de entrada.
- **Vale la pena adaptar**: la **normalización catiónica** y los índices geoquímicos; útil para la calculadora de fórmula estructural. GPL: reimplementar.

#### 3.6.2 tobiste/cipwnormR — licencia ambigua ⭐0

- **Código verificado**: `R/CIPW.R` y `R/CIPW.trace.R` implementan la norma; `R/TASplot.R`. Repositorio nuevo (2026-10-26), sin historial de uso. Licencia incompleta → no usar código.

#### 3.6.3 wjsawyer/cipw — sin licencia ⭐0, abandonado (2014)

- **Código verificado**: `main.m` convierte 11 óxidos a moles con pesos moleculares explícitos (SiO2 60.0843; Al2O3 101.9613; K2O 94.1960…) y calcula minerales normativos (ap, il, or, ab, an, mt, hm, Q, ol, di, hy, ne…).
- **Vale la pena adaptar**: es la **referencia más transparente** del algoritmo CIPW; sin licencia, solo lectura.

---

## 4. Qué adaptar y qué no (matriz de decisión)

| Necesidad del sitio | Mejor referencia | Licencia | Código reutilizable | Estrategia |
|---|---|---|---|---|
| UMF/Seger | openglaze + glazelab | MIT / s/l | Parcial (openglaze) | Portar UMF y tabla de PM; validar con caso del doc 3 |
| CTE del esmalte | openglaze (JSON Appen/Sankey) | MIT | Sí | Copiar tabla con atribución y nota de incertidumbre |
| Receta inversa desde UMF | glaze-formulator | GPL | No | Reimplementar mínimos cuadrados |
| Mezcla de arcillas | regla de mezclas + glaze-formulator | — / GPL | No | Implementación propia |
| Contracción/absorción | clay-shrink-calculator + ASTM | MIT | Parcial | Fórmulas propias + normas |
| Bentonita arena verde | CastCalc (contexto fundición) | MIT | Parcial | Fórmula propia con composición típica |
| Granulometría | PSD-curve, PSD-Analyser, pySSAS | MIT / GPL / s/l | Parcial | Implementar % pasante, D10/50/90, phi |
| Stokes | StokesSimulator + Wikipedia | MIT | Sí (fórmula) | Portar fórmula y validar Re < 1 |
| Balance metalúrgico | 911Metallurgist + MetalurgIA | artículo / MIT | Parcial | Implementar 2 y 3 productos; corregir bugs |
| Valuación pagable | pcb-metallurgy-valuation | MIT | Parcial | Adaptar a concentrados |
| Geoquímica/norma | shinyNORRRM, cipw | GPL / s/l | No | Solo normalización catiónica propia |
| Precio/humedad/landed | **sin repo** | — | — | Fórmula propia (ASTM D2216 + Incoterms) |

**Reglas de licencia (resumen práctico):**

- **MIT/BSD**: se puede reutilizar el código citando autor y licencia (openglaze, CastCalc, clay-shrink-calculator, PSD-curve, StokesSimulator, MetalurgIA, pcb-metallurgy-valuation, prommis).
- **GPL-3.0**: solo se puede integrar si el sitio se distribuye bajo GPL; en un sitio propietario **no** se copia el código (glaze-formulator, PSD-Analyser, shinyNORRRM). Las **ideas y fórmulas** no son objeto de derecho de autor.
- **Sin licencia o licencia ambigua**: todos los derechos reservados por defecto → **prohibido copiar** (GlazeHub, cone6-umf-pro, glazelab, kiln-it, pySSAS, cipw, cipwnormR).
- En todos los casos: los datos de materiales de terceros (p. ej. CSV de Glazy) tienen sus propias condiciones; no reutilizarlos sin permiso.

---

## 5. Recomendación: las 8 calculadoras del sitio

Ordenadas por **valor de negocio + esfuerzo** (la implementación corre por cuenta del equipo de cálculo; ver `docs/calculadoras/`).

| # | Calculadora | Slug propuesto | Referencias | Justificación | Complejidad |
|---|---|---|---|---|---|
| 1 | **Balance metalúrgico no ferroso** | `balance-metalurgico` | 911Metallurgist; MetalurgIA (MIT); pcb-valuation (MIT) | Es la calculadora con retorno directo: el cliente tiene leyes/tonelajes y necesita recuperación, razón de concentración y valor pagable antes de comprar. Diferencia clara frente a un directorio. | Media |
| 2 | **Normalización de precio/humedad y costo landed** | `precio-normalizado` | ASTM D2216; Incoterms FOB/CIF (Wikipedia); Banxico (tipo de cambio) | El broker negocia todos los días: pasar de tonelada húmeda a seca, castigar humedad, sumar flete/seguro/arancel/IVA y comparar contra costo por unidad de óxido. Nadie más lo ofrece. | Baja |
| 3 | **Mezcla de arcillas** | `mezcla-arcillas` | regla de mezclas + glaze-formulator (GPL, solo matemática) | El cliente formula su pasta: "quiero 9 % de contracción y 30 % de Al2O3, ¿en qué proporción mezclo A y B?". Convierte una cotización en asesoría. | Media |
| 4 | **Bentonita para arena verde** | `bentonita-arena-verde` | CastCalc (MIT, contexto fundición); composición típica de arena verde (Wikipedia/sand casting) | La bentonita es un producto estrella del catálogo; la calculadora de dosificación por lote y reposición es una herramienta de venta técnica que fideliza al fundidor. | Media |
| 5 | **UMF/Seger de esmalte** | `umf-esmalte` | openglaze (MIT), glazelab, Digitalfire | Atrae al ceramista/formulador (SEO alto: "calculadora UMF", "Seger"), y permite ofrecer feldespato/arcilla como insumo de la receta. Efecto de red: se comparte en talleres y escuelas. | Alta |
| 6 | **Contracción y absorción cerámica** | `contraccion-absorcion` | ASTM C326 y C373; clay-shrink-calculator (MIT) | Complemento natural de la 5ª: el ceramista necesita ajustar molde y verificar absorción/porosidad. Datos que el broker puede medir en planta y mostrar como valor agregado. | Baja |
| 7 | **Granulometría y Stokes** | `granulometria-stokes` | PSD-curve (MIT), PSD-Analyser (GPL, solo matemática), StokesSimulator (MIT), Wikipedia | Sirve a fundición, cerámica y pinturas: malla/d50/% pasante y sedimentación. También ayuda a cobrar mejor la molienda. | Media |
| 8 | **Fórmula estructural de arcillas** | `formula-estructural` | shinyNORRRM (GPL, normalización); cipw (referencia); mineralogía estándar | Da autoridad técnica (clasificar caolinita/illita/montmorillonita a partir de un análisis químico) y genera contenido citable; ideal como herramienta "de marca" del sitio. | Alta |

**Prioridad de entrega sugerida:** 1 y 2 (impacto comercial inmediato) → 3, 4 y 6 (bajo esfuerzo, alto uso) → 5 y 7 (SEO/atracción) → 8 (autoridad, más compleja).

**Nota sobre la normalización UMF:** la calculadora del equipo (task-2) especifica "moles normalizados a RO+R2O3 = 1". La convención estándar (Digitalfire, Glazy, openglaze, glazelab) es **normalizar a la suma de fundentes = 1** (todo RO+R2O+…), y por eso SiO2 aparece típicamente entre 2 y 6 y Al2O3 entre 0.2 y 0.6. Si se normaliza solo RO+R2O3 se obtiene un número distinto; conviene fijar una sola convención y documentarla en la interfaz. Ver `formulas-referencias.md` §C1.

---

## 6. Búsquedas ejecutadas y resultados negativos (transparencia)

Consultas a la API de búsqueda de GitHub (ordenadas, con `per_page` ≤ 15):

- `glaze calculator` → 397 resultados (ruido de "awesome lists"); `"glaze calculator" in:name,description` → 14.
- `UMF glaze` → 33; `umf seger` → 1; `cone6 umf` → 1.
- `glazelab` → 10 (varios homónimos sin relación cerámica).
- `ceramic glaze` → 67; `ceramic recipe calculator` → 2.
- `clay shrinkage` → 7; `water absorption ceramic` → 0.
- `green sand foundry` → 0; `foundry sand` → 92 (todos "Foundry" = Ethereum); `foundry calculator` → 99 (casi todos Ethereum); `molding sand` → 5; `sand testing foundry` → 5; finalmente `foundry calculator` filtrando por lenguaje Python encontró **CastCalc**.
- `bentonite` → 49 (ninguno calculadora; **Beno vacío**).
- `particle size distribution` → 194; `sieve analysis` → 130; `stokes settling velocity` → 1; `molding sand` → 5.
- `metallurgical balance` y `CIPW norm` y `normative mineralogy` → limitados por rate limit al inicio; se resolvieron con búsquedas posteriores (`CIPW` → 61; `green sand molding` → 9; `mining calculator` → 357, todos cripto).
- `moisture content` → 380 (sensores/IoT, no normalización comercial); `water absorption ceramic` → 0.
- **No se encontró** ningún repo de "landed cost" / "moisture deduction" / "price normalization" para minerales; las calculadoras existentes son de criptomonedas o de logística genérica.

> Ningún repositorio listado fue inventado: todos se abrieron, se clonaron y se inspeccionaron. Las estrellas y licencias corresponden a la API al 2026-11-11 y pueden cambiar; el código de este documento debe revisarse antes de publicar en producción.

---

## 7. Fuentes

### 7.1 Repositorios (todos verificados HTTP 200)

1. <https://github.com/KyaniteLabs/openglaze>
2. <https://github.com/Mariki816/GlazeHub>
3. <https://github.com/robocomm/cone6-umf-pro>
4. <https://github.com/iLanis-agent/glazelab>
5. <https://github.com/dlizotte-uwo/glaze-formulator>
6. <https://github.com/lamanopottery/clay-shrink-calculator>
7. <https://github.com/emilyparkes/kiln-it>
8. <https://github.com/pjparis/pySSAS-python-Sediment-Size-Analysis-by-Sieve>
9. <https://github.com/paddy-r/PSD-Analyser>
10. <https://github.com/AgyeyaMishra/particle-size-distribution-curve>
11. <https://github.com/weber1158/StokesSimulator>
12. <https://github.com/prommis/prommis>
13. <https://github.com/clayton-iliquin/software-MetalurgIA>
14. <https://github.com/HarmanBhangu1313/pcb-metallurgy-valuation>
15. <https://github.com/TheRFrog/shinyNORRRM>
16. <https://github.com/tobiste/cipwnormR>
17. <https://github.com/wjsawyer/cipw>
18. <https://github.com/nicoll55/Beno> (vacío)
19. <https://github.com/yunlongcasting/CastCalc>

### 7.2 Referencias técnicas usadas para contrastar fórmulas

- Digitalfire — UMF: <https://digitalfire.com/4sight/education/unity_molecular_formula_101_1.html> (URL responde 200; el HTML bloquea lectura automatizada, por lo que no se citan cifras textuales de allí).
- Digitalfire — Fórmulas límite: <https://digitalfire.com/4sight/education/limit_formulas_101_1.html>
- Digitalfire — Contracción: <https://digitalfire.com/4sight/education/shrinkage_101_1.html>
- Digitalfire — Absorción de agua: <https://digitalfire.com/4sight/education/water_absorption_101_1.html>
- 911Metallurgist — Metallurgical Accounting Formulas: <https://www.911metallurgist.com/blog/metallurgical-accounting/>
- Wikipedia — Molding sand: <https://en.wikipedia.org/wiki/Molding_sand>
- Wikipedia — Sand casting: <https://en.wikipedia.org/wiki/Sand_casting>
- Wikipedia — Stokes's law: <https://en.wikipedia.org/wiki/Stokes%27_law>
- Wikipedia — Normative mineralogy (CIPW): <https://en.wikipedia.org/wiki/Normative_mineralogy>
- Wikipedia — Material balance: <https://en.wikipedia.org/wiki/Material_balance>
- ASTM C326 (contracción): <https://www.astm.org/c0326-09.html>
- ASTM C373 (absorción): <https://www.astm.org/c0373-18.html>
- ASTM D2216 (humedad): <https://www.astm.org/d2216-19.html>
- Banxico (tipo de cambio): <https://www.banxico.org.mx/tipcamb/main.do?page=tip&idioma=sp>

> Los repositorios marcados "sin licencia" **no** deben usarse como código; se citan solo como evidencia de que la funcionalidad existe y como referencia de enfoque.
