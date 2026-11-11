<?php
declare(strict_types=1);

namespace App\Services\Calculators;

use InvalidArgumentException;

/**
 * Normalización comercial de un mineral o arcilla: precio húmedo → seco,
 * merma, flete, margen del broker, IVA y costo landed por tonelada seca y por
 * unidad de óxido contenido.
 *
 * Convención: precio y flete se cotizan por tonelada húmeda (lo que marca la
 * báscula). La merma es una pérdida adicional de manejo sobre el material ya
 * seco. El margen del broker se aplica sobre el costo landed antes de IVA.
 *
 * Importes MXN a 2 decimales, USD a 2, costo por óxido a 2 (MXN/USD por t) y
 * costos por libra a 4.
 */
final class PriceNormalizationCalculator extends AbstractCalculator
{
    public const SLUG = 'precio-normalizado';

    private const LIBRAS_POR_TONELADA = 2204.62262185;

    /** @var array<string,string> */
    private const OXIDOS = [
        'al2o3' => 'Al2O3',
        'fe2o3' => 'Fe2O3',
        'sio2'  => 'SiO2',
        'tio2'  => 'TiO2',
        'cao'   => 'CaO',
        'mgo'   => 'MgO',
        'k2o'   => 'K2O',
        'na2o'  => 'Na2O',
        'loi'   => 'LOI (pérdida por ignición)',
        'otro'  => 'Otro óxido o componente',
    ];

    public function slug(): string
    {
        return self::SLUG;
    }

    public function nombre(): string
    {
        return 'Precio normalizado y costo landed';
    }

    public function categoria(): string
    {
        return 'Comercial';
    }

    public function descripcion(): string
    {
        return 'Convierte un precio por tonelada húmeda a costo landed por tonelada '
            . 'seca: descuenta humedad y merma, prorratea flete, aplica margen de broker '
            . 'e IVA, y calcula el costo por unidad de óxido contenido en MXN y USD.';
    }

    public function campos(): array
    {
        return [
            $this->campoNumero('precio_mxn_t_humeda', 'Precio base por tonelada húmeda', [
                'unidad'    => 'MXN/t',
                'min'       => 0.01,
                'max'       => 10000000.0,
                'paso'      => 0.01,
                'requerido' => true,
                'defecto'   => 2500.0,
                'grupo'     => 'Precio y merma',
                'ayuda'     => 'Precio de compra del material tal como llega, con su humedad.',
            ]),
            $this->campoNumero('humedad_pct', 'Humedad del material', [
                'unidad'    => '%',
                'min'       => 0.0,
                'max'       => 99.9,
                'paso'      => 0.1,
                'requerido' => true,
                'defecto'   => 10.0,
                'grupo'     => 'Precio y merma',
                'ayuda'     => 'Agua libre del material. Determina el rendimiento de tonelada '
                    . 'húmeda a seca.',
            ]),
            $this->campoNumero('merma_pct', 'Merma de manejo', [
                'unidad'    => '%',
                'min'       => 0.0,
                'max'       => 99.9,
                'paso'      => 0.1,
                'requerido' => true,
                'defecto'   => 2.0,
                'grupo'     => 'Precio y merma',
                'ayuda'     => 'Pérdida adicional de material seco por manejo, almacenaje o '
                    . 'selección. Se aplica después de descontar la humedad.',
            ]),
            $this->campoNumero('flete_mxn_t', 'Flete por tonelada húmeda', [
                'unidad'    => 'MXN/t',
                'min'       => 0.0,
                'max'       => 1000000.0,
                'paso'      => 0.01,
                'requerido' => true,
                'defecto'   => 350.0,
                'grupo'     => 'Logística y margen',
                'ayuda'     => 'Flete cotizado por tonelada húmeda transportada.',
            ]),
            $this->campoNumero('margen_broker_pct', 'Margen del broker', [
                'unidad'    => '%',
                'min'       => 0.0,
                'max'       => 200.0,
                'paso'      => 0.1,
                'requerido' => true,
                'defecto'   => 8.0,
                'grupo'     => 'Logística y margen',
                'ayuda'     => 'Margen comercial aplicado sobre el costo landed antes de IVA.',
            ]),
            $this->campoNumero('iva_pct', 'IVA', [
                'unidad'    => '%',
                'min'       => 0.0,
                'max'       => 100.0,
                'paso'      => 0.1,
                'requerido' => true,
                'defecto'   => 16.0,
                'grupo'     => 'Impuestos',
                'ayuda'     => 'Tasa de IVA aplicada al costo landed. El IVA no es costo para '
                    . 'empresas que lo acreditan; se presenta como referencia.',
            ]),
            $this->campoNumero('tipo_cambio_mxn_usd', 'Tipo de cambio', [
                'unidad'    => 'MXN/USD',
                'min'       => 0.01,
                'max'       => 1000.0,
                'paso'      => 0.01,
                'requerido' => true,
                'defecto'   => 18.5,
                'grupo'     => 'Impuestos',
                'ayuda'     => 'Pesos mexicanos por dólar estadounidense.',
            ]),
            $this->campoSelect('oxido_nombre', 'Óxido o componente de referencia', self::OXIDOS, [
                'requerido' => true,
                'defecto'   => 'al2o3',
                'grupo'     => 'Costo por óxido contenido',
                'ayuda'     => 'Se usa para expresar el costo por tonelada de componente útil.',
            ]),
            $this->campoNumero('oxido_pct', 'Contenido del óxido de referencia', [
                'unidad'    => '%',
                'min'       => 0.01,
                'max'       => 100.0,
                'paso'      => 0.01,
                'requerido' => true,
                'defecto'   => 37.5,
                'grupo'     => 'Costo por óxido contenido',
                'ayuda'     => 'Porcentaje del óxido en el material seco (por ejemplo, Al2O3 '
                    . 'en un caolín).',
            ]),
        ];
    }

