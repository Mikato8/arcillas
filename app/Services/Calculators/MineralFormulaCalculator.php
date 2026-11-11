<?php
declare(strict_types=1);

namespace App\Services\Calculators;

use InvalidArgumentException;

/**
 * Fórmula estructural de arcillas y filosilicatos a partir de óxidos en %.
 *
 * Procedimiento (método clásico de recálculo de fórmulas de arcilla):
 *   1. Moles de cada óxido = % óxido / masa molecular.
 *   2. Oxígeno total = oxígeno aportado por los óxidos + H2O (se interpreta la
 *      LOI como agua estructural).
 *   3. Se escala para que el oxígeno total de la base elegida valga 18
 *      [O10(OH)8], 12 [O10(OH)2], 9 [O5(OH)4] o 24 [O20(OH)4].
 *   4. OH = 2 × H2O normalizada; O = oxígeno total de la base − OH.
 *   5. Los cationes se reparten en tetraédrica/octaédrica/interlaminar y la
 *      carga de capa se obtiene por diferencia de cargas.
 *
 * La base 'auto' detecta si el análisis corresponde a un filosilicato 1:1
 * (caolín) o 2:1 (illita, esmectita) y elige O10(OH)8 u O10(OH)2. Cationes a
 * 4 decimales, cargas a 3 y fórmula con 2 decimales.
 */
final class MineralFormulaCalculator extends AbstractCalculator
{
    public const SLUG = 'formula-estructural-arcillas';

    private const MASA_AGUA = 18.015;

    /** @var array<string,array{total_oxigeno:float,sitios_tetra:float,sitios_octa:float,familia:string,etiqueta:string}> */
    private const BASES = [
        'o10_oh8' => [
            'total_oxigeno' => 18.0,
            'sitios_tetra'  => 4.0,
            'sitios_octa'   => 4.0,
            'familia'       => '1:1',
            'etiqueta'      => 'O10(OH)8 · filosilicato 1:1, celda completa',
        ],
        'o10_oh2' => [
            'total_oxigeno' => 12.0,
            'sitios_tetra'  => 4.0,
            'sitios_octa'   => 2.0,
            'familia'       => '2:1',
            'etiqueta'      => 'O10(OH)2 · filosilicato 2:1, media celda',
        ],
        'o5_oh4' => [
            'total_oxigeno' => 9.0,
            'sitios_tetra'  => 2.0,
            'sitios_octa'   => 2.0,
            'familia'       => '1:1',
            'etiqueta'      => 'O5(OH)4 · filosilicato 1:1, media celda',
        ],
        'o20_oh4' => [
            'total_oxigeno' => 24.0,
            'sitios_tetra'  => 8.0,
            'sitios_octa'   => 4.0,
            'familia'       => '2:1',
            'etiqueta'      => 'O20(OH)4 · filosilicato 2:1, celda completa',
        ],
    ];

    /** @var array<string,array{etiqueta:string,cation:string,cationes:float,oxigenos:float,carga:float,masa:float}> */
    private const OXIDOS = [
        'sio2'  => ['etiqueta' => 'SiO2',  'cation' => 'Si',  'cationes' => 1.0, 'oxigenos' => 2.0, 'carga' => 4.0, 'masa' => 60.084],
        'al2o3' => ['etiqueta' => 'Al2O3', 'cation' => 'Al',  'cationes' => 2.0, 'oxigenos' => 3.0, 'carga' => 3.0, 'masa' => 101.961],
        'fe2o3' => ['etiqueta' => 'Fe2O3', 'cation' => 'Fe3', 'cationes' => 2.0, 'oxigenos' => 3.0, 'carga' => 3.0, 'masa' => 159.688],
        'feo'   => ['etiqueta' => 'FeO',   'cation' => 'Fe2', 'cationes' => 1.0, 'oxigenos' => 1.0, 'carga' => 2.0, 'masa' => 71.844],
        'mgo'   => ['etiqueta' => 'MgO',   'cation' => 'Mg',  'cationes' => 1.0, 'oxigenos' => 1.0, 'carga' => 2.0, 'masa' => 40.304],
        'cao'   => ['etiqueta' => 'CaO',   'cation' => 'Ca',  'cationes' => 1.0, 'oxigenos' => 1.0, 'carga' => 2.0, 'masa' => 56.077],
        'na2o'  => ['etiqueta' => 'Na2O',  'cation' => 'Na',  'cationes' => 2.0, 'oxigenos' => 1.0, 'carga' => 1.0, 'masa' => 61.979],
        'k2o'   => ['etiqueta' => 'K2O',   'cation' => 'K',   'cationes' => 2.0, 'oxigenos' => 1.0, 'carga' => 1.0, 'masa' => 94.196],
        'tio2'  => ['etiqueta' => 'TiO2',  'cation' => 'Ti',  'cationes' => 1.0, 'oxigenos' => 2.0, 'carga' => 4.0, 'masa' => 79.866],
        'mno'   => ['etiqueta' => 'MnO',   'cation' => 'Mn',  'cationes' => 1.0, 'oxigenos' => 1.0, 'carga' => 2.0, 'masa' => 70.937],
        'p2o5'  => ['etiqueta' => 'P2O5',  'cation' => 'P',   'cationes' => 2.0, 'oxigenos' => 5.0, 'carga' => 5.0, 'masa' => 141.944],
    ];

