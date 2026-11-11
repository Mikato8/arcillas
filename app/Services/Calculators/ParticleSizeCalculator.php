<?php
declare(strict_types=1);

namespace App\Services\Calculators;

use InvalidArgumentException;

/**
 * Análisis granulométrico por mallas y velocidad de sedimentación Stokes.
 *
 * A partir de % retenido por malla o % pasante acumulado se interpolan D10,
 * D30, D50, D60 y D90 en escala logarítmica (tamaño vs % pasante), se calculan
 * Cu = D60/D10 y Cc = D30²/(D10·D60), y se estima la velocidad de caída de una
 * partícula con la ley de Stokes:
 *
 *   v = d² · (ρ_p − ρ_f) · g / (18 · μ)
 *
 * Tamaños a 2 decimales (µm), coeficientes a 2, velocidad a 4 decimales (cm/s).
 */
final class ParticleSizeCalculator extends AbstractCalculator
{
    public const SLUG = 'granulometria-stokes';

    private const TOTAL_MALLAS = 8;
    private const GRAVEDAD = 9.80665;

    /** @var array<int,float> */
    private const MALLAS_DEFECTO = [4750.0, 2360.0, 1180.0, 600.0, 300.0, 150.0, 75.0, 45.0];

    /** @var array<int,float> */
    private const PASANTE_DEFECTO = [100.0, 97.0, 90.0, 74.0, 52.0, 30.0, 16.0, 10.0];

    public function slug(): string
    {
        return self::SLUG;
    }

    public function nombre(): string
    {
        return 'Granulometría y velocidad de Stokes';
    }

    public function categoria(): string
    {
        return 'Mineralogía y procesos';
    }

    public function descripcion(): string
    {
        return 'Interpola D10, D30, D50, D60 y D90 de un análisis por mallas, calcula los '
            . 'coeficientes de uniformidad y curvatura, y estima la velocidad de '
            . 'sedimentación de una partícula con la ley de Stokes.';
    }

    public function campos(): array
    {
        $campos = [
            $this->campoSelect('tipo_datos', 'Tipo de datos de entrada', [
                'pasante'  => 'Porcentaje pasante acumulado',
                'retenido' => 'Porcentaje retenido por malla',
            ], [
                'requerido' => true,
                'defecto'   => 'pasante',
                'grupo'     => 'Análisis granulométrico',
                'ayuda'     => 'El porcentaje pasante acumulado debe disminuir al pasar a mallas '
                    . 'más finas. El retenido se acumula automáticamente.',
            ]),
        ];

        for ($i = 1; $i <= self::TOTAL_MALLAS; $i++) {
            $indice = $i - 1;
            $campos[] = $this->campoNumero('malla_' . $i, 'Malla ' . $i . ' · abertura', [
                'unidad'    => 'µm',
                'min'       => 0.0,
                'max'       => 1000000.0,
                'paso'      => 0.1,
                'requerido' => false,
                'defecto'   => self::MALLAS_DEFECTO[$indice],
                'grupo'     => 'Análisis granulométrico',
                'ayuda'     => 'Abertura de la malla en micrómetros. Usa 0 para omitir esta malla.',
            ]);
            $campos[] = $this->campoNumero('valor_' . $i, 'Malla ' . $i . ' · porcentaje', [
                'unidad'    => '%',
                'min'       => 0.0,
                'max'       => 100.0,
                'paso'      => 0.1,
                'requerido' => false,
                'defecto'   => self::PASANTE_DEFECTO[$indice],
                'grupo'     => 'Análisis granulométrico',
                'ayuda'     => 'Porcentaje pasante (o retenido) en esta malla, según el tipo de dato.',
            ]);
        }

        $campos[] = $this->campoNumero('diametro_stokes_um', 'Diámetro de partícula para Stokes', [
            'unidad'    => 'µm',
            'min'       => 0.1,
            'max'       => 10000.0,
            'paso'      => 0.1,
            'requerido' => true,
            'defecto'   => 50.0,
            'grupo'     => 'Sedimentación (Stokes)',
            'ayuda'     => 'Diámetro equivalente de la partícula que se quiere evaluar.',
        ]);
        $campos[] = $this->campoNumero('densidad_particula', 'Densidad de la partícula', [
            'unidad'    => 'g/cm³',
            'min'       => 0.5,
            'max'       => 20.0,
            'paso'      => 0.01,
            'requerido' => true,
            'defecto'   => 2.65,
            'grupo'     => 'Sedimentación (Stokes)',
            'ayuda'     => 'Densidad del sólido (cuarzo ≈ 2.65 g/cm³; arcilla ≈ 2.6–2.8 g/cm³).',
        ]);
        $campos[] = $this->campoNumero('densidad_fluido', 'Densidad del fluido', [
            'unidad'    => 'g/cm³',
            'min'       => 0.1,
            'max'       => 15.0,
            'paso'      => 0.001,
            'requerido' => true,
            'defecto'   => 0.998,
            'grupo'     => 'Sedimentación (Stokes)',
            'ayuda'     => 'Agua a 20 °C ≈ 0.998 g/cm³.',
        ]);
        $campos[] = $this->campoNumero('viscosidad_fluido', 'Viscosidad dinámica del fluido', [
            'unidad'    => 'cP',
            'min'       => 0.0001,
            'max'       => 100000.0,
            'paso'      => 0.001,
            'requerido' => true,
            'defecto'   => 1.002,
            'grupo'     => 'Sedimentación (Stokes)',
            'ayuda'     => 'Agua a 20 °C ≈ 1.002 cP (mPa·s).',
        ]);

        return $campos;
    }

