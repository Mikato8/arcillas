<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    private static ?array $jsonCache = null;

    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);
        return $path === '' ? '/' : $path;
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function isAjax(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    public static function wantsJson(): bool
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        return str_contains($accept, 'application/json') || self::isAjax() || str_starts_with(self::path(), '/api/');
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? self::json()[$key] ?? $_GET[$key] ?? $default;
    }

    /** @return array<string,mixed> */
    public static function all(): array
    {
        return array_merge($_GET, self::json(), $_POST);
    }

    /** @return array<string,mixed> */
    public static function json(): array
    {
        if (self::$jsonCache !== null) {
            return self::$jsonCache;
        }
        self::$jsonCache = [];
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    self::$jsonCache = $decoded;
                }
            }
        }
        return self::$jsonCache;
    }

    public static function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($_SERVER[$key]) ? (string) $_SERVER[$key] : null;
    }

    public static function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public static function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public static function scheme(): string
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off') {
            return 'https';
        }
        if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
            return 'https';
        }
        return 'http';
    }

    public static function host(): string
    {
        return (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    public static function baseUrl(): string
    {
        return self::scheme() . '://' . self::host();
    }
}
