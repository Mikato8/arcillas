<?php
declare(strict_types=1);

/**
 * Pruebas del motor de calculadoras de Arcillas MX.
 *
 * Uso:  php tests/calculators.php
 *
 * Runner propio, sin PHPUnit ni Composer: define asserts con tolerancia,
 * recorre las 8 calculadoras y devuelve código de salida 1 si algo falla.
 * No necesita base de datos, sesión, red ni configuración del sitio.
 */

$raiz = dirname(__DIR__);

spl_autoload_register(static function (string $clase) use ($raiz): void {
    $prefijo = 'App\\Services\\Calculators\\';
    if (!str_starts_with($clase, $prefijo)) {
        return;
    }
    $relativa = substr($clase, strlen($prefijo));
    $archivo = $raiz . '/app/Services/Calculators/' . str_replace('\\', '/', $relativa) . '.php';
    if (is_file($archivo)) {
        require $archivo;
    }
});

use App\Services\Calculators\BentoniteSandCalculator;
use App\Services\Calculators\CalculatorRegistry;
use App\Services\Calculators\ClayBlendCalculator;
use App\Services\Calculators\MetalBalanceCalculator;
use App\Services\Calculators\MineralFormulaCalculator;
use App\Services\Calculators\ParticleSizeCalculator;
use App\Services\Calculators\PriceNormalizationCalculator;
use App\Services\Calculators\ShrinkageCalculator;
use App\Services\Calculators\UmfCalculator;

$GLOBALS['_pruebas'] = 0;
$GLOBALS['_fallos'] = [];

// ─────────────────────────────────────────────────────────────────────
// Runner
// ─────────────────────────────────────────────────────────────────────

function grupo(string $nombre): void
{
    echo PHP_EOL . '── ' . $nombre . ' ' . str_repeat('─', 8) . PHP_EOL;
}

function _ok(string $mensaje): void
{
    $GLOBALS['_pruebas']++;
    echo '  [OK]    ' . $mensaje . PHP_EOL;
}

function _fallo(string $mensaje, string $detalle = ''): void
{
    $GLOBALS['_pruebas']++;
    $texto = $mensaje . ($detalle !== '' ? ' → ' . $detalle : '');
    $GLOBALS['_fallos'][] = $texto;
    echo '  [FALLO] ' . $texto . PHP_EOL;
}

function _repr(mixed $valor): string
{
    if (is_float($valor)) {
        return rtrim(rtrim(number_format($valor, 6, '.', ''), '0'), '.');
    }
    if (is_string($valor)) {
        return '«' . $valor . '»';
    }

    return var_export($valor, true);
}

function verificar(bool $condicion, string $mensaje, string $detalle = ''): void
{
    if ($condicion) {
        _ok($mensaje);

        return;
    }
    _fallo($mensaje, $detalle);
}

function igualA(mixed $esperado, mixed $real, string $mensaje): void
{
    verificar(
        $esperado === $real,
        $mensaje,
        'esperado ' . _repr($esperado) . ', real ' . _repr($real)
    );
}

function cercaDe(float $esperado, mixed $real, float $tolerancia, string $mensaje): void
{
    if (!is_int($real) && !is_float($real)) {
        _fallo($mensaje, 'se esperaba un número y se obtuvo ' . _repr($real));

        return;
    }
    $diferencia = abs($esperado - (float) $real);
    verificar(
        $diferencia <= $tolerancia,
        $mensaje,
        'esperado ' . _repr($esperado) . ' ± ' . _repr($tolerancia) . ', real ' . _repr($real)
    );
}

function esNulo(mixed $valor, string $mensaje): void
{
    verificar($valor === null, $mensaje, 'real ' . _repr($valor));
}

function contiene(string $aguja, string $pajar, string $mensaje): void
{
    verificar(str_contains($pajar, $aguja), $mensaje, 'texto real: ' . _repr($pajar));
}

function lanza(callable $fn, string $mensaje, ?string $fragmento = null): void
{
    try {
        $fn();
        _fallo($mensaje, 'no se lanzó ninguna excepción');
    } catch (InvalidArgumentException $e) {
        if ($fragmento === null) {
            _ok($mensaje);

            return;
        }
        verificar(
            str_contains($e->getMessage(), $fragmento),
            $mensaje,
            'mensaje real: ' . _repr($e->getMessage())
        );
    } catch (Throwable $e) {
        _fallo($mensaje, 'excepción inesperada ' . get_class($e) . ': ' . $e->getMessage());
    }
}

function ejecutarGrupo(string $nombre, callable $fn): void
{
    grupo($nombre);
    try {
        $fn();
    } catch (Throwable $e) {
        _fallo(
            'Excepción inesperada en el grupo',
            get_class($e) . ': ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')'
        );
    }
}

/** @param array<string,mixed> $salida */
function valorResultado(array $salida, string $etiqueta): mixed
{
    foreach ($salida['resultados'] as $fila) {
        if ($fila['etiqueta'] === $etiqueta) {
            return $fila['valor'];
        }
    }
    _fallo('Falta el resultado «' . $etiqueta . '»', 'resultados: '
        . implode(', ', array_column($salida['resultados'], 'etiqueta')));

    return null;
}

/** @param array<string,mixed> $salida */
function existeResultado(array $salida, string $etiqueta): bool
{
    foreach ($salida['resultados'] as $fila) {
        if ($fila['etiqueta'] === $etiqueta) {
            return true;
        }
    }

    return false;
}

/** @param array<string,mixed> $salida */
function valorDetalle(array $salida, string $etiqueta): mixed
{
    foreach ($salida['detalle'] as $fila) {
        if ($fila['etiqueta'] === $etiqueta) {
            return $fila['valor'];
        }
    }
    _fallo('Falta el detalle «' . $etiqueta . '»', 'detalle: '
        . implode(', ', array_column($salida['detalle'], 'etiqueta')));

    return null;
}

function jsonValido(array $salida): bool
{
    $json = json_encode($salida, JSON_UNESCAPED_UNICODE);

    return is_string($json) && json_decode($json, true) !== null;
}

// ─────────────────────────────────────────────────────────────────────
// 1. Registro, esquema y catálogo
// ─────────────────────────────────────────────────────────────────────

