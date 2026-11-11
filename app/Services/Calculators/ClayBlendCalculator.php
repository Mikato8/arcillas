<?php
declare(strict_types=1);

namespace App\Services\Calculators;

use InvalidArgumentException;
use RuntimeException;

/**
 * Mezcla de 2 a 4 arcillas para acercarse a una composición objetivo.
 *
 * Resuelve por mínimos cuadrados con restricciones Σw = 1 y w ≥ 0: búsqueda
 * determinista en rejilla sobre el simplex + descenso por coordenadas para
 * refinar. No usa librerías externas ni aleatoriedad. Las proporciones se
 * redondean a 2 decimales, las composiciones a 2 y los errores a 3–4.
 */
final class ClayBlendCalculator extends AbstractCalculator
{
    public const SLUG = 'mezcla-arcillas';

    private const OXIDOS = ['sio2', 'al2o3', 'fe2o3', 'loi'];

    private const ETIQUETAS_OXIDOS = [
        'sio2'  => 'SiO2',
        'al2o3' => 'Al2O3',
        'fe2o3' => 'Fe2O3',
        'loi'   => 'LOI',
    ];

    /** @var array<int,array{nombre:string,sio2:float,al2o3:float,fe2o3:float,loi:float}> */
    private const DEFAULTS = [
        1 => ['nombre' => 'Caolín', 'sio2' => 46.3, 'al2o3' => 37.5, 'fe2o3' => 0.7, 'loi' => 14.5],
        2 => ['nombre' => 'Ball clay', 'sio2' => 55.0, 'al2o3' => 28.0, 'fe2o3' => 1.2, 'loi' => 9.5],
        3 => ['nombre' => 'Arcilla roja', 'sio2' => 50.0, 'al2o3' => 20.0, 'fe2o3' => 7.0, 'loi' => 8.0],
        4 => ['nombre' => 'Bentonita', 'sio2' => 62.0, 'al2o3' => 18.0, 'fe2o3' => 4.0, 'loi' => 8.0],
    ];

    public function slug(): string
    {
        return self::SLUG;
    }

    public function nombre(): string
    {
        return 'Mezcla de arcillas por objetivos';
    }

    public function categoria(): string
    {
        return 'Arcillas y pastas';
    }

    public function descripcion(): string
    {
        return 'Calcula las proporciones de 2 a 4 arcillas (SiO2, Al2O3, Fe2O3 y LOI) '
            . 'que mejor reproducen una composición objetivo, con restricciones de suma '
            . '100 % y proporciones no negativas.';
    }

    public function campos(): array
    {
        $campos = [
            $this->campoSelect(
                'numero_arcillas',
                'Número de arcillas a mezclar',
                ['2' => '2 arcillas', '3' => '3 arcillas', '4' => '4 arcillas'],
                [
                    'requerido' => true,
                    'defecto'   => '2',
                    'grupo'     => 'Mezcla',
                    'ayuda'     => 'Solo se usan los análisis de las primeras N arcillas.',
                ]
            ),
        ];

        foreach (self::DEFAULTS as $indice => $default) {
            $grupo = 'Arcilla ' . $indice;
            $campos[] = $this->campoTexto(
                'arcilla_' . $indice . '_nombre',
                'Nombre de la arcilla ' . $indice,
                [
                    'requerido' => false,
                    'defecto'   => $default['nombre'],
                    'grupo'     => $grupo,
                    'ayuda'     => 'Solo para etiquetar los resultados.',
                ]
            );
            foreach (self::OXIDOS as $oxido) {
                $campos[] = $this->campoNumero(
                    'arcilla_' . $indice . '_' . $oxido,
                    self::ETIQUETAS_OXIDOS[$oxido] . ' de la arcilla ' . $indice,
                    [
                        'unidad'    => '%',
                        'min'       => 0.0,
                        'max'       => 100.0,
                        'paso'      => 0.1,
                        'requerido' => false,
                        'defecto'   => $default[$oxido],
                        'grupo'     => $grupo,
                        'ayuda'     => $oxido === 'loi'
                            ? 'Pérdida por ignición (agua estructural y carbonatos).'
                            : 'Porcentaje en peso del óxido en la arcilla seca.',
                    ]
                );
            }
        }

        $objetivos = [
            'sio2'  => ['Objetivo de SiO2', 50.0],
            'al2o3' => ['Objetivo de Al2O3', 32.0],
            'fe2o3' => ['Objetivo de Fe2O3', 1.0],
            'loi'   => ['Objetivo de LOI', 12.0],
        ];
        foreach ($objetivos as $oxido => [$etiqueta, $defecto]) {
            $campos[] = $this->campoNumero(
                'objetivo_' . $oxido,
                $etiqueta,
                [
                    'unidad'    => '%',
                    'min'       => 0.0,
                    'max'       => 100.0,
                    'paso'      => 0.1,
                    'requerido' => false,
                    'defecto'   => $defecto,
                    'grupo'     => 'Objetivos de composición',
                    'ayuda'     => $oxido === 'loi'
                        ? 'La LOI objetivo debe ser alcanzable con las arcillas disponibles.'
                        : 'Un objetivo en 0 se ignora (no pesa en el ajuste).',
                ]
            );
        }

        return $campos;
    }

