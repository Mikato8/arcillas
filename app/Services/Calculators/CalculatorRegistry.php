<?php
declare(strict_types=1);

namespace App\Services\Calculators;

/**
 * Registro de las ocho calculadoras técnicas de Arcillas MX.
 *
 * Todos los métodos son estáticos y deterministas. `all()` devuelve las
 * calculadoras indexadas por slug estable (kebab-case), que es el mismo valor
 * que se usa en la ruta pública /calculadoras/{slug}.
 */
final class CalculatorRegistry
{
    /**
     * @return array<string,CalculatorInterface> slug => calculadora
     */
    public static function all(): array
    {
        $calculadoras = [
            new UmfCalculator(),
            new ClayBlendCalculator(),
            new ShrinkageCalculator(),
            new MetalBalanceCalculator(),
            new BentoniteSandCalculator(),
            new ParticleSizeCalculator(),
            new PriceNormalizationCalculator(),
            new MineralFormulaCalculator(),
        ];

        $registro = [];
        foreach ($calculadoras as $calculadora) {
            $registro[$calculadora->slug()] = $calculadora;
        }

        return $registro;
    }

    /**
     * @return array<int,string> slugs en el orden de presentación sugerido
     */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }

    /**
     * Devuelve la calculadora o `null` si el slug no existe. Un slug
     * desconocido es un caso normal de ruta (404), no un error de datos.
     */
    public static function obtenerPorSlug(string $slug): ?CalculatorInterface
    {
        $slug = trim($slug, " \t\n\r\0\x0B/");
        $calculadoras = self::all();

        return $calculadoras[$slug] ?? null;
    }

    /**
     * Catálogo completo serializable para el índice /calculadoras y para que el
     * endpoint arme el formulario y la validación sin duplicar el esquema.
     *
     * @return array<string,array{
     *     slug:string,
     *     nombre:string,
     *     categoria:string,
     *     descripcion:string,
     *     ruta:string,
     *     campos:array<int,array<string,mixed>>
     * }>
     */
    public static function catalogo(): array
    {
        $catalogo = [];
        foreach (self::all() as $slug => $calculadora) {
            $catalogo[$slug] = [
                'slug'        => $slug,
                'nombre'      => $calculadora->nombre(),
                'categoria'   => $calculadora->categoria(),
                'descripcion' => $calculadora->descripcion(),
                'ruta'        => '/calculadoras/' . $slug,
                'campos'      => $calculadora->campos(),
            ];
        }

        return $catalogo;
    }

    /**
     * Agrupa los slugs por categoría conservando el orden del registro.
     *
     * @return array<string,array<int,string>> categoría => slugs
     */
    public static function categorias(): array
    {
        $categorias = [];
        foreach (self::all() as $slug => $calculadora) {
            $categoria = $calculadora->categoria();
            $categorias[$categoria] ??= [];
            $categorias[$categoria][] = $slug;
        }

        return $categorias;
    }

    /**
     * Número de calculadoras registradas (debe ser 8).
     */
    public static function total(): int
    {
        return count(self::all());
    }
}
