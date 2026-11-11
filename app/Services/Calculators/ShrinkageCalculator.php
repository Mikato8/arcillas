<?php
declare(strict_types=1);

namespace App\Services\Calculators;

use InvalidArgumentException;

/**
 * Contracción de secado, cocción y total de una pasta cerámica a partir de
 * largos en verde, seco y cocido; corrección de molde y absorción de agua.
 *
 * Contracción de una etapa = (L_entrada − L_salida) / L_entrada × 100.
 * La contracción total no es la suma aritmética: se calcula con el largo en
 * verde como referencia. Porcentajes a 2 decimales, longitudes a 2.
 */
final class ShrinkageCalculator extends AbstractCalculator
{
    public const SLUG = 'contraccion-absorcion';

    public function slug(): string
    {
        return self::SLUG;
    }

    public function nombre(): string
    {
        return 'Contracción y absorción de una pasta';
    }

    public function categoria(): string
    {
        return 'Cerámica y esmaltes';
    }

    public function descripcion(): string
    {
        return 'Calcula la contracción de secado, cocción y total con largos en verde, '
            . 'seco y cocido, corrige la medida del molde para una pieza final, y estima '
            . 'la absorción de agua con pesos seco y saturado.';
    }

    public function campos(): array
    {
        return [
            $this->campoNumero('largo_verde', 'Largo en verde', [
                'unidad'    => 'mm',
                'min'       => 0.001,
                'max'       => 100000.0,
                'paso'      => 0.1,
                'requerido' => true,
                'defecto'   => 100.0,
                'grupo'     => 'Largos de referencia',
                'ayuda'     => 'Largo de la probeta o pieza recién moldeada.',
            ]),
            $this->campoNumero('largo_seco', 'Largo en seco', [
                'unidad'    => 'mm',
                'min'       => 0.001,
                'max'       => 100000.0,
                'paso'      => 0.1,
                'requerido' => true,
                'defecto'   => 92.0,
                'grupo'     => 'Largos de referencia',
                'ayuda'     => 'Largo medido después del secado, antes de cocer.',
            ]),
            $this->campoNumero('largo_cocido', 'Largo cocido', [
                'unidad'    => 'mm',
                'min'       => 0.001,
                'max'       => 100000.0,
                'paso'      => 0.1,
                'requerido' => true,
                'defecto'   => 88.0,
                'grupo'     => 'Largos de referencia',
                'ayuda'     => 'Largo medido después de la cocción.',
            ]),
            $this->campoNumero('medida_final_deseada', 'Medida final deseada (opcional)', [
                'unidad'    => 'mm',
                'min'       => 0.0,
                'max'       => 100000.0,
                'paso'      => 0.1,
                'requerido' => false,
                'defecto'   => 0.0,
                'grupo'     => 'Corrección de molde',
                'ayuda'     => 'Si es mayor que 0, se calcula el largo en verde necesario '
                    . 'para obtener esa medida cocida. Usa 0 para omitir.',
            ]),
            $this->campoNumero('peso_seco', 'Peso seco (opcional)', [
                'unidad'    => 'g',
                'min'       => 0.0,
                'max'       => 1000000000.0,
                'paso'      => 0.1,
                'requerido' => false,
                'defecto'   => 0.0,
                'grupo'     => 'Absorción de agua',
                'ayuda'     => 'Peso de la probeta seca. Deja 0 si no mediste absorción.',
            ]),
            $this->campoNumero('peso_saturado', 'Peso saturado (opcional)', [
                'unidad'    => 'g',
                'min'       => 0.0,
                'max'       => 1000000000.0,
                'paso'      => 0.1,
                'requerido' => false,
                'defecto'   => 0.0,
                'grupo'     => 'Absorción de agua',
                'ayuda'     => 'Peso después de saturar en agua y escurrir la gota superficial.',
            ]),
        ];
    }