    public function calcular(array $entrada): array
    {
        $datos = $this->datosValidados($entrada);
        $arcillas = $this->leerArcillas($entrada, $datos);
        $objetivos = $this->leerObjetivos($entrada, $datos);

        $numero = count($arcillas);
        $sumaObjetivos = 0.0;
        $pesos = [];
        foreach (self::OXIDOS as $oxido) {
            $sumaObjetivos += $objetivos[$oxido];
            $pesos[$oxido] = $objetivos[$oxido] > 0.0 ? 1.0 : 0.0;
        }
        if (array_sum($pesos) <= 0.0) {
            throw new InvalidArgumentException(
                'Define al menos un objetivo mayor que cero para poder ajustar la mezcla.'
            );
        }

        $proporciones = $this->resolverProporciones($arcillas, $objetivos, $pesos);
        $composicion = $this->componer($arcillas, $proporciones);

        $resultados = [];
        foreach ($arcillas as $i => $arcilla) {
            $resultados[] = $this->resultado(
                $arcilla['nombre'],
                $this->redondear($proporciones[$i] * 100.0, 2),
                '%',
                true
            );
        }
        foreach (self::OXIDOS as $oxido) {
            $resultados[] = $this->resultado(
                self::ETIQUETAS_OXIDOS[$oxido] . ' de la mezcla',
                $this->redondear($composicion[$oxido], 2),
                '%',
                $oxido === 'sio2' || $oxido === 'al2o3'
            );
        }

        $sumaCuadrados = 0.0;
        $maxDesviacion = 0.0;
        $numObjetivos = 0;
        $detalle = [];
        foreach (self::OXIDOS as $oxido) {
            if ($pesos[$oxido] <= 0.0) {
                continue;
            }
            $desviacion = $composicion[$oxido] - $objetivos[$oxido];
            $sumaCuadrados += $desviacion * $desviacion;
            $maxDesviacion = max($maxDesviacion, abs($desviacion));
            $numObjetivos++;
            $detalle[] = $this->fila(
                'Objetivo ' . self::ETIQUETAS_OXIDOS[$oxido],
                $this->redondear($objetivos[$oxido], 2),
                '%'
            );
            $detalle[] = $this->fila(
                'Desviación ' . self::ETIQUETAS_OXIDOS[$oxido],
                $this->redondear($desviacion, 3),
                'pp'
            );
        }
        $errorCuadraticoMedio = $numObjetivos > 0 ? $sumaCuadrados / $numObjetivos : 0.0;

        $detalle[] = $this->fila('Desviación absoluta máxima', $this->redondear($maxDesviacion, 3), 'pp');
        $detalle[] = $this->fila('Error cuadrático medio', $this->redondear($errorCuadraticoMedio, 4), 'pp²');
        $detalle[] = $this->fila('Suma de proporciones', $this->redondear(array_sum($proporciones) * 100.0, 2), '%');
        $detalle[] = $this->fila('Arcillas consideradas', $numero, '');
        foreach ($arcillas as $arcilla) {
            $detalle[] = $this->fila(
                'Suma de óxidos declarados · ' . $arcilla['nombre'],
                $this->redondear($arcilla['total'], 2),
                '%'
            );
        }

        $advertencias = [];
        if ($sumaObjetivos > 101.0) {
            $advertencias[] = 'Los objetivos suman ' . $this->numeroCorto($this->redondear($sumaObjetivos, 1))
                . ' %; una composición de SiO2 + Al2O3 + Fe2O3 + LOI no puede exceder 100 %, '
                . 'revisa las metas.';
        }
        if ($maxDesviacion > 2.0) {
            $advertencias[] = 'No es posible alcanzar los objetivos con estas arcillas: la '
                . 'desviación máxima es de ' . $this->numeroCorto($this->redondear($maxDesviacion, 2))
                . ' puntos porcentuales. Agrega una arcilla con un perfil distinto.';
        }
        foreach ($proporciones as $i => $w) {
            if ($w * 100.0 < 0.5) {
                $advertencias[] = 'La proporción de «' . $arcillas[$i]['nombre'] . '» quedó '
                    . 'prácticamente en cero; la solución no la necesita.';
            } elseif ($w * 100.0 >= 99.5 && $numero > 1) {
                $advertencias[] = 'La solución óptima usa prácticamente una sola arcilla: «'
                    . $arcillas[$i]['nombre'] . '». Revisa si los objetivos ya coinciden con su análisis.';
            }
        }
        foreach ($arcillas as $arcilla) {
            if ($arcilla['total'] < 90.0) {
                $advertencias[] = 'El análisis de «' . $arcilla['nombre'] . '» suma '
                    . $this->numeroCorto($this->redondear($arcilla['total'], 1))
                    . ' %; faltan óxidos no declarados (álcalis, MgO, TiO2, etc.) y la '
                    . 'composición calculada es solo parcial.';
            }
        }
        for ($i = 0; $i < $numero; $i++) {
            for ($j = $i + 1; $j < $numero; $j++) {
                $diferencia = 0.0;
                foreach (self::OXIDOS as $oxido) {
                    $diferencia = max($diferencia, abs($arcillas[$i][$oxido] - $arcillas[$j][$oxido]));
                }
                if ($diferencia < 0.5) {
                    $advertencias[] = 'Las arcillas «' . $arcillas[$i]['nombre'] . '» y «'
                        . $arcillas[$j]['nombre'] . '» tienen análisis casi idénticos; la '
                        . 'proporción entre ellas no es única.';
                }
            }
        }

        $formulas = [
            'Mezcla: C(óxido) = Σ wᵢ · A(óxido)ᵢ, con Σ wᵢ = 1 y wᵢ ≥ 0',
            'Objetivo: minimizar Σ [C(óxido) − objetivo(óxido)]² (mínimos cuadrados)',
            'Restricciones resueltas por rejilla determinista sobre el simplex + descenso por coordenadas',
            'Un objetivo igual a 0 se ignora (peso 0) para no forzar composiciones imposibles',
        ];

        return $this->salida($resultados, $detalle, $advertencias, $formulas);
    }

