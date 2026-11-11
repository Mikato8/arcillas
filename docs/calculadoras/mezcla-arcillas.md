# Mezcla de arcillas por objetivos (`mezcla-arcillas`)

- **Categoría:** Arcillas y pastas
- **Ruta:** `/calculadoras/mezcla-arcillas`
- **Clase:** `App\Services\Calculators\ClayBlendCalculator`
- **Entrada:** análisis de 2 a 4 arcillas (SiO2, Al2O3, Fe2O3 y LOI) y una
  composición objetivo.
- **Salida:** proporciones óptimas de cada arcilla, composición resultante y
  desviaciones respecto a los objetivos.

## Fórmulas

Regla de mezclas para cada óxido `j`:

```text
C_j = Σ_i w_i · A_ij          con  Σ_i w_i = 1  y  w_i ≥ 0
```

Objetivo de mínimos cuadrados (solo óxidos con objetivo > 0):

```text
minimizar  f(w) = Σ_j [ C_j − T_j ]²
```

Donde:

| Símbolo | Significado | Unidad |
|---|---|---|
| `w_i` | proporción de la arcilla `i` | fracción (0–1) |
| `A_ij` | % del óxido `j` en la arcilla `i` | % en peso |
| `C_j` | % del óxido `j` en la mezcla | % en peso |
| `T_j` | objetivo del óxido `j` (0 = se ignora) | % en peso |

Métricas reportadas:

```text
Desviación_j = C_j − T_j
Desviación absoluta máxima = max_j |C_j − T_j|
Error cuadrático medio (ECM) = (Σ_j Desviación_j²) / número_de_objetivos
```

## Método de solución

No se usan librerías de optimización. El solver es determinista:

1. **Rejilla sobre el simplex:** 2 arcillas → 2 001 puntos; 3 → ~20 000;
   4 → ~23 000 combinaciones con paso 1/50.
2. **Descenso por coordenadas:** mueve masa de una arcilla a otra con pasos
   decrecientes (0.02 → 0.0001) mientras reduzca el error.
3. Se normaliza para que las proporciones sumen exactamente 1 y se eliminan
   negativos numéricos.

La solución puede quedar en la frontera (`w_i = 0`); en ese caso se emite una
advertencia. Si dos arcillas tienen análisis casi idénticos, la proporción entre
ellas no es única y también se advierte.

## Entrada

Formulario: `numero_arcillas` (2/3/4) más los campos
`arcilla_{n}_{nombre,sio2,al2o3,fe2o3,loi}` y `objetivo_{sio2,al2o3,fe2o3,loi}`.

Por API también acepta listas:

```php
[
  'arcillas' => [
    ['nombre' => 'Caolín',   'sio2' => 46.3, 'al2o3' => 37.5, 'fe2o3' => 0.7, 'loi' => 14.5],
    ['nombre' => 'Ball clay','sio2' => 55.0, 'al2o3' => 28.0, 'fe2o3' => 1.2, 'loi' => 9.5],
  ],
  'objetivos' => ['sio2' => 50.0, 'al2o3' => 32.0, 'fe2o3' => 1.0, 'loi' => 12.0],
]
```

## Ejemplo numérico

Arcilla A: SiO2 60 %, Al2O3 25 %, Fe2O3 1 %, LOI 10 %.
Arcilla B: SiO2 40 %, Al2O3 35 %, Fe2O3 2 %, LOI 20 %.
Objetivo: SiO2 50 %, Al2O3 30 %, Fe2O3 1.5 %, LOI 15 %.

La solución exacta es **50 % A + 50 % B**:

- Composición: 50 / 30 / 1.5 / 15 %.
- Desviación absoluta máxima: **0.00 pp**; ECM: **0**.
- Suma de proporciones: 100.00 %.

Otro caso con tres arcillas A (60/25/1/10), B (40/35/2/20), C (50/20/7/8) y
objetivo 52.5/26.25/2.75/12 → **50 % A + 25 % B + 25 % C**.

## Validaciones y advertencias

- 2 a 4 arcillas; análisis con suma > 0 y ≤ 101 % (si no, error).
- Si la suma de SiO2 + Al2O3 + Fe2O3 + LOI es < 50 %, se considera análisis
  incompleto (error); si es < 90 %, se emite advertencia (faltan álcalis, MgO,
  TiO2, etc.).
- Al menos un objetivo > 0; sin objetivos activos el cálculo es imposible.
- Desviación máxima > 2 pp: advertencia de objetivos inalcanzables.
- Objetivos que suman > 101 %: advertencia.
- Proporción < 0.5 % o ≥ 99.5 %: advertencia de solución en la frontera.

## Redondeo

- Proporciones y composición: 2 decimales.
- Desviaciones: 3 decimales; ECM: 4 decimales.
- Sumas de análisis: 2 decimales.

## Supuestos y limitaciones

- Solo se optimizan los cuatro óxidos capturados; plasticidad, contracción,
  color, refractariedad, comportamiento en colada y costo no se modelan.
- Los objetivos se ponderan con **error absoluto**; un objetivo en 0 se ignora.
  Si necesitas priorizar un óxido, se puede extender la fórmula con pesos.
- La solución es matemática, no reológica: una mezcla “ideal” en óxidos puede no
  ser plástica, no secar bien o no comportarse en el torno.
- El análisis debe estar en la misma base (seca/calcinada). Mezclar bases
  distintas sesga el resultado.
- Las LOI de cada arcilla se tratan como un óxido más de la regla de mezclas;
  esto es válido como aproximación de composición, no de comportamiento.
- Solución determinista: la misma entrada siempre da la misma mezcla, pero puede
  haber óptimos alternativos casi equivalentes.

## Fuentes

- Digitalfire, *Line blend*: <https://digitalfire.com/glossary/line+blend>
- Digitalfire, *Clay*: <https://digitalfire.com/glossary/clay>
- Wikipedia, *Least squares*: <https://en.wikipedia.org/wiki/Least_squares>
- Wikipedia, *Simplex*: <https://en.wikipedia.org/wiki/Simplex>

## Pruebas asociadas

Grupo «Mezcla de arcillas»: mezcla exacta 50/50, solución en vértice,
tres arcillas, objetivo inalcanzable, análisis con suma > 101 %, análisis vacío,
más de 4 arcillas, objetivos todos en cero, JSON y determinismo.