ejecutarGrupo('Registro y esquema declarativo', static function (): void {
    $esperados = [
        'umf-esmalte',
        'mezcla-arcillas',
        'contraccion-absorcion',
        'balance-metalurgico',
        'bentonita-arena-verde',
        'granulometria-stokes',
        'precio-normalizado',
        'formula-estructural-arcillas',
    ];

    igualA(8, CalculatorRegistry::total(), 'El registro contiene exactamente 8 calculadoras');
    igualA($esperados, CalculatorRegistry::slugs(), 'Los slugs coinciden con la lista estable del sitio');

    $todas = CalculatorRegistry::all();
    igualA($esperados, array_keys($todas), 'all() está indexado por slug');

    foreach ($esperados as $slug) {
        $calculadora = CalculatorRegistry::obtenerPorSlug($slug);
        igualA($slug, $calculadora->slug(), 'obtenerPorSlug("' . $slug . '") devuelve la instancia correcta');
        verificar(
            $calculadora->nombre() !== '' && $calculadora->categoria() !== '' && $calculadora->descripcion() !== '',
            'Metadatos completos para ' . $slug
        );
        verificar(
            preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $calculadora->slug()) === 1,
            'Slug en kebab-case: ' . $slug
        );

        $campos = $calculadora->campos();
        verificar($campos !== [], 'La calculadora ' . $slug . ' declara campos');

        $claves = [];
        $clavesObligatorias = [
            'clave', 'etiqueta', 'tipo', 'unidad', 'min', 'max', 'paso',
            'requerido', 'defecto', 'opciones', 'ayuda', 'grupo',
        ];
        $esquemaCompleto = true;
        $tiposValidos = true;
        $rangosValidos = true;
        $clavesUnicas = true;
        foreach ($campos as $campo) {
            if (array_keys($campo) !== $clavesObligatorias) {
                $esquemaCompleto = false;
            }
            if (!in_array($campo['tipo'], ['number', 'select', 'text'], true)) {
                $tiposValidos = false;
            }
            if ($campo['tipo'] === 'number') {
                if (!is_numeric($campo['min']) || !is_numeric($campo['max'])
                    || (float) $campo['min'] >= (float) $campo['max']
                    || !is_numeric($campo['paso']) || (float) $campo['paso'] <= 0.0) {
                    $rangosValidos = false;
                }
                if ($campo['defecto'] !== null
                    && ((float) $campo['defecto'] < (float) $campo['min']
                        || (float) $campo['defecto'] > (float) $campo['max'])) {
                    $rangosValidos = false;
                }
            }
            if ($campo['tipo'] === 'select') {
                if ($campo['opciones'] === []) {
                    $rangosValidos = false;
                }
                $valores = array_column($campo['opciones'], 'valor');
                if ($campo['defecto'] !== null && !in_array((string) $campo['defecto'], $valores, true)) {
                    $rangosValidos = false;
                }
                foreach ($campo['opciones'] as $opcion) {
                    if (!isset($opcion['valor'], $opcion['etiqueta'])
                        || !is_string($opcion['valor']) || !is_string($opcion['etiqueta'])) {
                        $rangosValidos = false;
                    }
                }
            }
            if (in_array($campo['clave'], $claves, true)) {
                $clavesUnicas = false;
            }
            $claves[] = $campo['clave'];
            if (!is_string($campo['ayuda'])) {
                $esquemaCompleto = false;
            }
        }
        verificar($esquemaCompleto, 'Esquema completo (12 claves) en ' . $slug);
        verificar($tiposValidos, 'Tipos number/select/text válidos en ' . $slug);
        verificar($rangosValidos, 'Min/max/paso/defecto/opciones coherentes en ' . $slug);
        verificar($clavesUnicas, 'Claves de campo únicas en ' . $slug);
    }

    verificar(
        CalculatorRegistry::obtenerPorSlug('no-existe') === null,
        'Un slug desconocido devuelve null (la ruta responde 404)'
    );
    verificar(
        CalculatorRegistry::obtenerPorSlug('') === null,
        'Un slug vacío devuelve null'
    );

    $catalogo = CalculatorRegistry::catalogo();
    igualA(8, count($catalogo), 'El catálogo incluye las 8 calculadoras');
    igualA('/calculadoras/balance-metalurgico', $catalogo['balance-metalurgico']['ruta'], 'El catálogo expone la ruta pública');
    verificar(json_encode($catalogo, JSON_UNESCAPED_UNICODE) !== false, 'El catálogo es serializable a JSON');

    $categorias = CalculatorRegistry::categorias();
    igualA(8, array_sum(array_map('count', $categorias)), 'Las categorías agrupan las 8 calculadoras sin perder ninguna');
});

// ─────────────────────────────────────────────────────────────────────
// 2. UMF / Seger
// ─────────────────────────────────────────────────────────────────────

