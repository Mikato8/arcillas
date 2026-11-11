# UMF / Seger de esmaltes (`umf-esmalte`)

- **Categoría:** Cerámica y esmaltes
- **Ruta:** `/calculadoras/umf-esmalte`
- **Clase:** `App\Services\Calculators\UmfCalculator`
- **Entrada:** receta en gramos o partes de 14 materiales cerámicos típicos.
- **Salida:** fórmula molecular unitaria con **fundentes RO normalizados a 1
  (ΣRO = 1)**, relación Si:Al y clasificación orientativa del esmalte.

La fórmula molecular unitaria (UMF, *unity molecular formula*) expresa la
composición de un esmalte en moles relativos de óxidos, independientes de la
escala de la receta. Se usa la convención estándar de Seger/Digitalfire/Glazy:
**ΣRO = 1**, y los sesquióxidos (Al2O3, B2O3, Fe2O3…) y óxidos RO2 (SiO2, TiO2,
ZrO2) se reportan como valores molares relativos a esa base.

## Fórmulas

Para cada material `k` de la receta y cada óxido `i` de su análisis:

```text
n_i = Σ_k [ m_k · (x_ki / 100) / M_i ]

RO    = Σ n_i  para Na2O, K2O, Li2O, CaO, MgO, ZnO, CoO (fundentes)
R2O3  = Σ n_i  para Al2O3, B2O3, Fe2O3
RO2   = Σ n_i  para SiO2, TiO2, ZrO2

UMF_i = n_i / RO          (por definición: ΣRO = 1)
Si:Al = n(SiO2) / n(Al2O3)
```

- `m_k`: masa del material en la receta (g o partes; la escala es irrelevante).
- `x_ki`: % en peso del óxido en el material.
- `M_i`: masa molecular del óxido (g/mol), definida en
  `app/Services/Calculators/Data/ceramic_materials.php`.
- La LOI del material no aporta moles de óxidos y se ignora en la normalización.
- `B2O3` se reporta con los sesquióxidos, pero **no** forma parte del divisor:
  solo los fundentes RO normalizan a 1.

Ejemplo de interpretación: un valor `SiO2 = 3.50` significa 3.50 moles de SiO2
por cada mol de fundentes RO totales; `Al2O3 = 0.55`, 0.55 moles por mol de RO.

## Biblioteca de materiales

`app/Services/Calculators/Data/ceramic_materials.php` contiene 14 materiales con
análisis tipo (editable): feldespato potásico, feldespato sódico, caolín, sílice,
carbonato de calcio, dolomita, talco, bórax, óxido de zinc, carbonato de litio,
alúmina calcinada, óxido de zirconio, óxido de hierro y óxido de cobalto. Cada
material declara `analisis` (óxido => %), `loi` y una nota de uso.

Los campos del formulario se generan con prefijo `mat_` y la clave del material
(`mat_caolin`, `mat_silice`, …). Todos arrancan en 0; la suma debe ser > 0.

También se aceptan estructuras por API:

```php
// Mapa material => cantidad
['receta' => ['feldespato_potasico' => 50, 'caolin' => 25, 'silice' => 25]]

// Lista de {material, cantidad}
['receta' => [
  ['material' => 'feldespato_potasico', 'cantidad' => 50],
  ['material' => 'caolin', 'cantidad' => 25],
  ['material' => 'silice', 'cantidad' => 25],
]]
```

Para importar o verificar una fórmula ya expresada en moles se puede usar
`UmfCalculator::normalizarFormula(['K2O' => …, 'SiO2' => …])`, que devuelve los
moles normalizados a ΣRO = 1 (útil para contrastar con Digitalfire/Glazy).

## Ejemplo numérico

### 100 g de feldespato potásico

Análisis tipo: K2O 10.50 %, Na2O 2.80 %, Al2O3 18.20 %, SiO2 67.50 %,
Fe2O3 0.12 %, CaO 0.25 %, MgO 0.05 %, TiO2 0.03 %, LOI 0.55 %.

| Óxido | Moles sin normalizar | UMF (ΣRO = 1, redondeado) |
|---|---:|---:|
| SiO2 | 1.123427 | **6.9200** |
| Al2O3 | 0.178500 | **1.0995** |
| B2O3 | 0 | 0 |
| Na2O | 0.045177 | 0.2783 |
| K2O | 0.111470 | 0.6866 |
| CaO | 0.004458 | 0.0275 |
| MgO | 0.001241 | 0.0076 |
| Fe2O3 | 0.000752 | 0.0046 |
| TiO2 | 0.000376 | 0.0023 |
| ZrO2 | 0 | 0 |

`ΣRO = 0.162345` mol; factor de normalización `1/ΣRO = 6.1597`.
La suma de K2O + Na2O + CaO + MgO en la salida redondeada es **1.0000**.
Relación **Si:Al = 6.294** (invariante a la normalización).

### Receta 50 % feldespato potásico + 25 % caolín + 25 % sílice

| Resultado | Valor |
|---|---:|
| SiO2 | 13.9478 |
| Al2O3 | 2.1704 |
| K2O | 0.6733 |
| Na2O | 0.2745 |
| ΣRO | 1.0000 |
| Si:Al | 6.426 |

