<?php
declare(strict_types=1);

namespace App\Services\Calculators;

use InvalidArgumentException;
use RuntimeException;

/**
 * Calculadora de fórmula molecular unitaria (UMF / fórmula de Seger) de un
 * esmalte cerámico a partir de la receta en partes o gramos.
 *
 * Normalización estándar (Seger/Digitalfire/Glazy): los moles de óxidos se
 * dividen entre las moles totales de fundentes RO (Na2O, K2O, Li2O, CaO, MgO,
 * ZnO, CoO) para que ΣRO = 1.0000. Los sesquióxidos (Al2O3, B2O3, Fe2O3) y los
 * óxidos RO2 (SiO2, TiO2, ZrO2) se reportan como moles relativos a esa base.
 * Los moles se redondean a 4 decimales y la relación Si:Al a 3.
 */
final class UmfCalculator extends AbstractCalculator
{
    public const SLUG = 'umf-esmalte';

    private const DECIMALES_MOL = 4;
    private const DECIMALES_RELACION = 3;
    private const DECIMALES_DETALLE = 6;

    /** Órdenes de salida solicitados, siempre presentes aunque valgan 0. */
    private const ORDEN_SALIDA = [
        'SiO2', 'Al2O3', 'B2O3', 'Na2O', 'K2O', 'CaO', 'MgO', 'Fe2O3', 'TiO2', 'ZrO2',
    ];

    /** @var array<string,mixed>|null */
    private static ?array $biblioteca = null;

    public function slug(): string
    {
        return self::SLUG;
    }

    public function nombre(): string
    {
        return 'Fórmula molecular unitaria (UMF / Seger)';
    }

    public function categoria(): string
    {
        return 'Cerámica y esmaltes';
    }

    public function descripcion(): string
    {
        return 'Convierte la receta de un esmalte en fórmula molecular unitaria: moles '
            . 'de SiO2, Al2O3, B2O3 y demás óxidos con los fundentes RO normalizados a '
            . 'ΣRO = 1 (convención Seger estándar), relación Si:Al y clasificación '
            . 'orientativa del esmalte.';
    }

    public function campos(): array
    {
        $campos = [];
        foreach ($this->biblioteca()['materiales'] as $clave => $material) {
            $analisis = [];
            foreach ($material['analisis'] as $oxido => $pct) {
                $analisis[] = $oxido . ' ' . $this->numeroCorto((float) $pct) . ' %';
            }

            $campos[] = $this->campoNumero(
                'mat_' . $clave,
                (string) $material['nombre'],
                [
                    'unidad'    => 'g',
                    'min'       => 0.0,
                    'max'       => 10000.0,
                    'paso'      => 0.1,
                    'requerido' => false,
                    'defecto'   => 0.0,
                    'grupo'     => 'Receta (partes o gramos)',
                    'ayuda'     => 'Fórmula ' . $material['formula'] . '. Análisis tipo: '
                        . implode(' · ', $analisis) . '.',
                ]
            );
        }

        return $campos;
    }