    public function calcular(array $entrada): array
    {
        $datos = $this->datosValidados($entrada);

        $puntos = $this->leerPuntos($entrada, $datos);
        $tipo = (string) $datos['tipo_datos'];
        $puntos = $this->prepararPuntos($puntos, $tipo);

        $d10 = $this->percentil(10.0, $puntos);
        $d30 = $this->percentil(30.0, $puntos);
        $d50 = $this->percentil(50.0, $puntos);
        $d60 = $this->percentil(60.0, $puntos);
        $d90 = $this->percentil(90.0, $puntos);

        $cu = ($d10 !== null && $d60 !== null && $d10 > 0.0) ? $d60 / $d10 : null;
        $cc = ($cu !== null && $d30 !== null && $d10 > 0.0 && $d60 > 0.0)
            ? ($d30 * $d30) / ($d10 * $d60)
            : null;

        $diametroUm = (float) $datos['diametro_stokes_um'];
        $densidadParticula = (float) $datos['densidad_particula'];
        $densidadFluido = (float) $datos['densidad_fluido'];
        $viscosidadCp = (float) $datos['viscosidad_fluido'];

        if ($densidadParticula <= $densidadFluido) {
            throw new InvalidArgumentException(
                'La densidad de la partícula debe ser mayor que la densidad del fluido para '
                . 'aplicar la ley de Stokes.'
            );
        }
        if ($viscosidadCp <= 0.0) {
            throw new InvalidArgumentException(
                'La viscosidad del fluido debe ser mayor que cero.'
            );
        }

        $diametroM = $diametroUm * 1.0e-6;
        $deltaDensidad = ($densidadParticula - $densidadFluido) * 1000.0; // kg/m³
        $viscosidadPaS = $viscosidadCp * 1.0e-3;
        $velocidadMs = ($diametroM * $diametroM * $deltaDensidad * self::GRAVEDAD)
            / (18.0 * $viscosidadPaS);
        $velocidadCmS = $velocidadMs * 100.0;
        $velocidadMmS = $velocidadMs * 1000.0;
        $reynolds = (($densidadFluido * 1000.0) * $velocidadMs * $diametroM) / $viscosidadPaS;

        $resultados = [
            $this->resultado('D50 (diámetro mediano)', $d50 === null ? null : $this->redondear($d50, 2), 'µm', true),
            $this->resultado('D10', $d10 === null ? null : $this->redondear($d10, 2), 'µm'),
            $this->resultado('D90', $d90 === null ? null : $this->redondear($d90, 2), 'µm'),
            $this->resultado(
                'Coeficiente de uniformidad Cu',
                $cu === null ? null : $this->redondear($cu, 2),
                '×',
                true
            ),
            $this->resultado(
                'Coeficiente de curvatura Cc',
                $cc === null ? null : $this->redondear($cc, 2),
                '×'
            ),
            $this->resultado(
                'Velocidad de sedimentación Stokes',
                $this->redondear($velocidadCmS, 4),
                'cm/s',
                true
            ),
            $this->resultado(
                'Graduación del material',
                $this->clasificarGraduacion($cu, $cc),
                ''
            ),
        ];

        $detalle = [];
        foreach ($puntos as $punto) {
            $detalle[] = $this->fila(
                'Malla ' . $this->numeroCorto($punto['malla']) . ' µm · % pasante',
                $this->redondear($punto['pasante'], 2),
                '%'
            );
        }
        $detalle[] = $this->fila('D30', $d30 === null ? null : $this->redondear($d30, 2), 'µm');
        $detalle[] = $this->fila('D60', $d60 === null ? null : $this->redondear($d60, 2), 'µm');
        $detalle[] = $this->fila('Mallas con datos', count($puntos), '');
        $detalle[] = $this->fila(
            'Velocidad de sedimentación',
            $this->redondear($velocidadMmS, 3),
            'mm/s'
        );
        $detalle[] = $this->fila('Número de Reynolds de la partícula', $this->redondear($reynolds, 4), '');
        $detalle[] = $this->fila(
            'Régimen de Stokes',
            $reynolds <= 1.0 ? 'Válido (Re ≤ 1)' : 'Fuera del régimen ideal (Re > 1)',
            ''
        );
        $detalle[] = $this->fila(
            'Diferencia de densidad partícula − fluido',
            $this->redondear($densidadParticula - $densidadFluido, 4),
            'g/cm³'
        );
        $detalle[] = $this->fila(
            'Rango de % pasante analizado',
            $this->redondear($puntos[0]['pasante'], 2) . ' a '
                . $this->redondear($puntos[count($puntos) - 1]['pasante'], 2),
            '%'
        );

        $advertencias = [];
        $faltantes = [];
        foreach (['D10' => $d10, 'D30' => $d30, 'D50' => $d50, 'D60' => $d60, 'D90' => $d90] as $nombre => $valor) {
            if ($valor === null) {
                $faltantes[] = $nombre;
            }
        }
        if ($faltantes !== []) {
            $advertencias[] = 'No se pudieron interpolar ' . implode(', ', $faltantes)
                . ': el porcentaje buscado queda fuera del intervalo analizado ('
                . $this->numeroCorto($this->redondear($puntos[0]['pasante'], 2)) . '–'
                . $this->numeroCorto($this->redondear($puntos[count($puntos) - 1]['pasante'], 2))
                . ' % pasante). Agrega mallas más gruesas o más finas.';
        }
        if ($cu === null) {
            $advertencias[] = 'No se pudo calcular Cu ni Cc porque faltan D10 o D60; '
                . 'la clasificación granulométrica queda indeterminada.';
        } elseif ($cu >= 6.0 && ($cc === null || $cc < 1.0 || $cc > 3.0)) {
            $advertencias[] = 'El coeficiente de curvatura Cc está fuera del intervalo 1–3; '
                . 'la muestra no se considera bien graduada aunque Cu sea alto.';
        }
        if ($reynolds > 1.0) {
            $advertencias[] = 'El número de Reynolds es mayor que 1 ('
                . $this->numeroCorto($this->redondear($reynolds, 3))
                . '): la ley de Stokes no es estrictamente válida; la velocidad es orientativa.';
        }
        if ($diametroUm > 500.0) {
            $advertencias[] = 'Para diámetros mayores a 500 µm el régimen suele ser transicional '
                . 'o turbulento; la velocidad de Stokes tiende a sobreestimar la caída.';
        }
        if (($densidadParticula - $densidadFluido) < 0.2) {
            $advertencias[] = 'La diferencia de densidad entre partícula y fluido es muy baja '
                . '(< 0.2 g/cm³): la sedimentación será muy lenta y sensible a la temperatura.';
        }

        $formulas = [
            'Interpolación logarítmica: log D = log D₁ + (x − P₁)/(P₂ − P₁) · (log D₂ − log D₁)',
            'Cu = D60 / D10',
            'Cc = D30² / (D10 · D60)',
            'Ley de Stokes: v = d² · (ρ_p − ρ_f) · g / (18 · μ)',
            'Reynolds: Re = ρ_f · v · d / μ (Stokes válido para Re ≤ 1)',
        ];

        return $this->salida($resultados, $detalle, $advertencias, $formulas);
    }