    public function calcular(array $entrada): array
    {
        $datos = $this->datosValidados($entrada);

        $largoVerde = (float) $datos['largo_verde'];
        $largoSeco = (float) $datos['largo_seco'];
        $largoCocido = (float) $datos['largo_cocido'];
        $medidaFinal = (float) $datos['medida_final_deseada'];
        $pesoSeco = (float) $datos['peso_seco'];
        $pesoSaturado = (float) $datos['peso_saturado'];

        if ($largoVerde <= 0.0 || $largoSeco <= 0.0 || $largoCocido <= 0.0) {
            throw new InvalidArgumentException(
                'Los largos en verde, seco y cocido deben ser mayores que cero.'
            );
        }

        $contraccionSecado = (($largoVerde - $largoSeco) / $largoVerde) * 100.0;
        $contraccionCoccion = (($largoSeco - $largoCocido) / $largoSeco) * 100.0;
        $contraccionTotal = (($largoVerde - $largoCocido) / $largoVerde) * 100.0;

        $resultados = [
            $this->resultado('Contracción de secado', $this->redondear($contraccionSecado, 2), '%', true),
            $this->resultado('Contracción de cocción', $this->redondear($contraccionCoccion, 2), '%'),
            $this->resultado('Contracción total', $this->redondear($contraccionTotal, 2), '%', true),
        ];

        $largoVerdeNecesario = null;
        $factorMolde = null;
        if ($medidaFinal > 0.0) {
            $fraccionTotal = $contraccionTotal / 100.0;
            if ($fraccionTotal >= 1.0) {
                throw new InvalidArgumentException(
                    'La contracción total es del 100 % o más: no es posible corregir el molde '
                    . 'con estos largos.'
                );
            }
            $factorMolde = 1.0 / (1.0 - $fraccionTotal);
            $largoVerdeNecesario = $medidaFinal * $factorMolde;
            $resultados[] = $this->resultado(
                'Largo en verde necesario',
                $this->redondear($largoVerdeNecesario, 2),
                'mm',
                true
            );
            $resultados[] = $this->resultado(
                'Factor de sobredimensionado del molde',
                $this->redondear($factorMolde, 4),
                '×'
            );
        }

        $absorcion = null;
        if ($pesoSeco > 0.0 || $pesoSaturado > 0.0) {
            if ($pesoSeco <= 0.0 || $pesoSaturado <= 0.0) {
                throw new InvalidArgumentException(
                    'Para calcular la absorción de agua se requieren ambos pesos: seco y saturado.'
                );
            }
            if ($pesoSaturado < $pesoSeco) {
                throw new InvalidArgumentException(
                    'El peso saturado debe ser mayor o igual que el peso seco.'
                );
            }
            $absorcion = (($pesoSaturado - $pesoSeco) / $pesoSeco) * 100.0;
            $resultados[] = $this->resultado(
                'Absorción de agua',
                $this->redondear($absorcion, 2),
                '%',
                true
            );
        }

        $detalle = [
            $this->fila('Largo en verde', $this->redondear($largoVerde, 2), 'mm'),
            $this->fila('Largo en seco', $this->redondear($largoSeco, 2), 'mm'),
            $this->fila('Largo cocido', $this->redondear($largoCocido, 2), 'mm'),
            $this->fila(
                'Suma aritmética de las etapas',
                $this->redondear($contraccionSecado + $contraccionCoccion, 2),
                '%'
            ),
            $this->fila(
                'Diferencia total − suma de etapas',
                $this->redondear(
                    $contraccionTotal - ($contraccionSecado + $contraccionCoccion),
                    2
                ),
                'pp'
            ),
            $this->fila(
                'Clasificación de la contracción de secado',
                $this->clasificarSecado($contraccionSecado),
                ''
            ),
            $this->fila(
                'Clasificación de la contracción de cocción',
                $this->clasificarCoccion($contraccionCoccion),
                ''
            ),
            $this->fila(
                'Clasificación de la contracción total',
                $this->clasificarTotal($contraccionTotal),
                ''
            ),
        ];
        if ($medidaFinal > 0.0 && $factorMolde !== null) {
            $detalle[] = $this->fila('Medida final deseada', $this->redondear($medidaFinal, 2), 'mm');
            $detalle[] = $this->fila(
                'Factor de conversión seco → verde',
                $this->redondear($factorMolde, 4),
                '×'
            );
        }
        if ($absorcion !== null) {
            $detalle[] = $this->fila('Peso seco', $this->redondear($pesoSeco, 2), 'g');
            $detalle[] = $this->fila('Peso saturado', $this->redondear($pesoSaturado, 2), 'g');
            $detalle[] = $this->fila(
                'Agua absorbida',
                $this->redondear($pesoSaturado - $pesoSeco, 2),
                'g'
            );
            $detalle[] = $this->fila(
                'Clasificación de la absorción',
                $this->clasificarAbsorcion($absorcion),
                ''
            );
        }

        $advertencias = [];
        if ($contraccionSecado < 0.0) {
            $advertencias[] = 'El largo seco es mayor que el largo en verde: hay expansión '
                . 'durante el secado. Revisa la medición o el método de secado.';
        }
        if ($contraccionCoccion < 0.0) {
            $advertencias[] = 'El largo cocido es mayor que el seco: hay expansión durante la '
                . 'cocción (poco común). Verifica la temperatura y la medición.';
        }
        if ($contraccionSecado > 8.0) {
            $advertencias[] = 'Contracción de secado alta (> 8 %): aumenta el riesgo de '
                . 'agrietamiento; seca las piezas lentamente y con humedad controlada.';
        }
        if ($contraccionTotal > 15.0) {
            $advertencias[] = 'Contracción total alta (> 15 %): ajusta la curva de secado y '
                . 'cocción, y verifica la medida del molde.';
        }
        if ($absorcion !== null && $absorcion < 1.0) {
            $advertencias[] = 'Absorción muy baja (< 1 %): la pieza está prácticamente '
                . 'vitrificada; puede haber mayor riesgo de craquelado por desajuste con el esmalte.';
        }
        if ($absorcion !== null && $absorcion > 20.0) {
            $advertencias[] = 'Absorción alta (> 20 %): pasta muy porosa, con baja resistencia '
                . 'mecánica y al hielo-deshielo.';
        }

        $formulas = [
            'Contracción de secado (%) = (L_verde − L_seco) / L_verde × 100',
            'Contracción de cocción (%) = (L_seco − L_cocido) / L_seco × 100',
            'Contracción total (%) = (L_verde − L_cocido) / L_verde × 100',
            'Largo en verde necesario = medida_final / (1 − contracción_total / 100)',
            'Absorción (%) = (peso_saturado − peso_seco) / peso_seco × 100',
        ];

        return $this->salida($resultados, $detalle, $advertencias, $formulas);
    }