    // ─────────────────────────────────────────────────────────────────
    // Lectura de entrada
    // ─────────────────────────────────────────────────────────────────

    /**
     * @param array<string,mixed>        $entrada
     * @param array<string,float|string> $datos
     * @return array<int,array{nombre:string,sio2:float,al2o3:float,fe2o3:float,loi:float,total:float}>
     */
    private function leerArcillas(array $entrada, array $datos): array
    {
        $arcillas = [];

        if (array_key_exists('arcillas', $entrada)) {
            if (!is_array($entrada['arcillas'])) {
                throw new InvalidArgumentException(
                    'El campo «arcillas» debe ser una lista de análisis de arcillas.'
                );
            }
            foreach ($entrada['arcillas'] as $indice => $item) {
                if (!is_array($item)) {
                    throw new InvalidArgumentException(
                        'Cada arcilla de la lista debe ser un arreglo con sio2, al2o3, fe2o3 y loi.'
                    );
                }
                $nombre = isset($item['nombre']) && is_string($item['nombre']) && trim($item['nombre']) !== ''
                    ? trim($item['nombre'])
                    : 'Arcilla ' . ((int) $indice + 1);
                $analisis = [];
                foreach (self::OXIDOS as $oxido) {
                    if (!array_key_exists($oxido, $item)) {
                        throw new InvalidArgumentException(
                            'Falta el valor de ' . self::ETIQUETAS_OXIDOS[$oxido]
                            . ' en «' . $nombre . '».'
                        );
                    }
                    $analisis[$oxido] = $this->numeroSuelto(
                        $item[$oxido],
                        self::ETIQUETAS_OXIDOS[$oxido] . ' de ' . $nombre
                    );
                }
                $arcillas[] = $this->prepararArcilla($nombre, $analisis);
            }
        } else {
            $numero = (int) $datos['numero_arcillas'];
            if ($numero < 2 || $numero > 4) {
                throw new InvalidArgumentException(
                    'El número de arcillas debe estar entre 2 y 4.'
                );
            }
            for ($i = 1; $i <= $numero; $i++) {
                $nombre = trim((string) $datos['arcilla_' . $i . '_nombre']);
                if ($nombre === '') {
                    $nombre = 'Arcilla ' . $i;
                }
                $analisis = [];
                foreach (self::OXIDOS as $oxido) {
                    $analisis[$oxido] = (float) $datos['arcilla_' . $i . '_' . $oxido];
                }
                $arcillas[] = $this->prepararArcilla($nombre, $analisis);
            }
        }

        $numero = count($arcillas);
        if ($numero < 2 || $numero > 4) {
            throw new InvalidArgumentException(
                'La mezcla debe incluir entre 2 y 4 arcillas con análisis completo.'
            );
        }

        return $arcillas;
    }