ejecutarGrupo('UMF / Seger', static function (): void {
    $calc = new UmfCalculator();

    $fsp = $calc->calcular(['mat_feldespato_potasico' => 100]);
    cercaDe(6.9200, valorResultado($fsp, 'SiO2'), 0.005, 'UMF de feldespato: SiO2 ≈ 6.9200 (ΣRO = 1)');
    cercaDe(1.0995, valorResultado($fsp, 'Al2O3'), 0.005, 'UMF de feldespato: Al2O3 ≈ 1.0995');
    cercaDe(0.6866, valorResultado($fsp, 'K2O'), 0.005, 'UMF de feldespato: K2O ≈ 0.6866');
    cercaDe(0.2783, valorResultado($fsp, 'Na2O'), 0.005, 'UMF de feldespato: Na2O ≈ 0.2783');
    cercaDe(0.0275, valorResultado($fsp, 'CaO'), 0.001, 'UMF de feldespato: CaO ≈ 0.0275');
    cercaDe(0.0076, valorResultado($fsp, 'MgO'), 0.001, 'UMF de feldespato: MgO ≈ 0.0076');
    cercaDe(0.0046, valorResultado($fsp, 'Fe2O3'), 0.001, 'UMF de feldespato: Fe2O3 ≈ 0.0046');
    cercaDe(0.0023, valorResultado($fsp, 'TiO2'), 0.001, 'UMF de feldespato: TiO2 ≈ 0.0023');
    cercaDe(6.294, valorResultado($fsp, 'Relación Si:Al'), 0.01, 'UMF de feldespato: relación Si:Al ≈ 6.294');
    cercaDe(0.0, valorResultado($fsp, 'B2O3'), 1.0e-9, 'UMF de feldespato: B2O3 = 0');
    cercaDe(0.0, valorResultado($fsp, 'ZrO2'), 1.0e-9, 'UMF de feldespato: ZrO2 = 0');

    $sumaRo = 0.0;
    foreach (['K2O', 'Na2O', 'CaO', 'MgO'] as $oxido) {
        $sumaRo += (float) valorResultado($fsp, $oxido);
    }
    cercaDe(1.0, $sumaRo, 0.002, 'ΣRO normalizada = 1 (suma de las filas redondeadas de fundentes)');
    cercaDe(1.0, valorDetalle($fsp, 'ΣRO normalizada'), 1.0e-6, 'El detalle reporta ΣRO normalizada = 1.000000');
    contiene('Aluminoso', (string) valorResultado($fsp, 'Carácter del esmalte (Si:Al)'), 'Clasificación: esmalte aluminoso');
    contiene('Alcalino', (string) valorResultado($fsp, 'Tipo de fundente dominante'), 'Clasificación: fundente alcalino');

    $esmalte = $calc->calcular([
        'mat_feldespato_potasico' => 50,
        'mat_caolin'              => 25,
        'mat_silice'              => 25,
    ]);
    cercaDe(13.9478, valorResultado($esmalte, 'SiO2'), 0.01, 'Esmalte sencillo: SiO2 ≈ 13.9478');
    cercaDe(2.1704, valorResultado($esmalte, 'Al2O3'), 0.005, 'Esmalte sencillo: Al2O3 ≈ 2.1704');
    cercaDe(0.6733, valorResultado($esmalte, 'K2O'), 0.005, 'Esmalte sencillo: K2O ≈ 0.6733');
    cercaDe(0.2745, valorResultado($esmalte, 'Na2O'), 0.005, 'Esmalte sencillo: Na2O ≈ 0.2745');
    cercaDe(6.426, valorResultado($esmalte, 'Relación Si:Al'), 0.01, 'Esmalte sencillo: Si:Al ≈ 6.426');

    $ejemplo40 = $calc->calcular([
        'mat_feldespato_potasico' => 40,
        'mat_caolin'              => 20,
        'mat_silice'              => 20,
        'mat_carbonato_calcio'    => 20,
    ]);
    cercaDe(3.5041, valorResultado($ejemplo40, 'SiO2'), 0.01, 'Ejemplo 40/20/20/20: SiO2 ≈ 3.5041');
    cercaDe(0.5453, valorResultado($ejemplo40, 'Al2O3'), 0.005, 'Ejemplo 40/20/20/20: Al2O3 ≈ 0.5453');
    cercaDe(0.7571, valorResultado($ejemplo40, 'CaO'), 0.005, 'Ejemplo 40/20/20/20: CaO ≈ 0.7571');
    cercaDe(0.1691, valorResultado($ejemplo40, 'K2O'), 0.005, 'Ejemplo 40/20/20/20: K2O ≈ 0.1691');
    cercaDe(0.0690, valorResultado($ejemplo40, 'Na2O'), 0.005, 'Ejemplo 40/20/20/20: Na2O ≈ 0.0690');
    cercaDe(6.426, valorResultado($ejemplo40, 'Relación Si:Al'), 0.01, 'Ejemplo 40/20/20/20: Si:Al ≈ 6.426');
    $sumaRo40 = (float) valorResultado($ejemplo40, 'K2O')
        + (float) valorResultado($ejemplo40, 'Na2O')
        + (float) valorResultado($ejemplo40, 'CaO')
        + (float) valorResultado($ejemplo40, 'MgO');
    cercaDe(1.0, $sumaRo40, 0.002, 'Ejemplo 40/20/20/20: ΣRO = 1');

    // Caso de referencia del investigador (Digitalfire/Glazy): la fórmula
    // normalizada tiene K2O 0.16769, CaO 0.83231, Al2O3 0.32528, SiO2 2.29235
    // y Si:Al 7.0473. Se entrega escalada ×2.5 para comprobar que la
    // normalización a ΣRO = 1 elimina la escala.
    $referencia = $calc->normalizarFormula([
        'K2O'   => 0.16769 * 2.5,
        'CaO'   => 0.83231 * 2.5,
        'Al2O3' => 0.32528 * 2.5,
        'SiO2'  => 2.29235 * 2.5,
    ]);
    cercaDe(0.16769, $referencia['K2O'], 1.0e-9, 'Referencia investigador: K2O = 0.16769');
    cercaDe(0.83231, $referencia['CaO'], 1.0e-9, 'Referencia investigador: CaO = 0.83231');
    cercaDe(0.32528, $referencia['Al2O3'], 1.0e-9, 'Referencia investigador: Al2O3 = 0.32528');
    cercaDe(2.29235, $referencia['SiO2'], 1.0e-9, 'Referencia investigador: SiO2 = 2.29235');
    cercaDe(1.0, $referencia['K2O'] + $referencia['CaO'], 1.0e-9, 'Referencia investigador: ΣRO = 1');
    cercaDe(7.0473, $referencia['SiO2'] / $referencia['Al2O3'], 1.0e-4, 'Referencia investigador: Si:Al = 7.0473');

    $referenciaEscalada = $calc->normalizarFormula([
        'K2O'   => 0.16769 * 10.0,
        'CaO'   => 0.83231 * 10.0,
        'Al2O3' => 0.32528 * 10.0,
        'SiO2'  => 2.29235 * 10.0,
    ]);
    cercaDe(
        $referencia['SiO2'],
        $referenciaEscalada['SiO2'],
        1.0e-12,
        'normalizarFormula() es invariante a la escala de moles'
    );

    $doble = $calc->calcular([
        'mat_feldespato_potasico' => 100,
        'mat_caolin'              => 50,
        'mat_silice'              => 50,
    ]);
    cercaDe(
        (float) valorResultado($esmalte, 'SiO2'),
        valorResultado($doble, 'SiO2'),
        1.0e-9,
        'La UMF no depende de la escala de la receta'
    );

    $conReceta = $calc->calcular(['receta' => ['feldespato_potasico' => 50, 'caolin' => 25, 'silice' => 25]]);
    cercaDe(
        (float) valorResultado($esmalte, 'Al2O3'),
        valorResultado($conReceta, 'Al2O3'),
        1.0e-9,
        'Acepta la receta como mapa material => cantidad'
    );
    $conLista = $calc->calcular(['receta' => [
        ['material' => 'feldespato_potasico', 'cantidad' => 50],
        ['material' => 'caolin', 'cantidad' => 25],
        ['material' => 'silice', 'cantidad' => 25],
    ]]);
    cercaDe(
        (float) valorResultado($esmalte, 'SiO2'),
        valorResultado($conLista, 'SiO2'),
        1.0e-9,
        'Acepta la receta como lista de {material, cantidad}'
    );

    $conComa = $calc->calcular(['mat_feldespato_potasico' => '100,0']);
    cercaDe(6.9200, valorResultado($conComa, 'SiO2'), 0.005, 'Acepta coma decimal en campos numéricos');

    $conLitio = $calc->calcular(['mat_feldespato_potasico' => 90, 'mat_carbonato_litio' => 10]);
    verificar(valorResultado($conLitio, 'Li2O') !== null, 'Reporta óxidos fuera de la lista estándar (Li2O)');

    lanza(
        static fn () => $calc->calcular(['receta' => []]),
        'Receta vacía lanza InvalidArgumentException',
        'receta'
    );
    lanza(
        static fn () => $calc->calcular(['receta' => ['material_inventado' => 10]]),
        'Material desconocido lanza InvalidArgumentException',
        'biblioteca'
    );
    lanza(
        static fn () => $calc->calcular(['receta' => ['caolin' => -5]]),
        'Cantidad negativa lanza InvalidArgumentException',
        'negativa'
    );
    lanza(
        static fn () => $calc->calcular(['mat_caolin' => 0]),
        'Receta sin cantidades mayores que cero lanza InvalidArgumentException',
        'al menos un material'
    );
    lanza(
        static fn () => $calc->normalizarFormula([]),
        'normalizarFormula() sin óxidos lanza InvalidArgumentException',
        'al menos un óxido'
    );
    lanza(
        static fn () => $calc->normalizarFormula(['OXIDO_FALSO' => 1.0]),
        'normalizarFormula() con óxido desconocido lanza InvalidArgumentException',
        'no está en la biblioteca'
    );
    lanza(
        static fn () => $calc->normalizarFormula(['K2O' => -0.1, 'SiO2' => 1.0]),
        'normalizarFormula() con moles negativos lanza InvalidArgumentException',
        'negativos'
    );

    verificar(jsonValido($fsp), 'La salida de UMF es serializable a JSON');
    igualA(
        json_encode($fsp),
        json_encode($calc->calcular(['mat_feldespato_potasico' => 100])),
        'UMF es determinista'
    );
});

// ─────────────────────────────────────────────────────────────────────
// 3. Mezcla de arcillas
// ─────────────────────────────────────────────────────────────────────

