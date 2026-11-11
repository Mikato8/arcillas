<?php
declare(strict_types=1);

namespace App\Services\Calculators;

use InvalidArgumentException;

/**
 * Dosificación de bentonita y agua para arena verde de fundición.
 *
 * Las dosificaciones (% bentonita, % agua, % finos) se interpretan sobre la
 * masa de arena seca del lote. La bentonita se dosifica en peso húmedo: para
 * entregar la bentonita seca objetivo se corrige por su humedad, y el agua que
 * aporta esa humedad se descuenta del agua de mezcla.
 *
 * Masas a 3 decimales (kg), porcentajes a 2.
 */
final class BentoniteSandCalculator extends AbstractCalculator
{
    public const SLUG = 'bentonita-arena-verde';

    public function slug(): string
    {
        return self::SLUG;
    }

    public function nombre(): string
    {
        return 'Bentonita y agua para arena verde';
    }

    public function categoria(): string
    {
        return 'Fundición';
    }

    public function descripcion(): string
    {
        return 'Calcula la bentonita seca y húmeda y el agua neta por lote y por tonelada '
            . 'de arena para preparar arena verde de fundición, ajustando por la humedad '
            . 'de la bentonita y por los finos presentes.';
    }

    public function campos(): array
    {
        return [
            $this->campoNumero('masa_arena_kg', 'Masa de arena por lote', [
                'unidad'    => 'kg',
                'min'       => 1.0,
                'max'       => 10000000.0,
                'paso'      => 10.0,
                'requerido' => true,
                'defecto'   => 1000.0,
                'grupo'     => 'Lote',
                'ayuda'     => 'Arena seca base del lote, sin contar bentonita ni agua.',
            ]),
            $this->campoNumero('bentonita_pct', 'Bentonita objetivo', [
                'unidad'    => '%',
                'min'       => 0.0,
                'max'       => 30.0,
                'paso'      => 0.1,
                'requerido' => true,
                'defecto'   => 8.0,
                'grupo'     => 'Dosificación',
                'ayuda'     => 'Porcentaje de bentonita seca sobre la masa de arena seca. '
                    . 'Rango típico en arena verde: 4–10 %.',
            ]),
            $this->campoNumero('agua_pct', 'Agua objetivo', [
                'unidad'    => '%',
                'min'       => 0.0,
                'max'       => 15.0,
                'paso'      => 0.1,
                'requerido' => true,
                'defecto'   => 4.0,
                'grupo'     => 'Dosificación',
                'ayuda'     => 'Agua total de mezcla sobre arena seca. Rango típico: 2–5 %.',
            ]),
            $this->campoNumero('finos_pct', 'Finos presentes', [
                'unidad'    => '%',
                'min'       => 0.0,
                'max'       => 50.0,
                'paso'      => 0.1,
                'requerido' => true,
                'defecto'   => 10.0,
                'grupo'     => 'Arena',
                'ayuda'     => 'Finos (arcilla muerta, polvo y bentonita quemada) ya presentes '
                    . 'en la arena. Rango típico: 8–15 %.',
            ]),
            $this->campoNumero('lotes', 'Número de lotes', [
                'unidad'    => '',
                'min'       => 1.0,
                'max'       => 10000.0,
                'paso'      => 1.0,
                'requerido' => true,
                'defecto'   => 1.0,
                'grupo'     => 'Lote',
                'ayuda'     => 'Multiplica las cantidades para preparar varios lotes iguales.',
            ]),
            $this->campoNumero('humedad_bentonita_pct', 'Humedad de la bentonita', [
                'unidad'    => '%',
                'min'       => 0.0,
                'max'       => 40.0,
                'paso'      => 0.1,
                'requerido' => true,
                'defecto'   => 10.0,
                'grupo'     => 'Ajuste por humedad',
                'ayuda'     => 'Humedad de la bentonita tal como se recibe. La bentonita seca '
                    . 'objetivo se entrega con más peso húmedo y aporta parte del agua.',
            ]),
        ];
    }

