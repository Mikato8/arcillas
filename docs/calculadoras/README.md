# Calculadoras técnicas · Arcillas MX

Motor de cálculo puro en PHP 8.3+ para las calculadoras públicas del sitio
`arcillas.mx`. Convierte recetas, análisis de laboratorio y datos comerciales en
resultados técnicos explicables, sin base de datos, sin sesión y sin red.

- **Código:** `app/Services/Calculators/` (namespace `App\Services\Calculators`).
- **Pruebas:** `php tests/calculators.php` (runner propio, sin PHPUnit).
- **Documentación por calculadora:** este directorio, un archivo por slug.
- **Rutas públicas sugeridas:** `/calculadoras` (índice) y
  `/calculadoras/{slug}` (formulario y resultado).

Todas las calculadoras son deterministas: la misma entrada produce exactamente
la misma salida, con redondeo explícito y sin `NAN`/`INF` en los resultados.

## Las 8 calculadoras

| Slug | Nombre | Categoría | Documento |
|---|---|---|---|
| `umf-esmalte` | Fórmula molecular unitaria (UMF / Seger) | Cerámica y esmaltes | [umf-esmalte.md](umf-esmalte.md) |
| `mezcla-arcillas` | Mezcla de arcillas por objetivos | Arcillas y pastas | [mezcla-arcillas.md](mezcla-arcillas.md) |
| `contraccion-absorcion` | Contracción y absorción de una pasta | Cerámica y esmaltes | [contraccion-absorcion.md](contraccion-absorcion.md) |
| `balance-metalurgico` | Balance metalúrgico no ferroso | Metalurgia no ferrosa | [balance-metalurgico.md](balance-metalurgico.md) |
| `bentonita-arena-verde` | Bentonita y agua para arena verde | Fundición | [bentonita-arena-verde.md](bentonita-arena-verde.md) |
| `granulometria-stokes` | Granulometría y velocidad de Stokes | Mineralogía y procesos | [granulometria-stokes.md](granulometria-stokes.md) |
| `precio-normalizado` | Precio normalizado y costo landed | Comercial | [precio-normalizado.md](precio-normalizado.md) |
| `formula-estructural-arcillas` | Fórmula estructural de arcillas | Mineralogía y procesos | [formula-estructural-arcillas.md](formula-estructural-arcillas.md) |

Los slugs son estables y están en kebab-case porque se usan en la URL, en la
tabla `calculator_runs.calculadora` y en los enlaces desde las fichas de
material. **No cambiar un slug sin migrar datos y redirecciones.**

## Uso desde PHP

```php
use App\Services\Calculators\CalculatorRegistry;

$calculadora = CalculatorRegistry::obtenerPorSlug('umf-esmalte');

// Esquema declarativo para generar el formulario HTML.
$campos = $calculadora->campos();

try {
    $resultado = $calculadora->calcular($_POST);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    // Error de captura: mostrar $e->getMessage() junto al formulario (HTTP 422).
    echo $e->getMessage();
}
```

`CalculatorRegistry::all()` devuelve las calculadoras indexadas por slug;
`obtenerPorSlug()` devuelve `null` si el slug no existe (para responder 404);
`catalogo()` devuelve un arreglo serializable con metadatos, ruta y campos para
construir el índice `/calculadoras`; `categorias()` las agrupa por categoría.

## Esquema de `campos()`

Cada campo es un arreglo con **siempre** estas llaves:

| Llave | Tipo | Descripción |
|---|---|---|
| `clave` | string | Identificador único (snake_case). |
| `etiqueta` | string | Texto visible. |
| `tipo` | string | `number`, `select` o `text`. |
| `unidad` | string | Unidad visible; `''` si no aplica. |
| `min` | float/null | Mínimo permitido (solo `number`). |
| `max` | float/null | Máximo permitido (solo `number`). |
| `paso` | float/null | Incremento sugerido (solo `number`). |
| `requerido` | bool | Si el endpoint debe exigirlo. |
| `defecto` | mixed | Valor inicial sugerido. |
| `opciones` | array | Solo `select`: `[['valor'=>string,'etiqueta'=>string], …]`. |
| `ayuda` | string | Texto de ayuda bajo el campo. |
| `grupo` | string | Sección o pestaña sugerida para el formulario. |

La validación del motor se deriva del mismo esquema: `min`/`max`, opciones de
`select`, campos obligatorios y tipos se cumplen igual en el formulario, en el
endpoint y en `calcular()`.

## Contrato de entrada

- Acepta números (`int`/`float`) y cadenas numéricas, incluida la **coma
  decimal** (`"1,5"`) y notación científica (`"1e3"`).
- Un campo vacío usa `defecto`; si no hay defecto y es obligatorio lanza
  `InvalidArgumentException` con un mensaje en español.
- Los valores no numéricos, fuera de rango, negativos no permitidos o
  selecciones inválidas lanzan `InvalidArgumentException` con el nombre del
  campo en el mensaje.
- Algunas calculadoras aceptan además listas estructuradas para capturas
  dinámicas: `receta` (UMF), `arcillas`/`objetivos` (mezcla) y `mallas`
  (granulometría). Ver cada documento.
- **Reglas cruzadas** que el esquema de campos no puede expresar se validan
  dentro de `calcular()` y también lanzan `InvalidArgumentException`: al menos
  un material en la receta UMF, `concentrado > alimentación > colas` en el
  balance, ambos pesos para la absorción, monotonía de las mallas, etc. El
  endpoint puede limitarse a llamar a `calcular()` y devolver el mensaje de
  error (HTTP 422).