    // ─────────────────────────────────────────────────────────────────
    // Lectura y validación de mallas
    // ─────────────────────────────────────────────────────────────────

    /**
     * @param array<string,mixed>        $entrada
     * @param array<string,float|string> $datos
     * @return array<int,array{malla:float,valor:float}>
     */
    private function leerPuntos(array $entrada, array $datos): array
    {
        $puntos = [];
        if (array_key_exists('mallas', $entrada)) {
            if (!is_array($entrada['mallas'])) {
                throw new InvalidArgumentException(
                    'El campo «mallas» debe ser una lista de pares abertura/porcentaje.'
                );
            }
            foreach ($entrada['mallas'] as $item) {
                if (!is_array($item)) {
                    throw new InvalidArgumentException(
                        'Cada malla debe ser un arreglo con «malla_um» y «valor».'
                    );
                }
                $malla = $item['malla_um'] ?? $item['malla'] ?? $item['abertura_um'] ?? null;
                $valor = $item['valor'] ?? $item['porcentaje'] ?? $item['pasante']
                    ?? $item['retenido'] ?? null;
                if ($malla === null || $valor === null) {
                    throw new InvalidArgumentException(
                        'Cada malla debe incluir «malla_um» y «valor».'
                    );
                }
                $puntos[] = [
                    'malla' => $this->numeroSuelto($malla, 'abertura de malla'),
                    'valor' => $this->numeroSuelto($valor, 'porcentaje de malla'),
                ];
            }
        } else {
            for ($i = 1; $i <= self::TOTAL_MALLAS; $i++) {
                $malla = (float) $datos['malla_' . $i];
                $valor = (float) $datos['valor_' . $i];
                if ($malla <= 0.0) {
                    continue;
                }
                $puntos[] = ['malla' => $malla, 'valor' => $valor];
            }
        }

        if (count($puntos) < 2) {
            throw new InvalidArgumentException(
                'Se requieren al menos dos mallas con datos para interpolar los percentiles.'
            );
        }

        return $puntos;
    }

