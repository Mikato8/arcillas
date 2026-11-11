<?php
declare(strict_types=1);

namespace App\Services\Calculators;

/**
 * Contrato de una calculadora técnica de Arcillas MX.
 *
 * Todas las calculadoras son cálculo puro y determinista: no usan base de
 * datos, sesión, red ni el sistema de archivos. La entrada llega tal como la
 * envía un formulario HTML (strings) o como números (API interna/pruebas) y
 * la salida siempre es serializable a JSON.
 *
 * ─────────────────────────────────────────────────────────────────────
 * ESQUEMA DE `campos()`
 * ─────────────────────────────────────────────────────────────────────
 * `campos()` devuelve una lista ordenada de definiciones. Cada definición es
 * un arreglo con SIEMPRE estas claves, de modo que el generador de formularios
 * y la validación del endpoint puedan trabajar sin casos especiales:
 *
 *   clave     string  Identificador único del campo (snake_case).
 *   etiqueta  string  Texto visible en el formulario.
 *   tipo      string  'number' | 'select' | 'text'.
 *   unidad    string  Unidad visible; cadena vacía si no aplica.
 *   min       ?float  Mínimo permitido (solo number; null si no aplica).
 *   max       ?float  Máximo permitido (solo number; null si no aplica).
 *   paso      ?float  Incremento sugerido del input numérico (null si no aplica).
 *   requerido bool    Si el endpoint debe exigirlo.
 *   defecto   mixed   Valor inicial sugerido (number|null, string|null).
 *   opciones  array   Solo select: lista de ['valor' => string, 'etiqueta' => string].
 *   ayuda     string  Texto de ayuda / nota técnica bajo el campo.
 *   grupo     string  Agrupador visual sugerido (sección o pestaña).
 *
 * Los campos numéricos aceptan enteros, decimales con punto o coma y notación
 * científica en forma de cadena; cualquier otro valor produce
 * InvalidArgumentException con un mensaje en español.
 *
 * ─────────────────────────────────────────────────────────────────────
 * SALIDA DE `calcular()`
 * ─────────────────────────────────────────────────────────────────────
 *   [
 *     'resultados'  => [ ['etiqueta'=>string, 'valor'=>int|float|string|null,
 *                         'unidad'=>string, 'destacado'=>bool], ... ],
 *     'detalle'     => [ ['etiqueta'=>string, 'valor'=>int|float|string|null,
 *                         'unidad'=>string], ... ],
 *     'advertencias'=> [ string, ... ],
 *     'formulas'    => [ string, ... ],
 *   ]
 *
 * Solo contiene escalares y arreglos: nada de objetos, recursos ni ciclos.
 * Ningún valor numérico puede ser NAN ni INFINITO; los números se redondean
 * de forma explícita en cada calculadora (documentada en docs/calculadoras).
 */
interface CalculatorInterface
{
    /** Slug estable en kebab-case usado en la URL /calculadoras/{slug}. */
    public function slug(): string;

    /** Nombre corto para títulos y tarjetas. */
    public function nombre(): string;

    /** Categoría comercial/técnica para agrupar en el índice. */
    public function categoria(): string;

    /** Descripción de una o dos frases para la tarjeta y el meta description. */
    public function descripcion(): string;

    /**
     * Esquema declarativo del formulario (ver cabecera de la interfaz).
     *
     * @return array<int,array{
     *     clave:string,
     *     etiqueta:string,
     *     tipo:string,
     *     unidad:string,
     *     min:float|null,
     *     max:float|null,
     *     paso:float|null,
     *     requerido:bool,
     *     defecto:int|float|string|null,
     *     opciones:array<int,array{valor:string,etiqueta:string}>,
     *     ayuda:string,
     *     grupo:string
     * }>
     */
    public function campos(): array;

    /**
     * Ejecuta el cálculo.
     *
     * @param array<string,mixed> $entrada
     * @return array{
     *     resultados:array<int,array{etiqueta:string,valor:int|float|string|null,unidad:string,destacado:bool}>,
     *     detalle:array<int,array{etiqueta:string,valor:int|float|string|null,unidad:string}>,
     *     advertencias:array<int,string>,
     *     formulas:array<int,string>
     * }
     * @throws \InvalidArgumentException Si la entrada es inválida o el cálculo
     *         no es posible con esos datos.
     */
    public function calcular(array $entrada): array;
}
