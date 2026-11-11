# Bentonita y agua para arena verde (`bentonita-arena-verde`)

- **Categoría:** Fundición
- **Ruta:** `/calculadoras/bentonita-arena-verde`
- **Clase:** `App\Services\Calculators\BentoniteSandCalculator`
- **Entrada:** masa de arena por lote, % bentonita, % agua, % finos, número de
  lotes y humedad de la bentonita.
- **Salida:** bentonita seca y húmeda, agua neta a agregar, cantidades por
  tonelada de arena y totales para N lotes.

## Fórmulas

Las dosificaciones se interpretan **sobre la masa de arena seca** del lote.

```text
Bentonita seca      = arena × (% bentonita / 100)
Factor de humedad   = 1 / (1 − humedad bentonita / 100)
Bentonita húmeda    = bentonita seca × factor de humedad
Agua en bentonita   = bentonita húmeda − bentonita seca
Agua objetivo       = arena × (% agua / 100)
Agua neta a agregar = agua objetivo − agua en bentonita   (mínimo 0)
Finos presentes     = arena × (% finos / 100)

Arena nueva para diluir finos a 12 %:
  x = finos / 0.12 − arena        (si los finos actuales > 12 %)
```

| Variable | Significado | Unidad |
|---|---|---|
| `arena` | arena seca base del lote | kg |
| `% bentonita` | bentonita seca objetivo | % sobre arena |
| `% agua` | agua total de mezcla objetivo | % sobre arena |
| `% finos` | finos ya presentes | % sobre arena |
| `humedad bentonita` | agua libre de la bentonita recibida | % |
| `lotes` | número de lotes iguales | entero |

Las cantidades por tonelada se obtienen normalizando a 1 000 kg de arena; son
independientes del tamaño del lote.

## Ejemplo numérico

Lote de 1 000 kg de arena, 8 % bentonita, 4 % agua, 10 % finos, 2 lotes,
bentonita con 10 % de humedad.

| Resultado | Valor |
|---|---:|
| Bentonita seca requerida por lote | **80.000 kg** |
| Bentonita húmeda a dosificar por lote | **88.889 kg** |
| Agua aportada por la bentonita | 8.889 kg |
| Agua objetivo de mezcla | 40.000 kg |
| **Agua neta a agregar por lote** | **31.111 kg** |
| Bentonita seca por tonelada de arena | 80.000 kg/t |
| Bentonita húmeda por tonelada de arena | 88.889 kg/t |
| Agua neta por tonelada de arena | 31.111 kg/t |
| Bentonita húmeda total (2 lotes) | 177.778 kg |
| Agua neta total (2 lotes) | 62.222 kg |
| Finos presentes por lote | 100.000 kg |

Si la bentonita estuviera seca (0 % de humedad), se dosificarían 80 kg y el agua
neta sería 40 kg. Si la humedad aportada supera el agua objetivo, el resultado se
reporta en 0 (nunca negativo) y se advierte el déficit.

## Rangos típicos de arena verde

| Parámetro | Rango típico | Advertencia fuera de rango |
|---|---|---|
| Bentonita | 4–10 % | < 4 % o > 12 % |
| Agua | 2–5 % | < 2 % o > 6 % |
| Finos | 5–15 % | < 5 % o > 15 % |
| Humedad de bentonita | 8–12 % | > 20 % |

Estos valores son orientativos para fundición de hierro y metales no ferrosos en
arena verde; dependen del tipo de bentonita, del sistema de moldeo, de la
temperatura del metal y del equipo de mezcla.

## Redondeo

- Masas: 3 decimales (kg).
- Porcentajes: 2 decimales.
- Factor de humedad: 5 decimales.

## Advertencias automáticas

- Bentonita < 4 % → poca resistencia en verde y plasticidad.
- Bentonita > 12 % → riesgo de sopladuras, arena pegajosa y exceso de finos.
- Agua < 2 % → arena quebradiza y difícil de compactar.
- Agua > 6 % → sopladuras, reventones y baja permeabilidad.
- Finos > 15 % → requiere renovación parcial de arena.
- Finos < 5 % → posible pérdida de cohesión.
- Humedad de bentonita > 20 % → almacenamiento inadecuado y ajuste de agua.
- Agua neta negativa → el aporte de la bentonita excede el agua objetivo.

## Supuestos y limitaciones

- La arena se considera seca y sin agua propia; si la arena reciclada tiene
  humedad, debe descontarse manualmente (o extender el modelo).
- La bentonita se dosifica por peso húmedo; se supone que su humedad es agua
  disponible para la mezcla.
- No se modelan bentonita sódica vs cálcica, activación, *mulling*, tiempo de
  mezcla, temperatura de la arena, *sea coal*, polvo de carbón, cereales ni
  aditivos; todos afectan la resistencia en verde y la permeabilidad.
- La fórmula de “arena nueva para diluir finos” es una regla de mezcla
  simplificada; no considera la distribución de tamaño de los finos ni la
  bentonita quemada.
- No sustituye pruebas de resistencia en verde, compactabilidad, permeabilidad
  ni el control de humedad del laboratorio de fundición.

## Fuentes

- Wikipedia, *Green sand*: <https://en.wikipedia.org/wiki/Green_sand>
- Wikipedia, *Bentonite*: <https://en.wikipedia.org/wiki/Bentonite>
- Digitalfire, *Bentonite*: <https://digitalfire.com/glossary/bentonite>
- American Foundry Society: <https://www.afsinc.org/>
- Digitalfire, *Moisture*: <https://digitalfire.com/glossary/moisture>

## Pruebas asociadas

Grupo «Bentonita para arena verde»: caso 1 000 kg/8 %/4 %/10 %/2 lotes con
humedad 10 %, bentonita seca (0 % humedad), exceso de humedad con agua neta 0 y
déficit reportado, errores de lotes fraccionarios, bentonita fuera de rango y
masa cero, y JSON.
