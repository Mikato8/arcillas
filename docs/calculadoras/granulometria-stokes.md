# Granulometría y velocidad de Stokes (`granulometria-stokes`)

- **Categoría:** Mineralogía y procesos
- **Ruta:** `/calculadoras/granulometria-stokes`
- **Clase:** `App\Services\Calculators\ParticleSizeCalculator`
- **Entrada:** hasta 8 mallas con abertura (µm) y porcentaje pasante acumulado o
  retenido por malla; diámetro y propiedades de partícula/fluido para Stokes.
- **Salida:** D10, D30, D50, D60, D90, coeficientes Cu y Cc, graduación y
  velocidad de sedimentación.

## Fórmulas

### Curva granulométrica

Con los datos ordenados de malla más gruesa a más fina:

```text
Modo % pasante:  P_i = valor capturado
Modo % retenido: R_acum_i = Σ retenidos hasta i;  P_i = 100 − R_acum_i
```

La interpolación de un diámetro `D_x` (tamaño al `x` % pasante) es **logarítmica
en el tamaño y lineal en el porcentaje**:

```text
log D_x = log D₁ + [(x − P₁) / (P₂ − P₁)] · (log D₂ − log D₁)
```

donde `(D₁, P₁)` y `(D₂, P₂)` son las dos mallas consecutivas que encierran a `x`.
Si `x` queda fuera del intervalo analizado, el resultado es `null` y se emite una
advertencia (no se extrapola).

```text
Cu = D60 / D10
Cc = D30² / (D10 · D60)
```

### Ley de Stokes

```text
v = d² · (ρ_p − ρ_f) · g / (18 · μ)

Re = ρ_f · v · d / μ
```

| Variable | Significado | Unidad |
|---|---|---|
| `d` | diámetro equivalente de partícula | m (se captura en µm) |
| `ρ_p` | densidad de la partícula | kg/m³ (se captura en g/cm³) |
| `ρ_f` | densidad del fluido | kg/m³ (se captura en g/cm³) |
| `μ` | viscosidad dinámica del fluido | Pa·s (se captura en cP = mPa·s) |
| `g` | aceleración de la gravedad | 9.80665 m/s² |
| `v` | velocidad de sedimentación | m/s (se reporta en cm/s y mm/s) |
| `Re` | número de Reynolds de la partícula | adimensional |

Conversiones usadas: `d[m] = d[µm] × 10⁻⁶`; `ρ[kg/m³] = ρ[g/cm³] × 1000`;
`μ[Pa·s] = μ[cP] × 10⁻³`. La ley de Stokes es válida en régimen laminar
(`Re ≤ 1`); fuera de ese rango la velocidad es solo orientativa.

## Ejemplo numérico

Curva (% pasante): 2000 µm → 100; 1000 → 90; 500 → 70; 250 → 45; 125 → 20;
63 → 5.

| Resultado | Valor |
|---|---:|
| D10 | **79.16 µm** |
| D30 | 164.94 µm |
| **D50** | **287.17 µm** |
| D60 | 378.93 µm |
| D90 | **1000.00 µm** |
| Cu | **4.79** |
| Cc | **0.91** |
| Graduación | Graduación intermedia o mal graduada |

Cálculo de D50: se interpola entre 500 µm (70 % pasante) y 250 µm (45 %):

```text
log D50 = log 500 + [(50 − 70)/(45 − 70)] · (log 250 − log 500)
        → D50 = 287.17 µm
```

Stokes con partícula de cuarzo (2.65 g/cm³), agua a 20 °C (0.998 g/cm³,
1.002 cP) y d = 50 µm:

```text
v = (50×10⁻⁶)² · (2.65 − 0.998)·1000 · 9.80665 / (18 · 1.002×10⁻³)
  = 0.002246 m/s = 0.2246 cm/s ≈ 2.246 mm/s
Re ≈ 0.1118  → régimen de Stokes válido
```

Modo retenido equivalente: retenidos 10/20/25/20/15/10 % sobre las mismas mallas
producen pasantes 90/70/45/25/10/0 y **D50 = 574.35 µm**, D10 = 125 µm,
D90 = 2000 µm.

## Clasificación orientativa de graduación

- `Cu < 4` → **uniforme (mal graduada)**.
- `Cu ≥ 6` y `1 ≤ Cc ≤ 3` → **bien graduada**.
- Otros casos → **graduación intermedia o mal graduada**.

El criterio de suelo bien graduado cambia entre arena y grava (Cu ≥ 6 vs ≥ 4) y
depende del sistema de clasificación; aquí se usa como referencia orientativa.

## Redondeo

- Diámetros: 2 decimales (µm).
- Cu y Cc: 2 decimales.
- Velocidad: 4 decimales en cm/s; 3 en mm/s.
- Reynolds: 4 decimales.

## Validaciones y advertencias

- Mínimo dos mallas con datos; aberturas > 0, únicas y ≤ 1 000 000 µm.
- Porcentajes entre 0 y 100; los retenidos no pueden sumar más de 100 %.
- Los porcentajes pasantes deben disminuir al pasar a mallas más finas (se tolera
  hasta 0.5 % por redondeo, que se aplana); si no, se lanza
  `InvalidArgumentException`.
- Percentiles fuera de rango → `null` + advertencia con el intervalo analizado.
- Reynolds > 1 → advertencia de fuera del régimen de Stokes.
- d > 500 µm → advertencia de régimen transicional.
- Diferencia de densidad < 0.2 g/cm³ → advertencia de sedimentación lenta.

## Supuestos y limitaciones

- La interpolación logarítmica es una convención habitual para curvas de mallas;
  no sustituye la curva completa ni un ajuste de distribución (Rosin–Rammler,
  Gates–Gaudin–Schuhmann).
- Solo se interpola entre mallas; no se extrapola más allá de la malla más fina
  (fondo/pan) ni de la más gruesa. Si hace falta D10 por debajo del fondo, agrega
  mallas o usa sedigrafía/láser.
- Los datos de malla se asumen en base seca y con tamizado representativo.
- Stokes supone partículas esféricas, lisas, diluidas, sin interacción entre
  partículas, fluido newtoniano y régimen laminar. Para arcillas laminares o
  partículas no esféricas la velocidad real es menor.
- La densidad y viscosidad dependen de la temperatura; se capturan como datos.
- El número de Reynolds se calcula con la velocidad de Stokes; si Re > 1 la
  estimación pierde validez (usar correlaciones de régimen transicional).

## Fuentes

- Wikipedia, *Stokes' law*: <https://en.wikipedia.org/wiki/Stokes%27_law>
- Wikipedia (español), *Ley de Stokes*: <https://es.wikipedia.org/wiki/Ley_de_Stokes>
- Wikipedia, *Sieve analysis*: <https://en.wikipedia.org/wiki/Sieve_analysis>
- Wikipedia, *Particle-size distribution*:
  <https://en.wikipedia.org/wiki/Particle-size_distribution>
- Digitalfire, *Particle size*: <https://digitalfire.com/glossary/particle+size>

## Pruebas asociadas

Grupo «Granulometría y Stokes»: curva de 6 mallas (D10/D30/D50/D60/D90, Cu, Cc,
Stokes y Reynolds), modo retenido equivalente, percentiles fuera de rango,
viscosidad alta, errores de monotonía, mallas repetidas, una sola malla,
retenidos > 100 %, densidad de partícula menor que el fluido y diámetro fuera de
rango, y JSON.
