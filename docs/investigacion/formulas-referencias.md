# Fórmulas, variables y casos de prueba para las 8 calculadoras de arcillas.mx

> **Proyecto:** Arcillas MX · ARCILLAS PROTEÍNAS Y DESARROLLOS GARCÍA
> **Autor:** investigador técnico (equipo Arcillas MX)
> **Fecha:** 2026-11-11
> **Uso:** documento de referencia para el equipo de cálculo (`app/Services/Calculators/**`) y para QA. Cada calculadora trae: definición, fórmula, variables, unidades, constantes, **caso numérico resuelto** y valores esperados con tolerancia.

> **Convención de este documento:** los resultados se redondean **solo al mostrar**; los cálculos se hacen en doble precisión. Los casos de prueba están pensados para `assert` con tolerancias indicadas (normalmente `1e-4` relativo salvo que se diga otra cosa).

---

## Índice

- [C0. Constantes y convenciones generales](#c0-constantes-y-convenciones-generales)
- [C1. UMF / Seger de un esmalte](#c1-umf--seger-de-un-esmalte)
- [C2. Mezcla de arcillas](#c2-mezcla-de-arcillas)
- [C3. Contracción y absorción cerámica](#c3-contracción-y-absorción-cerámica)
- [C4. Balance metalúrgico no ferroso](#c4-balance-metalúrgico-no-ferroso)
- [C5. Dosificación de bentonita para arena verde](#c5-dosificación-de-bentonita-para-arena-verde)
- [C6. Granulometría y ley de Stokes](#c6-granulometría-y-ley-de-stokes)
- [C7. Normalización de precio, humedad y costo landed](#c7-normalización-de-precio-humedad-y-costo-landed)
- [C8. Fórmula estructural de arcillas y filosilicatos](#c8-fórmula-estructural-de-arcillas-y-filosilicatos)
- [Anexo A. Tabla consolidada de casos de prueba](#anexo-a-tabla-consolidada-de-casos-de-prueba)
- [Anexo B. Fuentes](#anexo-b-fuentes)

---

## C0. Constantes y convenciones generales

### C0.1 Pesos moleculares de óxidos (g/mol)

Valores usados en todos los cálculos (coinciden con los de openglaze, glazelab y la práctica cerámica estándar):

| Óxido | M (g/mol) | Óxido | M (g/mol) | Óxido | M (g/mol) |
|---|---|---|---|---|---|
| SiO₂ | 60.083 | K₂O | 94.196 | FeO | 71.844 |
| Al₂O₃ | 101.961 | Na₂O | 61.979 | Fe₂O₃ | 159.688 |
| CaO | 56.077 | MgO | 40.304 | TiO₂ | 79.866 |
| BaO | 153.326 | SrO | 103.619 | ZnO | 81.379 |
| Li₂O | 29.881 | B₂O₃ | 69.620 | ZrO₂ | 123.218 |
| Cr₂O₃ | 151.990 | P₂O₅ | 141.944 | MnO | 70.937 |

### C0.2 Pesos atómicos de cationes (para fórmula estructural, g/mol)

| Si | Al | Fe | Mg | Ca | Na | K | Ti | Mn | O | H |
|---|---|---|---|---|---|---|---|---|---|---|
| 28.085 | 26.982 | 55.845 | 24.305 | 40.078 | 22.990 | 39.098 | 47.867 | 54.938 | 15.999 | 1.008 |

### C0.3 Densidades y viscosidades de referencia (SI)

| Sustancia | ρ (kg/m³) | μ (Pa·s) | Uso |
|---|---|---|---|
| Agua a 20 °C | 998.2 | 1.002×10⁻³ | Stokes en agua |
| Silicato/cuarzo | 2650 | — | Stokes en agua |
| Caolinita | 2600 | — | Stokes en agua |
| Aire a 20 °C, 1 atm | 1.204 | 1.825×10⁻⁵ | Stokes en aire |

### C0.4 Unidades y equivalencias

- 1 t = 1000 kg; 1 kg = 2.20462 lb; 1 oz troy = 31.1035 g.
- 1 g/t = 1 ppm = 0.0001 % (leyes de Au/Ag en concentrados).
- 1 µm = 0.001 mm; φ (phi) = −log₂(d en mm).
- 1 meq/100 g = 1 cmol(+)/kg = 0.001 eq/100 g = 0.01 eq/kg.
- Porcentaje en peso: `%` significa g/100 g de material seco, salvo indicación contraria.

### C0.5 Reglas de validación generales

1. Toda entrada numérica debe ser finita, positiva y dentro de rango físico; si no, `InvalidArgumentException` en español.
2. Nunca dividir entre cero: si el denominador es 0 → error con mensaje ("La ley del concentrado debe ser distinta de la ley de la alimentación", etc.).
3. Coherencia física: `0 ≤ x ≤ 1` en proporciones; `c > f > t` en leyes de concentración; `T ≥ 0`; `m_saturado ≥ m_seco`.
4. Los porcentajes que deben sumar 100 se normalizan explícitamente y se devuelve advertencia si la suma original se desvía más de 1 punto porcentual.
5. Las cifras orientativas (clasificaciones, rangos de absorción, conos) se marcan como tales en la interfaz.

---

## C1. UMF / Seger de un esmalte

### C1.1 Definición

La **fórmula molecular unitaria (UMF)** o **fórmula Seger** expresa la composición de un esmalte como **moles relativos de óxidos normalizados a la suma de óxidos fundentes = 1**. Es la forma estándar de comparar esmaltes entre sí y contra fórmulas límite. Referencia conceptual: [Digitalfire — Unity Molecular Formula](https://digitalfire.com/4sight/education/unity_molecular_formula_101_1.html).

Convención adoptada (la de la bibliografía cerámica y la de openglaze/glazelab):

```
fundentes (flux) = K2O + Na2O + CaO + MgO + BaO + ZnO + SrO + Li2O + FeO
```

### C1.2 Fórmulas

Para cada material `j` de la receta con porcentaje en peso `p_j` (suma 100) y análisis de óxidos `w_ij` (% en peso del óxido `i` en el material, base cocida, sin LOI):

```
moles_i = Σ_j  ( p_j · w_ij / 100 ) / M_i          [mol]
F       = Σ_{i ∈ fundentes} moles_i                 [mol]
UMF_i   = moles_i / F                               [adimensional]
```

Derivados útiles:

```
Si:Al       = UMF(SiO2) / UMF(Al2O3)
R2O:RO      = (UMF(K2O)+UMF(Na2O)) / (UMF(CaO)+UMF(MgO)+UMF(BaO)+UMF(ZnO)+…)
B2O3/SiO2   = UMF(B2O3) / UMF(SiO2)
```

**Coeficiente de expansión térmica aproximado** (método aditivo de Appen, con revisión de Sankey):

```
CTE = Σ_i  ( UMF_i / Σ_k UMF_k ) · α_i        [×10⁻⁶ / K]
```

donde la suma del denominador es **sobre todos los óxidos** (no solo fundentes), porque se usan fracciones molares del vidrio.

Coeficientes `α_i` (×10⁻⁶/K), tabla publicada en `openglaze/ceramics-foundation/data/thermal-expansion.json` con fuente "Appen (1950), Sankey":

| Óxido | α | Óxido | α | Óxido | α |
|---|---|---|---|---|---|
| Na₂O | 39.5 | K₂O | 46.5 | Li₂O | 27.0 |
| CaO | 13.0 | MgO | 3.4 | SrO | 16.0 |
| BaO | 20.0 | ZnO | 5.0 | FeO | 5.5 |
| Fe₂O₃ | 11.0 | Al₂O₃ | 4.2 | SiO₂ | 3.8 |
| B₂O₃ | −2.0 | TiO₂ | −1.0 | ZrO₂ | −6.0 |
| Cr₂O₃ | 5.0 | MnO | 5.5 | P₂O₅ | 5.0 |

> ⚠️ **Advertencia**: los coeficientes de Al₂O₃ y MgO están en disputa (Appen: Al₂O₃ −3.0, MgO 6.0; Sankey: Al₂O₃ 4.2, MgO 3.4). El CTE calculado así es **orientativo** y sirve para comparar esmaltes, no para predecir absolutos. Mostrar en la interfaz "CTE estimado ±".

### C1.3 Fórmulas límite orientativas (por cono)

Rangos objetivo publicados en `umf-target-ranges.json` (fuente declarada: Stull 1912 vía Glazy, Digitalfire, Ceramic Arts Network — Dick White, 2025). **Son guía, no reglas**:

| Óxido | Cono 04 | Cono 6 | Cono 10 |
|---|---|---|---|
| Al₂O₃ | 0.15–0.40 | 0.25–0.50 | 0.20–0.60 |
| SiO₂ | 2.0–4.0 | 2.5–5.0 | 2.5–6.0 |
| K₂O | 0–0.5 | 0–0.5 | 0–0.5 |
| Na₂O | 0–0.5 | 0–0.5 | 0–0.5 |
| CaO | 0.2–0.8 | 0.2–0.8 | 0.2–0.8 |
| MgO | 0–0.4 | 0–0.4 | 0–0.4 |
| BaO | 0–0.3 | 0–0.3 | 0–0.3 |
| ZnO | 0–0.3 | 0–0.3 | 0–0.3 |
| B₂O₃ | 0–0.5 | 0–0.4 | 0–0.3 |

**Nota sobre la convención del equipo de cálculo:** normalizar a "RO+R2O3 = 1" no es la convención estándar. Si se normaliza solo a RO + R2O3 (excluyendo ZnO/BaO/SrO/Li₂O/FeO), los números cambian. Recomendación: **normalizar a la suma de todos los fundentes = 1** y documentarlo en la UI; si se desea RO+R2O3 = 1, mostrarlo como segunda normalización explícita, nunca mezclar ambas.

### C1.4 Composición de los materiales de la biblioteca (base cocida)

| Material | SiO₂ | Al₂O₃ | K₂O | Na₂O | CaO | MgO | Fe₂O₃ | B₂O₃ | Otros | M fórmula (g/mol) |
|---|---|---|---|---|---|---|---|---|---|---|
| Feldespato potásico (Or) | 64.76 | 18.32 | 16.92 | — | — | — | — | — | — | 556.66 |
| Feldespato sódico (Ab) | 68.74 | 19.44 | — | 11.82 | — | — | — | — | — | 524.45 |
| Caolín (metacaolín, Al₂O₃·2SiO₂) | 54.10 | 45.90 | — | — | — | — | — | — | — | 222.13 |
| Sílice (cuarzo) | 100 | — | — | — | — | — | — | — | — | 60.08 |
| Carbonato de calcio (cal) | — | — | — | — | 100 (como CaO) | — | — | — | — | 56.08 |
| Dolomita (calcinada) | — | — | — | — | 58.2 | 41.8 | — | — | — | 96.38 |
| Talco (calcinado) | 66.5 | — | — | — | — | 33.5 | — | — | — | 361.24 (3MgO·4SiO₂) |
| Nefelina sienita | 60.1 | 23.0 | 4.6 | 12.3 | — | — | — | — | — | (aprox.) |
| Bórax (anhídro) | — | — | — | 30.8 | — | — | — | 69.2 | — | 201.22 |
| Óxido de zinc | — | — | — | — | — | — | — | — | ZnO 100 | 81.38 |
| Óxido de litio (Li₂CO₃→Li₂O) | — | — | — | — | — | — | — | — | Li₂O 100 | 29.88 |
| Alúmina calcinada | — | 100 | — | — | — | — | — | — | — | 101.96 |
| Circón (ZrO₂·SiO₂) | 32.8 | — | — | — | — | — | — | — | ZrO₂ 67.2 | 183.30 |
| Óxido de hierro (Fe₂O₃) | — | — | — | — | — | — | 100 | — | — | 159.69 |
| Óxido de cobalto (CoO) | — | — | — | — | — | — | — | — | CoO 100 | 74.93 |

> Los porcentajes de carbonatos y materias primas con LOI se expresan **como óxido en base cocida** (lo que queda tras la calcinación), que es lo que exige el cálculo UMF. Documentar esto en la interfaz: "análisis en base cocida, sin pérdida por calcinación".

### C1.5 Caso resuelto A — UMF de un esmalte de cono alto

**Entrada**

| Material | % en peso | Composición usada (base cocida) |
|---|---|---|
| Feldespato potásico | 40 | K₂O 16.922 %, Al₂O₃ 18.317 %, SiO₂ 64.761 % |
| Carbonato de calcio (cal) | 20 | CaO 100 % |
| Sílice | 25 | SiO₂ 100 % |
| Caolín | 15 | Al₂O₃ 45.902 %, SiO₂ 54.098 % |

**Paso 1 — moles por óxido**

| Óxido | Cálculo | Moles (mol) |
|---|---|---|
| K₂O | 40×0.16922/94.196 | 0.071858 |
| Al₂O₃ | 40×0.18317/101.961 + 15×0.45902/101.961 | 0.139387 |
| CaO | 20×1.00000/56.077 | 0.356652 |
| SiO₂ | 40×0.64761/60.083 + 25×1.0/60.083 + 15×0.54098/60.083 | 0.982296 |

**Paso 2 — normalización a fundentes**

`F = 0.071858 + 0.356652 = 0.428510 mol`

| Óxido | UMF |
|---|---|
| K₂O | **0.16769** |
| CaO | **0.83231** |
| Al₂O₃ | **0.32528** |
| SiO₂ | **2.29235** |

**Paso 3 — derivados**

- `Si:Al = 2.29235 / 0.32528 =` **7.0473**
- `R2O:RO = 0.16769 / 0.83231 =` **0.20148**
- Suma de todos los UMF = 3.61763
- `CTE = (0.16769×46.5 + 0.83231×13.0 + 0.32528×4.2 + 2.29235×3.8) / 3.61763 =` **7.93 ×10⁻⁶/K**

**Valores esperados (tolerancia 1e-4 absoluta)**

| Variable | Esperado |
|---|---|
| K₂O | 0.16769 |
| CaO | 0.83231 |
| Al₂O₃ | 0.32528 |
| SiO₂ | 2.29235 |
| Si:Al | 7.0473 |
| CTE (×10⁻⁶/K) | 7.93 (tolerancia 0.05) |
| Avisos | Ninguno (CaO y SiO₂ dentro de rango de cono 10; Al₂O₃ 0.325 dentro; Si:Al alto → esmalte "duro"/mate si se cuece bajo) |

**Verificación cruzada ejecutada:** el motor `engine.js` de `iLanis-agent/glazelab` (ejecutado con Node 26.8.1) devolvió exactamente `K2O 0.1676901, Al2O3 0.3252805, SiO2 2.2923416, CaO 0.8323099, siAl 7.05` para la misma receta → el caso es reproducible.

### C1.6 Caso resuelto B — receta inversa desde una UMF objetivo (para "¿qué mezcla me da esta fórmula?")

Problema: dados `p` materiales con matriz de moles por 100 g `M` (óxidos × materiales) y un objetivo `τ` (vector de moles), hallar la receta `r` (hectogramos o proporciones) que minimiza el error:

```
min_r  (M·r − τ)ᵀ (M·r − τ)   sujeto a  Σ r_j = 1,  r_j ≥ 0
```

Solución sin restricciones: `r = (MᵀM)⁻¹ Mᵀ τ` (ecuaciones normales). Con restricciones, parametrizar `r = r₀ + N·z` donde `r₀ = (1/p, …, 1/p)` y `N` es una base del espacio nulo de `1ᵀ` (matriz p×(p−1)), resolver mínimos cuadrados para `z` y verificar `r ≥ 0`; si alguna componente es negativa, fijarla en 0 y resolver de nuevo (o búsqueda en rejilla / descenso coordinado, como pide el equipo de cálculo).

> Referencia matemática: `dlizotte-uwo/glaze-formulator`, `OptimizationDetails.Rmd` (deriva exactamente `(Mr−τ)ᵀ(Mr−τ)`). **No copiar el código** (GPL-3.0); reimplementar el álgebra.

### C1.7 Limitaciones

- El análisis debe estar en **base cocida** (sin LOI) y con la misma base de óxidos; si un material trae FeO y Fe₂O₃, convertirlos a Fe₂O₃ equivalente.
- La UMF no predice color, textura, craquelado ni madurez reales; las fórmulas límite son orientativas.
- La clasificación por cono es heurística: no sustituye pruebas de cono.
- El CTE aditivo ignora estructura del vidrio (B₂O₃ 3- vs 4-coordinado, cristalización de TiO₂/ZrO₂).

---

## C2. Mezcla de arcillas

### C2.1 Definición

Dadas `n` arcillas (o minerales) con propiedades medidas y proporciones `x_k` (fracción en peso, seca), estimar las propiedades de la mezcla y, en el problema inverso, encontrar las proporciones que cumplen objetivos de composición o de comportamiento.

### C2.2 Fórmulas

**Regla de mezclas (propiedad conservativa):**

```
P_mezcla = Σ_{k=1..n} x_k · P_k          con  Σ x_k = 1,  0 ≤ x_k ≤ 1
```

Válida para propiedades que se promedian por masa: composición química (% SiO₂, Al₂O₃, Fe₂O₃, LOI), contracción, absorción, densidad, precio por tonelada.

**Mezcla de 2 materiales, objetivo de una propiedad:**

```
x_A = (P_objetivo − P_B) / (P_A − P_B)      x_B = 1 − x_A
```

**Mezcla de n materiales, varios objetivos (mínimos cuadrados con restricciones):**

```
min_x  Σ_i ( Σ_k C_ik x_k − d_i )²
s.a.   Σ_k x_k = 1,  0 ≤ x_k ≤ 1
```

donde `C_ik` es la propiedad `i` del material `k` y `d_i` el objetivo de la propiedad `i`. Se resuelve como en C1.6 (base nula + mínimos cuadrados + no negatividad).

**Costo por punto de propiedad / por unidad de óxido contenido:**

```
costo_por_punto   = precio / (% de la propiedad)
costo_por_t_óxido = precio / (% óxido / 100)
```

### C2.3 Caso resuelto C — mezcla 60/40

| Propiedad | Arcilla A | Arcilla B | Mezcla 60 % A + 40 % B |
|---|---|---|---|
| Contracción (%) | 12.0 | 6.0 | **9.6** |
| Absorción (%) | 8.0 | 14.0 | **10.4** |
| Al₂O₃ (%) | 22.0 | 38.0 | **28.4** |
| Fe₂O₃ (%) | 1.2 | 3.5 | **2.12** |
| Precio (MXN/t) | 2 800 | 4 200 | **3 360** |

Verificación: `0.6·12 + 0.4·6 = 9.6`; `0.6·8 + 0.4·14 = 10.4`; etc.

**Problema inverso (1 propiedad):** objetivo Al₂O₃ = 30 % → `x_A = (30−38)/(22−38) = 0.5` → **50/50**.

**Costo por punto de Al₂O₃:** A = `2800/22 = 127.27 MXN/punto`; B = `4200/38 = 110.53 MXN/punto` → B es más eficiente por unidad de alúmina. Costo por tonelada de Al₂O₃ puro: A = `2800/0.22 = 12 727.3 MXN`; B = `4200/0.38 = 11 052.6 MXN`.

### C2.4 Caso resuelto D — 3 arcillas con mínimos cuadrados restringidos

| Propiedad | A | B | C | Objetivo |
|---|---|---|---|---|
| SiO₂ (%) | 62 | 45 | 55 | 55 |
| Al₂O₃ (%) | 22 | 38 | 30 | 30 |
| Fe₂O₃ (%) | 1.2 | 3.5 | 0.8 | 1.8 |

Solución con `Σx = 1` y `x ≥ 0` (parametrización por base nula):

```
x = (0.2491, 0.2091, 0.5418)
```

Propiedades logradas: `SiO₂ = 54.653 %`, `Al₂O₃ = 29.680 %`, `Fe₂O₃ = 1.464 %`; error cuadrático `‖C·x − d‖ = 0.5795` (en puntos porcentuales). Todas las fracciones positivas → solución factible.

**Valores esperados (tolerancia 5e-3):** `x = [0.2491, 0.2091, 0.5418]`, `SiO₂ 54.653`, `Al₂O₃ 29.680`, `Fe₂O₃ 1.464`.

### C2.5 Limitaciones

- La regla de mezclas **no captura interacciones**: plasticidad (índice de Atterberg), color tras cocción, vitrificación, contracción anisotrópica y comportamiento reológico no son lineales.
- La contracción y la absorción medidas en probetas de cada arcilla pura no se promedian exactamente al mezclar; usar el resultado como punto de partida y validar con probetas de la mezcla.
- Las propiedades deben estar en la **misma base** (seca, cocida a la misma temperatura, misma granulometría).
- Para mezclas de 3–4 arcillas, el sistema suele ser no cuadrado: reportar el residuo y las desviaciones por propiedad.

---

## C3. Contracción y absorción cerámica

### C3.1 Definición

La contracción es la reducción dimensional del material cerámico al secarse (contracción de secado) y al cocerse (contracción de cocción). La absorción de agua mide la porosidad abierta del producto cocido.

### C3.2 Fórmulas

**Contracción lineal de una etapa:**

```
S = (L_0 − L_1) / L_0 × 100        [%]
```

- `L_0` = longitud inicial (estado de referencia: verde para secado, seco para cocción).
- `L_1` = longitud final (seco o cocido).

**Contracción total (verde → cocido), componiendo etapas:**

```
S_total = [ 1 − (1 − S_secado/100)·(1 − S_cocción/100) ] × 100        [%]
```

**Corrección de molde / dimensión de partida para lograr una medida final:**

```
L_0 = L_final × 100 / (100 − S_total)
factor = 100 / (100 − S_total)          (p. ej. 1.0989 para S = 9 %)
```

**Absorción de agua (ASTM C373, ebullición o vacío):**

```
WA = (m_sat − m_seco) / m_seco × 100        [%]
```

**Densidad aparente, porosidad aparente y gravedad específica aparente (ASTM C373, pesada hidrostática):**

```
ρ_ap   = m_seco / (m_sat − m_susp) × ρ_agua          [g/cm³]
P_ap   = (m_sat − m_seco) / (m_sat − m_susp) × 100    [%]
G_ap   = m_seco / (m_seco − m_susp)                    [adimensional]
```

- `m_seco` = masa seca (g); `m_sat` = masa saturada (g); `m_susp` = masa suspendida en agua (g); `ρ_agua = 1.0 g/cm³`.

### C3.3 Caso resuelto E — contracción y corrección de molde

- Probeta verde: `L_0 = 100.0 mm`; seca: `L_1 = 96.0 mm`; cocida: `L_2 = 91.0 mm`.
- `S_secado = (100−96)/100 = 4.0 %`
- `S_cocción = (96−91)/96 = 5.2083 %`
- `S_total = [1 − 0.96×0.947917] × 100 = 9.0 %` (equivale a `(100−91)/100`)
- Para una pieza final de 100.0 mm: `L_0 = 100 × 100/91 = 109.89 mm`

**Valores esperados (tolerancia 1e-3):** `S_secado 4.0`, `S_cocción 5.2083`, `S_total 9.0`, `L_0 109.89 mm`.

### C3.4 Caso resuelto F — absorción, densidad y porosidad

- `m_seco = 200.0 g`, `m_sat = 230.0 g`, `m_susp = 130.0 g`.

| Magnitud | Cálculo | Resultado |
|---|---|---|
| Absorción de agua | (230−200)/200 ×100 | **15.00 %** |
| Densidad aparente | 200/(230−130) | **2.000 g/cm³** |
| Porosidad aparente | 30/100 ×100 | **30.00 %** |
| Gravedad específica aparente | 200/(200−130) | **2.857** |

**Valores esperados (tolerancia 1e-3):** `WA 15.0`, `ρ_ap 2.0`, `P_ap 30.0`, `G_ap 2.857`.

### C3.5 Clasificación orientativa (solo como etiqueta)

| Producto | Absorción de agua (orientativa) |
|---|---|
| Porcelana | 0–0.5 % |
| Gres / stoneware | 0.5–3 % |
| Loza / earthenware | 6–15 % |

> Estas bandas varían por norma y producto; mostrarlas como "orientativo" y no como especificación.

### C3.6 Limitaciones

- La contracción depende de la dirección (extrusión, prensado), del contenido de agua y del ciclo de cocción.
- ASTM C326 se refiere a arcillas de loza blanca; ASTM C373 a productos cocidos. Para arcillas rojas o refractarias, usar el método interno del laboratorio y documentarlo.
- La absorción con ebullición 5 h vs vacío da resultados distintos; registrar el método.

---

## C4. Balance metalúrgico no ferroso

### C4.1 Definición

El balance metalúrgico relaciona la masa y la ley de metal de la alimentación (F), el concentrado (C) y la cola (T) de un circuito de concentración. Permite calcular la **recuperación** (R), la **razón de concentración** (K) y el **valor pagable** del concentrado.

Referencia principal de fórmulas: [911Metallurgist — Metallurgical Accounting Formulas](https://www.911metallurgist.com/blog/metallurgical-accounting/) (fórmulas de 2 y 3 productos, con ejemplo de cobre).

### C4.2 Variables y unidades

| Símbolo | Significado | Unidad |
|---|---|---|
| `F`, `C`, `T` | masa de alimentación, concentrado y cola (base seca) | t |
| `f`, `c`, `t` | ley del metal en alimentación, concentrado y cola | % (o g/t) |
| `K` | razón de concentración = F/C | adimensional |
| `R` | recuperación del metal en el concentrado | % |
| `Me` | metal fino contenido | t o kg |
| `P` | precio del metal | USD/t (o USD/oz) |
| `pagable` | proporción del metal pagada | fracción (0–1) |
| `TC` | cargo de tratamiento (treatment charge) | USD/t de concentrado |
| `RC` | cargo de refinación (refining charge) | USD/lb o USD/oz |

### C4.3 Fórmulas — 2 productos (1 concentrado)

```
K = F/C = (c − t)/(f − t)
C = F/K = F·(f − t)/(c − t)
T = F − C
R = 100·c·(f − t) / [ f·(c − t) ]   =   100·C·c / (F·f)
Metal fino en concentrado:  Me_C = C · c/100
Metal fino en alimentación: Me_F = F · f/100
```

**Cierre de balance (QA):** `C + T = F` y `C·c + T·t = F·f` (tolerancia 1e-6 relativo).

### C4.4 Fórmulas — 3 productos (2 concentrados + cola)

Incógnitas: `C₁`, `C₂`, `T` (masas). Datos: `F`, leyes de 2 metales en los 4 flujos. Sistema lineal:

```
C₁ + C₂ + T = F
(c₁)C₁ + (c₂)C₂ + (c_t)T = f_Cu · F        ← metal 1 (leyes en %)
(z₁)C₁ + (z₂)C₂ + (z_t)T = f_Zn · F        ← metal 2 (leyes en %)
```

Resolver `A·x = b` con `A = [[1,1,1],[c₁,c₂,c_t],[z₁,z₂,z_t]]`, `x = [C₁,C₂,T]ᵀ`, `b = [F, f_Cu·F, f_Zn·F]ᵀ`. Las leyes pueden expresarse en % en ambos lados del sistema (es homogéneo); si se convierten a fracción, hacerlo en **todos** los términos para no mezclar unidades. Si `det(A) ≈ 0` → datos inconsistentes (error en español, no dividir). La forma cerrada de 911Metallurgist es algebraicamente equivalente; usar la matriz por estabilidad numérica.

Recuperaciones:

```
R_Cu = 100·C₁·c₁ / (F·f_Cu)        R_Zn = 100·C₂·z₂ / (F·f_Zn)
R_Cu global = 100·(C₁·c₁ + C₂·c₂) / (F·f_Cu)
K_Cu = F/C₁                        K_Zn = F/C₂
```

### C4.5 Caso resuelto G — 2 productos (ejemplo publicado por 911Metallurgist)

Datos: `F = 15 000 t/d`, `f = 1.15 % Cu`, `c = 32.7 % Cu`, `t = 0.18 % Cu`.

| Magnitud | Cálculo | Resultado |
|---|---|---|
| Razón de concentración | (32.7−0.18)/(1.15−0.18) = 32.52/0.97 | **K = 33.5258** |
| Masa de concentrado | 15 000/33.5258 | **C = 447.42 t/d** |
| Recuperación | 100×32.7×0.97/(1.15×32.52) | **R = 84.8147 %** |
| Masa de cola | 15 000 − 447.42 | **T = 14 552.58 t/d** |
| Metal fino en concentrado | 447.42×0.327 | **146.31 t Cu/d** |
| Metal fino en alimentación | 15 000×0.0115 | **172.50 t Cu/d** |
| Verificación R | 100×146.31/172.50 | 84.8147 % ✔ |

**Valores esperados (tolerancia 1e-3):** `K 33.5258`, `C 447.42`, `R 84.8147`, `T 14552.58`.

**Valor pagable del concentrado (ejemplo ilustrativo, no del artículo):** con `P_Cu = 9 000 USD/t`, `pagable = 96.5 %`, `TC = 90 USD/t concentrado`, `RC = 0.09 USD/lb`:

```
Metal fino            = 146.31 t Cu
Valor bruto           = 146.31 × 9 000            = 1 316 757.06 USD
Valor pagable         = 1 316 757.06 × 0.965      = 1 270 670.56 USD
TC                    = 447.42 × 90               =    40 267.80 USD
RC                    = 146.31 × 2 204.6226 × 0.09 =   29 029.52 USD
Valor neto            = 1 270 670.56 − 40 267.80 − 29 029.52 = 1 201 373.24 USD
Por tonelada de conc. = 1 201 373.24 / 447.42     =     2 685.11 USD/t
Por tonelada de feed  = 1 201 373.24 / 15 000     =        80.09 USD/t
```

**Valores esperados (tolerancia 0.01 USD):** bruto `1316757.06`, pagable `1270670.56`, TC `40267.80`, RC `29029.52`, neto `1201373.24`, por t conc `2685.11`, por t feed `80.09`.

### C4.6 Caso resuelto H — 3 productos (ejemplo propio consistente)

Construido hacia adelante para que cierre exacto: `F = 1 000 t`; `C₁ = 20 t`, `C₂ = 15 t`, `T = 965 t`; leyes (%): Cu = 28.0 / 0.9 / 0.10; Zn = 1.2 / 52.0 / 0.15 (C₁, C₂, T).

| Variable | Cálculo | Resultado |
|---|---|---|
| Ley Cu alimentación | (20×28 + 15×0.9 + 965×0.10)/1000 | **f_Cu = 0.67 %** |
| Ley Zn alimentación | (20×1.2 + 15×52 + 965×0.15)/1000 | **f_Zn = 0.94875 %** |
| R_Cu en C₁ | 100×20×28/(1000×0.67) | **83.5821 %** |
| R_Zn en C₂ | 100×15×52/(1000×0.94875) | **82.2134 %** |
| R_Cu global | 100×(20×28 + 15×0.9)/(1000×0.67) | **85.5970 %** |
| R_Zn global | 100×(20×1.2 + 15×52)/(1000×0.94875) | **84.7431 %** |
| K_Cu | 1000/20 | **50.0** |
| K_Zn | 1000/15 | **66.667** |
| Cierre de Cu | 20×28 + 15×0.9 + 965×0.10 | 670.0 = 1000×0.67 ✔ |

**Valores esperados (tolerancia 1e-3):** `f_Cu 0.67`, `f_Zn 0.94875`, `C₁ 20`, `C₂ 15`, `T 965`, `R_Cu 83.5821`, `R_Zn 82.2134`, `K_Cu 50`, `K_Zn 66.667`.

### C4.7 Validaciones y errores típicos

- Debe cumplirse `c > f > t` y `C < F`. Si no, error de entrada (no "corregir" datos).
- Si `f = t`, `K` es indeterminado (todo el metal está en la cola o no hay concentración) → error.
- Leyes en g/t para Au/Ag: convertir a % (`1 g/t = 0.0001 %`) o mantener g/t consistentemente; el código debe exigir la unidad y no mezclar.
- El balance de 3 productos puede dar masas negativas si las leyes son inconsistentes; reportar advertencia y no mostrar resultados como válidos.
- La recuperación **no** es la ley del concentrado: R depende de la masa y de la ley de alimentación.

---

## C5. Dosificación de bentonita para arena verde

### C5.1 Definición

La arena verde de fundición es una mezcla de arena base (sílice, cromita o circón), bentonita (aglomerante), agua, finos inertes y, opcionalmente, antracita. La calculadora dosifica bentonita y agua por lote y por tonelada, y estima la reposición por pérdida al quemado (burn-out).

**Composición típica de arena verde** (rangos publicados): arena base 75–85 %; bentonita 5–11 %; agua 2–4 %; lodo inerte 3–5 %; antracita 0–1 %. Fuente: [Wikipedia — Sand casting](https://en.wikipedia.org/wiki/Sand_casting) y [Wikipedia — Molding sand](https://en.wikipedia.org/wiki/Molding_sand).

> No existe una implementación open source verificada de esta dosificación (ver `repositorios-calculadoras.md` §3.3). Las fórmulas de esta sección son de balance de masa elemental, no de un estándar único; documentar la fuente de los objetivos que use el broker.

### C5.2 Fórmulas

**Bentonita a agregar por lote (para alcanzar un objetivo de bentonita activa):**

```
m_bentonita = m_arena · (B_objetivo − B_actual) / 100        [kg]
```

**Reposición considerando pérdida por quemado:**

```
m_reposición = m_bentonita / (1 − p_perdida)                 [kg]
B_final = B_actual + 100 · m_reposición / m_arena             [%]
```

**Agua a agregar:**

```
m_agua = m_arena · (H_objetivo − H_actual) / 100             [kg]
```

**Equivalente seco de bentonita húmeda comercial:**

```
m_bentonita_seca = m_bentonita_húmeda · (1 − humedad/100)    [kg]
```

**Dosificación por tonelada de arena:**

```
kg/t = % · 10
```

### C5.3 Caso resuelto I — lote de 1 000 kg

Datos: `m_arena = 1 000 kg`; bentonita actual `B_actual = 5.2 %`; objetivo `B_objetivo = 8.0 %`; pérdida por quemado `p = 10 %`; agua actual `2.6 %`, objetivo `3.2 %`; bentonita comercial con `12 %` de humedad.

| Magnitud | Cálculo | Resultado |
|---|---|---|
| Bentonita a agregar (sin pérdida) | 1000×(8.0−5.2)/100 | **28.0 kg** |
| Reposición con 10 % de pérdida | 28/(1−0.10) | **31.11 kg** |
| % bentonita final | 5.2 + 31.11/10 | **8.311 %** |
| Equivalente seco de 31.11 kg al 12 % humedad | 31.11×0.88 | **27.378 kg** |
| Agua a agregar | 1000×(3.2−2.6)/100 | **6.0 kg** |
| Dosificación por tonelada (8 %) | 8×10 | **80 kg/t** |

**Valores esperados (tolerancia 1e-3):** `m_bentonita 28.0`, `m_reposición 31.11`, `B_final 8.311`, `seca 27.378`, `m_agua 6.0`, `80 kg/t`.

### C5.4 Limitaciones y controles de laboratorio

- Los objetivos de bentonita y agua dependen de la arena base, del metal, del tamaño de molde y del sistema de moldeo; deben fijarse por planta.
- La bentonita sódica vs cálcica y su activación (carbonato de sodio) cambian la demanda; el % óptimo se determina con pruebas de resistencia en verde, permeabilidad y "compactabilidad".
- El **ensayo de azul de metileno (MB)** estima la bentonita activa; **no se incluye aquí una ecuación de conversión** porque no se pudo verificar una fuente normativa accesible en este entorno. Si el broker la usa, documentar el método y el factor del laboratorio.
- La pérdida por quemado varía con la temperatura del metal y el tiempo de permanencia; 10 % es un supuesto del ejemplo, no un valor universal.

---

## C6. Granulometría y ley de Stokes

### C6.1 Definición y unidades

Análisis granulométrico por mallas (tamices) y sedimentación. Se reporta el porcentaje retenido o pasante por malla, los diámetros característicos (D10, D50, D90), la uniformidad y, para partículas finas, la velocidad de sedimentación por la ley de Stokes.

### C6.2 Fórmulas

**Escala phi y conversión:**

```
φ = −log₂(d_mm)            d_mm = 2^(−φ)            d_µm = 1000·d_mm
```

**Porcentaje retenido y pasante acumulado:**

```
ret_i = m_i / Σ m                    × 100        [%]
R_i   = Σ_{k≤i} ret_k                              [% retenido acumulado]
P_i   = 100 − R_i                                  [% pasante]
```

**Interpolación logarítmica del diámetro para un porcentaje pasante objetivo (D10, D50, D90):**

```
log d_q = log d_1 + (log d_2 − log d_1)·(q − P_1)/(P_2 − P_1)
```

donde `P_1 < q < P_2` son los porcentajes pasantes que rodean al objetivo `q`. Se puede usar logaritmo natural o decimal, siempre el mismo en numerador y denominador.

**Coeficiente de uniformidad y de curvatura:**

```
Cu = D60 / D10          Cc = D30² / (D10 · D60)
```

**Ley de Stokes (sedimentación libre, partícula esférica):**

```
v = (ρ_p − ρ_f)·g·d² / (18·μ)          [m/s]
```

- `ρ_p` densidad de la partícula (kg/m³); `ρ_f` densidad del fluido (kg/m³); `g = 9.81 m/s²`; `d` diámetro (m); `μ` viscosidad dinámica (Pa·s).
- **Validez:** `Re = ρ_f·v·d/μ < 1` (en la práctica se recomienda `Re < 0.1`); si no, la ley sobrestima `v` y hay que usar correlaciones con coeficiente de arrastre.
- **Tiempo de sedimentación** para una altura `h`: `t = h / v` (con `v` constante; en la práctica las partículas aceleran en los primeros instantes, despreciable para limos/arcillas).
- **Corrección por no esfericidad** (opcional): usar `d_eq` (diámetro equivalente de esfera) o multiplicar por un factor de forma; documentarlo.

**Tabla de mallas ASTM de referencia:**

| Malla (mesh) | Abertura (mm) | Abertura (µm) |
|---|---|---|
| 4 | 4.750 | 4 750 |
| 10 | 2.000 | 2 000 |
| 20 | 0.850 | 850 |
| 40 | 0.425 | 425 |
| 60 | 0.250 | 250 |
| 100 | 0.150 | 150 |
| 200 | 0.075 | 75 |
| 325 | 0.045 | 45 |
| 400 | 0.038 | 38 |

### C6.3 Caso resuelto J — curva granulométrica y D50

Datos de masa retenida (ejemplo adaptado del repo MIT `particle-size-distribution-curve`):

| Abertura (mm) | Masa retenida (g) | % pasante acumulado |
|---|---|---|
| 4.750 | 0.0 | 100.00 |
| 2.000 | 17.6 | 96.09 |
| 0.850 | 56.3 | 83.60 |
| 0.425 | 108.2 | 59.60 |
| 0.250 | 91.9 | 39.21 |
| 0.150 | 94.1 | 18.33 |
| 0.075 | 57.6 | 5.55 |
| 0 (fondo) | 25.0 | 0.00 |

Masa total = **450.7 g**. El D50 cae entre 0.425 mm (59.60 %) y 0.250 mm (39.21 %):

```
log d50 = log(0.425) + [log(0.250) − log(0.425)]·(50 − 59.60)/(39.21 − 59.60)
d50 = 0.3311 mm = 331.1 µm          φ = 1.595
```

**Valores esperados (tolerancia 1e-3):** `pasante(4.75) 100.00`, `pasante(2.0) 96.09`, `pasante(0.85) 83.60`, `pasante(0.425) 59.60`, `pasante(0.25) 39.21`, `pasante(0.15) 18.33`, `pasante(0.075) 5.55`, `d50 0.3311 mm`, `φ 1.595`.

### C6.4 Caso resuelto K — velocidad de Stokes en agua

`ρ_p = 2 650 kg/m³` (sílice), `ρ_f = 1 000 kg/m³`, `μ = 1.0×10⁻³ Pa·s`, `g = 9.81 m/s²`.

| d (µm) | v (m/s) | v (mm/s) | v (m/h) | Re |
|---|---|---|---|---|
| 75 | 5.058×10⁻³ | 5.058 | 18.210 | 0.3794 |
| 50 | 2.248×10⁻³ | 2.248 | 8.093 | 0.1124 |
| 20 | 3.597×10⁻⁴ | 0.360 | 1.295 | 0.0072 |
| 5 | 2.248×10⁻⁵ | 0.0225 | 0.081 | 0.0001 |
| 2 | 3.597×10⁻⁶ | 0.0036 | 0.013 | 0.00002 |

Tiempo para sedimentar 30 cm con `d = 50 µm`: `t = 0.30 / 2.248×10⁻³ =` **133.44 s**; para 50 cm: **222.41 s**.

**Valores esperados (tolerancia 1e-4 relativo):** `v(75 µm) 5.0583 mm/s`, `v(50 µm) 2.2481 mm/s`, `v(20 µm) 0.3597 mm/s`, `Re(50 µm) 0.1124`, `t(50 µm, 30 cm) 133.44 s`.

### C6.5 Limitaciones

- Stokes supone partículas esféricas, diluidas, sin interacción y régimen laminar (`Re < 1`); para arenas finas y limos es aceptable; para arenas gruesas sobrestima.
- Las arcillas floculan y sedimentan en masa, no individualmente; para arcillas usar hidrómetro (ASTM D7928) o sedígrafo y no Stokes simple.
- La densidad de partícula debe medirse (picnómetro), no asumirse.
- La interpolación logarítmica es una aproximación; para distribuciones muy asimétricas, ajustar lognormal (ver `PSD-Analyser`, GPL).

---

## C7. Normalización de precio, humedad y costo landed

### C7.1 Definición

Convierte un precio cotizado en toneladas húmedas (WMT) a base seca, ajusta por humedad respecto a una base contractual, y calcula el costo puesto en planta (landed) en USD/t y MXN/t, más el costo por unidad de óxido contenido.

### C7.2 Fórmulas

**Tonelaje seco y precio por tonelada seca:**

```
DMT   = WMT · (1 − m/100)                 [t secas]
P_seco = P_húmedo / (1 − m/100)           [USD o MXN / t seca]
```

**Ajuste por humedad respecto a una base contractual `m_base` (equivalente de tonelaje pagable):**

```
WMT_equivalente = WMT · (100 − m_actual) / (100 − m_base)
```

- Si `m_actual > m_base`, el cargamento "pesa menos" en equivalente seco y el precio por tonelada pagable sube.
- Forma equivalente: `P_ajustado = P_base · (100 − m_base)/(100 − m_actual)`.

**Merma / pérdida de manipulación:**

```
m_util = WMT · (1 − m/100) · (1 − merma/100)
```

**Costo landed (por tonelada):**

```
FOB    = precio en origen
Flete  = costo de flete marítimo/terrestre
Seguro = tasa_seguro · (FOB + Flete)
CIF    = FOB + Flete + Seguro
Arancel = tasa_arancel · CIF
Pre_IVA = CIF + Arancel + gastos_puerto
IVA    = tasa_IVA · Pre_IVA
Landed = Pre_IVA + IVA                    (costo desembolsado)
Costo neto (IVA acreditable) = Pre_IVA
```

**Margen del broker y precio de venta:**

```
Precio_venta = Costo_neto / (1 − margen)          (margen sobre venta)
Precio_venta = Costo_neto · (1 + markup)          (markup sobre costo)
```

**Costo por unidad de óxido contenido:**

```
Costo_por_t_óxido = Precio / (% óxido / 100)
```

**Conversión de divisas:**

```
MXN = USD · tipo_de_cambio          (Banxico, DOF/FIX)
```

### C7.3 Caso resuelto L — humedad y precio

Datos: `WMT = 10 000 t`; humedad actual `m = 10 %`; base contractual `m_base = 8 %`; precio cotizado `45 USD/WMT`.

| Magnitud | Cálculo | Resultado |
|---|---|---|
| Toneladas secas | 10 000×0.90 | **9 000 t** |
| WMT equivalente a base 8 % | 10 000×90/92 | **9 782.61 t** |
| Precio por tonelada seca | 45/0.90 | **50.00 USD/t seca** |
| Sobrecosto de humedad 10 % vs 8 % | (1/0.90)/(1/0.92) − 1 | **+2.222 %** |

**Valores esperados (tolerancia 1e-3):** `DMT 9000`, `WMT_eq 9782.61`, `P_seco 50.00`, `sobrecosto 2.222 %`.

### C7.4 Caso resuelto M — costo landed

Datos: `FOB = 180 USD/t`; `flete = 35 USD/t`; `seguro = 0.5 %` de (FOB+flete); `arancel = 5 %` de CIF; `IVA = 16 %`; `tipo de cambio = 17.50 MXN/USD`; `Al₂O₃ = 38 %`.

| Concepto | Cálculo | Resultado |
|---|---|---|
| Seguro | 0.005×(180+35) | 1.075 USD/t |
| CIF | 180+35+1.075 | **216.075 USD/t** |
| Arancel | 0.05×216.075 | 10.80375 USD/t |
| Pre-IVA | 216.075+10.80375 | **226.87875 USD/t** |
| IVA | 0.16×226.87875 | 36.3006 USD/t |
| Landed (con IVA) | 226.87875+36.3006 | **263.17935 USD/t** |
| Landed sin IVA (acreditable) | — | **226.87875 USD/t** |
| Landed en MXN (con IVA) | 263.17935×17.50 | **4 605.64 MXN/t** |
| Costo por t de Al₂O₃ | 4 605.64/0.38 | **12 120.10 MXN/t Al₂O₃** |

**Valores esperados (tolerancia 0.01):** `CIF 216.075`, `arancel 10.80375`, `IVA 36.3006`, `landed 263.17935`, `MXN 4605.64`, `MXN/t Al₂O₃ 12120.10`.

### C7.5 Limitaciones

- El IVA es acreditable para empresas formales: mostrar **costo neto sin IVA** y **desembolso con IVA** por separado.
- Arancel depende de la fracción arancelaria (TIGIE) y de tratados (T-MEC); no asumir 5 %.
- El flete marítimo varía por ruta, volumen y temporada; documentar supuestos.
- La humedad debe medirse por norma (ASTM D2216 para suelos; ISO 3087 para minerales de hierro) y con muestreo representativo.
- El pagable y las penalizaciones por humedad/impurezas se negocian por contrato; la calculadora debe permitir editar todos los supuestos.

---

## C8. Fórmula estructural de arcillas y filosilicatos

### C8.1 Definición

A partir del análisis químico de óxidos (% en peso), se calcula la **fórmula estructural** (cationes por celda unidad) de un filosilicato, se reparten las posiciones tetraédricas y octaédricas, se estima la **carga de capa** y se clasifica el mineral (caolinita, illita, montmorillonita, vermiculita, etc.).

### C8.2 Fórmulas

**Paso 1 — moles de cationes por 100 g de muestra:**

```
n_catión = (% óxido / 100) / M_óxido × (nº de cationes en la fórmula del óxido)
```

Ejemplos: `Al₂O₃ → 2 Al`; `Fe₂O₃ → 2 Fe³⁺`; `Na₂O → 2 Na`; `K₂O → 2 K`; `CaO → 1 Ca`.

**Paso 2 — normalización a una base de aniones.** Para filosilicatos 2:1 (mica, smectita, vermiculita) la base habitual es la **media celda `O₁₀(OH)₂`** (12 oxígenos equivalentes); para caolinita (1:1) la base es `Si₂Al₂O₅(OH)₄` (equivalente a `O₁₀(OH)₈` si se cuenta el agua estructural aparte). El factor de escala es:

```
f = base_aniones / Σ oxígenos calculados de los cationes
```

En la práctica se normaliza **Si = 4** para 2:1 o **Si = 2** para 1:1 (método equivalente y más simple), y se verifica que la suma de oxígenos cierre.

**Paso 3 — reparto de posiciones:**

```
Tetraédrica:  T = Si + Al_tet                        (máximo 4 para 2:1; 2 para 1:1)
Octaédrica:   O = Al_oct + Fe³⁺ + Mg + Ti + Mn + …   (2 en dioctaédricos; 3 en trioctaédricos)
Intercapa:    I = Na + K + 2·Ca + 2·Mg_cambiable     (carga)
```

**Paso 4 — carga de capa por media celda:**

```
carga = (Al_tet − 4) + (Al_oct + Fe³⁺ + … − 2)   [eq por media celda]  (dioctaédrico)
```

o, de forma más robusta, a partir de la suma de cationes intercapa (que compensan la carga):

```
carga = Na + K + 2·Ca + 2·Mg   [eq por media celda, con Ca y Mg divalentes]
```

**Paso 5 — relación con la CEC:**

```
CEC (meq/100 g) = carga · 100 000 / M_media_celda
carga = CEC · M_media_celda / 100 000
```

con `M_media_celda ≈ 360 g/mol` para smectitas (varía con la composición).

**Clasificación orientativa por carga de capa (por `O₁₀(OH)₂`):**

| Mineral | Carga de capa | Notas |
|---|---|---|
| Caolinita (1:1) | ≈ 0 | Sin intercapa; base `Si₂Al₂O₅(OH)₄` |
| Pirofilita/talco | ≈ 0 | 2:1 sin carga |
| Montmorillonita (smectita) | 0.2–0.6 | Intercapa con agua; hinchamiento |
| Vermiculita | 0.6–0.9 | Mayor carga, menos hinchamiento |
| Illita/mica | 0.6–1.0 | K fijo en intercapa; no hincha |
| Clorita | variable | Capa de hidróxido de Mg/Al en la intercapa |

### C8.3 Caso resuelto N — de análisis químico a fórmula estructural

Análisis (base seca, % en peso): `SiO₂ 60.0; Al₂O₃ 18.0; Fe₂O₃ 3.0; MgO 2.5; CaO 1.5; Na₂O 2.0; K₂O 0.5; TiO₂ 0.3; LOI 12.2` (suma 100.0).

**Moles de cationes por 100 g:**

| Catión | Cálculo | mol/100 g |
|---|---|---|
| Si | 60.0/60.083 | 0.99862 |
| Al | 2×18.0/101.961 | 0.35308 |
| Fe³⁺ | 2×3.0/159.688 | 0.03757 |
| Mg | 2.5/40.304 | 0.06203 |
| Ca | 1.5/56.077 | 0.02675 |
| Na | 2×2.0/61.979 | 0.06454 |
| K | 2×0.5/94.196 | 0.01062 |
| Ti | 0.3/79.866 | 0.00376 |

**Normalización a Si = 4** (factor `f = 4/0.99862 = 4.0055`):

| Catión | Normalizado |
|---|---|
| Si | **4.0000** |
| Al | **1.4143** |
| Fe³⁺ | **0.1505** |
| Mg | **0.2485** |
| Ca | **0.1071** |
| Na | **0.2585** |
| K | **0.0425** |
| Ti | **0.0150** |

**Reparto y carga:**

- Tetraédrica: `Si = 4.000` → `Al_tet = 0` (todo el Al es octaédrico).
- Octaédrica: `Al_oct 1.414 + Fe³⁺ 0.150 + Mg 0.249 + Ti 0.015 = 1.828` → cercano a 2 → **dioctaédrico**.
- Intercapa: `Na 0.2585 + K 0.0425 + 2·Ca 0.2142 = 0.5152 eq/media celda`.
- Carga de capa ≈ **0.52** → cae en el límite smectita/vermiculita; si se interpreta como smectita, la CEC implícita sería `0.5152×100 000/360 =` **143 meq/100 g**, valor **alto** para una bentonita típica (80–120 meq/100 g). Esto indica que parte del Ca/Na proviene de carbonatos o sales solubles, no solo del mineral de arcilla.

**Valores esperados (tolerancia 1e-3):** `Si 4.0000`, `Al 1.4143`, `Fe³⁺ 0.1505`, `Mg 0.2485`, `Ca 0.1071`, `Na 0.2585`, `K 0.0425`, `Ti 0.0150`, `carga 0.5152`.

### C8.4 Limitaciones

- La fórmula estructural calculada a partir de un análisis de roca entera (sin separar la fracción arcilla) **mezcla minerales**: cuarzo, feldespatos, carbonatos, óxidos de hierro. Para una fórmula válida hay que usar la fracción < 2 µm, pretratada (eliminación de carbonatos, materia orgánica y óxidos libres) y, preferiblemente, difracción de rayos X.
- Fe²⁺/Fe³⁺ no se distingue por análisis de óxidos: se reporta como Fe₂O₃ total.
- La asignación tetraédrica/octaédrica no es única; seguir una convención (p. ej. llenar primero la tetraédrica con Si y luego Al) y documentarla.
- La carga de capa por CEC es una estimación global; no toda la CEC viene de la capa (bordes, materia orgánica).
- La clasificación por carga es orientativa; para confirmar el mineral, XRD.

---

## Anexo A. Tabla consolidada de casos de prueba

Casos listos para `tests/calculators.php` (tolerancias sugeridas; el equipo de cálculo puede endurecerlas):

| ID | Calculadora | Entrada resumida | Salida esperada | Tolerancia |
|---|---|---|---|---|
| C1-A | UMF | 40 feldespato K + 20 cal + 25 sílice + 15 caolín | K₂O 0.16769; CaO 0.83231; Al₂O₃ 0.32528; SiO₂ 2.29235; Si:Al 7.0473; CTE 7.93 | 1e-4 (CTE 0.05) |
| C2-C | Mezcla 2 | A 60 % / B 40 % (contracción, absorción, Al₂O₃, Fe₂O₃) | 9.6 %; 10.4 %; 28.4 %; 2.12 % | 1e-6 |
| C2-D | Mezcla 3 (LSQ) | A/B/C con objetivos SiO₂ 55, Al₂O₃ 30, Fe₂O₃ 1.8 | x = 0.2491/0.2091/0.5418; logrados 54.653/29.680/1.464 | 5e-3 |
| C3-E | Contracción | 100 → 96 → 91 mm | S_secado 4.0; S_cocción 5.2083; S_total 9.0; L₀ 109.89 | 1e-3 |
| C3-F | Absorción | 200 / 230 / 130 g | WA 15.0; ρ 2.0; porosidad 30.0; G 2.857 | 1e-3 |
| C4-G | Balance 2 prod. | F 15000, f 1.15, c 32.7, t 0.18 | K 33.5258; C 447.42; R 84.8147; T 14552.58 | 1e-3 |
| C4-G2 | Pagable | C 447.42 t, 32.7 %, 9000 USD/t, 96.5 %, TC 90, RC 0.09 | neto 1 201 373.24 USD; 2 685.11 USD/t conc | 0.01 |
| C4-H | Balance 3 prod. | F 1000; C₁ 20/C₂ 15/T 965; Cu 28/0.9/0.10; Zn 1.2/52/0.15 | f_Cu 0.67; f_Zn 0.94875; R_Cu 83.5821; R_Zn 82.2134; K 50/66.667 | 1e-3 |
| C5-I | Bentonita | 1000 kg; 5.2→8.0 %; pérdida 10 %; agua 2.6→3.2 % | 28.0 kg; 31.11 kg; 8.311 %; 27.378 kg seca; 6.0 kg agua | 1e-3 |
| C6-J | PSD | mallas 4.75…0.075 mm | % pasante y D50 0.3311 mm; φ 1.595 | 1e-3 |
| C6-K | Stokes | 50 µm, 2650/1000 kg/m³, 1 mPa·s | v 2.2481 mm/s; Re 0.1124; t(30 cm) 133.44 s | 1e-4 rel. |
| C7-L | Humedad | 10 000 WMT; 10 % vs 8 % base | 9 000 t secas; 9 782.61 WMT eq.; 50.00 USD/t seca | 1e-3 |
| C7-M | Landed | FOB 180; flete 35; seguro 0.5 %; arancel 5 %; IVA 16 %; FX 17.50 | CIF 216.075; landed 263.179; 4 605.64 MXN/t; 12 120.10 MXN/t Al₂O₃ | 0.01 |
| C8-N | Fórmula estructural | análisis de 9 óxidos | Si 4.0000; Al 1.4143; Fe 0.1505; Mg 0.2485; carga 0.5152 | 1e-3 |

**Caso de borde para QA:** UMF de un material con LOI (caolín, carbonato) debe usar la composición en base cocida; si se usan los porcentajes crudos (incluyendo agua/CO₂), el resultado difiere y el test debe fallar explícitamente para evitar el error silencioso.

---

## Anexo B. Fuentes

### B1. Referencias técnicas

- Digitalfire — Unity Molecular Formula: <https://digitalfire.com/4sight/education/unity_molecular_formula_101_1.html>
- Digitalfire — Fórmulas límite: <https://digitalfire.com/4sight/education/limit_formulas_101_1.html>
- Digitalfire — Contracción: <https://digitalfire.com/4sight/education/shrinkage_101_1.html>
- Digitalfire — Absorción de agua: <https://digitalfire.com/4sight/education/water_absorption_101_1.html>
- Digitalfire — Expansión térmica: <https://digitalfire.com/4sight/education/thermal_expansion_101_1.html>
- Digitalfire — Glosario "Seger": <https://digitalfire.com/glossary/seger>
- 911Metallurgist — Metallurgical Accounting Formulas: <https://www.911metallurgist.com/blog/metallurgical-accounting/>
- Wikipedia — Stokes's law: <https://en.wikipedia.org/wiki/Stokes%27_law>
- Wikipedia — Normative mineralogy (CIPW): <https://en.wikipedia.org/wiki/Normative_mineralogy>
- Wikipedia — Molding sand: <https://en.wikipedia.org/wiki/Molding_sand>
- Wikipedia — Sand casting: <https://en.wikipedia.org/wiki/Sand_casting>
- Wikipedia — Material balance: <https://en.wikipedia.org/wiki/Material_balance>
- Wikipedia — Free on board (FOB): <https://en.wikipedia.org/wiki/Free_on_board>
- Wikipedia — Cost, insurance and freight (CIF): <https://en.wikipedia.org/wiki/Cost,_insurance_and_freight>
- ASTM C326 — Drying and Firing Shrinkages of Ceramic Whiteware Clays: <https://www.astm.org/c0326-09.html>
- ASTM C373 — Water Absorption, Bulk Density, Apparent Porosity…: <https://www.astm.org/c0373-18.html>
- ASTM D2216 — Laboratory Determination of Water (Moisture) Content: <https://www.astm.org/d2216-19.html>
- Banxico — Tipo de cambio: <https://www.banxico.org.mx/tipcamb/main.do?page=tip&idioma=sp>

> Las URLs de Digitalfire devolvieron HTTP 200 a la verificación, pero su HTML bloquea la lectura automatizada; por eso las fórmulas de este documento se presentan como matemática estándar (no se citan textos literales de esas páginas). Las páginas de Wikipedia, 911Metallurgist y ASTM sí fueron leídas y citadas por su contenido.

### B2. Implementaciones de referencia (verificadas; ver `repositorios-calculadoras.md`)

- `KyaniteLabs/openglaze` (MIT): pesos moleculares, normalización UMF, CTE, rangos objetivo.
- `iLanis-agent/glazelab` (sin licencia, solo referencia): motor UMF mínimo; se usó como verificación cruzada del caso C1-A.
- `dlizotte-uwo/glaze-formulator` (GPL-3.0): formulación matemática de la receta inversa.
- `AgyeyaMishra/particle-size-distribution-curve` (MIT): % pasante.
- `weber1158/StokesSimulator` (MIT): ley de Stokes y unidades.
- `clayton-iliquin/software-MetalurgIA` (MIT): balance matricial de 2–3 concentrados (con correcciones indicadas en `repositorios-calculadoras.md`).
- `HarmanBhangu1313/pcb-metallurgy-valuation` (MIT): lógica de valuación pagable.
- `TheRFrog/shinyNORRRM` (GPL-3.0) y `wjsawyer/cipw` (sin licencia): normalización catiónica y norma CIPW, solo como referencia conceptual.

### B3. Nota de trazabilidad

Cada caso numérico de este documento se calculó con doble precisión en un script reproducible (Python 3.13 + NumPy), y el caso C1-A se contrastó además ejecutando el motor `glazelab` en Node 26.8.1. Si el equipo de cálculo cambia constantes (por ejemplo, pesos moleculares o coeficientes de Appen), debe actualizar esta tabla y los asserts en el mismo commit.