    /**
     * Ordena de grueso a fino, valida monotonía y completa el % pasante.
     *
     * @param array<int,array{malla:float,valor:float}> $puntos
     * @return array<int,array{malla:float,pasante:float}>
     */
    private function prepararPuntos(array $puntos, string $tipo): array
    {
        foreach ($puntos as $punto) {
            if ($punto['malla'] <= 0.0) {
                throw new InvalidArgumentException(
                    'La abertura de cada malla debe ser mayor que cero micrómetros.'
                );
            }
            if (!is_finite($punto['malla']) || $punto['malla'] > 1000000.0) {
                throw new InvalidArgumentException(
                    'La abertura de malla debe estar entre 0 y 1 000 000 µm.'
                );
            }
            if ($punto['valor'] < 0.0 || $punto['valor'] > 100.0) {
                throw new InvalidArgumentException(
                    'El porcentaje de cada malla debe estar entre 0 y 100 %.'
                );
            }
        }

        usort(
            $puntos,
            static fn (array $a, array $b): int => $b['malla'] <=> $a['malla']
        );

        $vistas = [];
        foreach ($puntos as $punto) {
            $clave = sprintf('%.6F', $punto['malla']);
            if (isset($vistas[$clave])) {
                throw new InvalidArgumentException(
                    'La abertura de malla ' . $this->numeroCorto($punto['malla'])
                    . ' µm está repetida; elimina una de las dos entradas.'
                );
            }
            $vistas[$clave] = true;
        }

        $resultado = [];
        if ($tipo === 'retenido') {
            $suma = 0.0;
            foreach ($puntos as $punto) {
                $suma += $punto['valor'];
            }
            if ($suma > 100.5) {
                throw new InvalidArgumentException(
                    'La suma de los porcentajes retenidos es '
                    . $this->numeroCorto($this->redondear($suma, 2))
                    . ' % y no puede exceder 100 %.'
                );
            }

            $retenidoAcumulado = 0.0;
            foreach ($puntos as $punto) {
                $retenidoAcumulado += $punto['valor'];
                $pasante = 100.0 - $retenidoAcumulado;
                $resultado[] = [
                    'malla'   => $punto['malla'],
                    'pasante' => max(0.0, min(100.0, $pasante)),
                ];
            }
        } else {
            $anterior = null;
            $anteriorMalla = 0.0;
            foreach ($puntos as $punto) {
                $pasante = $punto['valor'];
                if ($anterior !== null && $pasante > $anterior + 0.5) {
                    throw new InvalidArgumentException(
                        'Los porcentajes pasantes deben disminuir al pasar a mallas más finas: '
                        . 'la malla ' . $this->numeroCorto($anteriorMalla ?? 0.0) . ' µm da '
                        . $this->numeroCorto($this->redondear($anterior, 2)) . ' % y la malla '
                        . $this->numeroCorto($punto['malla']) . ' µm da '
                        . $this->numeroCorto($this->redondear($pasante, 2)) . ' %.'
                    );
                }
                if ($anterior !== null && $pasante > $anterior) {
                    $pasante = $anterior; // diferencia de redondeo, se aplana
                }
                $resultado[] = ['malla' => $punto['malla'], 'pasante' => $pasante];
                $anterior = $pasante;
                $anteriorMalla = $punto['malla'];
            }
        }

        return $resultado;
    }

