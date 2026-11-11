<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Límite de peticiones sencillo y sin dependencias.
 * Usa archivos en storage/cache para no necesitar una tabla extra.
 */
final class RateLimit
{
    /**
     * @return bool true si la petición está permitida
     */
    public static function check(string $accion, int $maximo = 5, int $ventanaSegundos = 600): bool
    {
        $ip = Request::ip();
        $dir = APP_STORAGE . '/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $archivo = $dir . '/rl-' . preg_replace('/[^a-z0-9]+/i', '-', $accion) . '-' . md5($ip) . '.json';

        $ahora = time();
        $marcas = [];
        if (is_file($archivo)) {
            $raw = @file_get_contents($archivo);
            $data = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($data)) {
                $marcas = array_values(array_filter($data, static fn ($t) => is_numeric($t) && (int) $t > $ahora - $ventanaSegundos));
            }
        }

        if (count($marcas) >= $maximo) {
            return false;
        }

        $marcas[] = $ahora;
        @file_put_contents($archivo, json_encode($marcas), LOCK_EX);
        return true;
    }

    public static function reset(string $accion): void
    {
        $archivo = APP_STORAGE . '/cache/rl-' . preg_replace('/[^a-z0-9]+/i', '-', $accion) . '-' . md5(Request::ip()) . '.json';
        @unlink($archivo);
    }

    public static function disponibleEn(string $accion, int $maximo = 5, int $ventanaSegundos = 600): int
    {
        $archivo = APP_STORAGE . '/cache/rl-' . preg_replace('/[^a-z0-9]+/i', '-', $accion) . '-' . md5(Request::ip()) . '.json';
        if (!is_file($archivo)) {
            return 0;
        }
        $data = json_decode((string) @file_get_contents($archivo), true);
        if (!is_array($data) || $data === []) {
            return 0;
        }
        sort($data);
        return max(0, ((int) end($data) + $ventanaSegundos) - time());
    }
}
