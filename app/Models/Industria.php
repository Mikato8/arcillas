<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Industria
{
    /** @return array<int,array<string,mixed>> */
    public static function todas(bool $soloActivas = true): array
    {
        $where = $soloActivas ? 'WHERE i.activo = 1' : '';
        return Database::fetchAll(
            'SELECT i.*, (SELECT COUNT(*) FROM material_industries mi
                           INNER JOIN materials m ON m.id = mi.material_id AND m.activo = 1
                           WHERE mi.industry_id = i.id) AS total
             FROM industries i ' . $where . '
             ORDER BY i.orden ASC, i.nombre ASC'
        );
    }

    /** @return array<string,mixed>|null */
    public static function porSlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM industries WHERE slug = ? LIMIT 1', [$slug]);
    }

    /** @return array<string,mixed>|null */
    public static function porId(int $id): ?array
    {
        return Database::fetch('SELECT * FROM industries WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function deMaterial(int $materialId): array
    {
        return Database::fetchAll(
            'SELECT i.* FROM material_industries mi
             INNER JOIN industries i ON i.id = mi.industry_id
             WHERE mi.material_id = ? AND i.activo = 1 ORDER BY i.orden ASC',
            [$materialId]
        );
    }
}