ejecutarGrupo('Mezcla de arcillas', static function (): void {
    $calc = new ClayBlendCalculator();
    $arcillas = [
        ['nombre' => 'A', 'sio2' => 60.0, 'al2o3' => 25.0, 'fe2o3' => 1.0, 'loi' => 10.0],
        ['nombre' => 'B', 'sio2' => 40.0, 'al2o3' => 35.0, 'fe2o3' => 2.0, 'loi' => 20.0],
    ];

    $exacta = $calc->calcular([
        'arcillas'  => $arcillas,
        'objetivos' => ['sio2' => 50.0, 'al2o3' => 30.0, 'fe2o3' => 1.5, 'loi' => 15.0],
    ]);
    cercaDe(50.0, valorResultado($exacta, 'A'), 0.01, 'Mezcla exacta A/B: 50 % de A');
    cercaDe(50.0, valorResultado($exacta, 'B'), 0.01, 'Mezcla exacta A/B: 50 % de B');
    cercaDe(50.0, valorResultado($exacta, 'SiO2 de la mezcla'), 0.01, 'Mezcla exacta: SiO2 = 50 %');
    cercaDe(30.0, valorResultado($exacta, 'Al2O3 de la mezcla'), 0.01, 'Mezcla exacta: Al2O3 = 30 %');
    cercaDe(1.5, valorResultado($exacta, 'Fe2O3 de la mezcla'), 0.01, 'Mezcla exacta: Fe2O3 = 1.5 %');
    cercaDe(15.0, valorResultado($exacta, 'LOI de la mezcla'), 0.01, 'Mezcla exacta: LOI = 15 %');
    cercaDe(0.0, valorDetalle($exacta, 'Desviación absoluta máxima'), 0.001, 'Mezcla exacta: desviación máxima = 0');

    $vertice = $calc->calcular([
        'arcillas'  => $arcillas,
        'objetivos' => ['sio2' => 60.0, 'al2o3' => 25.0, 'fe2o3' => 1.0, 'loi' => 10.0],
    ]);
    cercaDe(100.0, valorResultado($vertice, 'A'), 0.5, 'Objetivo igual a la arcilla A: proporción 100 %');
    cercaDe(0.0, valorResultado($vertice, 'B'), 0.5, 'Objetivo igual a la arcilla A: proporción B ≈ 0 %');
    verificar(count($vertice['advertencias']) > 0, 'La solución en un vértice genera advertencias');

    $tres = $calc->calcular([
        'arcillas' => [
            ['nombre' => 'A', 'sio2' => 60.0, 'al2o3' => 25.0, 'fe2o3' => 1.0, 'loi' => 10.0],
            ['nombre' => 'B', 'sio2' => 40.0, 'al2o3' => 35.0, 'fe2o3' => 2.0, 'loi' => 20.0],
            ['nombre' => 'C', 'sio2' => 50.0, 'al2o3' => 20.0, 'fe2o3' => 7.0, 'loi' => 8.0],
        ],
        'objetivos' => ['sio2' => 52.5, 'al2o3' => 26.25, 'fe2o3' => 2.75, 'loi' => 12.0],
    ]);
    cercaDe(50.0, valorResultado($tres, 'A'), 0.5, 'Mezcla de 3 arcillas: A ≈ 50 %');
    cercaDe(25.0, valorResultado($tres, 'B'), 0.5, 'Mezcla de 3 arcillas: B ≈ 25 %');
    cercaDe(25.0, valorResultado($tres, 'C'), 0.5, 'Mezcla de 3 arcillas: C ≈ 25 %');

    $inalcanzable = $calc->calcular([
        'arcillas'  => $arcillas,
        'objetivos' => ['sio2' => 90.0, 'al2o3' => 30.0, 'fe2o3' => 1.5, 'loi' => 15.0],
    ]);
    $tieneAviso = false;
    foreach ($inalcanzable['advertencias'] as $aviso) {
        if (str_contains($aviso, 'No es posible alcanzar')) {
            $tieneAviso = true;
        }
    }
    verificar($tieneAviso, 'Objetivo inalcanzable produce advertencia explícita');

    lanza(
        static fn () => $calc->calcular([
            'arcillas' => [
                ['nombre' => 'A', 'sio2' => 80.0, 'al2o3' => 30.0, 'fe2o3' => 0.0, 'loi' => 0.0],
                ['nombre' => 'B', 'sio2' => 40.0, 'al2o3' => 35.0, 'fe2o3' => 2.0, 'loi' => 20.0],
            ],
            'objetivos' => ['sio2' => 50.0, 'al2o3' => 30.0, 'fe2o3' => 1.0, 'loi' => 10.0],
        ]),
        'Análisis con suma > 101 % lanza InvalidArgumentException',
        'no puede exceder'
    );
    lanza(
        static fn () => $calc->calcular([
            'arcillas' => [
                ['nombre' => 'A', 'sio2' => 0.0, 'al2o3' => 0.0, 'fe2o3' => 0.0, 'loi' => 0.0],
                ['nombre' => 'B', 'sio2' => 40.0, 'al2o3' => 35.0, 'fe2o3' => 2.0, 'loi' => 20.0],
            ],
            'objetivos' => ['sio2' => 50.0, 'al2o3' => 30.0, 'fe2o3' => 1.0, 'loi' => 10.0],
        ]),
        'Análisis vacío lanza InvalidArgumentException',
        'vacío'
    );
    lanza(
        static fn () => $calc->calcular([
            'arcillas' => [
                ['sio2' => 60, 'al2o3' => 25, 'fe2o3' => 1, 'loi' => 10],
                ['sio2' => 40, 'al2o3' => 35, 'fe2o3' => 2, 'loi' => 20],
                ['sio2' => 50, 'al2o3' => 20, 'fe2o3' => 7, 'loi' => 8],
                ['sio2' => 62, 'al2o3' => 18, 'fe2o3' => 4, 'loi' => 8],
                ['sio2' => 55, 'al2o3' => 28, 'fe2o3' => 1.2, 'loi' => 9],
            ],
        ]),
        'Más de 4 arcillas lanza InvalidArgumentException',
        'entre 2 y 4'
    );
    lanza(
        static fn () => $calc->calcular([
            'arcillas'  => $arcillas,
            'objetivos' => ['sio2' => 0.0, 'al2o3' => 0.0, 'fe2o3' => 0.0, 'loi' => 0.0],
        ]),
        'Objetivos todos en cero lanzan InvalidArgumentException',
        'al menos un objetivo'
    );

    verificar(jsonValido($exacta), 'La salida de mezcla es serializable a JSON');
    igualA(
        json_encode($exacta),
        json_encode($calc->calcular([
            'arcillas'  => $arcillas,
            'objetivos' => ['sio2' => 50.0, 'al2o3' => 30.0, 'fe2o3' => 1.5, 'loi' => 15.0],
        ])),
        'Mezcla de arcillas es determinista'
    );
});

// ─────────────────────────────────────────────────────────────────────
// 4. Contracción y absorción
// ─────────────────────────────────────────────────────────────────────

ejecutarGrupo('Contracción y absorción', static function (): void {
    $calc = new ShrinkageCalculator();

    $base = $calc->calcular([
        'largo_verde'           => 100,
        'largo_seco'            => 92,
        'largo_cocido'          => 88,
        'medida_final_deseada'  => 100,
        'peso_seco'             => 500,
        'peso_saturado'         => 550,
    ]);
    cercaDe(8.00, valorResultado($base, 'Contracción de secado'), 0.01, 'Contracción de secado = 8.00 %');
    cercaDe(4.35, valorResultado($base, 'Contracción de cocción'), 0.01, 'Contracción de cocción = 4.35 %');
    cercaDe(12.00, valorResultado($base, 'Contracción total'), 0.01, 'Contracción total = 12.00 %');
    cercaDe(113.64, valorResultado($base, 'Largo en verde necesario'), 0.01, 'Corrección de molde: 113.64 mm');
    cercaDe(1.1364, valorResultado($base, 'Factor de sobredimensionado del molde'), 0.001, 'Factor de molde ≈ 1.1364');
    cercaDe(10.00, valorResultado($base, 'Absorción de agua'), 0.01, 'Absorción de agua = 10.00 %');
    contiene('Alta', (string) valorDetalle($base, 'Clasificación de la contracción de secado'), 'Clasificación de secado: alta');
    contiene('Moderada', (string) valorDetalle($base, 'Clasificación de la contracción de cocción'), 'Clasificación de cocción: moderada');
    contiene('Media', (string) valorDetalle($base, 'Clasificación de la absorción'), 'Clasificación de absorción: media');
    cercaDe(12.35, valorDetalle($base, 'Suma aritmética de las etapas'), 0.01, 'Suma de etapas = 12.35 % (mayor que la total)');

    $sinAbsorcion = $calc->calcular(['largo_verde' => 100, 'largo_seco' => 92, 'largo_cocido' => 88]);
    verificar(
        !existeResultado($sinAbsorcion, 'Absorción de agua'),
        'Sin pesos no se calcula absorción'
    );
    verificar(
        !existeResultado($sinAbsorcion, 'Largo en verde necesario'),
        'Sin medida final no se corrige molde'
    );

    $expansion = $calc->calcular(['largo_verde' => 100, 'largo_seco' => 101, 'largo_cocido' => 88]);
    cercaDe(-1.0, valorResultado($expansion, 'Contracción de secado'), 0.01, 'Expansión de secado = -1.00 %');
    $avisoExpansion = false;
    foreach ($expansion['advertencias'] as $aviso) {
        if (str_contains($aviso, 'expansión')) {
            $avisoExpansion = true;
        }
    }
    verificar($avisoExpansion, 'La expansión de secado genera advertencia');

    lanza(
        static fn () => $calc->calcular([
            'largo_verde' => 100,
            'largo_seco'  => 92,
            'largo_cocido' => 88,
            'peso_seco'   => 500,
        ]),
        'Un solo peso lanza InvalidArgumentException',
        'ambos pesos'
    );
    lanza(
        static fn () => $calc->calcular([
            'largo_verde' => 100,
            'largo_seco'  => 92,
            'largo_cocido' => 88,
            'peso_seco'   => 550,
            'peso_saturado' => 500,
        ]),
        'Peso saturado menor que el seco lanza InvalidArgumentException',
        'mayor o igual'
    );
    lanza(
        static fn () => $calc->calcular(['largo_verde' => 0, 'largo_seco' => 92, 'largo_cocido' => 88]),
        'Largo en verde cero lanza InvalidArgumentException',
        'mayor o igual'
    );

    verificar(jsonValido($base), 'La salida de contracción es serializable a JSON');
});