    public function calcular(array $entrada): array
    {
        $datos = $this->datosValidados($entrada);

        $precio = (float) $datos['precio_mxn_t_humeda'];
        $humedadPct = (float) $datos['humedad_pct'];
        $mermaPct = (float) $datos['merma_pct'];
        $flete = (float) $datos['flete_mxn_t'];
        $margenPct = (float) $datos['margen_broker_pct'];
        $ivaPct = (float) $datos['iva_pct'];
        $tipoCambio = (float) $datos['tipo_cambio_mxn_usd'];
        $oxidoClave = (string) $datos['oxido_nombre'];
        $oxidoPct = (float) $datos['oxido_pct'];

        if ($tipoCambio <= 0.0) {
            throw new InvalidArgumentException('El tipo de cambio debe ser mayor que cero.');
        }
        if ($oxidoPct <= 0.0) {
            throw new InvalidArgumentException(
                'El contenido del óxido de referencia debe ser mayor que cero.'
            );
        }

        $factorSeco = (1.0 - ($humedadPct / 100.0)) * (1.0 - ($mermaPct / 100.0));
        if ($factorSeco <= 0.0) {
            throw new InvalidArgumentException(
                'La humedad y la merma combinadas dejan 0 % de material seco: revisa los '
                . 'porcentajes capturados.'
            );
        }

        $rendimientoSeco = $factorSeco * 100.0;
        $costoBasePorSeca = $precio / $factorSeco;
        $fletePorSeca = $flete / $factorSeco;
        $subtotal = $costoBasePorSeca + $fletePorSeca;
        $margen = $subtotal * ($margenPct / 100.0);
        $costoLanded = $subtotal + $margen;
        $iva = $costoLanded * ($ivaPct / 100.0);
        $costoLandedConIva = $costoLanded + $iva;
        $costoLandedUsd = $costoLanded / $tipoCambio;
        $costoLandedConIvaUsd = $costoLandedConIva / $tipoCambio;

        $fraccionOxido = $oxidoPct / 100.0;
        $costoPorToneladaOxidoMxn = $costoLanded / $fraccionOxido;
        $costoPorToneladaOxidoUsd = $costoLandedUsd / $fraccionOxido;
        $costoPorLibraOxidoUsd = $costoPorToneladaOxidoUsd / self::LIBRAS_POR_TONELADA;
        $etiquetaOxido = self::OXIDOS[$oxidoClave] ?? 'óxido';

        $resultados = [
            $this->resultado(
                'Costo landed por tonelada seca (MXN)',
                $this->redondear($costoLanded, 2),
                'MXN/t',
                true
            ),
            $this->resultado(
                'Costo landed por tonelada seca (USD)',
                $this->redondear($costoLandedUsd, 2),
                'USD/t',
                true
            ),
            $this->resultado(
                'Costo por tonelada de ' . $etiquetaOxido . ' (MXN)',
                $this->redondear($costoPorToneladaOxidoMxn, 2),
                'MXN/t',
                true
            ),
            $this->resultado(
                'Costo por tonelada de ' . $etiquetaOxido . ' (USD)',
                $this->redondear($costoPorToneladaOxidoUsd, 2),
                'USD/t'
            ),
            $this->resultado(
                'Rendimiento de material seco',
                $this->redondear($rendimientoSeco, 2),
                '%'
            ),
        ];

        $detalle = [
            $this->fila('Precio base por tonelada húmeda', $this->redondear($precio, 2), 'MXN/t'),
            $this->fila('Factor húmedo → seco', $this->redondear($factorSeco, 6), 't seca/t húmeda'),
            $this->fila('Costo base por tonelada seca', $this->redondear($costoBasePorSeca, 2), 'MXN/t'),
            $this->fila('Flete prorrateado por tonelada seca', $this->redondear($fletePorSeca, 2), 'MXN/t'),
            $this->fila('Subtotal sin margen', $this->redondear($subtotal, 2), 'MXN/t'),
            $this->fila('Margen del broker', $this->redondear($margen, 2), 'MXN/t'),
            $this->fila('IVA', $this->redondear($iva, 2), 'MXN/t'),
            $this->fila('Costo landed con IVA (MXN)', $this->redondear($costoLandedConIva, 2), 'MXN/t'),
            $this->fila('Costo landed con IVA (USD)', $this->redondear($costoLandedConIvaUsd, 2), 'USD/t'),
            $this->fila('Contenido de ' . $etiquetaOxido, $this->redondear($oxidoPct, 2), '%'),
            $this->fila('Costo por libra de ' . $etiquetaOxido, $this->redondear($costoPorLibraOxidoUsd, 4), 'USD/lb'),
            $this->fila('Tipo de cambio usado', $this->redondear($tipoCambio, 4), 'MXN/USD'),
            $this->fila('Humedad + merma descontadas', $this->redondear(100.0 - $rendimientoSeco, 2), '%'),
        ];

        $advertencias = [];
        if ($humedadPct > 20.0) {
            $advertencias[] = 'Humedad alta (> 20 %): el costo por tonelada seca se multiplica; '
                . 'conviene negociar el precio sobre base seca o exigir secado previo.';
        }
        if ($mermaPct > 5.0) {
            $advertencias[] = 'Merma alta (> 5 %): revisa el manejo, el almacenaje y la '
                . 'selección del material.';
        }
        if ($margenPct > 20.0) {
            $advertencias[] = 'Margen del broker alto (> 20 %): verifica que el precio final '
                . 'siga siendo competitivo frente a otros proveedores.';
        }
        if ($oxidoPct < 10.0) {
            $advertencias[] = 'Contenido de óxido bajo (< 10 %): el costo por unidad de '
                . 'componente útil será elevado; compara contra una fuente de mayor ley.';
        }
        if ($precio <= 0.0 || $flete <= 0.0) {
            $advertencias[] = 'El precio o el flete están en cero: el costo landed calculado '
                . 'solo refleja los conceptos capturados.';
        }
        if ($ivaPct <= 0.0) {
            $advertencias[] = 'El IVA está en 0 %; el resultado es un costo neto sin impuesto.';
        }

        $formulas = [
            'Factor húmedo → seco = (1 − humedad / 100) × (1 − merma / 100)',
            'Costo base por t seca = precio por t húmeda / factor',
            'Flete por t seca = flete por t húmeda / factor',
            'Costo landed = (costo base por t seca + flete por t seca) × (1 + margen / 100)',
            'Costo por t de óxido = costo landed por t seca / (% óxido / 100)',
            'Precio con IVA = costo landed × (1 + IVA / 100); USD = MXN / tipo de cambio',
        ];

        return $this->salida($resultados, $detalle, $advertencias, $formulas);
    }
}
