<?php
declare(strict_types=1);

namespace App\Services\Calculators;

use InvalidArgumentException;
use RuntimeException;

/**
 * Utilidades compartidas por las calculadoras: construcción del esquema de
 * campos, normalización de la entrada (string|int|float → float), validación
 * de mínimos/máximos/opciones y armado de la salida.
 *
 * Las clases concretas solo se ocupan del cálculo; toda la entrada pasa por
 * `datosValidados()`, de modo que la validación del endpoint y la del cálculo
 * son exactamente la misma (derivada de `campos()`).
 */
abstract class AbstractCalculator implements CalculatorInterface
{
    /**
     * Valida y normaliza una entrada sin ejecutar el cálculo. Útil para que el
     * endpoint valide antes de llamar a `calcular()` o para depuración.
     *
     * @param array<string,mixed> $entrada
     * @return array<string,float|string>
     * @throws InvalidArgumentException
     */
    public function validar(array $entrada): array
    {
        return $this->datosValidados($entrada);
    }

    /**
     * @param array<string,mixed> $entrada
     * @return array<string,float|string>
     * @throws InvalidArgumentException
     */
    protected function datosValidados(array $entrada): array
    {
        $datos = [];
        foreach ($this->campos() as $campo) {
            $clave = (string) $campo['clave'];
            $bruto = $entrada[$clave] ?? null;
            $datos[$clave] = match ((string) $campo['tipo']) {
                'number' => $this->normalizarNumero($campo, $bruto),
                'select' => $this->normalizarSelect($campo, $bruto),
                default  => $this->normalizarTexto($campo, $bruto),
            };
        }

        return $datos;
    }

    // ─────────────────────────────────────────────────────────────────
    // Construcción del esquema de campos
    // ─────────────────────────────────────────────────────────────────

    /**
     * Define un campo numérico.
     *
     * @param array<string,mixed> $opciones unidad, min, max, paso, requerido, defecto, ayuda, grupo
     * @return array<string,mixed>
     */
    protected function campoNumero(string $clave, string $etiqueta, array $opciones = []): array
    {
        return $this->campoBase($clave, $etiqueta, 'number', $opciones);
    }

    /**
     * Define un campo de selección.
     *
     * @param array<string,string> $opcionesEtiquetadas valor => etiqueta
     * @param array<string,mixed>  $opciones            requerido, defecto, ayuda, grupo
     * @return array<string,mixed>
     */
    protected function campoSelect(
        string $clave,
        string $etiqueta,
        array $opcionesEtiquetadas,
        array $opciones = []
    ): array {
        $opciones['opciones'] = $this->opcionesSelect($opcionesEtiquetadas);

        return $this->campoBase($clave, $etiqueta, 'select', $opciones);
    }

    /**
     * Define un campo de texto.
     *
     * @param array<string,mixed> $opciones requerido, defecto, ayuda, grupo
     * @return array<string,mixed>
     */
    protected function campoTexto(string $clave, string $etiqueta, array $opciones = []): array
    {
        return $this->campoBase($clave, $etiqueta, 'text', $opciones);
    }

    /**
     * @param array<string,mixed> $opciones
     * @return array<string,mixed>
     */
    private function campoBase(string $clave, string $etiqueta, string $tipo, array $opciones): array
    {
        return [
            'clave'     => $clave,
            'etiqueta'  => $etiqueta,
            'tipo'      => $tipo,
            'unidad'    => (string) ($opciones['unidad'] ?? ''),
            'min'       => array_key_exists('min', $opciones) ? $opciones['min'] : null,
            'max'       => array_key_exists('max', $opciones) ? $opciones['max'] : null,
            'paso'      => array_key_exists('paso', $opciones) ? $opciones['paso'] : null,
            'requerido' => (bool) ($opciones['requerido'] ?? false),
            'defecto'   => $opciones['defecto'] ?? null,
            'opciones'  => $opciones['opciones'] ?? [],
            'ayuda'     => (string) ($opciones['ayuda'] ?? ''),
            'grupo'     => (string) ($opciones['grupo'] ?? 'General'),
        ];
    }

    /**
     * @param array<string,string> $pares valor => etiqueta
     * @return array<int,array{valor:string,etiqueta:string}>
     */
    protected function opcionesSelect(array $pares): array
    {
        $opciones = [];
        foreach ($pares as $valor => $etiqueta) {
            $opciones[] = ['valor' => (string) $valor, 'etiqueta' => (string) $etiqueta];
        }

        return $opciones;
    }

    // ─────────────────────────────────────────────────────────────────
    // Normalización de la entrada
    // ─────────────────────────────────────────────────────────────────