    public function calcular(array $entrada): array
    {
        $datos = $this->datosValidados($entrada);
        $biblioteca = $this->biblioteca();
        $materiales = $biblioteca['materiales'];
        $masas = $biblioteca['masas_moleculares'];

        $receta = $this->leerReceta($entrada, $datos);
        $moles = [];
        $masaTotal = 0.0;
        $loiTotal = 0.0;

        foreach ($receta as $claveMaterial => $cantidad) {
            $material = $materiales[$claveMaterial];
            $masaTotal += $cantidad;
            $loiTotal += $cantidad * ((float) $material['loi'] / 100.0);

            foreach ($material['analisis'] as $oxido => $pct) {
                if (!isset($masas[$oxido]) || (float) $masas[$oxido] <= 0.0) {
                    throw new RuntimeException(
                        'La biblioteca cerámica no define la masa molecular de ' . $oxido . '.'
                    );
                }
                $moles[$oxido] = ($moles[$oxido] ?? 0.0)
                    + ($cantidad * ((float) $pct / 100.0)) / (float) $masas[$oxido];
            }
        }

        if ($masaTotal <= 0.0) {
            throw new InvalidArgumentException(
                'La receta debe tener una masa total mayor que cero para calcular la fórmula unitaria.'
            );
        }

        $normalizacion = $this->normalizarMoles($moles);
        $sumaRo = $normalizacion['suma_ro'];
        $sumaR2o3 = $normalizacion['suma_r2o3'];
        $factor = $normalizacion['factor'];
        $formula = $normalizacion['formula'];

        $sio2 = $formula['SiO2'] ?? 0.0;
        $al2o3 = $formula['Al2O3'] ?? 0.0;
        $b2o3 = $formula['B2O3'] ?? 0.0;
        $fe2o3 = $formula['Fe2O3'] ?? 0.0;
        $coo = $formula['CoO'] ?? 0.0;
        $zno = $formula['ZnO'] ?? 0.0;
        $knao = ($formula['K2O'] ?? 0.0) + ($formula['Na2O'] ?? 0.0);
        $sumaFundentes = $sumaRo > 0.0 ? $sumaRo * $factor : 0.0;

        $relacionSiAl = $al2o3 > 0.0 ? $sio2 / $al2o3 : null;
        $caracter = $this->clasificarCaracter($relacionSiAl);
        $fundente = $this->clasificarFundente($formula, $sumaFundentes);

        $resultados = [];
        foreach (self::ORDEN_SALIDA as $oxido) {
            $resultados[] = $this->resultado(
                $oxido,
                $this->redondear($formula[$oxido] ?? 0.0, self::DECIMALES_MOL),
                'mol',
                $oxido === 'SiO2' || $oxido === 'Al2O3'
            );
        }
        // Óxidos presentes fuera de la lista estándar (Li2O, ZnO, CoO…).
        foreach ($formula as $oxido => $valor) {
            if (in_array($oxido, self::ORDEN_SALIDA, true) || $valor <= 0.0) {
                continue;
            }
            $resultados[] = $this->resultado(
                $oxido,
                $this->redondear($valor, self::DECIMALES_MOL),
                'mol'
            );
        }

        $resultados[] = $this->resultado(
            'Relación Si:Al',
            $relacionSiAl === null ? null : $this->redondear($relacionSiAl, self::DECIMALES_RELACION),
            'mol/mol',
            true
        );
        $resultados[] = $this->resultado('Carácter del esmalte (Si:Al)', $caracter, '', true);
        $resultados[] = $this->resultado('Tipo de fundente dominante', $fundente, '');

        $detalle = [
            $this->fila('Masa total de la receta', $this->redondear($masaTotal, 3), 'g'),
            $this->fila('Materiales en la receta', count($receta), ''),
            $this->fila(
                'Suma RO (moles sin normalizar)',
                $this->redondear($sumaRo, self::DECIMALES_DETALLE),
                'mol'
            ),
            $this->fila(
                'Suma R2O3 (moles sin normalizar)',
                $this->redondear($sumaR2o3, self::DECIMALES_DETALLE),
                'mol'
            ),
            $this->fila(
                'Factor de normalización (1 / ΣRO)',
                $this->redondear($factor, self::DECIMALES_DETALLE),
                ''
            ),
            $this->fila(
                'ΣRO normalizada',
                $this->redondear($sumaRo * $factor, self::DECIMALES_DETALLE),
                'mol'
            ),
            $this->fila(
                'LOI estimada de la receta',
                $this->redondear(($loiTotal / $masaTotal) * 100.0, 2),
                '%'
            ),
        ];
        foreach ($receta as $claveMaterial => $cantidad) {
            $detalle[] = $this->fila(
                'Aporte: ' . $materiales[$claveMaterial]['nombre'],
                $this->redondear($cantidad, 3),
                'g'
            );
        }

        $advertencias = [];
        if ($al2o3 <= 0.0) {
            $advertencias[] = 'La receta no contiene alúmina (Al2O3); la fórmula no es '
                . 'viable como esmalte estable y no se puede calcular la relación Si:Al.';
        }
        if ($sio2 <= 0.0) {
            $advertencias[] = 'La receta no contiene sílice (SiO2); revisa la formulación '
                . 'porque el esmalte tendría muy poca red vítrea.';
        }
        if ($relacionSiAl !== null && $relacionSiAl > 13.0) {
            $advertencias[] = 'Relación Si:Al muy alta (> 13): el esmalte es muy silícico, '
                . 'con riesgo de desconchado y baja resistencia química.';
        }
        if ($relacionSiAl !== null && $relacionSiAl < 4.0) {
            $advertencias[] = 'Relación Si:Al baja (< 4): el esmalte es muy aluminoso, '
                . 'tenderá a mate/opaco y puede no madurar a la temperatura prevista.';
        }
        if ($b2o3 > 0.5) {
            $advertencias[] = 'Contenido bórico alto (B2O3 > 0.5 mol): verifica la '
                . 'estabilidad del esmalte y el riesgo de exudación de boro.';
        }
        if ($sumaFundentes > 0.0 && $knao / $sumaFundentes > 0.65) {
            $advertencias[] = 'Los álcalis (Na2O + K2O) dominan los fundentes: el esmalte '
                . 'tendrá mayor expansión térmica y menor resistencia química.';
        }
        if ($zno > 0.35) {
            $advertencias[] = 'Óxido de zinc alto (ZnO > 0.35 mol): puede cristalizar, '
                . 'provocar matez no deseado o desconchado.';
        }
        if ($fe2o3 > 0.05) {
            $advertencias[] = 'El Fe2O3 actúa como colorante además de modificar la fusión; '
                . 'el color depende de la atmósfera del horno.';
        }
        if ($coo > 0.02) {
            $advertencias[] = 'El CoO es un colorante fuerte: 0.02 mol ya da azul intenso; '
                . 'revisa la dosis antes de cocer.';
        }

        $formulas = [
            'n(óxido) = Σ [masa_material (g) × %óxido / 100] / masa_molecular(óxido)',
            'Fórmula unitaria = n(óxido) / Σ n(RO); por definición ΣRO = 1 (convención Seger estándar)',
            'Al2O3, B2O3, Fe2O3, SiO2, TiO2 y ZrO2 se reportan como moles relativos a ΣRO = 1',
            'Relación Si:Al = n(SiO2) / n(Al2O3); es invariante a la normalización',
        ];

        return $this->salida($resultados, $detalle, $advertencias, $formulas);
    }

