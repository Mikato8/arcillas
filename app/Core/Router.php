<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int,array<string,mixed>> */
    private array $routes = [];

    /** @param array<string,mixed> $opts */
    public function get(string $path, array|callable $handler, array $opts = []): void
    {
        $this->add('GET', $path, $handler, $opts);
    }

    /** @param array<string,mixed> $opts */
    public function post(string $path, array|callable $handler, array $opts = []): void
    {
        $this->add('POST', $path, $handler, $opts);
    }

    /** @param array<string,mixed> $opts */
    public function add(string $method, string $path, array|callable $handler, array $opts = []): void
    {
        $this->routes[] = [
            'method'  => $method,
            'path'    => $path,
            'regex'   => $this->compile($path),
            'handler' => $handler,
            'opts'    => $opts,
        ];
    }

    private function compile(string $path): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . rtrim((string) $pattern, '/') . '/?$#';
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $path = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?: '/', '/');
        $path = $path === '//' ? '/' : $path;

        // Rutas estáticas primero (más específicas), luego paramétricas.
        $candidates = [];
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $params[$key] = urldecode((string) $value);
                    }
                }
                $route['params'] = $params;
                $candidates[] = $route;
            }
        }

        usort($candidates, static function (array $a, array $b) {
            $score = static fn (array $r) => substr_count((string) $r['path'], '{') * 100 + strlen((string) $r['path']);
            return $score($a) <=> $score($b);
        });

        if ($candidates === []) {
            self::notFound($path);
            return;
        }

        $route = $candidates[0];
        $opts  = $route['opts'];

        // CSRF para formularios web y API
        if ($method === 'POST' && ($opts['csrf'] ?? true) && !Csrf::verifyRequest()) {
            if (Request::wantsJson()) {
                Response::json(['ok' => false, 'error' => 'Token de seguridad inválido o expirado. Recarga la página.', 'codigo' => 'CSRF'], 419);
            }
            Session::flash('error', 'La sesión expiró. Intenta de nuevo.');
            Response::redirect('/login');
        }

        if (($opts['auth'] ?? false) === true) {
            Auth::requireUser();
        }
        if (!empty($opts['roles']) && is_array($opts['roles'])) {
            Auth::requireRole($opts['roles']);
        }

        $handler = $route['handler'];
        if (is_array($handler)) {
            [$class, $action] = $handler;
            $controller = new $class();
            $controller->{$action}($route['params']);
            return;
        }
        $handler($route['params']);
    }

    private static function notFound(string $path): void
    {
        http_response_code(404);
        if (str_starts_with($path, '/api/') || Request::wantsJson()) {
            Response::json(['ok' => false, 'error' => 'Recurso no encontrado', 'codigo' => 'NOT_FOUND'], 404);
        }
        try {
            View::render('errors/404', ['titulo' => 'Página no encontrada'], 'layouts/main');
        } catch (\Throwable) {
            echo '404 · Página no encontrada';
        }
        exit;
    }
}
