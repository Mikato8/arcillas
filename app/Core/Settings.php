<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Configuración editable del negocio (tabla `configuraciones`).
 * Incluye valores por defecto para que la app arranque aunque la tabla esté vacía.
 */
final class Settings
{
    /** @var array<string,mixed>|null */
    private static ?array $cache = null;

    /** @var array<string,string> */
    public const DEFAULTS = [
        'site_nombre'        => 'Arcillas MX',
        'site_descripcion'   => 'Directorio técnico de arcillas y minerales no ferrosos para la industria mexicana.',
        'empresa_nombre'     => 'ARCILLAS PROTEÍNAS Y DESARROLLOS GARCÍA',
        'empresa_corto'      => 'Arcillas MX',
        'empresa_logo'       => '',
        'empresa_rfc'        => 'APD200702M63',
        'empresa_email'      => 'contacto@arcillas.mx',
        'empresa_telefono'   => '+52 33 1713 5769',
        'empresa_whatsapp'   => '523317135769',
        'empresa_direccion'  => 'Guadalajara, Jalisco, México',
        'empresa_ciudad'     => 'Guadalajara, Jalisco',
        'empresa_linkedin'   => '',
        'empresa_google'     => 'https://share.google/b0zPLsZ8jtnF3UWAT',
        'empresa_facebook'   => 'https://www.facebook.com/profile.php?id=61596741170224',
        'hero_titulo'        => '',
        'hero_subtitulo'     => '',
        'hero_etiqueta'      => 'Broker técnico de minerales · México',
        'moneda'             => 'MXN',
        'iva_pct'            => '16',
        'respuesta_horas'    => '24',
        'mail_to'            => 'contacto@arcillas.mx',
        'version'            => '1.0.0',
    ];

    /** @return array<string,mixed> */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $values = self::DEFAULTS;
        try {
            $rows = Database::fetchAll('SELECT clave, valor FROM settings');
            $raw = [];
            foreach ($rows as $row) {
                $raw[(string) $row['clave']] = $row['valor'];
            }
            // Compatibilidad con claves sembradas por el catálogo (sitio_*).
            $alias = [
                'sitio_nombre'      => 'site_nombre',
                'sitio_descripcion' => 'site_descripcion',
                'sitio_email'       => 'empresa_email',
                'sitio_telefono'    => 'empresa_telefono',
                'sitio_whatsapp'    => 'empresa_whatsapp',
                'sitio_direccion'   => 'empresa_direccion',
            ];
            foreach ($alias as $legacy => $canonical) {
                if (isset($raw[$legacy]) && !array_key_exists($canonical, $raw)) {
                    $values[$canonical] = $raw[$legacy];
                }
            }
            foreach ($raw as $clave => $valor) {
                $values[$clave] = $valor;
            }
        } catch (\Throwable) {
            // Sin conexión o migración pendiente: se usan los valores por defecto.
        }
        self::$cache = $values;
        return $values;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    public static function float(string $key, float $default = 0.0): float
    {
        $value = self::get($key);
        return is_numeric($value) ? (float) $value : $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);
        return is_numeric($value) ? (int) $value : $default;
    }

    public static function set(string $key, string $value, string $grupo = 'general'): void
    {
        $exists = Database::scalar('SELECT COUNT(*) FROM settings WHERE clave = ?', [$key]);
        if ((int) $exists > 0) {
            Database::run('UPDATE settings SET valor = ? WHERE clave = ?', [$value, $key]);
        } else {
            Database::insert('settings', ['clave' => $key, 'valor' => $value, 'grupo' => $grupo]);
        }
        self::$cache = null;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