    public function calcular(array $entrada): array
    {
        $datos = $this->datosValidados($entrada);

        $arena = (float) $datos['masa_arena_kg'];
        $bentonitaPct = (float) $datos['bentonita_pct'];
        $aguaPct = (float) $datos['agua_pct'];
        $finosPct = (float) $datos['finos_pct'];
        $lotes = (float) $datos['lotes'];
        $humedadBentonita = (float) $datos['humedad_bentonita_pct'];

        if ($arena <= 0.0) {
            throw new InvalidArgumentException('La masa de arena por lote debe ser mayor que cero.');
        }
        if ($humedadBentonita >= 100.0) {
            throw new InvalidArgumentException(
                'La humedad de la bentonita debe ser menor que 100 % para poder dosificarla.'
            );
        }
        if ($lotes < 1.0 || floor($lotes) !== $lotes) {
            throw new InvalidArgumentException(
                'El número de lotes debe ser un entero mayor o igual a 1.'
            );
        }

        $bentonitaSeca = $arena * ($bentonitaPct / 100.0);
        $factorHumedad = 1.0 / (1.0 - ($humedadBentonita / 100.0));
        $bentonitaHumeda = $bentonitaSeca * $factorHumedad;
        $aguaAportadaBentonita = $bentonitaHumeda - $bentonitaSeca;
        $aguaObjetivo = $arena * ($aguaPct / 100.0);
        $aguaNeta = $aguaObjetivo - $aguaAportadaBentonita;
        $aguaNetaPositiva = max(0.0, $aguaNeta);
        $finosKg = $arena * ($finosPct / 100.0);

        $resultados = [
            $this->resultado('Bentonita seca requerida por lote', $this->redondear($bentonitaSeca, 3), 'kg', true),
            $this->resultado('Bentonita húmeda a dosificar por lote', $this->redondear($bentonitaHumeda, 3), 'kg', true),
            $this->resultado('Agua neta a agregar por lote', $this->redondear($aguaNetaPositiva, 3), 'kg', true),
            $this->resultado(
                'Bentonita seca por tonelada de arena',
                $this->redondear(($bentonitaSeca / $arena) * 1000.0, 3),
                'kg/t'
            ),
            $this->resultado(
                'Bentonita húmeda por tonelada de arena',
                $this->redondear(($bentonitaHumeda / $arena) * 1000.0, 3),
                'kg/t'
            ),
            $this->resultado(
                'Agua neta por tonelada de arena',
                $this->redondear(($aguaNetaPositiva / $arena) * 1000.0, 3),
                'kg/t'
            ),
            $this->resultado(
                'Bentonita húmeda total (' . (int) $lotes . ' lote(s))',
                $this->redondear($bentonitaHumeda * $lotes, 3),
                'kg'
            ),
            $this->resultado(
                'Agua neta total (' . (int) $lotes . ' lote(s))',
                $this->redondear($aguaNetaPositiva * $lotes, 3),
                'kg'
            ),
        ];

        $detalle = [
            $this->fila('Arena seca por lote', $this->redondear($arena, 3), 'kg'),
            $this->fila('Agua objetivo de mezcla', $this->redondear($aguaObjetivo, 3), 'kg'),
            $this->fila('Agua aportada por la bentonita', $this->redondear($aguaAportadaBentonita, 3), 'kg'),
            $this->fila('Factor de ajuste por humedad', $this->redondear($factorHumedad, 5), '×'),
            $this->fila('Finos presentes en el lote', $this->redondear($finosKg, 3), 'kg'),
            $this->fila('Arena total para los lotes', $this->redondear($arena * $lotes, 3), 'kg'),
            $this->fila('Bentonita seca total', $this->redondear($bentonitaSeca * $lotes, 3), 'kg'),
            $this->fila('Finos totales', $this->redondear($finosKg * $lotes, 3), 'kg'),
            $this->fila(
                'Arena nueva aproximada para diluir finos al 12 %',
                $this->redondear($this->arenaNuevaParaFinos($arena, $finosKg, 12.0), 3),
                'kg'
            ),
            $this->fila('Clasificación de bentonita', $this->clasificarBentonita($bentonitaPct), ''),
            $this->fila('Clasificación de agua', $this->clasificarAgua($aguaPct), ''),
            $this->fila('Clasificación de finos', $this->clasificarFinos($finosPct), ''),
        ];
        if ($aguaNeta < 0.0) {
            $detalle[] = $this->fila(
                'Déficit de agua por humedad de la bentonita',
                $this->redondear(-$aguaNeta, 3),
                'kg'
            );
        }

        $advertencias = [];
        if ($bentonitaPct < 4.0) {
            $advertencias[] = 'Dosificación de bentonita baja (< 4 %): la arena puede quedar '
                . 'con poca resistencia en verde y mala plasticidad.';
        }
        if ($bentonitaPct > 12.0) {
            $advertencias[] = 'Dosificación de bentonita alta (> 12 %): aumenta el riesgo de '
                . 'sopladuras, arena pegajosa y exceso de finos.';
        }
        if ($aguaPct < 2.0) {
            $advertencias[] = 'Agua baja (< 2 %): la arena puede quedar quebradiza y difícil de '
                . 'compactar.';
        }
        if ($aguaPct > 6.0) {
            $advertencias[] = 'Agua alta (> 6 %): exceso de humedad, riesgo de sopladuras, '
                . 'reventones y menor permeabilidad.';
        }
        if ($finosPct > 15.0) {
            $advertencias[] = 'Finos altos (> 15 %): la arena requiere renovación parcial; '
                . 'revisa la ventilación y el desterronado.';
        }
        if ($finosPct < 5.0) {
            $advertencias[] = 'Pocos finos (< 5 %): la arena puede perder cohesión; confirma '
                . 'el porcentaje de bentonita efectiva.';
        }
        if ($humedadBentonita > 20.0) {
            $advertencias[] = 'Bentonita con humedad alta (> 20 %): almacénala cubierta y '
                . 'verifica el ajuste, porque aporta mucha agua al lote.';
        }
        if ($aguaNeta < 0.0) {
            $advertencias[] = 'La humedad de la bentonita aporta más agua que la requerida: '
                . 'reduce el agua de mezcla o seca parcialmente la bentonita antes de dosificar.';
        }
        if ($bentonitaPct > 0.0 && $aguaPct > 0.0 && $aguaNetaPositiva <= 0.0) {
            $advertencias[] = 'No se requiere agua adicional para alcanzar la humedad objetivo; '
                . 'revisa el balance de agua del lote.';
        }

        $formulas = [
            'Bentonita seca = masa de arena × % bentonita / 100',
            'Bentonita húmeda = bentonita seca / (1 − humedad bentonita / 100)',
            'Agua aportada por la bentonita = bentonita húmeda − bentonita seca',
            'Agua neta a agregar = (arena × % agua / 100) − agua aportada por la bentonita',
            'Finos = arena × % finos / 100',
        ];

        return $this->salida($resultados, $detalle, $advertencias, $formulas);
    }

