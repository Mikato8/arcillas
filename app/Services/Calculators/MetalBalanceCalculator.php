<?php
declare(strict_types=1);

namespace App\Services\Calculators;

use InvalidArgumentException;

/**
 * Balance metalúrgico de dos productos (concentrado y colas) para minerales
 * no ferrosos.
 *
 * Con leyes de alimentación (f), concentrado (c) y colas (t) en la misma
 * unidad, el balance de masa y de metal fino da:
 *   Masa de concentrado:  C = F · (f − t) / (c − t)
 *   Razón de concentración: F / C = (c − t) / (f − t)
 *   Recuperación: R = C · c / (F · f) = c(f − t) / [f(c − t)]
 *
 * Los importes se redondean a 2 decimales, las masas a 4, las leyes y la
 * recuperación a 3.
 */
final class MetalBalanceCalculator extends AbstractCalculator
{
    public const SLUG = 'balance-metalurgico';

    private const LIBRAS_POR_TONELADA = 2204.62262185;
    private const ONZAS_TROY_POR_TONELADA = 32150.746565;

    public function slug(): string
    {
        return self::SLUG;
    }

    public function nombre(): string
    {
        return 'Balance metalúrgico no ferroso';
    }

    public function categoria(): string
    {
        return 'Metalurgia no ferrosa';
    }

    public function descripcion(): string
    {
        return 'Balance de masa y metal para concentración de Cu, Zn, Pb o metales '
            . 'preciosos: recuperación, razón de concentración, metal fino contenido y '
            . 'valor pagable neto con maquila, refinación y cargos.';
    }

    public function campos(): array
    {
        return [
            $this->campoSelect('unidad_ley', 'Unidad de las leyes', [
                'pct' => 'Porcentaje en peso (%)',
                'g_t' => 'Gramos por tonelada (g/t)',
            ], [
                'requerido' => true,
                'defecto'   => 'pct',
                'grupo'     => 'Leyes del balance',
                'ayuda'     => 'Usa % para metales base y g/t para oro, plata o metales preciosos.',
            ]),
            $this->campoNumero('ley_alimentacion', 'Ley de alimentación', [
                'unidad'    => '',
                'min'       => 0.000001,
                'max'       => 1000000.0,
                'paso'      => 0.01,
                'requerido' => true,
                'defecto'   => 1.0,
                'grupo'     => 'Leyes del balance',
                'ayuda'     => 'Ley del mineral que entra al circuito, en la unidad elegida.',
            ]),
            $this->campoNumero('ley_concentrado', 'Ley del concentrado', [
                'unidad'    => '',
                'min'       => 0.000001,
                'max'       => 1000000.0,
                'paso'      => 0.01,
                'requerido' => true,
                'defecto'   => 25.0,
                'grupo'     => 'Leyes del balance',
                'ayuda'     => 'Debe ser mayor que la ley de alimentación.',
            ]),
            $this->campoNumero('ley_colas', 'Ley de colas', [
                'unidad'    => '',
                'min'       => 0.0,
                'max'       => 1000000.0,
                'paso'      => 0.01,
                'requerido' => true,
                'defecto'   => 0.15,
                'grupo'     => 'Leyes del balance',
                'ayuda'     => 'Debe ser menor que la ley de alimentación (concentrado > alimentación > colas).',
            ]),
            $this->campoNumero('toneladas_alimentacion', 'Masa de alimentación', [
                'unidad'    => 't',
                'min'       => 0.001,
                'max'       => 1000000000.0,
                'paso'      => 1.0,
                'requerido' => true,
                'defecto'   => 1000.0,
                'grupo'     => 'Masa procesada',
                'ayuda'     => 'Toneladas métricas secas que entran al circuito.',
            ]),
            $this->campoNumero('precio_metal', 'Precio del metal', [
                'unidad'    => 'USD',
                'min'       => 0.0,
                'max'       => 1000000000.0,
                'paso'      => 0.01,
                'requerido' => true,
                'defecto'   => 9000.0,
                'grupo'     => 'Valorización',
                'ayuda'     => 'Precio en la unidad elegida abajo (por ejemplo USD/t de cobre).',
            ]),
            $this->campoSelect('unidad_precio', 'Unidad del precio', [
                'usd_t'  => 'USD por tonelada métrica',
                'usd_lb' => 'USD por libra',
                'usd_oz' => 'USD por onza troy',
            ], [
                'requerido' => true,
                'defecto'   => 'usd_t',
                'grupo'     => 'Valorización',
                'ayuda'     => 'El motor convierte el precio a USD/t de metal fino.',
            ]),
            $this->campoNumero('pagable_pct', 'Porcentaje pagable', [
                'unidad'    => '%',
                'min'       => 0.0,
                'max'       => 100.0,
                'paso'      => 0.1,
                'requerido' => true,
                'defecto'   => 96.5,
                'grupo'     => 'Valorización',
                'ayuda'     => 'Parte del metal contenido en el concentrado que paga la fundición.',
            ]),
            $this->campoNumero('maquila_usd_t_concentrado', 'Maquila (TC)', [
                'unidad'    => 'USD/t',
                'min'       => 0.0,
                'max'       => 1000000.0,
                'paso'      => 1.0,
                'requerido' => true,
                'defecto'   => 90.0,
                'grupo'     => 'Cargos de fundición',
                'ayuda'     => 'Treatment charge por tonelada de concentrado.',
            ]),
            $this->campoNumero('refinacion_usd_t_metal', 'Refinación (RC)', [
                'unidad'    => 'USD/t',
                'min'       => 0.0,
                'max'       => 1000000.0,
                'paso'      => 1.0,
                'requerido' => true,
                'defecto'   => 150.0,
                'grupo'     => 'Cargos de fundición',
                'ayuda'     => 'Refining charge por tonelada de metal pagable.',
            ]),
            $this->campoNumero('otros_cargos_usd_t_concentrado', 'Otros cargos', [
                'unidad'    => 'USD/t',
                'min'       => 0.0,
                'max'       => 1000000.0,
                'paso'      => 0.1,
                'requerido' => true,
                'defecto'   => 0.0,
                'grupo'     => 'Cargos de fundición',
                'ayuda'     => 'Penalizaciones, flete o cargos adicionales por tonelada de concentrado.',
            ]),
        ];
    }