### Receta 40/20/20/20 (feldespato potásico/caolín/sílice/calcita)

Es la receta del botón “Cargar ejemplo” del sitio.

| Resultado | Valor |
|---|---:|
| SiO2 | **3.5041** |
| Al2O3 | **0.5453** |
| CaO | **0.7571** |
| K2O | 0.1691 |
| Na2O | 0.0690 |
| MgO | 0.0048 |
| Fe2O3 | 0.0046 |
| TiO2 | 0.0040 |
| **ΣRO** | **1.0000** |
| **Si:Al** | **6.426** |
| Carácter | Aluminoso (satinado a mate) |
| Fundente dominante | Cálcico |

### Caso de referencia externo (verificación de convención)

Fórmula normalizada reportada por el investigador (contrastada con
Digitalfire/Glazy): K2O 0.16769, CaO 0.83231, Al2O3 0.32528, SiO2 2.29235,
Si:Al 7.0473. Alimentando esos moles escalados a `normalizarFormula()` se
recuperan exactamente esos valores y ΣRO = 1; está cubierto por las pruebas.

## Clasificación orientativa

Carácter por relación Si:Al (moles, razón invariante a la normalización):

| Si:Al | Interpretación |
|---|---|
| < 4 | Muy aluminoso: mate/opaco, puede no madurar |
| 4 – 7 | Aluminoso: satinado a mate |
| 7 – 10 | Equilibrado: típico de esmaltes brillantes estables |
| 10 – 13 | Silícico: brillante, mayor riesgo de craquelado |
| > 13 | Muy silícico: riesgo alto de desconchado |

Fundente dominante (participación sobre RO = 1): bórico (`B2O3 > 0.25` mol),
alcalino (`KNaO > 60 %` de RO), cálcico (`CaO > 50 %`), magnésico
(`MgO > 35 %`), zincífero (`ZnO > 25 %`) o mixto.

Advertencias por valores absolutos en la base ΣRO = 1: `B2O3 > 0.5`,
`ZnO > 0.35`, `Fe2O3 > 0.05`, `CoO > 0.02`, álcalis > 65 % de RO, y relaciones
Si:Al fuera de 4–13.

## Redondeo

- Moles de óxidos: 4 decimales.
- Relación Si:Al: 3 decimales.
- Detalle, factor de normalización y ΣRO: hasta 6 decimales.
- Masa de la receta: 3 decimales.

## Supuestos y limitaciones

- **Análisis tipo, no de laboratorio:** las cifras de la biblioteca son
  representativas; con el análisis real del proveedor los resultados cambian.
  Editar el archivo de datos o extender la interfaz para capturar análisis.
- **Convención ΣRO = 1:** coincide con la fórmula de unidad de Seger, Digitalfire
  e Insight/Glazy. Comparar valores con herramientas que usen otra base (por
  ejemplo, normalización a RO + R2O3 = 1) requiere convertir, porque todos los
  números cambian por un factor común; la relación Si:Al y las proporciones
  entre óxidos no cambian.
- **B2O3 con los sesquióxidos:** se presenta junto a Al2O3/Fe2O3 por costumbre,
  pero solo los RO entran al divisor. Algunos autores muestran B2O3 aparte; los
  valores son los mismos.
- **No predice el comportamiento en horno:** temperatura de maduración,
  craquelado, opacidad, textura y color dependen de la curva de cocción,
  atmósfera, molienda y espesor de aplicación. La UMF es una guía de formulación.
- **Colorantes:** Fe2O3 y CoO se incluyen como óxidos; los umbrales de aviso son
  orientativos y dependen del color buscado.
- **LOI y carbonatos:** no se usan para ajustar la fórmula; un esmalte con mucha
  LOI puede requerir corrección por análisis calcinado.
- **Escala:** la UMF no depende de la masa total de la receta; solo de las
  proporciones relativas.
- Si la receta no contiene fundentes RO, no es posible normalizar y se lanza
  `InvalidArgumentException` con un mensaje que pide agregar feldespato,
  carbonato de calcio o dolomita.

## Fuentes

- Digitalfire, *Unity formula*: <https://digitalfire.com/glossary/unity+formula>
- Digitalfire, *Seger formula*: <https://digitalfire.com/glossary/seger+formula>
- Digitalfire, *Silica:alumina ratio*: <https://digitalfire.com/glossary/silica:alumina+ratio>
- Digitalfire, *Calcination*: <https://digitalfire.com/glossary/calcination>
- Glazy: <https://glazy.org/>
- Hesselberth & Roy, *Mastering Cone 6 Glazes* (referencia de formulación; no en línea).

## Pruebas asociadas

En `tests/calculators.php`, grupo «UMF / Seger»: feldespato potásico 100 %
(ΣRO = 1), receta 50/25/25, receta del sitio 40/20/20/20, invariancia de escala,
formatos de receta, coma decimal, óxidos extra (Li2O), referencia del
investigador con `normalizarFormula()`, errores de receta vacía / material
desconocido / cantidad negativa / fórmula sin óxidos / óxido desconocido / moles
negativos, JSON y determinismo.