    // ─────────────────────────────────────────────────────────────────
    // Normalización de la fórmula
    // ─────────────────────────────────────────────────────────────────

    /**
     * Normaliza una fórmula molecular ya expresada en moles de óxidos a
     * ΣRO = 1. Acepta moles sin normalizar de cualquier escala; es útil para
     * importar o verificar UMF externas (por ejemplo, de Digitalfire/Glazy).
     *
     * @param array<string,mixed> $molesOxidos óxido => moles
     * @return array<string,float> óxido => moles normalizados (ΣRO = 1)
     * @throws InvalidArgumentException
     */
    public function normalizarFormula(array $molesOxidos): array
    {
        if ($molesOxidos === []) {
            throw new InvalidArgumentException(
                'Debes capturar al menos un óxido con sus moles para normalizar la fórmula.'
            );
        }

        $masas = $this->biblioteca()['masas_moleculares'];
        $moles = [];
        foreach ($molesOxidos as $oxido => $valor) {
            $clave = trim((string) $oxido);
            if (!isset($masas[$clave])) {
                throw new InvalidArgumentException(
                    'El óxido «' . $this->recortar($clave) . '» no está en la biblioteca '
                    . 'cerámica. Óxidos disponibles: ' . implode(', ', array_keys($masas)) . '.'
                );
            }
            $molesOxido = $this->numeroSuelto($valor, 'moles de ' . $clave);
            if ($molesOxido < 0.0) {
                throw new InvalidArgumentException(
                    'Los moles de «' . $clave . '» no pueden ser negativos.'
                );
            }
            $moles[$clave] = ($moles[$clave] ?? 0.0) + $molesOxido;
        }

        return $this->normalizarMoles($moles)['formula'];
    }