    /**
     * Arena nueva necesaria para bajar los finos a un objetivo (regla de mezcla).
     * Si los finos ya están por debajo del objetivo devuelve 0.
     */
    private function arenaNuevaParaFinos(float $arena, float $finosKg, float $objetivoPct): float
    {
        $objetivo = $objetivoPct / 100.0;
        if ($objetivo <= 0.0 || $objetivo >= 1.0 || $arena <= 0.0) {
            return 0.0;
        }
        $finosActuales = $finosKg / $arena;
        if ($finosActuales <= $objetivo) {
            return 0.0;
        }

        // (finos) / (arena + x) = objetivo  →  x = finos/objetivo − arena
        return max(0.0, ($finosKg / $objetivo) - $arena);
    }

    private function clasificarBentonita(float $pct): string
    {
        if ($pct < 4.0) {
            return 'Baja';
        }
        if ($pct <= 10.0) {
            return 'Típica';
        }

        return 'Alta';
    }

    private function clasificarAgua(float $pct): string
    {
        if ($pct < 2.0) {
            return 'Baja';
        }
        if ($pct <= 5.0) {
            return 'Típica';
        }

        return 'Alta';
    }

    private function clasificarFinos(float $pct): string
    {
        if ($pct < 5.0) {
            return 'Bajos';
        }
        if ($pct <= 15.0) {
            return 'Típicos';
        }

        return 'Altos';
    }
}