    /**
     * @param array<string,mixed> $campo
     * @throws InvalidArgumentException
     */
    private function normalizarNumero(array $campo, mixed $bruto): float
    {
        $etiqueta = (string) $campo['etiqueta'];
        $vacio = $bruto === null || (is_string($bruto) && trim($bruto) === '');
        if ($vacio) {
            if ($campo['defecto'] !== null) {
                return (float) $campo['defecto'];
            }
            if ((bool) $campo['requerido']) {
                throw new InvalidArgumentException('El campo «' . $etiqueta . '» es obligatorio.');
            }

            return 0.0;
        }

        if (is_bool($bruto) || is_array($bruto) || is_object($bruto)) {
            throw new InvalidArgumentException(
                'El campo «' . $etiqueta . '» debe ser un número válido.'
            );
        }

        if (is_int($bruto) || is_float($bruto)) {
            $numero = (float) $bruto;
        } else {
            $texto = trim((string) $bruto);
            // Acepta coma decimal (1,5) y notación científica, además del punto.
            if (preg_match('/^[+-]?\d+(?:[.,]\d+)?(?:[eE][+-]?\d+)?$/', $texto) !== 1) {
                throw new InvalidArgumentException(
                    'El campo «' . $etiqueta . '» debe ser un número válido (se recibió «'
                    . $this->recortar($texto) . '»).'
                );
            }
            $numero = (float) str_replace(',', '.', $texto);
        }

        if (!is_finite($numero)) {
            throw new InvalidArgumentException(
                'El campo «' . $etiqueta . '» debe ser un número finito.'
            );
        }

        $unidad = (string) ($campo['unidad'] ?? '');
        $sufijo = $unidad !== '' ? ' ' . $unidad : '';
        if ($campo['min'] !== null && $numero < (float) $campo['min']) {
            throw new InvalidArgumentException(
                'El campo «' . $etiqueta . '» debe ser mayor o igual a '
                . $this->numeroCorto((float) $campo['min']) . $sufijo . '.'
            );
        }
        if ($campo['max'] !== null && $numero > (float) $campo['max']) {
            throw new InvalidArgumentException(
                'El campo «' . $etiqueta . '» debe ser menor o igual a '
                . $this->numeroCorto((float) $campo['max']) . $sufijo . '.'
            );
        }

        return $numero;
    }

    /**
     * @param array<string,mixed> $campo
     * @throws InvalidArgumentException
     */
    private function normalizarSelect(array $campo, mixed $bruto): string
    {
        $etiqueta = (string) $campo['etiqueta'];
        $vacio = $bruto === null || (is_string($bruto) && trim($bruto) === '');
        if ($vacio) {
            if ((bool) $campo['requerido'] && $campo['defecto'] === null) {
                throw new InvalidArgumentException('El campo «' . $etiqueta . '» es obligatorio.');
            }
            $bruto = $campo['defecto'] ?? ($campo['opciones'][0]['valor'] ?? '');
        }

        if (is_bool($bruto) || is_array($bruto) || is_object($bruto)) {
            throw new InvalidArgumentException(
                'El campo «' . $etiqueta . '» debe ser una de las opciones disponibles.'
            );
        }

        $valor = (string) $bruto;
        $permitidos = [];
        $etiquetas = [];
        foreach ($campo['opciones'] as $opcion) {
            $permitidos[] = (string) $opcion['valor'];
            $etiquetas[] = (string) $opcion['etiqueta'];
        }

        if (!in_array($valor, $permitidos, true)) {
            throw new InvalidArgumentException(
                'El campo «' . $etiqueta . '» debe ser una de estas opciones: '
                . implode(', ', $etiquetas) . '.'
            );
        }

        return $valor;
    }

    /**
     * @param array<string,mixed> $campo
     * @throws InvalidArgumentException
     */
    private function normalizarTexto(array $campo, mixed $bruto): string
    {
        $etiqueta = (string) $campo['etiqueta'];
        $vacio = $bruto === null || (is_string($bruto) && trim($bruto) === '');
        if ($vacio) {
            if ($campo['defecto'] !== null && (string) $campo['defecto'] !== '') {
                return (string) $campo['defecto'];
            }
            if ((bool) $campo['requerido']) {
                throw new InvalidArgumentException('El campo «' . $etiqueta . '» es obligatorio.');
            }

            return '';
        }

        if (is_bool($bruto) || is_array($bruto) || is_object($bruto)) {
            throw new InvalidArgumentException(
                'El campo «' . $etiqueta . '» debe ser texto.'
            );
        }

        $texto = trim((string) $bruto);
        if ($this->longitud($texto) > 200) {
            $texto = $this->subcadena($texto, 0, 200);
        }

        return $texto;
    }

    /**
     * Cast seguro para números que no provienen de un campo declarado
     * (por ejemplo, listas estructuradas de recetas o mallas).
     *
     * @throws InvalidArgumentException
     */
    protected function numeroSuelto(mixed $bruto, string $contexto): float
    {
        if (is_bool($bruto) || is_array($bruto) || is_object($bruto) || $bruto === null) {
            throw new InvalidArgumentException(
                'El valor de «' . $contexto . '» debe ser un número válido.'
            );
        }

        if (is_int($bruto) || is_float($bruto)) {
            $numero = (float) $bruto;
        } else {
            $texto = trim((string) $bruto);
            if (preg_match('/^[+-]?\d+(?:[.,]\d+)?(?:[eE][+-]?\d+)?$/', $texto) !== 1) {
                throw new InvalidArgumentException(
                    'El valor de «' . $contexto . '» debe ser un número válido (se recibió «'
                    . $this->recortar($texto) . '»).'
                );
            }
            $numero = (float) str_replace(',', '.', $texto);
        }

        if (!is_finite($numero)) {
            throw new InvalidArgumentException(
                'El valor de «' . $contexto . '» debe ser un número finito.'
            );
        }

        return $numero;
    }

