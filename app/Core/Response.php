<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $path, int $status = 302): never
    {
        header('Location: ' . $path, true, $status);
        exit;
    }

    public static function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        self::redirect($referer);
    }

    public static function download(string $file, ?string $name = null): never
    {
        if (!is_file($file)) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            exit;
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . ($name ?? basename($file)) . '"');
        header('Content-Length: ' . (string) filesize($file));
        readfile($file);
        exit;
    }

    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }
}