// ─────────────────────────────────────────────────────────────────────
// 5. Balance metalúrgico
// ─────────────────────────────────────────────────────────────────────

ejecutarGrupo('Balance metalúrgico no ferroso', static function (): void {
    $calc = new MetalBalanceCalculator();

    $cobre = $calc->calcular([
        'unidad_ley'        => 'pct',
        'ley_alimentacion'  => 1.0,
        'ley_concentrado'   => 25.0,
        'ley_colas'         => 0.15,
        'toneladas_alimentacion' => 1000,
        'precio_metal'      => 9000,
        'unidad_precio'     => 'usd_t',
        'pagable_pct'       => 96.5,
        'maquila_usd_t_concentrado' => 90,
        'refinacion_usd_t_metal' => 150,
    ]);
    cercaDe(85.513, valorResultado($cobre, 'Recuperación metalúrgica'), 0.01, 'Recuperación Cu = 85.513 %');
    cercaDe(29.235, valorResultado($cobre, 'Razón de concentración'), 0.01, 'Razón de concentración = 29.235');
    cercaDe(34.2052, valorResultado($cobre, 'Masa de concentrado'), 0.001, 'Masa de concentrado = 34.2052 t');
    cercaDe(0.034205, valorResultado($cobre, 'Concentrado por tonelada de alimentación'), 1.0e-5, 'Concentrado por t de alimentación = 0.034205');
    cercaDe(8551.31, valorResultado($cobre, 'Metal fino en concentrado'), 0.1, 'Metal fino en concentrado = 8551.31 kg');
    cercaDe(2045.06, valorResultado($cobre, 'Valor neto por tonelada de concentrado'), 0.5, 'Valor neto por t de concentrado = 2045.06 USD/t');
    cercaDe(69951.84, valorResultado($cobre, 'Valor neto total'), 0.5, 'Valor neto total = 69 951.84 USD');
    cercaDe(74268.11, valorDetalle($cobre, 'Ingreso bruto'), 0.5, 'Ingreso bruto = 74 268.11 USD');
    cercaDe(3078.47, valorDetalle($cobre, 'Cargo de maquila (TC)'), 0.5, 'Maquila = 3 078.47 USD');
    cercaDe(1237.80, valorDetalle($cobre, 'Cargo de refinación (RC)'), 0.5, 'Refinación = 1 237.80 USD');
    cercaDe(1448.69, valorDetalle($cobre, 'Metal fino en colas'), 0.5, 'Metal fino en colas = 1 448.69 kg');
    cercaDe(9000.0, valorDetalle($cobre, 'Precio usado del metal'), 0.01, 'Precio usado = 9000 USD/t');

    $plata = $calc->calcular([
        'unidad_ley'        => 'g_t',
        'ley_alimentacion'  => 100,
        'ley_concentrado'   => 5000,
        'ley_colas'         => 10,
        'toneladas_alimentacion' => 1000,
        'precio_metal'      => 25,
        'unidad_precio'     => 'usd_oz',
        'pagable_pct'       => 95,
        'maquila_usd_t_concentrado' => 100,
        'refinacion_usd_t_metal' => 0,
    ]);
    cercaDe(90.18, valorResultado($plata, 'Recuperación metalúrgica'), 0.01, 'Recuperación Ag = 90.18 %');
    cercaDe(55.444, valorResultado($plata, 'Razón de concentración'), 0.01, 'Razón de concentración Ag = 55.444');
    cercaDe(18.0361, valorResultado($plata, 'Masa de concentrado'), 0.01, 'Masa de concentrado Ag = 18.0361 t');
    cercaDe(90.18, valorResultado($plata, 'Metal fino en concentrado'), 0.1, 'Metal fino Ag = 90.18 kg');

    $libra = $calc->calcular([
        'ley_alimentacion'  => 1.0,
        'ley_concentrado'   => 25.0,
        'ley_colas'         => 0.15,
        'toneladas_alimentacion' => 1000,
        'precio_metal'      => 4.0,
        'unidad_precio'     => 'usd_lb',
        'pagable_pct'       => 96.5,
        'maquila_usd_t_concentrado' => 90,
        'refinacion_usd_t_metal' => 150,
    ]);
    cercaDe(8818.49, valorDetalle($libra, 'Precio usado del metal'), 0.01, 'Precio USD/lb se convierte a USD/t');
    cercaDe(68454.02, valorResultado($libra, 'Valor neto total'), 1.0, 'Valor neto total con precio en USD/lb');

    lanza(
        static fn () => $calc->calcular([
            'ley_alimentacion' => 1.0,
            'ley_concentrado'  => 0.9,
            'ley_colas'        => 0.1,
        ]),
        'Concentrado ≤ alimentación lanza InvalidArgumentException',
        'mayor que la ley de alimentación'
    );
    lanza(
        static fn () => $calc->calcular([
            'ley_alimentacion' => 1.0,
            'ley_concentrado'  => 25.0,
            'ley_colas'        => 1.5,
        ]),
        'Colas ≥ alimentación lanza InvalidArgumentException',
        'mayor que la ley de colas'
    );
    lanza(
        static fn () => $calc->calcular([
            'unidad_ley'       => 'pct',
            'ley_alimentacion' => 1.0,
            'ley_concentrado'  => 120.0,
            'ley_colas'        => 0.1,
        ]),
        'Ley en % mayor a 100 lanza InvalidArgumentException',
        'no puede exceder 100'
    );

    verificar(jsonValido($cobre), 'La salida de balance es serializable a JSON');
});

// ─────────────────────────────────────────────────────────────────────
// 6. Bentonita y arena verde
// ─────────────────────────────────────────────────────────────────────