- Los métodos concretos que extienden `AbstractCalculator` exponen además
  `validar(array $entrada): array`, que normaliza y valida sin calcular. No forma
  parte de `CalculatorInterface`, pero es útil para depurar el endpoint.

## Contrato de salida

```php
[
  'resultados' => [
    ['etiqueta' => 'SiO2', 'valor' => 6.9200, 'unidad' => 'mol', 'destacado' => true],
    // …
  ],
  'detalle' => [
    ['etiqueta' => 'Factor de normalización (1 / ΣRO)', 'valor' => 6.1597, 'unidad' => ''],
    // …
  ],
  'advertencias' => ['Relación Si:Al baja (< 4): …'],
  'formulas' => ['Fórmula unitaria = n(óxido) / Σ n(RO); por definición ΣRO = 1'],
]
```

Solo contiene escalares y arreglos (serializable con `json_encode`). Los valores
`float` ya vienen redondeados por cada calculadora; los percentiles que no se
pueden interpolar se devuelven como `null` y generan una advertencia.

## Redondeo

| Calculadora | Resultados principales | Detalle |
|---|---|---|
| UMF | moles a 4 decimales; Si:Al a 3 | moles sin normalizar a 6 |
| Mezcla de arcillas | proporciones y composición a 2 | desviaciones a 3; ECM a 4 |
| Contracción / absorción | porcentajes y longitudes a 2 | factor de molde a 4 |
| Balance metalúrgico | recuperación y razón a 3; masas a 4; importes a 2 | leyes a 4 |
| Bentonita | kg a 3; kg/t a 3 | factor de humedad a 5 |
| Granulometría | diámetros y coeficientes a 2; Stokes a 4 cm/s | Reynolds a 4 |
| Precio normalizado | MXN/USD a 2; por óxido a 2 | USD/lb a 4; factor a 6 |
| Fórmula estructural | cationes a 4; carga a 3; fórmula a 2 | escala a 6 |

## Pruebas

```bash
php tests/calculators.php
```

- Runner propio con asserts y tolerancias; imprime `[OK]`/`[FALLO]` por caso.
- Cubre las 8 calculadoras, casos límite, errores esperados, serialización JSON,
  determinismo y coherencia del esquema declarativo.
- Devuelve **exit 0** si todo pasa y **exit 1** si hay fallos.
- No requiere base de datos ni configuración del sitio.

Resultado actual: **321 asserts, 0 fallos, 8/8 calculadoras**.

## Convenciones y supuestos generales

1. **Unidades del sistema:** masas en kg o t (según la calculadora), longitudes
   en mm o µm, porcentajes en % en peso, moles en mol, importes en MXN o USD.
2. **Base de los análisis:** se interpretan como % en peso tal como se reciben,
   salvo que el campo indique “seco”.
3. **UMF / Seger:** los fundentes RO (Na2O, K2O, Li2O, CaO, MgO, ZnO, CoO) se
   normalizan a **ΣRO = 1**; Al2O3, B2O3, Fe2O3, SiO2, TiO2 y ZrO2 se reportan
   como moles relativos a esa base (convención estándar de Digitalfire/Glazy).
4. **LOI:** se interpreta como pérdida por ignición; en UMF no aporta moles de
   óxidos y en la fórmula estructural se asume agua estructural.
5. **Resultados orientativos:** las clasificaciones (esmalte, contracción,
   graduación, mineral, arena verde) son heurísticas documentadas; no sustituyen
   un ensayo de laboratorio ni la ficha técnica del proveedor.
6. **Sin estado:** no hay caché, sesión ni escritura de archivos. El Lead puede
   registrar cada ejecución en `calculator_runs` desde el controlador.

## Fuentes generales

- Digitalfire, *Unity formula*: <https://digitalfire.com/glossary/unity+formula>
- Digitalfire, *Seger formula*: <https://digitalfire.com/glossary/seger+formula>
- Digitalfire, *Silica:alumina ratio*: <https://digitalfire.com/glossary/silica:alumina+ratio>
- Wikipedia, *Stokes' law*: <https://en.wikipedia.org/wiki/Stokes%27_law>
- 911 Metallurgist, *Metallurgical accounting formulas*:
  <https://www.911metallurgist.com/blog/metallurgical-accounting-formulas/>
- Wikipedia, *Clay mineral*: <https://en.wikipedia.org/wiki/Clay_mineral>
- Wikipedia, *Green sand*: <https://en.wikipedia.org/wiki/Green_sand>

Cada documento incluye las fuentes específicas y sus limitaciones.

## Cómo agregar una calculadora

1. Crear `app/Services/Calculators/MiCalculadora.php` que extienda
   `AbstractCalculator` (o implemente `CalculatorInterface`).
2. Definir metadatos, `campos()` y `calcular()`; redondear explícitamente y no
   devolver `NAN`/`INF`.
3. Registrarla en `CalculatorRegistry::all()` con un slug nuevo en kebab-case.
4. Agregar casos a `tests/calculators.php` (camino feliz, límites y errores).
5. Documentar fórmulas, unidades, ejemplo, fuentes y limitaciones en
   `docs/calculadoras/`.
6. Actualizar la tabla de este README y la navegación del sitio.

## Advertencia

Esta documentación y el motor implementan fórmulas de ingeniería ampliamente
usadas, pero los datos de entrada son responsabilidad del usuario. Los
resultados no constituyen una garantía de comportamiento en horno, fundición,
planta metalúrgica ni en una transacción comercial; valida siempre con
laboratorio, pruebas piloto y cotización formal.