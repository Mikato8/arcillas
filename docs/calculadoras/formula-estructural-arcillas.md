# Fórmula estructural de arcillas (`formula-estructural-arcillas`)

- **Categoría:** Mineralogía y procesos
- **Ruta:** `/calculadoras/formula-estructural-arcillas`
- **Clase:** `App\Services\Calculators\MineralFormulaCalculator`
- **Entrada:** óxidos en % (SiO2, Al2O3, Fe2O3, FeO, MgO, CaO, Na2O, K2O, TiO2,
  MnO, P2O5), LOI y base de normalización.
- **Salida:** fórmula estructural normalizada, carga de capa, reparto
  tetraédrico/octaédrico/interlaminar y clasificación orientativa del mineral.

## Fundamento

El recálculo de la fórmula estructural de un filosilicato convierte porcentajes
de óxidos en **cationes por celda unidad**, normalizando el oxígeno total de la
estructura. La LOI se interpreta como agua estructural (`H2O`); cada `H2O`
aporta un oxígeno y genera **dos grupos OH**.

```text
Moles de óxido:      n_óx = % óx / M_óx
Oxígeno de óxidos:   O_óx = Σ n_óx · O_por_óxido
Agua de la LOI:      n_H2O = LOI / 18.015
Oxígeno total:       O_T = O_óx + n_H2O

Escala:              s = O_base / O_T
Cationes norm.:      N_catión = n_catión · s
OH:                  OH = 2 · n_H2O · s
O estructural (sin OH): O = O_base − OH
```

Bases disponibles (`base_oxigeno` en el esquema, `O_base`):

| Base | O total | Sitios tetra | Sitios octa | Familia |
|---|---:|---:|---:|---|
| `O10(OH)8` | 18 | 4 | 4 | 1:1, celda completa |
| `O10(OH)2` | 12 | 4 | 2 | 2:1, media celda |
| `O5(OH)4` | 9 | 2 | 2 | 1:1, media celda |
| `O20(OH)4` | 24 | 8 | 4 | 2:1, celda completa |

La opción **`auto`** (recomendada) normaliza primero con `O10(OH)2` y calcula el
aluminio total por O10; si supera **3.2 cationes** asume un filosilicato 1:1
(caolín) y renormaliza con `O10(OH)8`. En caso contrario conserva `O10(OH)2`.

## Reparto de cationes y carga de capa

```text
Tetraédrica:  Si_tet = min(Si, T_sitios)
              Al_tet = min(Al, T_sitios − Si_tet)
Octaédrica:   Al_oct = Al − Al_tet;  + Fe3+, Fe2+, Mg, Ti, Mn
Interlaminar: K, Na, Ca

Carga de aniones  Q_an = 2·O + OH
Carga del marco   Q_fr = 4·Si_tet + 3·Al_tet + 3·Al_oct + 3·Fe3
                        + 2·Fe2 + 2·Mg + 4·Ti + 2·Mn
Carga de capa     Q_capa = Q_an − Q_fr
Carga interlamilar Q_int = K + Na + 2·Ca
```

La carga de capa calculada por sustituciones debe coincidir con la carga de los
cationes interlaminares; si difieren en más de 0.15 eq se emite una advertencia
(LOI no estructural, carbonatos, interestratificación o error analítico).

La clasificación se calcula **por O10** (`factor = 10 / O`), independientemente
de la base elegida, para que las familias 1:1 y 2:1 sean comparables.

## Ejemplo numérico

### Caolinita (1:1, teórica)

Análisis: SiO2 46.54 %, Al2O3 39.50 %, LOI 13.96 % (base automática).

| Resultado | Valor |
|---|---:|
| Fórmula | **(Al4)(Si4)O10(OH)8** |
| Si tetraédrico | 4.000 |
| Al octaédrico | 4.000 |
| Carga de capa | 0.000 eq/fórmula |
| Clasificación | **Caolinita (grupo caolín, 1:1)** |

Con `base_oxigeno = o5_oh4` la misma muestra da `(Al2)(Si2)O5(OH)4`, también
clasificada como caolinita (media celda).

### Montmorillonita (2:1, teórica)

Análisis: SiO2 65.48 %, Al2O3 23.20 %, MgO 3.62 %, Na2O 2.79 %, LOI 4.91 %.

| Resultado | Valor |
|---|---:|
| Fórmula | **Na0.33(Al1.67Mg0.33)(Si4)O10(OH)2** |
| Carga de capa | **0.330 eq/fórmula** |
| Cationes interlaminares | 0.330 eq/fórmula |
| Clasificación | **Montmorillonita (esmectita dioctaédrica)** |

### Illita (2:1, teórica)

Análisis: SiO2 52.33 %, Al2O3 31.34 %, MgO 2.07 %, K2O 9.65 %, LOI 4.61 %
(`base_oxigeno = o10_oh2`).