ejecutarGrupo('Bentonita para arena verde', static function (): void {
    $calc = new BentoniteSandCalculator();

    $base = $calc->calcular([
        'masa_arena_kg'          => 1000,
        'bentonita_pct'          => 8,
        'agua_pct'               => 4,
        'finos_pct'              => 10,
        'lotes'                  => 2,
        'humedad_bentonita_pct'  => 10,
    ]);
    cercaDe(80.0, valorResultado($base, 'Bentonita seca requerida por lote'), 0.001, 'Bentonita seca = 80 kg/lote');
    cercaDe(88.889, valorResultado($base, 'Bentonita húmeda a dosificar por lote'), 0.001, 'Bentonita húmeda = 88.889 kg/lote');
    cercaDe(31.111, valorResultado($base, 'Agua neta a agregar por lote'), 0.001, 'Agua neta = 31.111 kg/lote');
    cercaDe(80.0, valorResultado($base, 'Bentonita seca por tonelada de arena'), 0.001, 'Bentonita seca = 80 kg/t');
    cercaDe(88.889, valorResultado($base, 'Bentonita húmeda por tonelada de arena'), 0.001, 'Bentonita húmeda = 88.889 kg/t');
    cercaDe(31.111, valorResultado($base, 'Agua neta por tonelada de arena'), 0.001, 'Agua neta = 31.111 kg/t');
    cercaDe(177.778, valorResultado($base, 'Bentonita húmeda total (2 lote(s))'), 0.001, 'Bentonita húmeda total = 177.778 kg');
    cercaDe(62.222, valorResultado($base, 'Agua neta total (2 lote(s))'), 0.001, 'Agua total = 62.222 kg');
    cercaDe(8.889, valorDetalle($base, 'Agua aportada por la bentonita'), 0.001, 'Agua aportada por bentonita = 8.889 kg');
    cercaDe(100.0, valorDetalle($base, 'Finos presentes en el lote'), 0.001, 'Finos presentes = 100 kg');

    $seca = $calc->calcular([
        'masa_arena_kg'         => 1000,
        'bentonita_pct'         => 8,
        'agua_pct'              => 4,
        'finos_pct'             => 10,
        'lotes'                 => 1,
        'humedad_bentonita_pct' => 0,
    ]);
    cercaDe(80.0, valorResultado($seca, 'Bentonita húmeda a dosificar por lote'), 0.001, 'Bentonita seca: húmeda = seca');
    cercaDe(40.0, valorResultado($seca, 'Agua neta a agregar por lote'), 0.001, 'Bentonita seca: agua neta = 40 kg');

    $excesoHumedad = $calc->calcular([
        'masa_arena_kg'         => 1000,
        'bentonita_pct'         => 8,
        'agua_pct'              => 1,
        'finos_pct'             => 10,
        'lotes'                 => 1,
        'humedad_bentonita_pct' => 40,
    ]);
    cercaDe(0.0, valorResultado($excesoHumedad, 'Agua neta a agregar por lote'), 1.0e-9, 'Agua neta nunca es negativa');
    cercaDe(43.333, valorDetalle($excesoHumedad, 'Déficit de agua por humedad de la bentonita'), 0.001, 'Se reporta el déficit de agua');
    $avisoAgua = false;
    foreach ($excesoHumedad['advertencias'] as $aviso) {
        if (str_contains($aviso, 'aporta más agua')) {
            $avisoAgua = true;
        }
    }
    verificar($avisoAgua, 'Humedad excesiva de bentonita genera advertencia');

    lanza(
        static fn () => $calc->calcular(['lotes' => 1.5]),
        'Número de lotes fraccionario lanza InvalidArgumentException',
        'entero'
    );
    lanza(
        static fn () => $calc->calcular(['bentonita_pct' => 35]),
        'Bentonita fuera de rango lanza InvalidArgumentException',
        'menor o igual'
    );
    lanza(
        static fn () => $calc->calcular(['masa_arena_kg' => 0]),
        'Masa de arena cero lanza InvalidArgumentException',
        'mayor o igual'
    );

    verificar(jsonValido($base), 'La salida de bentonita es serializable a JSON');
});

// ─────────────────────────────────────────────────────────────────────
// 7. Granulometría y Stokes
// ─────────────────────────────────────────────────────────────────────

ejecutarGrupo('Granulometría y Stokes', static function (): void {
    $calc = new ParticleSizeCalculator();
    $mallas = [
        ['malla_um' => 2000, 'valor' => 100],
        ['malla_um' => 1000, 'valor' => 90],
        ['malla_um' => 500, 'valor' => 70],
        ['malla_um' => 250, 'valor' => 45],
        ['malla_um' => 125, 'valor' => 20],
        ['malla_um' => 63, 'valor' => 5],
    ];

    $curva = $calc->calcular(['mallas' => $mallas]);
    cercaDe(287.17, valorResultado($curva, 'D50 (diámetro mediano)'), 0.5, 'D50 interpolado = 287.17 µm');
    cercaDe(79.16, valorResultado($curva, 'D10'), 0.5, 'D10 interpolado = 79.16 µm');
    cercaDe(1000.0, valorResultado($curva, 'D90'), 0.01, 'D90 = 1000 µm (coincide con una malla)');
    cercaDe(4.79, valorResultado($curva, 'Coeficiente de uniformidad Cu'), 0.02, 'Cu = 4.79');
    cercaDe(0.91, valorResultado($curva, 'Coeficiente de curvatura Cc'), 0.02, 'Cc = 0.91');
    cercaDe(378.93, valorDetalle($curva, 'D60'), 1.0, 'D60 = 378.93 µm');
    cercaDe(164.94, valorDetalle($curva, 'D30'), 1.0, 'D30 = 164.94 µm');
    cercaDe(0.2246, valorResultado($curva, 'Velocidad de sedimentación Stokes'), 0.001, 'Stokes (50 µm, cuarzo, agua) = 0.2246 cm/s');
    cercaDe(0.1118, valorDetalle($curva, 'Número de Reynolds de la partícula'), 0.001, 'Reynolds ≈ 0.1118 (régimen de Stokes)');
    contiene('intermedia', (string) valorResultado($curva, 'Graduación del material'), 'Clasificación granulométrica orientativa');

    $retenido = $calc->calcular([
        'tipo_datos' => 'retenido',
        'mallas'     => [
            ['malla_um' => 2000, 'valor' => 10],
            ['malla_um' => 1000, 'valor' => 20],
            ['malla_um' => 500, 'valor' => 25],
            ['malla_um' => 250, 'valor' => 20],
            ['malla_um' => 125, 'valor' => 15],
            ['malla_um' => 63, 'valor' => 10],
        ],
    ]);
    cercaDe(574.35, valorResultado($retenido, 'D50 (diámetro mediano)'), 0.5, 'Modo retenido: D50 = 574.35 µm');
    cercaDe(125.0, valorResultado($retenido, 'D10'), 0.01, 'Modo retenido: D10 = 125 µm');
    cercaDe(2000.0, valorResultado($retenido, 'D90'), 0.01, 'Modo retenido: D90 = 2000 µm');

    $fueraDeRango = $calc->calcular(['mallas' => [
        ['malla_um' => 2000, 'valor' => 100],
        ['malla_um' => 1000, 'valor' => 90],
    ]]);
    esNulo(valorResultado($fueraDeRango, 'D50 (diámetro mediano)'), 'Percentil fuera de rango devuelve null (sin NAN)');
    esNulo(valorResultado($fueraDeRango, 'Coeficiente de uniformidad Cu'), 'Cu es null si no hay D10/D60');
    $avisoRango = false;
    foreach ($fueraDeRango['advertencias'] as $aviso) {
        if (str_contains($aviso, 'No se pudieron interpolar')) {
            $avisoRango = true;
        }
    }
    verificar($avisoRango, 'Percentiles no interpolables generan advertencia');

    $viscoso = $calc->calcular(['viscosidad_fluido' => 10.02]);
    cercaDe(0.0225, valorResultado($viscoso, 'Velocidad de sedimentación Stokes'), 0.0005, 'Stokes escala inverso con la viscosidad');

    lanza(
        static fn () => $calc->calcular(['mallas' => [
            ['malla_um' => 1000, 'valor' => 90],
            ['malla_um' => 500, 'valor' => 95],
        ]]),
        'Porcentajes pasantes no monótonos lanzan InvalidArgumentException',
        'disminuir'
    );
    lanza(
        static fn () => $calc->calcular(['mallas' => [
            ['malla_um' => 1000, 'valor' => 90],
            ['malla_um' => 1000, 'valor' => 80],
        ]]),
        'Mallas repetidas lanzan InvalidArgumentException',
        'repetida'
    );
    lanza(
        static fn () => $calc->calcular(['mallas' => [['malla_um' => 1000, 'valor' => 90]]]),
        'Una sola malla lanza InvalidArgumentException',
        'al menos dos'
    );
    lanza(
        static fn () => $calc->calcular(['tipo_datos' => 'retenido', 'mallas' => [
            ['malla_um' => 1000, 'valor' => 60],
            ['malla_um' => 500, 'valor' => 60],
        ]]),
        'Retenidos que suman más de 100 % lanzan InvalidArgumentException',
        'no puede exceder'
    );
    lanza(
        static fn () => $calc->calcular(['densidad_particula' => 0.9, 'densidad_fluido' => 0.998]),
        'Partícula menos densa que el fluido lanza InvalidArgumentException',
        'mayor que la densidad del fluido'
    );
    lanza(
        static fn () => $calc->calcular(['diametro_stokes_um' => 0.05]),
        'Diámetro fuera del mínimo lanza InvalidArgumentException',
        'mayor o igual'
    );

    verificar(jsonValido($curva), 'La salida granulométrica es serializable a JSON');
});