    /**
     * @param array<string,float> $moles moles de óxidos sin normalizar
     * @return array{formula:array<string,float>,suma_ro:float,suma_r2o3:float,factor:float}
     * @throws InvalidArgumentException Si no hay fundentes RO.
     */
    private function normalizarMoles(array $moles): array
    {
        $grupos = $this->biblioteca()['grupos'];

        $sumaRo = 0.0;
        foreach ($grupos['RO'] as $oxido) {
            $sumaRo += $moles[$oxido] ?? 0.0;
        }
        $sumaR2o3 = 0.0;
        foreach ($grupos['R2O3'] as $oxido) {
            $sumaR2o3 += $moles[$oxido] ?? 0.0;
        }

        if ($sumaRo <= 0.0) {
            throw new InvalidArgumentException(
                'La receta no contiene fundentes RO (Na2O, K2O, Li2O, CaO, MgO, ZnO, CoO); '
                . 'no es posible normalizar la fórmula unitaria a ΣRO = 1. Agrega, por '
                . 'ejemplo, feldespato, carbonato de calcio o dolomita.'
            );
        }

        $factor = 1.0 / $sumaRo;
        $formula = [];
        foreach ($moles as $oxido => $valor) {
            $formula[$oxido] = $valor * $factor;
        }

        return [
            'formula'   => $formula,
            'suma_ro'   => $sumaRo,
            'suma_r2o3' => $sumaR2o3,
            'factor'    => $factor,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // Entrada
    // ─────────────────────────────────────────────────────────────────

    /**
     * Acepta dos formatos:
     *   1) Campos planos `mat_<clave_material>` con gramos o partes.
     *   2) `receta` como lista [['material'=>'caolin','cantidad'=>50], …] o
     *      como mapa ['caolin'=>50, 'silice'=>25].
     *
     * @param array<string,mixed>         $entrada
     * @param array<string,float|string>  $datos
     * @return array<string,float>
     */
    private function leerReceta(array $entrada, array $datos): array
    {
        $receta = [];

        if (array_key_exists('receta', $entrada)) {
            $recetaBruta = $entrada['receta'];
            if (!is_array($recetaBruta) || $recetaBruta === []) {
                throw new InvalidArgumentException(
                    'El campo «receta» debe ser una lista o mapa de materiales con su cantidad.'
                );
            }
            foreach ($recetaBruta as $indice => $item) {
                if (is_array($item)) {
                    $clave = $item['material'] ?? $item['clave'] ?? $item['material_clave'] ?? null;
                    $cantidad = $item['cantidad'] ?? $item['gramos'] ?? $item['partes']
                        ?? $item['peso'] ?? null;
                    if (!is_string($clave) || $clave === '') {
                        throw new InvalidArgumentException(
                            'Cada elemento de «receta» debe incluir la clave del material.'
                        );
                    }
                    if ($cantidad === null) {
                        throw new InvalidArgumentException(
                            'Cada elemento de «receta» debe incluir una cantidad.'
                        );
                    }
                } elseif (is_string($indice) && $indice !== '') {
                    $clave = $indice;
                    $cantidad = $item;
                } else {
                    throw new InvalidArgumentException(
                        'El campo «receta» debe ser una lista [material => cantidad] o un mapa.'
                    );
                }

                $this->agregarMaterial($receta, $clave, $cantidad);
            }

            return $receta;
        }

        foreach ($datos as $claveCampo => $valor) {
            if (!str_starts_with($claveCampo, 'mat_')) {
                continue;
            }
            $claveMaterial = substr($claveCampo, 4);
            $cantidad = (float) $valor;
            if ($cantidad < 0.0) {
                throw new InvalidArgumentException(
                    'La cantidad de «' . $claveMaterial . '» no puede ser negativa.'
                );
            }
            if ($cantidad > 0.0) {
                $receta[$claveMaterial] = ($receta[$claveMaterial] ?? 0.0) + $cantidad;
            }
        }

        if ($receta === []) {
            throw new InvalidArgumentException(
                'Captura al menos un material con cantidad mayor que cero para calcular '
                . 'la fórmula molecular unitaria.'
            );
        }

        return $receta;
    }

    /** @param array<string,float> $receta */
    private function agregarMaterial(array &$receta, string $clave, mixed $cantidad): void
    {
        $clave = trim($clave);
        $biblioteca = $this->biblioteca();
        if (!isset($biblioteca['materiales'][$clave])) {
            throw new InvalidArgumentException(
                'El material «' . $this->recortar($clave) . '» no está en la biblioteca '
                . 'cerámica. Materiales disponibles: '
                . implode(', ', array_keys($biblioteca['materiales'])) . '.'
            );
        }

        $valor = $this->numeroSuelto($cantidad, 'cantidad de ' . $clave);
        if ($valor < 0.0) {
            throw new InvalidArgumentException(
                'La cantidad de «' . $biblioteca['materiales'][$clave]['nombre']
                . '» no puede ser negativa.'
            );
        }
        if ($valor == 0.0) {
            return;
        }

        $receta[$clave] = ($receta[$clave] ?? 0.0) + $valor;
    }

    // ─────────────────────────────────────────────────────────────────
    // Clasificación orientativa
    // ─────────────────────────────────────────────────────────────────

    private function clasificarCaracter(?float $relacionSiAl): string
    {
        if ($relacionSiAl === null) {
            return 'Sin alúmina: no aplica';
        }
        if ($relacionSiAl < 4.0) {
            return 'Muy aluminoso (mate/opaco)';
        }
        if ($relacionSiAl < 7.0) {
            return 'Aluminoso (satinado a mate)';
        }
        if ($relacionSiAl < 10.0) {
            return 'Equilibrado (brillante estable)';
        }
        if ($relacionSiAl < 13.0) {
            return 'Silícico (brillante, riesgo de craquelado)';
        }

        return 'Muy silícico (riesgo alto de desconchado)';
    }

    /** @param array<string,float> $formula */
    private function clasificarFundente(array $formula, float $sumaFundentes): string
    {
        $b2o3 = $formula['B2O3'] ?? 0.0;
        if ($b2o3 > 0.25) {
            return 'Bórico (B2O3 dominante)';
        }
        if ($sumaFundentes <= 0.0) {
            return 'Sin fundentes RO';
        }

        $knao = ($formula['K2O'] ?? 0.0) + ($formula['Na2O'] ?? 0.0);
        $cao = $formula['CaO'] ?? 0.0;
        $mgo = $formula['MgO'] ?? 0.0;
        $zno = $formula['ZnO'] ?? 0.0;

        if ($knao / $sumaFundentes > 0.60) {
            return 'Alcalino (KNaO dominante)';
        }
        if ($cao / $sumaFundentes > 0.50) {
            return 'Cálcico';
        }
        if ($mgo / $sumaFundentes > 0.35) {
            return 'Magnésico';
        }
        if ($zno / $sumaFundentes > 0.25) {
            return 'Zincífero';
        }

        return 'Mixto';
    }

    // ─────────────────────────────────────────────────────────────────
    // Biblioteca de materiales
    // ─────────────────────────────────────────────────────────────────

    /** @return array<string,mixed> */
    private function biblioteca(): array
    {
        if (self::$biblioteca === null) {
            $datos = require __DIR__ . '/Data/ceramic_materials.php';
            if (!is_array($datos) || !isset($datos['materiales'], $datos['masas_moleculares'])) {
                throw new RuntimeException(
                    'La biblioteca de materiales cerámicos no tiene el formato esperado.'
                );
            }
            self::$biblioteca = $datos;
        }

        return self::$biblioteca;
    }
}
