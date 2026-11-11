<?php
/**
 * Arcillas MX · Bootstrap
 * Punto único de arranque para web y CLI.
 */
declare(strict_types=1);

use App\Core\Env;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

define('APP_BASE', dirname(__DIR__));
define('APP_VERSION', '1.0.5');
define('APP_STORAGE', APP_BASE . '/storage');
define('APP_PUBLIC', is_dir(APP_BASE . '/public') ? APP_BASE . '/public' : APP_BASE);
define('APP_CLI', PHP_SAPI === 'cli');

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $path = APP_BASE . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

Env::load(APP_BASE . '/.env');

define('APP_DEBUG', Env::bool('APP_DEBUG', false));
define('APP_URL', rtrim((string) Env::get('APP_URL', ''), '/'));

date_default_timezone_set((string) Env::get('APP_TIMEZONE', 'America/Mexico_City'));
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'es_MX.UTF-8', 'es_MX', 'es_ES.UTF-8', 'es_ES');

error_reporting(APP_DEBUG ? E_ALL & ~E_DEPRECATED : E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

require APP_BASE . '/app/helpers.php';

set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
    if (!(error_reporting() & $severity) || ($severity & E_DEPRECATED)) {
        return false;
    }
    if (APP_DEBUG) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    Logger::log('warning', $message, ['file' => $file, 'line' => $line]);
    return true;
});

set_exception_handler(static function (Throwable $exception): void {
    Logger::error($exception->getMessage(), [
        'file'  => $exception->getFile(),
        'line'  => $exception->getLine(),
        'trace' => APP_DEBUG ? $exception->getTraceAsString() : null,
    ]);

    if (APP_CLI) {
        fwrite(STDERR, "[FATAL] " . $exception->getMessage() . PHP_EOL);
        if (APP_DEBUG) {
            fwrite(STDERR, $exception->getTraceAsString() . PHP_EOL);
        }
        exit(1);
    }

    if (Request::wantsJson()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        $payload = ['ok' => false, 'error' => 'Error interno del servidor', 'codigo' => 'SERVER'];
        if (APP_DEBUG) {
            $payload['detalle'] = $exception->getMessage();
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(500);
    try {
        View::render('pages/500', [
            'titulo'  => 'Algo salió mal',
            'mensaje' => APP_DEBUG ? $exception->getMessage() : null,
            'traza'   => APP_DEBUG ? $exception->getTraceAsString() : null,
        ], 'layouts/main');
    } catch (Throwable) {
        echo 'Error interno del servidor. Revisa storage/logs.';
    }
    exit;
});

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    Logger::error('Fatal: ' . $error['message'], ['file' => $error['file'], 'line' => $error['line']]);
    if (!headers_sent()) {
        http_response_code(500);
        echo APP_DEBUG
            ? '<pre>' . e($error['message'] . "\n" . $error['file'] . ':' . $error['line']) . '</pre>'
            : 'Error interno del servidor.';
    }
});

if (APP_CLI) {
    return;
}

Session::start();

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; media-src 'self' blob:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; connect-src 'self'; font-src 'self' data:; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
}

if (defined('APP_SKIP_DISPATCH')) {
    return;
}

$router = new Router();
require APP_BASE . '/config/routes.php';
$router->dispatch(Request::method(), Request::path());