    private function clasificarSecado(float $valor): string
    {
        if ($valor < 0.0) {
            return 'Expansión (revisar medición)';
        }
        if ($valor < 3.0) {
            return 'Baja';
        }
        if ($valor <= 6.0) {
            return 'Moderada';
        }
        if ($valor <= 10.0) {
            return 'Alta';
        }

        return 'Muy alta';
    }

    private function clasificarCoccion(float $valor): string
    {
        if ($valor < 0.0) {
            return 'Expansión (revisar medición)';
        }
        if ($valor < 2.0) {
            return 'Baja';
        }
        if ($valor <= 5.0) {
            return 'Moderada';
        }

        return 'Alta';
    }

    private function clasificarTotal(float $valor): string
    {
        if ($valor < 0.0) {
            return 'Expansión (revisar medición)';
        }
        if ($valor < 5.0) {
            return 'Baja';
        }
        if ($valor <= 10.0) {
            return 'Moderada';
        }
        if ($valor <= 15.0) {
            return 'Alta';
        }

        return 'Muy alta';
    }

    private function clasificarAbsorcion(float $valor): string
    {
        if ($valor < 1.0) {
            return 'Muy baja (vitrificado)';
        }
        if ($valor <= 5.0) {
            return 'Baja';
        }
        if ($valor <= 10.0) {
            return 'Media';
        }
        if ($valor <= 20.0) {
            return 'Alta';
        }

        return 'Muy alta (poroso)';
    }
}