// ─────────────────────────────────────────────────────────────────────
// 8. Precio normalizado
// ─────────────────────────────────────────────────────────────────────

ejecutarGrupo('Precio normalizado y costo landed', static function (): void {
    $calc = new PriceNormalizationCalculator();

    $base = $calc->calcular([
        'precio_mxn_t_humeda'     => 2500,
        'humedad_pct'             => 10,
        'merma_pct'               => 2,
        'flete_mxn_t'             => 350,
        'margen_broker_pct'       => 8,
        'iva_pct'                 => 16,
        'tipo_cambio_mxn_usd'     => 18.5,
        'oxido_nombre'            => 'al2o3',
        'oxido_pct'               => 37.5,
    ]);
    cercaDe(3489.80, valorResultado($base, 'Costo landed por tonelada seca (MXN)'), 0.01, 'Costo landed = 3 489.80 MXN/t');
    cercaDe(188.64, valorResultado($base, 'Costo landed por tonelada seca (USD)'), 0.01, 'Costo landed = 188.64 USD/t');
    cercaDe(9306.12, valorResultado($base, 'Costo por tonelada de Al2O3 (MXN)'), 0.01, 'Costo por t de Al2O3 = 9 306.12 MXN/t');
    cercaDe(503.03, valorResultado($base, 'Costo por tonelada de Al2O3 (USD)'), 0.01, 'Costo por t de Al2O3 = 503.03 USD/t');
    cercaDe(88.20, valorResultado($base, 'Rendimiento de material seco'), 0.01, 'Rendimiento seco = 88.20 %');
    cercaDe(0.882, valorDetalle($base, 'Factor húmedo → seco'), 1.0e-6, 'Factor húmedo → seco = 0.882');
    cercaDe(2834.47, valorDetalle($base, 'Costo base por tonelada seca'), 0.01, 'Costo base por t seca = 2 834.47 MXN');
    cercaDe(396.83, valorDetalle($base, 'Flete prorrateado por tonelada seca'), 0.01, 'Flete por t seca = 396.83 MXN');
    cercaDe(258.50, valorDetalle($base, 'Margen del broker'), 0.01, 'Margen = 258.50 MXN/t');
    cercaDe(558.37, valorDetalle($base, 'IVA'), 0.01, 'IVA = 558.37 MXN/t');
    cercaDe(4048.16, valorDetalle($base, 'Costo landed con IVA (MXN)'), 0.01, 'Costo con IVA = 4 048.16 MXN/t');
    cercaDe(0.2282, valorDetalle($base, 'Costo por libra de Al2O3'), 0.0005, 'Costo por lb de Al2O3 = 0.2282 USD/lb');

    $directo = $calc->calcular([
        'precio_mxn_t_humeda' => 1000,
        'humedad_pct'         => 0,
        'merma_pct'           => 0,
        'flete_mxn_t'         => 0,
        'margen_broker_pct'   => 0,
        'iva_pct'             => 0,
        'tipo_cambio_mxn_usd' => 20,
        'oxido_nombre'        => 'sio2',
        'oxido_pct'           => 50,
    ]);
    cercaDe(1000.0, valorResultado($directo, 'Costo landed por tonelada seca (MXN)'), 0.01, 'Sin humedad ni cargos: costo = precio');
    cercaDe(50.0, valorResultado($directo, 'Costo landed por tonelada seca (USD)'), 0.01, 'Sin humedad ni cargos: costo USD = 50');
    cercaDe(2000.0, valorResultado($directo, 'Costo por tonelada de SiO2 (MXN)'), 0.01, 'Costo por t de SiO2 = 2 000 MXN');
    cercaDe(100.0, valorResultado($directo, 'Costo por tonelada de SiO2 (USD)'), 0.01, 'Costo por t de SiO2 = 100 USD');

    $avisos = $calc->calcular([
        'humedad_pct'       => 25,
        'merma_pct'         => 6,
        'margen_broker_pct' => 22,
        'oxido_pct'         => 8,
    ]);
    $textoAvisos = implode(' | ', $avisos['advertencias']);
    contiene('Humedad alta', $textoAvisos, 'Advertencia por humedad alta');
    contiene('Merma alta', $textoAvisos, 'Advertencia por merma alta');
    contiene('Margen del broker alto', $textoAvisos, 'Advertencia por margen alto');
    contiene('Contenido de óxido bajo', $textoAvisos, 'Advertencia por bajo contenido de óxido');

    lanza(
        static fn () => $calc->calcular(['tipo_cambio_mxn_usd' => 0]),
        'Tipo de cambio cero lanza InvalidArgumentException',
        'mayor o igual'
    );
    lanza(
        static fn () => $calc->calcular(['humedad_pct' => 100]),
        'Humedad de 100 % lanza InvalidArgumentException',
        'menor o igual'
    );
    lanza(
        static fn () => $calc->calcular(['oxido_pct' => 0]),
        'Contenido de óxido cero lanza InvalidArgumentException',
        'mayor o igual'
    );

    verificar(jsonValido($base), 'La salida de precio es serializable a JSON');
});

// ─────────────────────────────────────────────────────────────────────
// 9. Fórmula estructural
// ─────────────────────────────────────────────────────────────────────

