<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Categoria
{
    /** @return array<int,array<string,mixed>> */
    public static function todas(bool $soloActivas = true): array
    {
        $where = $soloActivas ? 'WHERE c.activo = 1' : '';
        return Database::fetchAll(
            'SELECT c.*, (SELECT COUNT(*) FROM materials m WHERE m.category_id = c.id AND m.activo = 1) AS total
             FROM categories c ' . $where . '
             ORDER BY c.orden ASC, c.nombre ASC'
        );
    }

    /** @return array<string,mixed>|null */
    public static function porSlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM categories WHERE slug = ? LIMIT 1', [$slug]);
    }

    /** @return array<string,mixed>|null */
    public static function porId(int $id): ?array
    {
        return Database::fetch('SELECT * FROM categories WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<string,mixed>|null */
    public static function porNombre(string $nombre): ?array
    {
        return Database::fetch('SELECT * FROM categories WHERE nombre = ? LIMIT 1', [$nombre]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function conMateriales(int $limite = 8): array
    {
        return Database::fetchAll(
            'SELECT c.*, (SELECT COUNT(*) FROM materials m WHERE m.category_id = c.id AND m.activo = 1) AS total
             FROM categories c
             WHERE c.activo = 1 AND EXISTS (SELECT 1 FROM materials m WHERE m.category_id = c.id AND m.activo = 1)
             ORDER BY c.orden ASC LIMIT ' . max(1, $limite)
        );
    }
}