    /**
     * @param array<string,float> $analisis
     * @return array{nombre:string,sio2:float,al2o3:float,fe2o3:float,loi:float,total:float}
     */
    private function prepararArcilla(string $nombre, array $analisis): array
    {
        $total = 0.0;
        foreach (self::OXIDOS as $oxido) {
            $valor = $analisis[$oxido];
            if (!is_finite($valor) || $valor < 0.0 || $valor > 100.0) {
                throw new InvalidArgumentException(
                    'El valor de ' . self::ETIQUETAS_OXIDOS[$oxido] . ' de «' . $nombre
                    . '» debe estar entre 0 y 100 %.'
                );
            }
            $total += $valor;
        }

        if ($total <= 0.0) {
            throw new InvalidArgumentException(
                'El análisis de «' . $nombre . '» está vacío: captura al menos un óxido.'
            );
        }
        if ($total > 101.0) {
            throw new InvalidArgumentException(
                'La suma de SiO2 + Al2O3 + Fe2O3 + LOI de «' . $nombre . '» es '
                . $this->numeroCorto($this->redondear($total, 1)) . ' %, no puede exceder 101 %.'
            );
        }
        if ($total < 50.0) {
            throw new InvalidArgumentException(
                'El análisis de «' . $nombre . '» suma solo '
                . $this->numeroCorto($this->redondear($total, 1))
                . ' %; parece incompleto. Verifica los porcentajes capturados.'
            );
        }

        return [
            'nombre' => $nombre,
            'sio2'   => $analisis['sio2'],
            'al2o3'  => $analisis['al2o3'],
            'fe2o3'  => $analisis['fe2o3'],
            'loi'    => $analisis['loi'],
            'total'  => $total,
        ];
    }

    /**
     * @param array<string,mixed>        $entrada
     * @param array<string,float|string> $datos
     * @return array<string,float>
     */
    private function leerObjetivos(array $entrada, array $datos): array
    {
        $objetivos = [];
        $desdeArray = array_key_exists('objetivos', $entrada);
        if ($desdeArray && !is_array($entrada['objetivos'])) {
            throw new InvalidArgumentException(
                'El campo «objetivos» debe ser un arreglo con sio2, al2o3, fe2o3 y loi.'
            );
        }

        foreach (self::OXIDOS as $oxido) {
            if ($desdeArray) {
                if (!array_key_exists($oxido, $entrada['objetivos'])) {
                    throw new InvalidArgumentException(
                        'Falta el objetivo de ' . self::ETIQUETAS_OXIDOS[$oxido] . '.'
                    );
                }
                $valor = $this->numeroSuelto(
                    $entrada['objetivos'][$oxido],
                    'objetivo de ' . self::ETIQUETAS_OXIDOS[$oxido]
                );
            } else {
                $valor = (float) $datos['objetivo_' . $oxido];
            }

            if (!is_finite($valor) || $valor < 0.0 || $valor > 100.0) {
                throw new InvalidArgumentException(
                    'El objetivo de ' . self::ETIQUETAS_OXIDOS[$oxido]
                    . ' debe estar entre 0 y 100 %.'
                );
            }
            $objetivos[$oxido] = $valor;
        }

        return $objetivos;
    }

    // ─────────────────────────────────────────────────────────────────
    // Solver determinista sobre el simplex
    // ─────────────────────────────────────────────────────────────────