    /** @var array<string,float> */
    private const DEFAULTS = [
        'sio2'  => 46.54,
        'al2o3' => 39.50,
        'fe2o3' => 0.0,
        'feo'   => 0.0,
        'mgo'   => 0.0,
        'cao'   => 0.0,
        'na2o'  => 0.0,
        'k2o'   => 0.0,
        'tio2'  => 0.0,
        'mno'   => 0.0,
        'p2o5'  => 0.0,
    ];

    public function slug(): string
    {
        return self::SLUG;
    }

    public function nombre(): string
    {
        return 'Fórmula estructural de arcillas';
    }

    public function categoria(): string
    {
        return 'Mineralogía y procesos';
    }

    public function descripcion(): string
    {
        return 'Recalcula la fórmula estructural de una arcilla o filosilicato a partir de '
            . 'sus óxidos en %: normaliza cationes a O10(OH)8 o base equivalente, reparte '
            . 'tetraédrica/octaédrica, calcula carga de capa y clasifica el mineral.';
    }

    public function campos(): array
    {
        $campos = [];
        foreach (self::OXIDOS as $clave => $oxido) {
            $campos[] = $this->campoNumero($clave, $oxido['etiqueta'], [
                'unidad'    => '%',
                'min'       => 0.0,
                'max'       => 100.0,
                'paso'      => 0.01,
                'requerido' => false,
                'defecto'   => self::DEFAULTS[$clave],
                'grupo'     => 'Óxidos (% en peso)',
                'ayuda'     => 'Porcentaje del óxido en la muestra seca. Deja 0 si no se determinó.',
            ]);
        }
        $campos[] = $this->campoNumero('loi', 'LOI / H2O estructural', [
            'unidad'    => '%',
            'min'       => 0.1,
            'max'       => 100.0,
            'paso'      => 0.01,
            'requerido' => true,
            'defecto'   => 13.96,
            'grupo'     => 'Óxidos (% en peso)',
            'ayuda'     => 'Pérdida por ignición interpretada como agua estructural (H2O). '
                . 'Es necesaria para estimar los hidroxilos de la fórmula.',
        ]);
        $campos[] = $this->campoSelect('base_normalizacion', 'Base de normalización', [
            'auto'    => 'Automática (recomendada)',
            'o10_oh8' => 'O10(OH)8 (1:1, celda completa)',
            'o10_oh2' => 'O10(OH)2 (2:1, media celda)',
            'o5_oh4'  => 'O5(OH)4 (1:1, media celda)',
            'o20_oh4' => 'O20(OH)4 (2:1, celda completa)',
        ], [
            'requerido' => true,
            'defecto'   => 'auto',
            'grupo'     => 'Normalización',
            'ayuda'     => 'Automática elige O10(OH)8 para caolines (1:1) y O10(OH)2 para '
                . 'illitas/esmectitas (2:1). Puedes forzar otra base si conoces el grupo.',
        ]);

        return $campos;
    }

