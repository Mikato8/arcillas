<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    /**
     * Renderiza una vista dentro de un layout.
     *
     * @param array<string,mixed> $data
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        $content = self::capture($view, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::capture($layout, array_merge($data, ['content' => $content]));
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function capture(string $view, array $data = []): string
    {
        $file = APP_BASE . '/app/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new RuntimeException('Vista no encontrada: ' . $view);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function partial(string $view, array $data = []): void
    {
        echo self::capture($view, $data);
    }

    /** @param array<string,mixed> $data */
    public static function json(string $view, array $data = []): string
    {
        return self::capture($view, $data);
    }
}