    public function calcular(array $entrada): array
    {
        $datos = $this->datosValidados($entrada);

        $unidadLey = (string) $datos['unidad_ley'];
        $unidadPrecio = (string) $datos['unidad_precio'];
        $f = (float) $datos['ley_alimentacion'];
        $c = (float) $datos['ley_concentrado'];
        $t = (float) $datos['ley_colas'];
        $alimentacion = (float) $datos['toneladas_alimentacion'];
        $precio = (float) $datos['precio_metal'];
        $pagablePct = (float) $datos['pagable_pct'];
        $maquila = (float) $datos['maquila_usd_t_concentrado'];
        $refinacion = (float) $datos['refinacion_usd_t_metal'];
        $otros = (float) $datos['otros_cargos_usd_t_concentrado'];

        if ($alimentacion <= 0.0) {
            throw new InvalidArgumentException(
                'La masa de alimentación debe ser mayor que cero.'
            );
        }
        if ($unidadLey === 'pct') {
            foreach (['alimentación' => $f, 'concentrado' => $c, 'colas' => $t] as $nombre => $ley) {
                if ($ley > 100.0) {
                    throw new InvalidArgumentException(
                        'Con leyes en porcentaje, la ley de ' . $nombre
                        . ' no puede exceder 100 %. Cambia la unidad a g/t si corresponde.'
                    );
                }
            }
        }
        if ($c <= $f) {
            throw new InvalidArgumentException(
                'La ley del concentrado (' . $this->numeroCorto($c) . ') debe ser mayor que la '
                . 'ley de alimentación (' . $this->numeroCorto($f) . ').'
            );
        }
        if ($f <= $t) {
            throw new InvalidArgumentException(
                'La ley de alimentación (' . $this->numeroCorto($f) . ') debe ser mayor que la '
                . 'ley de colas (' . $this->numeroCorto($t) . ').'
            );
        }

        $factorLey = $unidadLey === 'pct' ? 1.0 / 100.0 : 1.0 / 1000000.0;
        $factorPrecio = match ($unidadPrecio) {
            'usd_lb' => self::LIBRAS_POR_TONELADA,
            'usd_oz' => self::ONZAS_TROY_POR_TONELADA,
            default  => 1.0,
        };
        $precioUsdTonelada = $precio * $factorPrecio;

        // Balance de masa y metal.
        $masaConcentrado = $alimentacion * ($f - $t) / ($c - $t);
        $masaColas = $alimentacion - $masaConcentrado;
        $finoAlimentacion = $alimentacion * $f * $factorLey;
        $finoConcentrado = $masaConcentrado * $c * $factorLey;
        $finoColas = $masaColas * $t * $factorLey;
        $recuperacion = $finoConcentrado / $finoAlimentacion * 100.0;
        $razonConcentracion = $alimentacion / $masaConcentrado;
        $concentradoPorTonelada = $masaConcentrado / $alimentacion;

        $pagableToneladas = $finoConcentrado * ($pagablePct / 100.0);
        $ingresoBruto = $pagableToneladas * $precioUsdTonelada;
        $cargoMaquila = $masaConcentrado * $maquila;
        $cargoRefinacion = $pagableToneladas * $refinacion;
        $cargoOtros = $masaConcentrado * $otros;
        $cargosTotales = $cargoMaquila + $cargoRefinacion + $cargoOtros;
        $valorNeto = $ingresoBruto - $cargosTotales;
        $valorNetoPorToneladaConcentrado = $valorNeto / $masaConcentrado;
        $valorNetoPorToneladaAlimentacion = $valorNeto / $alimentacion;

        $unidadFino = 'kg';
        $resultados = [
            $this->resultado('Recuperación metalúrgica', $this->redondear($recuperacion, 3), '%', true),
            $this->resultado(
                'Razón de concentración',
                $this->redondear($razonConcentracion, 3),
                't/t',
                true
            ),
            $this->resultado(
                'Masa de concentrado',
                $this->redondear($masaConcentrado, 4),
                't',
                true
            ),
            $this->resultado(
                'Concentrado por tonelada de alimentación',
                $this->redondear($concentradoPorTonelada, 5),
                't/t'
            ),
            $this->resultado(
                'Metal fino en concentrado',
                $this->redondear($finoConcentrado * 1000.0, 2),
                $unidadFino
            ),
            $this->resultado(
                'Valor neto por tonelada de concentrado',
                $this->redondear($valorNetoPorToneladaConcentrado, 2),
                'USD/t',
                true
            ),
            $this->resultado('Valor neto total', $this->redondear($valorNeto, 2), 'USD', true),
        ];

        $etiquetaLey = $unidadLey === 'pct' ? '%' : 'g/t';
        $detalle = [
            $this->fila('Masa de colas', $this->redondear($masaColas, 4), 't'),
            $this->fila('Metal fino en alimentación', $this->redondear($finoAlimentacion * 1000.0, 2), 'kg'),
            $this->fila('Metal fino en colas', $this->redondear($finoColas * 1000.0, 2), 'kg'),
            $this->fila('Metal pagable', $this->redondear($pagableToneladas * 1000.0, 2), 'kg'),
            $this->fila('Ingreso bruto', $this->redondear($ingresoBruto, 2), 'USD'),
            $this->fila('Cargo de maquila (TC)', $this->redondear($cargoMaquila, 2), 'USD'),
            $this->fila('Cargo de refinación (RC)', $this->redondear($cargoRefinacion, 2), 'USD'),
            $this->fila('Otros cargos', $this->redondear($cargoOtros, 2), 'USD'),
            $this->fila('Cargos totales', $this->redondear($cargosTotales, 2), 'USD'),
            $this->fila(
                'Valor neto por tonelada de alimentación',
                $this->redondear($valorNetoPorToneladaAlimentacion, 2),
                'USD/t'
            ),
            $this->fila('Ley de alimentación', $this->redondear($f, 4), $etiquetaLey),
            $this->fila('Ley de concentrado', $this->redondear($c, 4), $etiquetaLey),
            $this->fila('Ley de colas', $this->redondear($t, 4), $etiquetaLey),
            $this->fila(
                'Precio usado del metal',
                $this->redondear($precioUsdTonelada, 2),
                'USD/t'
            ),
        ];

        $advertencias = [];
        if ($recuperacion < 60.0) {
            $advertencias[] = 'Recuperación baja (< 60 %): revisa la liberación, la dosificación '
                . 'de reactivos o la ley de colas.';
        }
        if ($recuperacion > 95.0) {
            $advertencias[] = 'Recuperación muy alta (> 95 %): verifica que las leyes y las '
                . 'unidades del balance sean consistentes.';
        }
        if ($razonConcentracion > 50.0) {
            $advertencias[] = 'Razón de concentración alta (> 50): puede indicar una ley de '
                . 'alimentación muy baja o un concentrado de baja ley.';
        }
        if ($pagablePct < 90.0) {
            $advertencias[] = 'El porcentaje pagable es bajo (< 90 %); el valor neto depende '
                . 'fuertemente de las condiciones de la fundición.';
        }
        if ($valorNeto < 0.0) {
            $advertencias[] = 'El valor neto es negativo con estos precios y cargos: el '
                . 'concentrado no es rentable en las condiciones capturadas.';
        }
        if ($unidadLey === 'pct' && $c > 80.0) {
            $advertencias[] = 'La ley del concentrado es muy alta (> 80 %): confirma que las '
                . 'leyes estén en % y no en otra unidad.';
        }
        if ($unidadLey === 'g_t' && $f < 1.0) {
            $advertencias[] = 'Leyes en g/t con alimentación menor a 1 g/t: el valor pagable '
                . 'será muy sensible al precio y a la recuperación.';
        }

        $formulas = [
            'Masa de concentrado: C = F · (f − t) / (c − t)',
            'Razón de concentración: F / C = (c − t) / (f − t)',
            'Recuperación: R = C · c / (F · f) = c(f − t) / [f(c − t)]',
            'Valor neto = metal pagable × precio − (maquila × C + refinación × metal pagable + otros × C)',
        ];

        return $this->salida($resultados, $detalle, $advertencias, $formulas);
    }
}
