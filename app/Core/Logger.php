<?php
declare(strict_types=1);

namespace App\Core;

final class Logger
{
    /** @param array<string,mixed> $context */
    public static function log(string $level, string $message, array $context = []): void
    {
        $line = json_encode([
            'ts'      => date('c'),
            'level'   => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'user'    => Auth::id(),
            'uri'     => $_SERVER['REQUEST_URI'] ?? null,
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

        $dir = APP_STORAGE . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /** @param array<string,mixed> $context */
    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    /** @param array<string,mixed> $context */
    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }
}
