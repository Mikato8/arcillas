# Precio normalizado y costo landed (`precio-normalizado`)

- **Categoría:** Comercial
- **Ruta:** `/calculadoras/precio-normalizado`
- **Clase:** `App\Services\Calculators\PriceNormalizationCalculator`
- **Entrada:** precio por tonelada húmeda, humedad, merma, flete, margen del
  broker, IVA, tipo de cambio y contenido de óxido de referencia.
- **Salida:** costo landed por tonelada seca en MXN y USD, costo por tonelada (y
  por libra) de óxido contenido y desglose de conceptos.

## Fórmulas

```text
Factor húmedo → seco = (1 − humedad/100) × (1 − merma/100)
Rendimiento seco (%) = factor × 100

Costo base por t seca = precio húmedo / factor
Flete por t seca      = flete húmedo / factor
Subtotal              = costo base por t seca + flete por t seca
Margen                = subtotal × margen broker / 100
Costo landed          = subtotal + margen
IVA                   = costo landed × IVA / 100
Costo landed con IVA  = costo landed + IVA

Costo landed USD      = costo landed MXN / tipo de cambio
Costo por t de óxido  = costo landed / (contenido de óxido / 100)
Costo por lb de óxido = costo por t de óxido USD / 2 204.62262185
```

| Variable | Significado | Unidad |
|---|---|---|
| `precio` | precio del material tal como llega | MXN/t húmeda |
| `humedad` | agua libre del material | % |
| `merma` | pérdida adicional de manejo sobre material seco | % |
| `flete` | flete por tonelada húmeda pesada en báscula | MXN/t |
| `margen` | margen del broker sobre el costo landed antes de IVA | % |
| `IVA` | impuesto al valor agregado | % |
| `tipo de cambio` | pesos por dólar | MXN/USD |
| `contenido de óxido` | componente útil en el material seco | % |

La merma se aplica **después** de descontar humedad: es una pérdida de material
ya seco (manejo, almacenaje, selección). El margen se aplica antes del IVA.

## Ejemplo numérico

Precio 2 500 MXN/t húmeda, humedad 10 %, merma 2 %, flete 350 MXN/t húmeda,
margen 8 %, IVA 16 %, tipo de cambio 18.50 MXN/USD, contenido de Al2O3 37.5 %.

| Resultado | Valor |
|---|---:|
| Factor húmedo → seco | 0.8820 |
| Rendimiento seco | **88.20 %** |
| Costo base por t seca | 2 834.47 MXN/t |
| Flete prorrateado por t seca | 396.83 MXN/t |
| Subtotal sin margen | 3 231.29 MXN/t |
| Margen del broker | 258.50 MXN/t |
| **Costo landed por t seca** | **3 489.80 MXN/t** |
| **Costo landed por t seca** | **188.64 USD/t** |
| IVA | 558.37 MXN/t |
| Costo landed con IVA | 4 048.16 MXN/t |
| **Costo por t de Al2O3** | **9 306.12 MXN/t** |
| **Costo por t de Al2O3** | **503.03 USD/t** |
| Costo por lb de Al2O3 | 0.2282 USD/lb |

Comprobación: `2 500 / 0.882 = 2 834.47`; `(2 834.47 + 396.83) × 1.08 =
3 489.80`; `3 489.80 / 0.375 = 9 306.13` (diferencias por redondeo).

Caso sin humedad, merma, flete, margen ni IVA (precio 1 000 MXN/t, TC 20,
50 % SiO2): landed **1 000 MXN/t = 50 USD/t**, costo por t de SiO2
**2 000 MXN/t = 100 USD/t**.

## Advertencias automáticas

- Humedad > 20 % → el costo por tonelada seca se multiplica; conviene negociar
  base seca o exigir secado.
- Merma > 5 % → revisar manejo y almacenaje.
- Margen > 20 % → verificar competitividad.
- Óxido < 10 % → costo por unidad de componente útil elevado.
- Precio o flete en cero → el resultado solo refleja los conceptos capturados.
- IVA en 0 % → el resultado es un costo neto sin impuesto.

## Redondeo

- MXN/t y USD/t: 2 decimales.
- Costo por t de óxido: 2 decimales.
- USD/lb de óxido: 4 decimales.
- Factor húmedo → seco: 6 decimales.

## Supuestos y limitaciones

- El flete se cotiza por **tonelada húmeda** (peso de báscula) y se prorratea a
  tonelada seca. Si tu proveedor cotiza por tonelada seca, divide el flete entre
  el factor antes de capturarlo o ajusta el resultado.
- La merma es un porcentaje fijo sobre el material seco; no se modela merma
  diferenciada por etapa logística.
- El IVA no es costo para empresas que lo acreditan; se muestra como referencia
  de precio final. No se modelan retenciones, IEPS, aranceles ni otros impuestos.
- No se incluyen costos de almacenaje, financiamiento, seguros, demoras,
  maniobras portuarias ni costos de calidad (penalizaciones/bonificaciones).
- El contenido de óxido se interpreta sobre material seco; si el laboratorio lo
  reporta sobre base calcinada, debe corregirse antes de capturarlo.
- El tipo de cambio es un dato del usuario; no hay cotización en vivo.
- El costo por óxido es un indicador de valor unitario, no un precio de venta;
  no considera rendimiento metalúrgico ni otras impurezas.

## Fuentes

- Wikipedia, *Water content*: <https://en.wikipedia.org/wiki/Water_content>
- Wikipedia, *Incoterms*: <https://en.wikipedia.org/wiki/Incoterms>
- Digitalfire, *Moisture*: <https://digitalfire.com/glossary/moisture>

## Pruebas asociadas

Grupo «Precio normalizado y costo landed»: caso base completo (landed, IVA,
margin, costo por óxido y por libra), caso sin cargos, advertencias por humedad,
merma, margen y bajo contenido, errores de tipo de cambio cero, humedad 100 % y
óxido 0 %, y JSON.