    // ─────────────────────────────────────────────────────────────────
    // Redondeo y armado de la salida
    // ─────────────────────────────────────────────────────────────────

    /**
     * Redondea de forma explícita y garantiza que no haya NAN/INF.
     *
     * @throws RuntimeException Solo si el cálculo interno produjo un valor no
     *         finito (error de programación, nunca de datos de usuario).
     */
    protected function redondear(float $valor, int $decimales): float
    {
        if (!is_finite($valor)) {
            throw new RuntimeException(
                'El cálculo produjo un valor no finito; revisa la fórmula de la calculadora.'
            );
        }

        $redondeado = round($valor, $decimales);

        return $redondeado == 0.0 ? 0.0 : $redondeado;
    }

    /**
     * Fila de `resultados` (valor principal).
     *
     * @return array{etiqueta:string,valor:int|float|string|null,unidad:string,destacado:bool}
     */
    protected function resultado(
        string $etiqueta,
        int|float|string|null $valor,
        string $unidad = '',
        bool $destacado = false
    ): array {
        return $this->filaConDestacado($etiqueta, $valor, $unidad, $destacado);
    }

    /**
     * Fila de `detalle` (dato de apoyo).
     *
     * @return array{etiqueta:string,valor:int|float|string|null,unidad:string}
     */
    protected function fila(
        string $etiqueta,
        int|float|string|null $valor,
        string $unidad = ''
    ): array {
        return [
            'etiqueta' => $etiqueta,
            'valor'    => $this->valorFinito($etiqueta, $valor),
            'unidad'   => $unidad,
        ];
    }

    /**
     * @return array{etiqueta:string,valor:int|float|string|null,unidad:string,destacado:bool}
     */
    private function filaConDestacado(
        string $etiqueta,
        int|float|string|null $valor,
        string $unidad,
        bool $destacado
    ): array {
        return [
            'etiqueta'  => $etiqueta,
            'valor'     => $this->valorFinito($etiqueta, $valor),
            'unidad'    => $unidad,
            'destacado' => $destacado,
        ];
    }

    /**
     * Valida que un valor sea finito (no NAN/INF) y normaliza el cero negativo.
     */
    private function valorFinito(string $etiqueta, int|float|string|null $valor): int|float|string|null
    {
        if (is_float($valor)) {
            if (!is_finite($valor)) {
                throw new RuntimeException(
                    'La fila «' . $etiqueta . '» contiene un valor no finito (NAN/INF).'
                );
            }

            return $valor == 0.0 ? 0.0 : $valor;
        }

        return $valor;
    }

    /**
     * Empaqueta la salida con las cuatro claves del contrato.
     *
     * @param array<int,array<string,mixed>> $resultados
     * @param array<int,array<string,mixed>> $detalle
     * @param array<int,string>              $advertencias
     * @param array<int,string>              $formulas
     * @return array{resultados:array<int,array<string,mixed>>,detalle:array<int,array<string,mixed>>,advertencias:array<int,string>,formulas:array<int,string>}
     */
    protected function salida(
        array $resultados,
        array $detalle = [],
        array $advertencias = [],
        array $formulas = []
    ): array {
        if ($resultados === []) {
            throw new RuntimeException('La calculadora no produjo resultados.');
        }

        return [
            'resultados'   => array_values($resultados),
            'detalle'      => array_values($detalle),
            'advertencias' => array_values(array_filter(
                $advertencias,
                static fn (string $a): bool => trim($a) !== ''
            )),
            'formulas'     => array_values($formulas),
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // Auxiliares varios
    // ─────────────────────────────────────────────────────────────────

    /** Número corto para mensajes de error (0.001, 100, 50.5). */
    protected function numeroCorto(float $numero): string
    {
        if ($numero == (float) (int) $numero && abs($numero) < 1.0e12) {
            return (string) (int) $numero;
        }

        $texto = rtrim(rtrim(number_format($numero, 6, '.', ''), '0'), '.');

        return $texto === '' ? '0' : $texto;
    }

    /** Recorta un texto de usuario para incluirlo en un mensaje de error. */
    protected function recortar(string $texto): string
    {
        return $this->longitud($texto) > 40
            ? $this->subcadena($texto, 0, 40) . '…'
            : $texto;
    }

    /** Longitud de texto con fallback si mbstring no está disponible. */
    private function longitud(string $texto): int
    {
        return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
    }

    /** Subcadena con fallback si mbstring no está disponible. */
    private function subcadena(string $texto, int $inicio, int $largo): string
    {
        return function_exists('mb_substr')
            ? mb_substr($texto, $inicio, $largo, 'UTF-8')
            : substr($texto, $inicio, $largo);
    }
}
