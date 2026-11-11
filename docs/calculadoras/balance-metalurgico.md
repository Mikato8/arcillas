# Balance metalúrgico no ferroso (`balance-metalurgico`)

- **Categoría:** Metalurgia no ferrosa
- **Ruta:** `/calculadoras/balance-metalurgico`
- **Clase:** `App\Services\Calculators\MetalBalanceCalculator`
- **Entrada:** leyes de alimentación, concentrado y colas; masa de alimentación;
  precio del metal, pagable y cargos de fundición.
- **Salida:** recuperación, razón de concentración, masa de concentrado, metal
  fino contenido y valor pagable neto.

Modelo de **dos productos** (concentrado + colas) en estado estacionario, el
balance clásico de una planta de flotación o concentración.

## Fórmulas

Con leyes en la misma unidad (`%` o `g/t`):

```text
Balance de masa:  F = C + T
Balance de metal: F · f = C · c + T · t

Masa de concentrado:   C = F · (f − t) / (c − t)
Masa de colas:         T = F − C
Razón de concentración: F / C = (c − t) / (f − t)
Concentrado por tonelada de alimentación: C / F

Metal fino en alimentación:  F · f
Metal fino en concentrado:   C · c
Metal fino en colas:         T · t
Recuperación: R = C · c / (F · f) = c(f − t) / [f(c − t)] × 100
```

Conversión de leyes:

```text
%    → fracción: dividir entre 100
g/t  → fracción: dividir entre 1 000 000
```

Valorización:

```text
Precio (USD/t de metal) =
  USD/t  → precio
  USD/lb → precio × 2 204.62262185
  USD/oz → precio × 32 150.746565

Metal pagable = metal fino en concentrado × pagable %
Ingreso bruto = metal pagable × precio (USD/t)
Maquila total = C × TC (USD/t de concentrado)
Refinación total = metal pagable × RC (USD/t de metal pagable)
Otros cargos = C × otros (USD/t de concentrado)
Valor neto = ingreso bruto − maquila − refinación − otros
Valor neto por t de concentrado = valor neto / C
Valor neto por t de alimentación = valor neto / F
```

| Variable | Significado | Unidad |
|---|---|---|
| `F` | masa de alimentación | t |
| `C` | masa de concentrado | t |
| `T` | masa de colas | t |
| `f` | ley de alimentación | % o g/t |
| `c` | ley de concentrado | % o g/t |
| `t` | ley de colas | % o g/t |
| `TC` | *treatment charge* (maquila) | USD/t de concentrado |
| `RC` | *refining charge* (refinación) | USD/t de metal pagable |

Condición obligatoria: **concentrado > alimentación > colas** (`c > f > t`).
Si no se cumple, el balance es físicamente imposible y se lanza
`InvalidArgumentException`.

## Ejemplo numérico (cobre)

- Alimentación: 1 000 t con 1.0 % Cu.
- Concentrado: 25.0 % Cu; colas: 0.15 % Cu.
- Precio: 9 000 USD/t; pagable 96.5 %; TC 90 USD/t de concentrado; RC 150 USD/t
  de metal pagable; sin otros cargos.

| Resultado | Valor |
|---|---:|
| Masa de concentrado | **34.2052 t** |
| Masa de colas | 965.7948 t |
| Razón de concentración | **29.235 t/t** |
| Concentrado por t de alimentación | 0.034205 t/t |
| Metal fino en alimentación | 10 000 kg |
| Metal fino en concentrado | **8 551.31 kg** |
| Metal fino en colas | 1 448.69 kg |
| Recuperación | **85.513 %** |
| Metal pagable | 8 252.01 kg = 8.2520 t |
| Ingreso bruto | 74 268.11 USD |
| Maquila | 3 078.47 USD |
| Refinación | 1 237.80 USD |
| **Valor neto total** | **69 951.84 USD** |
| **Valor neto por t de concentrado** | **2 045.06 USD/t** |

Ejemplo de metales preciosos con leyes en g/t: alimentación 100 g/t Ag,
concentrado 5 000 g/t, colas 10 g/t, 1 000 t, precio 25 USD/oz, pagable 95 %,
TC 100 USD/t, RC 0 → masa de concentrado 18.0361 t, razón 55.444, recuperación
**90.18 %**, metal fino en concentrado 90.18 kg.

## Redondeo

- Recuperación, razón de concentración y leyes: 3–4 decimales.
- Masas en toneladas: 4 decimales; masas de metal fino: 2 decimales (kg).
- Importes: 2 decimales.
- Concentrado por tonelada: 5 decimales.

## Advertencias automáticas

- Recuperación < 60 % → revisar liberación, reactivos o ley de colas.
- Recuperación > 95 % → verificar consistencia de leyes y unidades.
- Razón de concentración > 50 → ley de alimentación baja o concentrado pobre.
- Pagable < 90 % → el valor depende mucho de las condiciones de fundición.
- Valor neto negativo → el concentrado no es rentable con esos cargos.
- Ley de concentrado > 80 % en modo % → posible error de unidades.

## Supuestos y limitaciones

- **Dos productos:** no modela tres productos (concentrado bulk + limpio +
  colas), *scavenger*, *cleaner* ni recirculación; para eso se requiere un
  balance por nodos y más ecuaciones.
- **Estado estacionario:** no considera inventarios, tiempos de residencia ni
  variaciones de alimentación.
- **Sin subproductos ni penalizaciones específicas:** los “otros cargos” son un
  monto por tonelada; no se modelan penalizaciones por As, Sb, Bi, Hg, sílice,
  humedad, etc., ni bonificaciones.
- **El precio es un dato del usuario**, no una cotización en vivo; los contratos
  de concentrado varían por fundición, país, TC/RC, *quotational period* y
  ajustes por inflación.
- **Leyes en g/t:** el metal fino se calcula en t y se reporta en kg; para
  onzas troy usar la conversión de precio incluida.
- **Pagable:** se aplica al metal fino del concentrado, no al de la alimentación.
- No sustituye un balance metalúrgico auditado ni un *metallurgical accounting*
  con muestreo estadístico y factores de conciliación.

## Fuentes

- 911 Metallurgist, *Metallurgical accounting formulas*:
  <https://www.911metallurgist.com/blog/metallurgical-accounting-formulas/>
- 911 Metallurgist, *Calculate recoveries with 2 or 3 product formulas*:
  <https://www.911metallurgist.com/blog/calculate-recoveries-formulas/>
- 911 Metallurgist, *Basic metallurgical formulas*:
  <https://www.911metallurgist.com/blog/basic-metallurgical-formulas/>
- Wikipedia, *Water content* (base seca/húmeda):
  <https://en.wikipedia.org/wiki/Water_content>

## Pruebas asociadas

Grupo «Balance metalúrgico no ferroso»: caso cobre con recuperación, masa, fino,
ingresos y cargos; caso plata en g/t; precio en USD/lb con conversión; errores de
orden de leyes y de ley > 100 % en modo porcentaje; JSON.