    public function calcular(array $entrada): array
    {
        $datos = $this->datosValidados($entrada);

        $oxidos = [];
        foreach (self::OXIDOS as $clave => $oxido) {
            $oxidos[$clave] = (float) $datos[$clave];
        }
        $loi = (float) $datos['loi'];
        $baseSolicitada = (string) $datos['base_normalizacion'];

        if ($oxidos['sio2'] + $oxidos['al2o3'] <= 0.0) {
            throw new InvalidArgumentException(
                'El análisis debe incluir SiO2 y/o Al2O3 para poder normalizar la estructura.'
            );
        }
        if ($loi <= 0.0) {
            throw new InvalidArgumentException(
                'La LOI debe ser mayor que cero para estimar los hidroxilos estructurales.'
            );
        }

        $molesOxido = [];
        $cationes = [];
        $oxigenosOxido = 0.0;
        foreach (self::OXIDOS as $clave => $oxido) {
            $moles = $oxidos[$clave] / $oxido['masa'];
            $molesOxido[$clave] = $moles;
            $oxigenosOxido += $moles * $oxido['oxigenos'];
            $cation = $oxido['cation'];
            $cationes[$cation] = ($cationes[$cation] ?? 0.0) + $moles * $oxido['cationes'];
        }
        $molesAgua = $loi / self::MASA_AGUA;
        $totalOxigeno = $oxigenosOxido + $molesAgua;
        if ($totalOxigeno <= 0.0) {
            throw new InvalidArgumentException(
                'El análisis no aporta oxígeno suficiente para normalizar la fórmula.'
            );
        }

        $baseElegida = $baseSolicitada;
        if ($baseSolicitada === 'auto') {
            $baseElegida = 'o10_oh2';
            $previa = $this->normalizarBase('o10_oh2', $cationes, $molesAgua, $totalOxigeno);
            $factor10 = $previa['n_oxigeno'] > 0.0 ? 10.0 / $previa['n_oxigeno'] : 1.0;
            $alTotal10 = ($previa['cationes']['Al'] ?? 0.0) * $factor10;
            if ($alTotal10 > 3.2) {
                $baseElegida = 'o10_oh8';
            }
        }

        if (!isset(self::BASES[$baseElegida])) {
            throw new InvalidArgumentException('La base de normalización no es válida.');
        }
        $resultado = $this->normalizarBase($baseElegida, $cationes, $molesAgua, $totalOxigeno);
        $clasificacion = $this->clasificar($resultado);

        $formula = $this->construirFormula($resultado);
        $cargaCapa = $resultado['carga_capa'];
        $cargaInterlaminar = $resultado['carga_interlaminar'];

        $resultados = [
            $this->resultado('Fórmula estructural normalizada', $formula, '', true),
            $this->resultado('Base de normalización', $resultado['etiqueta'], ''),
            $this->resultado('Carga de capa', $this->redondear($cargaCapa, 3), 'eq/fórmula', true),
            $this->resultado(
                'Cationes interlaminares (carga)',
                $this->redondear($cargaInterlaminar, 3),
                'eq/fórmula'
            ),
            $this->resultado(
                'Si tetraédrico',
                $this->redondear($resultado['si_tetra'], 3),
                'cationes'
            ),
            $this->resultado(
                'Al octaédrico',
                $this->redondear($resultado['al_octa'], 3),
                'cationes'
            ),
            $this->resultado('Clasificación orientativa', $clasificacion, '', true),
        ];

        $detalle = [];
        foreach ($resultado['cationes'] as $cation => $valor) {
            $detalle[] = $this->fila(
                'Catión ' . $cation,
                $this->redondear($valor, 4),
                'cationes/fórmula'
            );
        }
        $detalle[] = $this->fila('Si tetraédrico', $this->redondear($resultado['si_tetra'], 4), 'cationes');
        $detalle[] = $this->fila('Al tetraédrico', $this->redondear($resultado['al_tetra'], 4), 'cationes');
        $detalle[] = $this->fila('Al octaédrico', $this->redondear($resultado['al_octa'], 4), 'cationes');
        $detalle[] = $this->fila('Cationes octaédricos totales', $this->redondear($resultado['octa_total'], 4), 'cationes');
        $detalle[] = $this->fila('Oxígeno (O)', $this->redondear($resultado['n_oxigeno'], 4), 'átomos');
        $detalle[] = $this->fila('Hidroxilos (OH)', $this->redondear($resultado['oh'], 4), 'grupos');
        $detalle[] = $this->fila('Carga de aniones', $this->redondear($resultado['carga_aniones'], 4), 'eq');
        $detalle[] = $this->fila('Carga del marco catiónico', $this->redondear($resultado['carga_marco'], 4), 'eq');
        $detalle[] = $this->fila('Diferencia carga de capa − interlaminares', $this->redondear($cargaCapa - $cargaInterlaminar, 4), 'eq');
        $detalle[] = $this->fila('Factor de escala de cationes', $this->redondear($resultado['escala'], 6), '×');
        $detalle[] = $this->fila('Oxígeno total del análisis (incluye H2O)', $this->redondear($totalOxigeno, 6), 'mol');
        $detalle[] = $this->fila('H2O de la LOI', $this->redondear($molesAgua, 6), 'mol');
        $detalle[] = $this->fila('Suma de óxidos + LOI', $this->redondear(array_sum($oxidos) + $loi, 2), '%');

        $advertencias = [];
        $sumaAnalisis = array_sum($oxidos) + $loi;
        if ($sumaAnalisis > 102.0) {
            $advertencias[] = 'La suma de óxidos + LOI es '
                . $this->numeroCorto($this->redondear($sumaAnalisis, 2))
                . ' %, mayor que 102 %: revisa la base del análisis (seco vs. húmedo) o la LOI.';
        }
        if ($sumaAnalisis < 90.0) {
            $advertencias[] = 'La suma de óxidos + LOI es '
                . $this->numeroCorto($this->redondear($sumaAnalisis, 2))
                . ' %; faltan componentes y la fórmula estructural es aproximada.';
        }
        if ($resultado['oh_excedido']) {
            $advertencias[] = 'La LOI aporta más hidroxilos que el oxígeno disponible en la '
                . 'base elegida (OH > total de la base). Revisa la LOI, la base o si hay '
                . 'agua interlamilar/carbonatos; la fórmula es solo aproximada.';
        }
        if ($resultado['exceso_si'] > 0.1) {
            $advertencias[] = 'Hay exceso de silicio tetraédrico ('
                . $this->numeroCorto($this->redondear($resultado['exceso_si'], 2))
                . ' cationes): probable cuarzo libre o sílice amorfa; la fórmula del '
                . 'filosilicato puede estar sobreestimada en SiO2.';
        }
        $limiteOctaedrico = ($resultado['cationes']['Mg'] ?? 0.0) > 1.5
            ? $resultado['sitios_octa'] * 1.9
            : $resultado['sitios_octa'] * 1.15;
        if ($resultado['octa_total'] > $limiteOctaedrico) {
            $advertencias[] = 'Los cationes octaédricos (' . $this->numeroCorto($this->redondear($resultado['octa_total'], 2))
                . ') exceden los sitios octaédricos de la base ('
                . $this->numeroCorto($resultado['sitios_octa'], 1)
                . '): puede haber interestratificación, óxidos libres o una base inadecuada.';
        }
        if ($resultado['si_tetra'] + $resultado['al_tetra'] < $resultado['sitios_tetra'] * 0.9) {
            $advertencias[] = 'Los sitios tetraédricos están incompletos para esta base; '
                . 'verifica la base elegida o el análisis de SiO2/Al2O3.';
        }
        if (abs($cargaCapa - $cargaInterlaminar) > 0.15) {
            $advertencias[] = 'La carga de capa calculada por sustituciones no coincide con la '
                . 'carga de los cationes interlaminares (diferencia '
                . $this->numeroCorto($this->redondear($cargaCapa - $cargaInterlaminar, 3))
                . ' eq). Puede deberse a LOI no estructural, carbonatos o interestratificación.';
        }
        if ($oxidos['p2o5'] > 0.1) {
            $advertencias[] = 'El P2O5 no se asigna a la estructura del filosilicato; '
                . 'probablemente corresponde a apatita u otra impureza.';
        }
        if ($loi > 25.0) {
            $advertencias[] = 'LOI alta (> 25 %): puede incluir carbonatos, materia orgánica o '
                . 'agua interlamilar; la estimación de OH es solo aproximada.';
        }
        if ($oxidos['fe2o3'] > 2.0 && $oxidos['feo'] <= 0.0) {
            $advertencias[] = 'Todo el hierro se capturó como Fe2O3; si hay Fe2+ en la '
                . 'estructura, la carga de capa puede cambiar.';
        }
        if ($baseSolicitada === 'auto') {
            $detalle[] = $this->fila(
                'Detección automática',
                $resultado['familia'] === '1:1' ? 'Filosilicato 1:1 → O10(OH)8' : 'Filosilicato 2:1 → O10(OH)2',
                ''
            );
        }

        $formulas = [
            'Moles de óxido = % óxido / masa molecular; O total = Σ(O de óxidos) + H2O (LOI/18.015)',
            'Cationes normalizados = moles × (O_base / O total); OH = 2 × H2O normalizada',
            'Reparto: tetraédrica = Si + Al hasta 4 sitios por O10; octaédrica = Al restante + Fe + Mg + Ti + Mn',
            'Carga de capa = 2·O + OH − carga de los cationes del marco',
            'Base automática: si Al total por O10 > 3.2 se asume filosilicato 1:1 (caolín); si no, 2:1',
        ];

        return $this->salida($resultados, $detalle, $advertencias, $formulas);
    }