    /**
     * Interpola el tamaño correspondiente a un porcentaje pasante.
     *
     * @param array<int,array{malla:float,pasante:float}> $puntos ordenados de grueso a fino
     */
    private function percentil(float $porcentaje, array $puntos): ?float
    {
        $primero = $puntos[0];
        $ultimo = $puntos[count($puntos) - 1];
        if ($porcentaje > $primero['pasante'] + 1.0e-9) {
            return null;
        }
        if ($porcentaje < $ultimo['pasante'] - 1.0e-9) {
            return null;
        }

        $total = count($puntos);
        for ($i = 0; $i < $total - 1; $i++) {
            $p1 = $puntos[$i]['pasante'];
            $p2 = $puntos[$i + 1]['pasante'];
            if ($porcentaje > $p1 + 1.0e-9 || $porcentaje < $p2 - 1.0e-9) {
                continue;
            }
            if (abs($p1 - $p2) < 1.0e-9) {
                if (abs($porcentaje - $p1) < 1.0e-9) {
                    return $puntos[$i]['malla'];
                }
                continue;
            }
            $fraccion = ($porcentaje - $p1) / ($p2 - $p1);
            $logaritmo = log($puntos[$i]['malla'])
                + $fraccion * (log($puntos[$i + 1]['malla']) - log($puntos[$i]['malla']));

            return exp($logaritmo);
        }

        if (abs($porcentaje - $ultimo['pasante']) < 1.0e-9) {
            return $ultimo['malla'];
        }
        if (abs($porcentaje - $primero['pasante']) < 1.0e-9) {
            return $primero['malla'];
        }

        return null;
    }

    private function clasificarGraduacion(?float $cu, ?float $cc): string
    {
        if ($cu === null) {
            return 'No determinada (faltan D10 o D60)';
        }
        if ($cu < 4.0) {
            return 'Uniforme (mal graduada)';
        }
        if ($cu >= 6.0 && $cc !== null && $cc >= 1.0 && $cc <= 3.0) {
            return 'Bien graduada';
        }

        return 'Graduación intermedia o mal graduada';
    }
}
