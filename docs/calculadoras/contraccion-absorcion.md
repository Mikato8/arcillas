# Contracción y absorción de una pasta (`contraccion-absorcion`)

- **Categoría:** Cerámica y esmaltes
- **Ruta:** `/calculadoras/contraccion-absorcion`
- **Clase:** `App\Services\Calculators\ShrinkageCalculator`
- **Entrada:** largos en verde, seco y cocido; opcionalmente medida final deseada
  y pesos seco/saturado.
- **Salida:** contracción de secado, cocción y total, corrección de molde,
  factor de sobredimensionado y absorción de agua.

## Fórmulas

```text
S_secado (%) = (L_verde − L_seco)  / L_verde  × 100
S_cocción (%) = (L_seco  − L_cocido) / L_seco  × 100
S_total (%)  = (L_verde − L_cocido) / L_verde × 100

Suma de etapas = S_secado + S_cocción
S_total ≠ suma de etapas (la cocción actúa sobre la pieza ya seca)

L_verde_necesario = medida_final / (1 − S_total / 100)
Factor de molde   = 1 / (1 − S_total / 100)

Absorción (%) = (peso_saturado − peso_seco) / peso_seco × 100
```

| Variable | Significado | Unidad |
|---|---|---|
| `L_verde` | largo de la probeta recién moldeada | mm |
| `L_seco` | largo después del secado | mm |
| `L_cocido` | largo después de la cocción | mm |
| `medida_final` | medida cocida que se desea obtener | mm |
| `peso_seco` | peso de la probeta seca | g |
| `peso_saturado` | peso después de saturar y escurrir | g |

La corrección de molde usa la contracción **total** lineal; es válida para una
dimensión. Para una pieza tridimensional, aplica el factor a cada dimensión.

## Ejemplo numérico

Largos: verde 100 mm, seco 92 mm, cocido 88 mm. Medida final deseada: 100 mm.
Pesos: seco 500 g, saturado 550 g.

| Resultado | Valor |
|---|---:|
| Contracción de secado | **8.00 %** |
| Contracción de cocción | **4.35 %** |
| Contracción total | **12.00 %** |
| Largo en verde necesario | **113.64 mm** |
| Factor de sobredimensionado | **1.1364×** |
| Absorción de agua | **10.00 %** |

La suma aritmética de etapas es 12.35 %, mayor que la contracción total
(12.00 %); se reporta para explicar la diferencia.

## Clasificación orientativa

| Contracción de secado | Clasificación |
|---|---|
| < 0 % | Expansión (revisar medición) |
| < 3 % | Baja |
| 3 – 6 % | Moderada |
| 6 – 10 % | Alta |
| > 10 % | Muy alta |

| Contracción de cocción | Clasificación |
|---|---|
| < 0 % | Expansión (revisar medición) |
| < 2 % | Baja |
| 2 – 5 % | Moderada |
| > 5 % | Alta |

| Contracción total | Clasificación |
|---|---|
| < 5 % | Baja |
| 5 – 10 % | Moderada |
| 10 – 15 % | Alta |
| > 15 % | Muy alta |

| Absorción de agua | Clasificación |
|---|---|
| < 1 % | Muy baja (vitrificado) |
| 1 – 5 % | Baja |
| 5 – 10 % | Media |
| 10 – 20 % | Alta |
| > 20 % | Muy alta (poroso) |

## Advertencias automáticas

- Secado o cocción con valor negativo → posible expansión; revisar medición.
- Secado > 8 % → riesgo de agrietamiento; secar despacio.
- Total > 15 % → ajustar curva y molde.
- Absorción < 1 % → riesgo de craquelado por desajuste con el esmalte.
- Absorción > 20 % → pasta muy porosa, baja resistencia mecánica.

## Redondeo

- Porcentajes, largos y pesos: 2 decimales.
- Factor de molde: 4 decimales.

## Entrada opcional

- `medida_final_deseada = 0` omite la corrección de molde.
- Si no se capturan ambos pesos, la absorción no se calcula (no es error).
- Si se captura solo un peso, o el saturado es menor que el seco, se lanza
  `InvalidArgumentException`.

## Supuestos y limitaciones

- **Contracción lineal**, no volumétrica. Para volumen, eleva el factor al cubo
  (aproximación válida solo si la contracción es isotrópica).
- No corrige por humedad de la pieza al medir ni por deformación diferencial.
- La absorción se calcula por diferencia de pesos; el método de saturación
  (inmersión, ebullición, vacío) y el tiempo de escurrido deben ser consistentes
  entre mediciones. No se modela la porosidad abierta/cerrada.
- No sustituye probetas normalizadas ni la curva de secado real del taller.
- Las clasificaciones son orientativas para pastas cerámicas típicas; arcillas
  refractarias, gres o porcelanas pueden requerir otros umbrales.
- Los largos deben medirse en la misma dirección y con el mismo instrumento.

## Fuentes

- Digitalfire, *Shrinkage*: <https://digitalfire.com/glossary/shrinkage>
- Digitalfire, *Water absorption*: <https://digitalfire.com/glossary/water+absorption>
- Digitalfire, *Moisture*: <https://digitalfire.com/glossary/moisture>

## Pruebas asociadas

Grupo «Contracción y absorción»: caso 100/92/88 con absorción 10 %, corrección
de molde 113.64 mm, ausencia de absorción sin pesos, expansión de secado,
errores por un solo peso / saturado menor / largo cero, y JSON.