| Resultado | Valor |
|---|---:|
| Fórmula | **K0.8(Al1.8Mg0.2)(Si3.4Al0.6)O10(OH)2** |
| Si tetraédrico / Al octaédrico | 3.400 / 1.800 |
| Carga de capa | **0.800 eq/fórmula** |
| Clasificación | **Illita / mica dioctaédrica** |

## Clasificación orientativa (por O10)

| Condición | Clasificación |
|---|---|
| Carga < 0.10, Al octa > 2.5, Si > 3.2 | Caolinita (1:1) |
| Carga < 0.10, Mg > 2.2, Al octa < 0.6 | Talco (trioctaédrico) |
| Carga < 0.10, octa > 2.6, Si < 3.6 | Clorita |
| Carga < 0.10, octa 1.6–2.5 | Pirofilita o talco según Mg |
| Carga 0.10–0.60, Mg octa > 1.0 | Esmectita trioctaédrica |
| Carga 0.10–0.60, Fe > 0.5 | Nontronita |
| Carga 0.10–0.60, Al tetra > 0.30 | Beidellita |
| Carga 0.10–0.60 (resto) | Montmorillonita |
| Carga 0.60–1.20, K ≥ 0.50 | Illita / mica |
| Carga 0.60–1.20, Ca+Na ≥ 0.30 | Vermiculita |
| Carga > 1.20 | Filosilicato de carga alta / interestratificado |

Las fronteras son orientativas: las arcillas naturales suelen ser
interestratificadas y los análisis no distinguen capas.

## Redondeo

- Cationes y oxígeno/OH en el detalle: 4 decimales.
- Carga de capa: 3 decimales.
- Fórmula estructural: 2 decimales.
- Factor de escala y oxígeno total: 6 decimales.

## Validaciones y advertencias

- Se requiere SiO2 y/o Al2O3 > 0, y LOI > 0 (mínimo 0.1 %).
- Suma de óxidos + LOI > 102 % o < 90 % → advertencia (base húmeda/seca, faltantes).
- Exceso de Si tetraédrico → probable cuarzo libre; fórmula sobreestimada.
- Cationes octaédricos en exceso sobre los sitios de la base → advertencia.
- Sitios tetraédricos incompletos → revisar base o análisis.
- Diferencia carga de capa vs. interlaminares > 0.15 eq → advertencia.
- P2O5 > 0.1 % → probable apatita u otra impureza; no se asigna a la estructura.
- LOI > 25 % → posible carbonato, materia orgánica o agua interlamilar.
- Todo el hierro como Fe2O3 con Fe2O3 > 2 % → advertencia por estado de oxidación.

## Supuestos y limitaciones

- **LOI = agua estructural.** En esmectitas la mayor parte de la LOI es agua
  interlamilar, no estructural; esto infla los OH y distorsiona la normalización.
  Para un cálculo riguroso se requiere H2O+ determinado o análisis TGA.
- **Estado de oxidación del hierro:** se captura Fe2O3 y FeO por separado; si no
  se analiza FeO, se asume todo Fe3+, lo que cambia la carga de capa.
- **Método de normalización:** la elección de base cambia la fórmula mostrada
  (media celda vs celda completa) pero no la clasificación por O10. La detección
  automática 1:1/2:1 es heurística y puede fallar con interestratificados,
  cloritas o muestras con mucho cuarzo/alúmina libre.
- **No calcula la distribución real de capas** (illita/esmectita, clorita,
  interestratificación), ni el grado de orden, ni la composición de la capa
  tetraédrica por mineral.
- Las impurezas (cuarzo, feldespato, carbonatos, óxidos de hierro) contaminan el
  análisis y deben corregirse antes de usar la fórmula.
- La fórmula estructural no sustituye XRD, FTIR, DSC/TGA ni el análisis químico
  completo de laboratorio.
- El reparto tetraédrico supone llenar primero con Si y luego con Al; cuando hay
  exceso de Si o de Al, el exceso se reporta y advierte.

## Fuentes

- Wikipedia, *Clay mineral*: <https://en.wikipedia.org/wiki/Clay_mineral>
- Wikipedia, *Kaolinite*: <https://en.wikipedia.org/wiki/Kaolinite>
- Wikipedia, *Montmorillonite*: <https://en.wikipedia.org/wiki/Montmorillonite>
- Wikipedia, *Illite*: <https://en.wikipedia.org/wiki/Illite>
- Wikipedia, *Phyllosilicate*: <https://en.wikipedia.org/wiki/Phyllosilicate>
- Wikipedia, *Formula unit*: <https://en.wikipedia.org/wiki/Formula_unit>
- The Clay Minerals Society: <https://www.clays.org/>

## Pruebas asociadas

Grupo «Fórmula estructural de arcillas»: caolinita automática O10(OH)8, caolinita
forzada O5(OH)4, montmorillonita automática O10(OH)2, illita O10(OH)2, advertencia
de análisis incompleto, errores sin SiO2/Al2O3 y LOI cero, y JSON.