ejecutarGrupo('Fórmula estructural de arcillas', static function (): void {
    $calc = new MineralFormulaCalculator();

    $caolinita = $calc->calcular(['sio2' => 46.54, 'al2o3' => 39.50, 'loi' => 13.96]);
    $formulaCaolinita = (string) valorResultado($caolinita, 'Fórmula estructural normalizada');
    contiene('Al4', $formulaCaolinita, 'Caolinita: fórmula contiene Al4');
    contiene('Si4', $formulaCaolinita, 'Caolinita: fórmula contiene Si4');
    contiene('O10', $formulaCaolinita, 'Caolinita: fórmula contiene O10');
    contiene('(OH)8', $formulaCaolinita, 'Caolinita: fórmula contiene (OH)8');
    cercaDe(4.0, valorResultado($caolinita, 'Si tetraédrico'), 0.01, 'Caolinita: Si tetraédrico = 4');
    cercaDe(4.0, valorResultado($caolinita, 'Al octaédrico'), 0.01, 'Caolinita: Al octaédrico = 4');
    cercaDe(0.0, valorResultado($caolinita, 'Carga de capa'), 0.01, 'Caolinita: carga de capa ≈ 0');
    contiene('Caolinita', (string) valorResultado($caolinita, 'Clasificación orientativa'), 'Clasificación: caolinita');
    contiene('O10(OH)8', (string) valorResultado($caolinita, 'Base de normalización'), 'Detección automática elige O10(OH)8');

    $caolinitaMedia = $calc->calcular([
        'sio2' => 46.54, 'al2o3' => 39.50, 'loi' => 13.96, 'base_normalizacion' => 'o5_oh4',
    ]);
    contiene('Al2', (string) valorResultado($caolinitaMedia, 'Fórmula estructural normalizada'), 'Base O5(OH)4: fórmula Al2');
    contiene('(OH)4', (string) valorResultado($caolinitaMedia, 'Fórmula estructural normalizada'), 'Base O5(OH)4: fórmula OH4');
    contiene('Caolinita', (string) valorResultado($caolinitaMedia, 'Clasificación orientativa'), 'Base O5(OH)4: clasificación caolinita');

    $montmorillonita = $calc->calcular([
        'sio2' => 65.48, 'al2o3' => 23.20, 'mgo' => 3.62, 'na2o' => 2.79, 'loi' => 4.91,
    ]);
    $formulaMont = (string) valorResultado($montmorillonita, 'Fórmula estructural normalizada');
    contiene('Na0.33', $formulaMont, 'Montmorillonita: Na interlamilar 0.33');
    contiene('Al1.67', $formulaMont, 'Montmorillonita: Al octaédrico 1.67');
    contiene('Mg0.33', $formulaMont, 'Montmorillonita: Mg octaédrico 0.33');
    contiene('(OH)2', $formulaMont, 'Montmorillonita: OH2');
    cercaDe(0.33, valorResultado($montmorillonita, 'Carga de capa'), 0.01, 'Montmorillonita: carga de capa ≈ 0.33');
    cercaDe(0.33, valorResultado($montmorillonita, 'Cationes interlaminares (carga)'), 0.01, 'Montmorillonita: carga interlamilar ≈ 0.33');
    contiene('Montmorillonita', (string) valorResultado($montmorillonita, 'Clasificación orientativa'), 'Clasificación: montmorillonita');
    contiene('O10(OH)2', (string) valorResultado($montmorillonita, 'Base de normalización'), 'Detección automática elige O10(OH)2');

    $illita = $calc->calcular([
        'sio2' => 52.33, 'al2o3' => 31.34, 'mgo' => 2.07, 'k2o' => 9.65, 'loi' => 4.61,
        'base_normalizacion' => 'o10_oh2',
    ]);
    cercaDe(3.4, valorResultado($illita, 'Si tetraédrico'), 0.01, 'Illita: Si tetraédrico = 3.4');
    cercaDe(1.8, valorResultado($illita, 'Al octaédrico'), 0.01, 'Illita: Al octaédrico = 1.8');
    cercaDe(0.8, valorResultado($illita, 'Carga de capa'), 0.01, 'Illita: carga de capa = 0.8');
    contiene('Illita', (string) valorResultado($illita, 'Clasificación orientativa'), 'Clasificación: illita');
    contiene('K0.8', (string) valorResultado($illita, 'Fórmula estructural normalizada'), 'Illita: K interlamilar 0.8');

    $talco = $calc->calcular([
        'sio2' => 63.50, 'al2o3' => 0.0, 'fe2o3' => 0.0, 'feo' => 0.0, 'mgo' => 31.70,
        'cao' => 0.0, 'na2o' => 0.0, 'k2o' => 0.0, 'tio2' => 0.0, 'mno' => 0.0,
        'p2o5' => 0.0, 'loi' => 4.80,
    ]);
    cercaDe(4.0, valorResultado($talco, 'Si tetraédrico'), 0.01, 'Talco: Si tetraédrico = 4');
    cercaDe(0.0, valorResultado($talco, 'Carga de capa'), 0.05, 'Talco: carga de capa ≈ 0');
    contiene('Talco', (string) valorResultado($talco, 'Clasificación orientativa'), 'Clasificación: talco');
    contiene('Mg2.98', (string) valorResultado($talco, 'Fórmula estructural normalizada'), 'Talco: fórmula con Mg ≈ 3');

    $incompleto = $calc->calcular(['sio2' => 40, 'al2o3' => 30, 'loi' => 10]);
    $textoAvisos = implode(' | ', $incompleto['advertencias']);
    contiene('faltan componentes', $textoAvisos, 'Advertencia por análisis incompleto');

    lanza(
        static fn () => $calc->calcular(['sio2' => 0, 'al2o3' => 0, 'loi' => 14]),
        'Sin SiO2 ni Al2O3 lanza InvalidArgumentException',
        'SiO2'
    );
    lanza(
        static fn () => $calc->calcular(['sio2' => 46.54, 'al2o3' => 39.5, 'loi' => 0]),
        'LOI cero lanza InvalidArgumentException',
        'mayor o igual'
    );

    verificar(jsonValido($caolinita), 'La salida de fórmula estructural es serializable a JSON');
});

// ─────────────────────────────────────────────────────────────────────
// 10. Contrato global de salida
// ─────────────────────────────────────────────────────────────────────

ejecutarGrupo('Contrato global de salida', static function (): void {
    $entradas = [
        'umf-esmalte'                 => [
            'mat_feldespato_potasico' => 50,
            'mat_caolin'              => 25,
            'mat_silice'              => 25,
        ],
        'mezcla-arcillas'             => [],
        'contraccion-absorcion'       => [],
        'balance-metalurgico'         => [],
        'bentonita-arena-verde'       => [],
        'granulometria-stokes'        => [],
        'precio-normalizado'          => [],
        'formula-estructural-arcillas' => [],
    ];

    foreach (CalculatorRegistry::all() as $slug => $calculadora) {
        $salida = $calculadora->calcular($entradas[$slug]);
        igualA(
            ['resultados', 'detalle', 'advertencias', 'formulas'],
            array_keys($salida),
            'La salida de ' . $slug . ' tiene las cuatro claves del contrato'
        );

        $estructuraOk = true;
        foreach ($salida['resultados'] as $fila) {
            if (!is_string($fila['etiqueta']) || $fila['etiqueta'] === ''
                || !is_string($fila['unidad']) || !is_bool($fila['destacado'])
                || !(is_int($fila['valor']) || is_float($fila['valor'])
                    || is_string($fila['valor']) || $fila['valor'] === null)) {
                $estructuraOk = false;
            }
        }
        foreach ($salida['detalle'] as $fila) {
            if (array_keys($fila) !== ['etiqueta', 'valor', 'unidad']) {
                $estructuraOk = false;
            }
        }
        foreach ($salida['advertencias'] as $aviso) {
            if (!is_string($aviso) || $aviso === '') {
                $estructuraOk = false;
            }
        }
        foreach ($salida['formulas'] as $formula) {
            if (!is_string($formula) || $formula === '') {
                $estructuraOk = false;
            }
        }
        verificar($estructuraOk, 'Tipos y forma de filas válidos en ' . $slug);
        verificar($salida['resultados'] !== [], 'La calculadora ' . $slug . ' devuelve resultados');
        verificar(jsonValido($salida), 'La salida de ' . $slug . ' es JSON válido (sin NAN/INF)');
        igualA(
            json_encode($salida),
            json_encode($calculadora->calcular($entradas[$slug])),
            'La calculadora ' . $slug . ' es determinista'
        );
    }
});

// ─────────────────────────────────────────────────────────────────────
// Resumen
// ─────────────────────────────────────────────────────────────────────

$total = (int) $GLOBALS['_pruebas'];
$fallos = $GLOBALS['_fallos'];

echo PHP_EOL . str_repeat('═', 64) . PHP_EOL;
if ($fallos === []) {
    echo 'RESULTADO: OK — ' . $total . ' asserts, 0 fallos.' . PHP_EOL;
    echo 'Calculadoras verificadas: 8/8 (UMF, mezcla, contracción, balance, bentonita, '
        . 'granulometría, precio, fórmula estructural).' . PHP_EOL;
    exit(0);
}

echo 'RESULTADO: FALLO — ' . $total . ' asserts, ' . count($fallos) . ' fallos.' . PHP_EOL;
foreach ($fallos as $indice => $fallo) {
    echo '  ' . ($indice + 1) . '. ' . $fallo . PHP_EOL;
}
exit(1);