    // ─────────────────────────────────────────────────────────────────
    // Normalización
    // ─────────────────────────────────────────────────────────────────

    /**
     * @param array<string,float> $cationes moles sin normalizar
     * @return array<string,mixed>
     */
    private function normalizarBase(
        string $base,
        array $cationes,
        float $molesAgua,
        float $totalOxigeno
    ): array {
        $meta = self::BASES[$base];
        $escala = $meta['total_oxigeno'] / $totalOxigeno;

        $normalizados = [];
        foreach ($cationes as $cation => $moles) {
            $normalizados[$cation] = $moles * $escala;
        }

        $aguaNormalizada = $molesAgua * $escala;
        $oh = 2.0 * $aguaNormalizada;
        $nOxigeno = $meta['total_oxigeno'] - $oh;
        $ohExcedido = false;
        if ($nOxigeno < 0.0) {
            $nOxigeno = 0.0;
            $ohExcedido = true;
        }

        $siTotal = $normalizados['Si'] ?? 0.0;
        $alTotal = $normalizados['Al'] ?? 0.0;
        $siTetra = min($siTotal, $meta['sitios_tetra']);
        $alTetra = min($alTotal, max(0.0, $meta['sitios_tetra'] - $siTetra));
        $excesoSilicio = max(0.0, $siTotal - $meta['sitios_tetra']);
        $alOcta = max(0.0, $alTotal - $alTetra);

        $fe3 = $normalizados['Fe3'] ?? 0.0;
        $fe2 = $normalizados['Fe2'] ?? 0.0;
        $mg = $normalizados['Mg'] ?? 0.0;
        $ti = $normalizados['Ti'] ?? 0.0;
        $mn = $normalizados['Mn'] ?? 0.0;
        $ca = $normalizados['Ca'] ?? 0.0;
        $na = $normalizados['Na'] ?? 0.0;
        $k = $normalizados['K'] ?? 0.0;

        $octaTotal = $alOcta + $fe3 + $fe2 + $mg + $ti + $mn;
        $cargaInterlaminar = $k + $na + (2.0 * $ca);

        $cargaAniones = (2.0 * $nOxigeno) + $oh;
        $cargaMarco = (4.0 * $siTetra)
            + (3.0 * $alTetra)
            + (3.0 * $alOcta)
            + (3.0 * $fe3)
            + (2.0 * $fe2)
            + (2.0 * $mg)
            + (4.0 * $ti)
            + (2.0 * $mn);

        return [
            'base'              => $base,
            'etiqueta'          => $meta['etiqueta'],
            'familia'           => $meta['familia'],
            'sitios_tetra'      => $meta['sitios_tetra'],
            'sitios_octa'       => $meta['sitios_octa'],
            'escala'            => $escala,
            'cationes'          => $normalizados,
            'si_tetra'          => $siTetra,
            'al_tetra'          => $alTetra,
            'al_octa'           => $alOcta,
            'exceso_si'         => $excesoSilicio,
            'octa_total'        => $octaTotal,
            'n_oxigeno'         => $nOxigeno,
            'oh'                => $oh,
            'oh_excedido'       => $ohExcedido,
            'carga_aniones'     => $cargaAniones,
            'carga_marco'       => $cargaMarco,
            'carga_capa'        => $cargaAniones - $cargaMarco,
            'carga_interlaminar' => $cargaInterlaminar,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // Clasificación y fórmula
    // ─────────────────────────────────────────────────────────────────

    /** @param array<string,mixed> $r */
    private function clasificar(array $r): string
    {
        $nOxigeno = (float) $r['n_oxigeno'];
        if ($nOxigeno <= 0.0) {
            return 'Sin oxígeno estructural suficiente (revisar LOI y base)';
        }
        $factor = 10.0 / $nOxigeno;
        $si10 = $r['si_tetra'] * $factor;
        $alTet10 = $r['al_tetra'] * $factor;
        $alOct10 = $r['al_octa'] * $factor;
        $octa10 = $r['octa_total'] * $factor;
        $mg10 = ($r['cationes']['Mg'] ?? 0.0) * $factor;
        $fe10 = (($r['cationes']['Fe3'] ?? 0.0) + ($r['cationes']['Fe2'] ?? 0.0)) * $factor;
        $carga10 = $r['carga_capa'] * $factor;
        $k10 = ($r['cationes']['K'] ?? 0.0) * $factor;
        $na10 = ($r['cationes']['Na'] ?? 0.0) * $factor;
        $ca10 = ($r['cationes']['Ca'] ?? 0.0) * $factor;

        if ($carga10 < 0.10) {
            if ($alOct10 > 2.5 && $si10 > 3.2 && $alTet10 < 0.4) {
                return 'Caolinita (grupo caolín, 1:1)';
            }
            if ($mg10 > 2.2 && $alOct10 < 0.6) {
                return 'Talco (2:1 trioctaédrico, capa sin carga)';
            }
            if ($octa10 > 2.6 && $si10 < 3.6) {
                return 'Clorita (2:1 + hoja de hidróxido)';
            }
            if ($octa10 >= 1.6 && $octa10 <= 2.5) {
                return $mg10 > 1.2
                    ? 'Talco (2:1 trioctaédrico, capa sin carga)'
                    : 'Pirofilita (2:1 dioctaédrico)';
            }

            return 'Filosilicato sin carga de capa (¿pirofilita, talco o caolín?)';
        }

        if ($carga10 < 0.60) {
            if ($mg10 > 1.0) {
                return 'Esmectita trioctaédrica (saponita/vermiculita)';
            }
            if ($fe10 > 0.5) {
                return 'Esmectita férrica (nontronita)';
            }
            if ($alTet10 > 0.30) {
                return 'Esmectita beidellítica (beidellita)';
            }

            return 'Montmorillonita (esmectita dioctaédrica)';
        }

        if ($carga10 <= 1.2) {
            if ($k10 >= 0.50) {
                return 'Illita / mica dioctaédrica';
            }
            if ($ca10 + $na10 >= 0.30) {
                return 'Vermiculita (2:1 con carga alta)';
            }

            return 'Mica o vermiculita (revisar cationes interlaminares)';
        }

        return 'Filosilicato de carga alta o interestratificado (revisar análisis)';
    }

    /** @param array<string,mixed> $r */
    private function construirFormula(array $r): string
    {
        $grupos = [];

        $interlaminares = [];
        foreach (['K' => 'K', 'Na' => 'Na', 'Ca' => 'Ca'] as $cation => $etiqueta) {
            $valor = (float) ($r['cationes'][$cation] ?? 0.0);
            if ($valor >= 0.005) {
                $interlaminares[] = $etiqueta . $this->redondear($valor, 2);
            }
        }
        if ($interlaminares !== []) {
            $grupos[] = implode('', $interlaminares);
        }

        $octaedricos = [];
        $octaMapa = ['Al' => $r['al_octa'], 'Fe3' => ($r['cationes']['Fe3'] ?? 0.0), 'Fe2' => ($r['cationes']['Fe2'] ?? 0.0), 'Mg' => ($r['cationes']['Mg'] ?? 0.0), 'Ti' => ($r['cationes']['Ti'] ?? 0.0), 'Mn' => ($r['cationes']['Mn'] ?? 0.0)];
        $etiquetasOcta = ['Al' => 'Al', 'Fe3' => 'Fe3+', 'Fe2' => 'Fe2+', 'Mg' => 'Mg', 'Ti' => 'Ti', 'Mn' => 'Mn'];
        foreach ($octaMapa as $cation => $valor) {
            if ((float) $valor >= 0.005) {
                $octaedricos[] = $etiquetasOcta[$cation] . $this->redondear((float) $valor, 2);
            }
        }
        if ($octaedricos !== []) {
            $grupos[] = '(' . implode('', $octaedricos) . ')';
        }

        $tetraedricos = [];
        if ($r['si_tetra'] >= 0.005) {
            $tetraedricos[] = 'Si' . $this->redondear($r['si_tetra'], 2);
        }
        if ($r['al_tetra'] >= 0.005) {
            $tetraedricos[] = 'Al' . $this->redondear($r['al_tetra'], 2);
        }
        if ($tetraedricos !== []) {
            $grupos[] = '(' . implode('', $tetraedricos) . ')';
        }

        $formula = implode('', $grupos);
        $formula .= 'O' . $this->redondear($r['n_oxigeno'], 2);
        $formula .= '(OH)' . $this->redondear($r['oh'], 2);

        return $formula;
    }
}
