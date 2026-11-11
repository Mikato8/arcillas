<?php
/**
 * Router para el servidor embebido de PHP en desarrollo.
 * Uso: php -S 127.0.0.1:8099 tools/router.php
 */
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$archivo = __DIR__ . '/../public' . $path;

if ($path !== '/' && is_file($archivo)) {
    return false; // deja que el servidor embebido sirva el archivo (CSS, JS, SVG…)
}

require __DIR__ . '/../public/index.php';