    /**
     * @param array<int,array<string,float>> $arcillas
     * @param array<string,float>            $objetivos
     * @param array<string,float>            $pesos
     * @return array<int,float> proporciones (suman 1)
     */
    private function resolverProporciones(array $arcillas, array $objetivos, array $pesos): array
    {
        $n = count($arcillas);
        $mejor = array_fill(0, $n, 1.0 / $n);
        $mejorValor = $this->evaluar($mejor, $arcillas, $objetivos, $pesos);

        $divisiones = match ($n) {
            2 => 2000,
            3 => 200,
            default => 50,
        };

        if ($n === 2) {
            for ($a = 0; $a <= $divisiones; $a++) {
                $candidato = [$a / $divisiones, 1.0 - ($a / $divisiones)];
                $valor = $this->evaluar($candidato, $arcillas, $objetivos, $pesos);
                if ($valor < $mejorValor) {
                    $mejor = $candidato;
                    $mejorValor = $valor;
                }
            }
        } elseif ($n === 3) {
            for ($a = 0; $a <= $divisiones; $a++) {
                for ($b = 0; $b <= $divisiones - $a; $b++) {
                    $candidato = [
                        $a / $divisiones,
                        $b / $divisiones,
                        1.0 - (($a + $b) / $divisiones),
                    ];
                    $valor = $this->evaluar($candidato, $arcillas, $objetivos, $pesos);
                    if ($valor < $mejorValor) {
                        $mejor = $candidato;
                        $mejorValor = $valor;
                    }
                }
            }
        } else {
            for ($a = 0; $a <= $divisiones; $a++) {
                for ($b = 0; $b <= $divisiones - $a; $b++) {
                    for ($c = 0; $c <= $divisiones - $a - $b; $c++) {
                        $candidato = [
                            $a / $divisiones,
                            $b / $divisiones,
                            $c / $divisiones,
                            1.0 - (($a + $b + $c) / $divisiones),
                        ];
                        $valor = $this->evaluar($candidato, $arcillas, $objetivos, $pesos);
                        if ($valor < $mejorValor) {
                            $mejor = $candidato;
                            $mejorValor = $valor;
                        }
                    }
                }
            }
        }

        // Refinamiento por coordenadas: mueve masa de una arcilla a otra.
        foreach ([0.02, 0.01, 0.005, 0.002, 0.001, 0.0005, 0.0002, 0.0001] as $paso) {
            $mejoro = true;
            while ($mejoro) {
                $mejoro = false;
                for ($i = 0; $i < $n; $i++) {
                    for ($j = 0; $j < $n; $j++) {
                        if ($i === $j || $mejor[$j] <= $paso) {
                            continue;
                        }
                        $candidato = $mejor;
                        $candidato[$i] += $paso;
                        $candidato[$j] -= $paso;
                        $valor = $this->evaluar($candidato, $arcillas, $objetivos, $pesos);
                        if ($valor < $mejorValor - 1.0e-14) {
                            $mejor = $candidato;
                            $mejorValor = $valor;
                            $mejoro = true;
                        }
                    }
                }
            }
        }

        // Normaliza y elimina negativos numéricos.
        $suma = array_sum($mejor);
        if (!is_finite($suma) || $suma <= 0.0) {
            throw new RuntimeException('No fue posible resolver las proporciones de la mezcla.');
        }
        $normalizadas = [];
        foreach ($mejor as $valor) {
            $normalizadas[] = max(0.0, $valor / $suma);
        }
        $suma = array_sum($normalizadas);
        if ($suma <= 0.0) {
            throw new RuntimeException('No fue posible resolver las proporciones de la mezcla.');
        }

        return array_map(static fn (float $w): float => $w / $suma, $normalizadas);
    }

    /**
     * @param array<int,float>               $proporciones
     * @param array<int,array<string,float>> $arcillas
     * @param array<string,float>            $objetivos
     * @param array<string,float>            $pesos
     */
    private function evaluar(array $proporciones, array $arcillas, array $objetivos, array $pesos): float
    {
        $error = 0.0;
        foreach (self::OXIDOS as $oxido) {
            if ($pesos[$oxido] <= 0.0) {
                continue;
            }
            $mezcla = 0.0;
            foreach ($arcillas as $i => $arcilla) {
                $mezcla += $proporciones[$i] * $arcilla[$oxido];
            }
            $diferencia = $mezcla - $objetivos[$oxido];
            $error += $diferencia * $diferencia;
        }

        return $error;
    }

    /**
     * @param array<int,array<string,float>> $arcillas
     * @param array<int,float>               $proporciones
     * @return array<string,float>
     */
    private function componer(array $arcillas, array $proporciones): array
    {
        $composicion = [];
        foreach (self::OXIDOS as $oxido) {
            $valor = 0.0;
            foreach ($arcillas as $i => $arcilla) {
                $valor += $proporciones[$i] * $arcilla[$oxido];
            }
            $composicion[$oxido] = $valor;
        }

        return $composicion;
    }
}
